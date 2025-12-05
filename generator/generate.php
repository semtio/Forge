<?php
// Generation logic: create directories and files from $projects model

function ensureDir(string $path): void {
    if (!is_dir($path)) {
        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException("Failed to create directory: $path");
        }
    } else {
        // directory already exists; do nothing
    }
}

function escapeHtmlAttr(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function applyStaticHeaderData(string $root, array $data): void {
    $header = $root . DIRECTORY_SEPARATOR . 'header.php';
    if (!is_file($header)) {
        return;
    }
    $content = file_get_contents($header);
    if ($content === false) {
        return;
    }

    // Replace meta description tag entirely if data['description'] provided
    if (!empty($data['description']) && is_string($data['description'])) {
        $desc = escapeHtmlAttr($data['description']);
        $metaTag = '<meta name="description" content="' . $desc . '">';
        $lines = preg_split("/(\r\n|\r|\n)/", $content);
        if (is_array($lines)) {
            foreach ($lines as $i => $line) {
                if (preg_match('/<meta[^>]*name=("|\')description\1/i', $line)) {
                    $lines[$i] = $metaTag;
                    break;
                }
            }
            $content = implode(PHP_EOL, $lines);
        }
    }

    // Optionally replace <title>...</title> if provided
    if (!empty($data['title']) && is_string($data['title'])) {
        $title = escapeHtmlAttr($data['title']);
        if (preg_match('/<title>.*?<\/title>/is', $content)) {
            $content = preg_replace('/<title>.*?<\/title>/is', '<title>' . $title . '</title>', $content);
        }
    }

    // Optionally replace or insert favicon link
    if (!empty($data['favicon']) && is_string($data['favicon'])) {
        $favicon = escapeHtmlAttr($data['favicon']);
        $faviconTag = '<link rel="icon" href="' . $favicon . '" type="image/x-icon">';

        if (preg_match('/<link[^>]*rel=("|")(?:shortcut\s+)?icon\1[^>]*>/i', $content)) {
            // Replace existing favicon tag
            $content = preg_replace('/<link[^>]*rel=("|")(?:shortcut\s+)?icon\1[^>]*>/i', $faviconTag, $content, 1);
        } elseif (preg_match('/<head[^>]*>/i', $content)) {
            // Insert right after <head>
            $content = preg_replace('/(<head[^>]*>)/i', "$1\n    $faviconTag", $content, 1);
        } else {
            // Fallback: prepend
            $content = $faviconTag . "\n" . $content;
        }
    }

    file_put_contents($header, $content);
}

function normalizeCanonical(string $value, string $domain): string {
    $value = trim($value);
    if ($value === '') {
        $value = $domain;
    }
    // If looks like URL already, keep as is; else treat as hostname/path
    if (!preg_match('~^https?://~i', $value)) {
        // If it's a hostname (no slashes), prefix https://
        if (strpos($value, '/') === false) {
            $value = 'https://' . $value . '/';
        } else {
            // Path given without host – prefix domain
            $value = 'https://' . rtrim($domain, '/') . '/' . ltrim($value, '/');
        }
    }
    return $value;
}

function applyCanonicalLink(string $root, array $proj): void {
    $header = $root . DIRECTORY_SEPARATOR . 'header.php';
    if (!is_file($header)) {
        return;
    }
    $content = file_get_contents($header);
    if ($content === false || $content === '') {
        return;
    }

    $domain = (string)($proj['domain'] ?? ($proj['data']['domen_name'] ?? ''));
    $canonicalRaw = (string)($proj['canonical'] ?? '');
    $href = normalizeCanonical($canonicalRaw, $domain);
    $hrefEsc = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    $tag = '<link rel="canonical" href="' . $hrefEsc . '">';

    if (preg_match('/<link[^>]*rel=("|\')canonical\1[^>]*>/i', $content)) {
        // Replace existing canonical tag
        $content = preg_replace('/<link[^>]*rel=("|\')canonical\1[^>]*>/i', $tag, $content, 1);
    } else {
        // Insert before closing </head>, preferably after viewport or title
        if (preg_match('/<meta[^>]*name=("|\')viewport\1[^>]*>/i', $content)) {
            $content = preg_replace('/(<meta[^>]*name=("|\')viewport\2[^>]*>)/i', "$1\n    $tag", $content, 1);
        } elseif (preg_match('/<title>.*?<\/title>/is', $content)) {
            $content = preg_replace('/(<title>.*?<\/title>)/is', "$1\n    $tag", $content, 1);
        } elseif (preg_match('/<head[^>]*>/i', $content)) {
            $content = preg_replace('/(<head[^>]*>)/i', "$1\n    $tag", $content, 1);
        } else {
            // Fallback: append at beginning
            $content = $tag . "\n" . $content;
        }
    }

    file_put_contents($header, $content);
}

// Generate encrypted.php with link mappings from config
// Returns updated data array with encrypted links
function generateEncryptedLinksFile(string $root, array $data): array {
    $linksArray = [];
    $updatedData = $data;

    // Extract all link fields from data
    foreach ($data as $key => $value) {
        if (str_starts_with($key, 'btn_link') && is_string($value) && !empty($value)) {
            // Skip if already encrypted (starts with ./encrypted.php)
            if (str_starts_with($value, './encrypted.php') || str_starts_with($value, '/encrypted.php')) {
                continue;
            }

            // Generate hash from actual link
            $hash = substr(hash('sha256', $value), 0, 16);
            $linksArray[$hash] = $value;

            // Update data with encrypted link
            $updatedData[$key] = './encrypted.php?key=' . $hash;
        }
    }

    if (empty($linksArray)) {
        return $updatedData;
    }

    // Generate encrypted.php file
    $encryptedPhp = $root . DIRECTORY_SEPARATOR . 'encrypted.php';
    $content = <<<'PHP'
<?php
// Auto-generated file: encrypted link mappings
// Do not edit manually - this file is regenerated during build

$links = [
PHP;

    foreach ($linksArray as $hash => $url) {
        $content .= "\n    '" . addslashes($hash) . "' => '" . addslashes($url) . "',";
    }

    $content .= <<<'PHP'

];

$key = $_GET['key'] ?? '';

if (isset($links[$key])) {
    header('Location: ' . $links[$key], true, 302);
    exit;
}

// If link not found - redirect to home
header('Location: /', true, 302);
exit;
PHP;

    file_put_contents($encryptedPhp, $content);
    return $updatedData;
}

/**
 * Process HTML content to add encrypted links to buttons by class name
 * Finds all <a> tags with class="btn-link1", "btn-link2", "btn-link3" etc
 * and adds href with encrypted link from $data
 *
 * @param string $content HTML content to process
 * @param array $data Project data with btn_link1, btn_link2, etc (already encrypted)
 * @return string Processed HTML content
 */
function injectEncryptedLinksToClasses(string $content, array $data): string {
    // Collect all btn_link mappings (class => encrypted_url)
    $linkMappings = [];
    foreach ($data as $key => $value) {
        if (str_starts_with($key, 'btn_link') && is_string($value) && !empty($value)) {
            // Extract number from btn_link1, btn_link2, etc
            if (preg_match('/^btn_link(\d+)$/', $key, $match)) {
                $num = $match[1];
                $linkMappings['btn-link' . $num] = $value; // btn-link1, btn-link2, etc
            }
        }
    }

    if (empty($linkMappings)) {
        return $content; // No button links to process
    }

    $updated = $content;

    // For each button link class, find and update <a> tags
    foreach ($linkMappings as $className => $encryptedUrl) {
        // Pattern: <a ... class="..." containing btn-linkX ... >
        // We need to add or replace href attribute
        $pattern = '/<a\s+([^>]*class=["\'][^"\']*' . preg_quote($className, '/') . '[^"\']*["\'][^>]*)>/i';

        $updated = preg_replace_callback($pattern, function($matches) use ($encryptedUrl) {
            $fullTag = $matches[0];
            $attributes = $matches[1];

            // Check if href already exists
            if (preg_match('/\shref\s*=\s*["\'][^"\']*["\']/i', $attributes)) {
                // Replace existing href
                $newTag = preg_replace('/\shref\s*=\s*["\'][^"\']*["\']/i', ' href="' . htmlspecialchars($encryptedUrl, ENT_QUOTES) . '"', $fullTag);
            } else {
                // Add href before closing >
                $newTag = str_replace('>', ' href="' . htmlspecialchars($encryptedUrl, ENT_QUOTES) . '">', $fullTag);
            }

            return $newTag;
        }, $updated);
    }

    return $updated;
}

/**
 * Process HTML files to add encrypted links to buttons by class name
 * Finds all <a> tags with class="btn-link1", "btn-link2", "btn-link3" etc
 * and adds href with encrypted link from $data
 *
 * @param string $root Project root directory
 * @param array $data Project data with btn_link1, btn_link2, etc (already encrypted)
 * @return void
 */
function processButtonLinksByClass(string $root, array $data): void {
    // Find all HTML files in root (including data/*.html for TinyMCE content)
    $files = listFilesRecursive($root);

    foreach ($files as $file) {
        if (!is_file($file)) continue;

        // Process only HTML files
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext !== 'html' && $ext !== 'htm' && $ext !== 'php') continue;

        $contents = file_get_contents($file);
        if ($contents === false) continue;

        $updated = injectEncryptedLinksToClasses($contents, $data);

        // Save if changed
        if ($updated !== $contents) {
            file_put_contents($file, $updated);
        }
    }
}// Apply schema (microdata) flag logic: replace runtime $data conditional includes with static ones
function applyShemaFlags(string $root, array $proj): void {
    $header = $root . DIRECTORY_SEPARATOR . 'header.php';
    if (!is_file($header)) {
        return;
    }
    $content = file_get_contents($header);
    if ($content === false) {
        return;
    }

    $shema1Flag = $proj['shema1'] ?? null;
    $shema2Flag = $proj['shema2'] ?? null;

    $lines = preg_split("/(\r\n|\r|\n)/", $content);
    if (!is_array($lines)) {
        return;
    }
    $changed = false;

    foreach ($lines as $i => $line) {
        // Normalize whitespace for matching
        $trim = trim($line);
        // shema1
        if (preg_match('/if\s*\(\s*\$data\[\s*["\']shema1["\']\s*\]\s*\)\s*include_once\s*["\']shema1\.php["\']\s*;/', $trim)) {
            if ($shema1Flag) {
                $lines[$i] = "include_once 'shema1.php';"; // static include inside existing PHP block
            } else {
                $lines[$i] = ''; // remove line entirely
            }
            $changed = true;
            continue;
        }
        // shema2
        if (preg_match('/if\s*\(\s*\$data\[\s*["\']shema2["\']\s*\]\s*\)\s*include_once\s*["\']shema2\.php["\']\s*;/', $trim)) {
            if ($shema2Flag) {
                $lines[$i] = "include_once 'shema2.php';"; // static include
            } else {
                $lines[$i] = '';
            }
            $changed = true;
            continue;
        }
    }

    if ($changed) {
        $newContent = implode(PHP_EOL, $lines);
        file_put_contents($header, $newContent);
    }
}

