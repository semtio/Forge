<?php
/**
 * Load TinyMCE plugin content
 *
 * This function is available globally after generation via loadPluginFunctions()
 * Usage in templates: <?php echo loadTinyContent('homepage'); ?>
 *
 * @param string $pageId Page identifier (filename without .html extension)
 * @return string HTML content or empty string if not found
 */
function loadTinyContent(string $key): string {
    $currentDir = __DIR__;
    $safe = basename($key);
    $dataFile = $currentDir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $safe . '.html';
    if (!is_file($dataFile)) {
        return '<!-- TinyMCE content not found: ' . htmlspecialchars($safe) . ' -->';
    }
    $html = file_get_contents($dataFile);
    if ($html === false) {
        return '<!-- TinyMCE content read error: ' . htmlspecialchars($safe) . ' -->';
    }

    // Process images to add srcset and alt attributes
    $html = addResponsiveImages($html, $currentDir);

    return $html;
}

/**
 * Add srcset and alt attributes to images in HTML content
 *
 * @param string $html HTML content with images
 * @param string $baseDir Directory to use for relative path resolution
 * @return string HTML with srcset and alt attributes added
 */
function addResponsiveImages(string $html, string $baseDir): string {
    return preg_replace_callback('/<img([^>]*)>/i', function($matches) use ($baseDir) {
        $imgTag = $matches[0];
        $attributes = $matches[1];

        // Skip if srcset already exists
        if (preg_match('/\ssrcset\s*=/i', $attributes)) {
            return $imgTag;
        }

        // Extract src attribute
        if (!preg_match('/\ssrc\s*=\s*(["\'])([^\1]+?)\1/i', $attributes, $srcMatch)) {
            return $imgTag;
        }

        $src = $srcMatch[2];

        // Build srcset from image variants
        $srcset = buildSrcsetFromVariants($src, $baseDir);

        if (!empty($srcset)) {
            // Add srcset and sizes before closing >
            $imgTag = str_replace('>', ' srcset="' . $srcset . '" sizes="(max-width: 600px) 600px, (max-width: 1200px) 1200px, 1920px">', $imgTag);
        }

        return $imgTag;
    }, $html);
}

/**
 * Build srcset attribute value by checking for image variants
 *
 * @param string $src Original image src path
 * @param string $baseDir Base directory for file existence check
 * @return string Srcset attribute value or empty string
 */
function buildSrcsetFromVariants(string $src, string $baseDir): string {
    $pathInfo = pathinfo($src);
    $dir = isset($pathInfo['dirname']) && $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] : '';
    $filename = $pathInfo['filename'];
    $ext = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';

    $variants = [
        600 => ($dir ? $dir . '/' : '') . $filename . '-600.' . $ext,
        1200 => ($dir ? $dir . '/' : '') . $filename . '-1200.' . $ext,
        1920 => ($dir ? $dir . '/' : '') . $filename . '-1920.' . $ext
    ];

    $existingVariants = [];
    $rootDir = dirname(dirname($baseDir)); // Go up to site root

    foreach ($variants as $width => $path) {
        $checkPath = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (file_exists($checkPath)) {
            $existingVariants[] = $path . ' ' . $width . 'w';
        }
    }

    return !empty($existingVariants) ? implode(', ', $existingVariants) : '';
}