// Global functions container for plugin functions
$GLOBALS['pluginFunctions'] = [];

/**
 * Load plugin functions globally
 */
function loadPluginFunctions(string $pluginsDir): void {
    if (!is_dir($pluginsDir)) {
        return;
    }
    $items = scandir($pluginsDir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $pluginPath = $pluginsDir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($pluginPath)) {
            $pluginFile = $pluginPath . DIRECTORY_SEPARATOR . 'plugin.php';
            if (is_file($pluginFile)) {
                $pluginData = include $pluginFile;
                if (is_array($pluginData) && isset($pluginData['functions']) && is_array($pluginData['functions'])) {
                    foreach ($pluginData['functions'] as $name => $callback) {
                        if (is_callable($callback)) {
                            $GLOBALS['pluginFunctions'][$name] = $callback;
                        }
                    }
                }
            }
        }
    }
}

function listFilesRecursive(string $dir): array {
    $result = [];
    $items = scandir($dir);
    if ($items === false) {
        return $result;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $result = array_merge($result, listFilesRecursive($path));
        } else {
            $result[] = $path;
        }
    }
    return $result;
}

function matchPattern(string $pattern, string $relativePath): bool {
    // Normalize to forward slashes
    $pattern = ltrim(str_replace('\\', '/', $pattern), '/');
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    // Try fnmatch when available
    if (function_exists('fnmatch')) {
        if (fnmatch($pattern, $relativePath, FNM_PATHNAME)) {
            return true;
        }
    }
    // Fallback: convert * and ? to regex
    $quoted = preg_quote($pattern, '/');
    $quoted = str_replace(['\\*','\\?'], ['[^/]*', '.'], $quoted);
    $quoted = str_replace('/', '\/', $quoted);
    $regex = '/^' . $quoted . '$/i';
    return (bool)preg_match($regex, $relativePath);
}

function shouldProcess(string $root, string $filePath, ?array $patterns): bool {
    $rootNorm = rtrim(str_replace('\\', '/', $root), '/');
    $pathNorm = str_replace('\\', '/', $filePath);
    if (strpos($pathNorm, $rootNorm . '/') === 0) {
        $rel = substr($pathNorm, strlen($rootNorm) + 1);
    } elseif (strpos($pathNorm, $rootNorm) === 0) {
        $rel = substr($pathNorm, strlen($rootNorm));
    } else {
        $rel = $pathNorm;
    }
    $rel = ltrim($rel, '/');
    if (is_array($patterns) && !empty($patterns)) {
        foreach ($patterns as $p) {
            if (!is_string($p) || $p === '') continue;
            if (matchPattern($p, $rel)) return true;
        }
        return false;
    }
    // Default: only text-like extensions
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $allowed = ['php','html','htm','css','js','json','txt','md','svg'];
    return in_array($ext, $allowed, true);
}

function replaceDataPlaceholders(string $content, array $data): string {
    $pattern = '/<\?(?:php\s+echo|=)\s*\$data\[\s*([\'\"])\s*([^\'\"]+)\s*\1\s*\]\s*;?\s*\?>/i';
    return preg_replace_callback($pattern, function ($m) use ($data) {
        $key = $m[2] ?? '';
        if ($key !== '' && array_key_exists($key, $data) && (is_scalar($data[$key]) || (is_object($data[$key]) && method_exists($data[$key], '__toString')))) {
            return (string)$data[$key];
        }
        return $m[0];
    }, $content);
}

function applyGenericPlaceholders(string $root, array $data, ?array $patterns, $shortcodeProcessor = null): void {
    $files = listFilesRecursive($root);
    foreach ($files as $file) {
        if (!is_file($file)) continue;
        if (!shouldProcess($root, $file, $patterns)) continue;
        $contents = file_get_contents($file);
        if ($contents === false) continue;

        // Replace data placeholders
        $updated = replaceDataPlaceholders($contents, $data);

        // Process shortcodes if processor provided
        if ($shortcodeProcessor !== null && is_callable([$shortcodeProcessor, 'process'])) {
            // Set current file for shortcode processor
            if (is_callable([$shortcodeProcessor, 'setCurrentFile'])) {
                $shortcodeProcessor->setCurrentFile($file);
            }
            $updated = $shortcodeProcessor->process($updated);
        }

        // Note: Alt attributes are added dynamically via auto-alt.php in header
        // No need to process them during generation

        if ($updated !== $contents) {
            file_put_contents($file, $updated);
        }
    }
}

/**
 * Automatically add alt attributes to img tags that don't have them
 * @param string $html HTML content
 * @return string HTML with alt attributes added
 */
function addMissingAltAttributes(string $html): string {
    // Find all img tags
    $pattern = '/<img([^>]*)>/i';

    $html = preg_replace_callback($pattern, function($matches) {
        $imgTag = $matches[0];
        $attributes = $matches[1];

        // Check if alt attribute already exists
        if (preg_match('/\salt\s*=/i', $attributes)) {
            // Alt exists, check if it's empty
            if (preg_match('/\salt\s*=\s*["\'][\s]*["\']/i', $attributes)) {
                // Empty alt, try to generate from src
                if (preg_match('/\ssrc\s*=\s*["\']([^"\']+)["\']/i', $attributes, $srcMatch)) {
                    $src = $srcMatch[1];
                    $filename = basename($src);
                    $altText = pathinfo($filename, PATHINFO_FILENAME);
                    // Clean up: remove numbers, hyphens, underscores
                    $altText = preg_replace('/[-_\d]+/', ' ', $altText);
                    $altText = ucfirst(trim($altText));

                    // Replace empty alt with generated one
                    $imgTag = preg_replace('/\salt\s*=\s*["\'][\s]*["\']/i', ' alt="' . htmlspecialchars($altText, ENT_QUOTES) . '"', $imgTag);
                }
            }
            return $imgTag;
        }

        // No alt attribute, try to generate from src
        $altText = 'Image';
        if (preg_match('/\ssrc\s*=\s*["\']([^"\']+)["\']/i', $attributes, $srcMatch)) {
            $src = $srcMatch[1];
            $filename = basename($src);
            $altText = pathinfo($filename, PATHINFO_FILENAME);
            // Clean up: remove numbers, hyphens, underscores
            $altText = preg_replace('/[-_\d]+/', ' ', $altText);
            $altText = ucfirst(trim($altText));
            if (empty($altText)) {
                $altText = 'Image';
            }
        }

        // Add alt attribute before closing >
        $imgTag = str_replace('>', ' alt="' . htmlspecialchars($altText, ENT_QUOTES) . '">', $imgTag);

        return $imgTag;
    }, $html);

    return $html;
}


function generate(array $projects): void {
    $base = dirname(__DIR__); // .../public
    $buildRoot = $base . DIRECTORY_SEPARATOR . 'build';
    ensureDir($buildRoot);

    // Load shortcode system
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'shortcodes.php';
    $shortcodeProcessor = new ShortcodeProcessor();
    $pluginsDir = $base . DIRECTORY_SEPARATOR . 'plugins';
    loadPlugins($pluginsDir, $shortcodeProcessor);

    // Load plugin functions globally
    loadPluginFunctions($pluginsDir);

    foreach ($projects as $proj) {
        if (!isset($proj['domain'])) {
            continue;
        }

        $domain = (string)$proj['domain'];
        if (!preg_match('/^[A-Za-z0-9._-]+$/', $domain)) {
            continue;
        }

        $root = $buildRoot . DIRECTORY_SEPARATOR . $domain;
        ensureDir($root);


        // apply theme first (copy recursively without overwriting existing)
        if (!empty($proj['theme']) && is_string($proj['theme'])) {
            $theme = $proj['theme'];
            $relTheme = preg_replace('#^\./#', '', $theme);
            $absTheme = $base . DIRECTORY_SEPARATOR . $relTheme;
            if (is_dir($absTheme)) {
                copyDirNoOverwrite($absTheme, $root);
            }
        }

        // Generate robots.txt with domain substitution
        $robotsTxt = $root . DIRECTORY_SEPARATOR . 'robots.txt';
        if (file_exists($robotsTxt)) {
            $robotsContent = file_get_contents($robotsTxt);
            if ($robotsContent !== false) {
                $robotsContent = str_replace('{{DOMAIN}}', $domain, $robotsContent);
                file_put_contents($robotsTxt, $robotsContent);
            }
        }

        // Generate sitemap.xml with domain and date substitution
        $sitemapXml = $root . DIRECTORY_SEPARATOR . 'sitemap.xml';
        if (file_exists($sitemapXml)) {
            $sitemapContent = file_get_contents($sitemapXml);
            if ($sitemapContent !== false) {
                $currentDate = date('Y-m-d');
                $sitemapContent = str_replace('{{DOMAIN}}', $domain, $sitemapContent);
                $sitemapContent = str_replace('{{DATE}}', $currentDate, $sitemapContent);
                file_put_contents($sitemapXml, $sitemapContent);
            }
        }

        // Dynamic styles: expects $proj['styles'] as array of ['class' => string, 'background' => string, 'color' => string, 'file' => string (optional)]
        if (!empty($proj['styles']) && is_array($proj['styles'])) {
            // Group styles by target file
            $stylesByFile = [];
            foreach ($proj['styles'] as $styleRule) {
                if (!is_array($styleRule)) { continue; }
                $class = $styleRule['class'] ?? null;
                $file = $styleRule['file'] ?? 'styles/styles.css'; // default file

                // Skip if class is missing
                if (!is_string($class) || $class === '') { continue; }

                // Build CSS properties from all keys except 'class' and 'file'
                $properties = [];
                foreach ($styleRule as $key => $value) {
                    if ($key === 'class' || $key === 'file') { continue; }
                    if (!is_string($value) || $value === '') { continue; }

                    // Smart mapping for 'background' property:
                    // If value contains url() or gradient, use 'background'
                    // Otherwise use 'background-color' for backward compatibility
                    $cssProperty = $key;
                    if ($key === 'background') {
                        if (stripos($value, 'url(') === false && stripos($value, 'gradient') === false) {
                            $cssProperty = 'background-color';
                        }
                    }
                    $properties[] = $cssProperty . ": " . $value;
                }

                // Skip if no valid properties
                if (empty($properties)) { continue; }

                // normalize selector: ensure leading dot
                $selector = $class[0] === '.' ? $class : '.' . $class;
                $line = $selector . " { " . implode("; ", $properties) . "; }";
                if (!isset($stylesByFile[$file])) {
                    $stylesByFile[$file] = [];
                }
                $stylesByFile[$file][] = $line;
            }

            // Write to each file
            foreach ($stylesByFile as $relPath => $lines) {
                $cssFile = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                ensureDir(dirname($cssFile));
                if (!file_exists($cssFile)) {
                    // create empty if missing
                    file_put_contents($cssFile, "/* Generated styles */\n\n");
                }
                // Prevent duplicate additions
                $existing = file_get_contents($cssFile);
                $newLines = [];
                foreach ($lines as $line) {
                    if (strpos($existing, $line) === false) {
                        $newLines[] = $line;
                    }
                }
                if ($newLines) {
                    file_put_contents($cssFile, "\n/* Generated dynamic styles */\n" . implode("\n", $newLines) . "\n", FILE_APPEND);
                }
            }
        }

        // Project data: inject static values into templates
        if (!empty($proj['data']) && is_array($proj['data'])) {
            // Generate encrypted.php and get updated data with encrypted links
            $dataWithEncryption = generateEncryptedLinksFile($root, $proj['data']);
            // Specific replacements for header (with encrypted links)
            applyStaticHeaderData($root, $dataWithEncryption);
            // Add or update canonical link
            applyCanonicalLink($root, $proj);
            // Replace shema flags conditionals with static includes (independent of $data)
            applyShemaFlags($root, $proj);
            // Generic replacements across files according to optional patterns
            $patterns = [];
            if (!empty($proj['postprocess']) && is_array($proj['postprocess'])) {
                $patterns = $proj['postprocess'];
            }
            applyGenericPlaceholders($root, $dataWithEncryption, $patterns, $shortcodeProcessor);

            // Process buttons with classes btn-link1, btn-link2, etc - add encrypted hrefs
            processButtonLinksByClass($root, $dataWithEncryption);
        }

        // Copy plugin assets (CSS, JS) to build
        if (is_dir($pluginsDir)) {
            $pluginAssets = $root . DIRECTORY_SEPARATOR . 'plugins';
            ensureDir($pluginAssets);
            copyDirNoOverwrite($pluginsDir, $pluginAssets);

            // Process TinyMCE content files in build directory with button classes
            $tinyDataDirInBuild = $root . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'tinymce' . DIRECTORY_SEPARATOR . 'data';
            if (is_dir($tinyDataDirInBuild) && !empty($dataWithEncryption)) {
                processButtonLinksByClass($tinyDataDirInBuild, $dataWithEncryption);
            }
        }





        // Create admin.key for TinyMCE plugin if it doesn't exist
        $adminKeyFile = $root . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'tinymce' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'admin.key';
        if (!file_exists($adminKeyFile)) {
            $defaultPassword = 'admin'; // Change this default password
            $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);
            ensureDir(dirname($adminKeyFile));
            file_put_contents($adminKeyFile, $hash);
            echo "  [TinyMCE] Created admin.key with default password: admin (please change!)\n";
        }

        // generation done for this domain
    }
}

function copyDirNoOverwrite(string $src, string $dst): void {
    if (!is_dir($src)) {
        return;
    }
    ensureDir($dst);
    $items = scandir($src);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $srcPath = $src . DIRECTORY_SEPARATOR . $item;
        $dstPath = $dst . DIRECTORY_SEPARATOR . $item;
        if (is_dir($srcPath)) {
            copyDirNoOverwrite($srcPath, $dstPath);
        } else {
            if (!file_exists($dstPath)) {
                ensureDir(dirname($dstPath));
                @copy($srcPath, $dstPath);
            }
        }
    }
}
