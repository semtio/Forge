<?php
/**
 * Auto Alt Fixer
 * Automatically adds alt attributes to img tags that don't have them
 * Include this file in header.php before </head>
 */

// Start output buffering
if (!isset($autoAltEnabled)) {
    $autoAltEnabled = true;

    ob_start(function($buffer) {
        // Process buffer to add missing alt attributes and srcset
        $buffer = addMissingAlts($buffer);
        $buffer = addResponsiveSrcset($buffer);
        return $buffer;
    });

    // Register shutdown function to flush buffer
    register_shutdown_function(function() {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    });
}

/**
 * Add missing alt attributes to all img tags in HTML
 * @param string $html HTML content
 * @return string HTML with alt attributes added
 */
function addMissingAlts($html) {
    // Pattern to find img tags
    $pattern = '/<img([^>]*)>/i';

    $html = preg_replace_callback($pattern, function($matches) {
        $imgTag = $matches[0];
        $attributes = $matches[1];

        // Check if alt attribute already exists
        if (preg_match('/\salt\s*=/i', $attributes)) {
            // Alt exists, check if it's empty
            if (preg_match('/\salt\s*=\s*(["\'])[\s]*\1/i', $attributes)) {
                // Empty alt, generate from src
                if (preg_match('/\ssrc\s*=\s*(["\'])([^\1]+?)\1/i', $attributes, $srcMatch)) {
                    $src = $srcMatch[2];
                    $altText = generateAltFromSrc($src);

                    // Replace empty alt with generated one
                    $imgTag = preg_replace(
                        '/\salt\s*=\s*(["\'])[\s]*\1/i',
                        ' alt="' . htmlspecialchars($altText, ENT_QUOTES) . '"',
                        $imgTag
                    );
                }
            }
            return $imgTag;
        }

        // No alt attribute found, add it
        $altText = 'Image';
        if (preg_match('/\ssrc\s*=\s*(["\'])([^\1]+?)\1/i', $attributes, $srcMatch)) {
            $src = $srcMatch[2];
            $altText = generateAltFromSrc($src);
        }

        // Add alt before closing >
        $imgTag = str_replace('>', ' alt="' . htmlspecialchars($altText, ENT_QUOTES) . '">', $imgTag);

        return $imgTag;
    }, $html);

    return $html;
}

/**
 * Generate alt text from image src
 * @param string $src Image source path
 * @return string Generated alt text
 */
function generateAltFromSrc($src) {
    // Get filename without extension
    $filename = basename($src);
    $altText = pathinfo($filename, PATHINFO_FILENAME);

    // If filename is only numbers (like 1, 2, 123), keep it as is
    if (preg_match('/^\d+$/', $altText)) {
        return $altText;
    }

    // Clean up the name:
    // 1. Replace hyphens, underscores with spaces
    $altText = str_replace(['-', '_'], ' ', $altText);

    // 2. Remove standalone numbers only if there's text too
    $altText = preg_replace('/\b\d+\b/', '', $altText);

    // 3. Remove extra spaces
    $altText = preg_replace('/\s+/', ' ', $altText);
    $altText = trim($altText);

    // 4. Capitalize first letter
    if (!empty($altText)) {
        $altText = ucfirst($altText);
    } else {
        $altText = 'Image';
    }

    return $altText;
}

/**
 * Add responsive srcset attributes to images in body tag
 * Checks for -600, -1200, -1920 variants and adds srcset if they exist
 *
 * @param string $html HTML content
 * @return string Modified HTML with srcset attributes
 */
function addResponsiveSrcset($html) {
    // Extract body content only
    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $bodyMatch)) {
        $bodyContent = $bodyMatch[1];

        // Find all img tags and add srcset if variants exist
        $bodyContent = preg_replace_callback('/<img([^>]*)>/i', function($matches) {
            $imgTag = $matches[0];
            $attributes = $matches[1];

            // Skip if srcset already exists
            if (preg_match('/\ssrcset\s*=/i', $attributes)) {
                return $imgTag;
            }

            // Extract src attribute
            if (preg_match('/\ssrc\s*=\s*(["\'])([^\1]+?)\1/i', $attributes, $srcMatch)) {
                $src = $srcMatch[2];
                $srcsetAttr = generateSrcset($src);

                if ($srcsetAttr) {
                    // Add srcset and sizes attributes before closing >
                    $imgTag = str_replace('>', ' srcset="' . $srcsetAttr . '" sizes="(max-width: 600px) 600px, (max-width: 1200px) 1200px, 1920px">', $imgTag);
                }
            }

            return $imgTag;
        }, $bodyContent);

        // Replace body content in original HTML
        $html = preg_replace('/<body[^>]*>.*?<\/body>/is', '<body>' . $bodyContent . '</body>', $html);
    }

    return $html;
}

/**
 * Generate srcset attribute value for responsive images
 * Checks for -600, -1200, -1920 variants of the image
 *
 * @param string $src Original image source path
 * @return string Srcset attribute value or empty string if no variants exist
 */
function generateSrcset($src) {
    // Get path components
    $pathInfo = pathinfo($src);
    $dir = isset($pathInfo['dirname']) && $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] : '';
    $filename = $pathInfo['filename'];
    $ext = isset($pathInfo['extension']) ? $pathInfo['extension'] : '';

    // Build variant paths (relative URLs for srcset)
    $variants = [
        600 => ($dir ? $dir . '/' : '') . $filename . '-600.' . $ext,
        1200 => ($dir ? $dir . '/' : '') . $filename . '-1200.' . $ext,
        1920 => ($dir ? $dir . '/' : '') . $filename . '-1920.' . $ext
    ];

    // Check which variants exist on disk
    $existingVariants = [];
    foreach ($variants as $width => $path) {
        // Normalize path for file_exists check
        $fsPath = str_replace(['\\', '//'], '/', $path);

        // Try multiple approaches to find the file on disk
        $checkPaths = [
            __DIR__ . '/' . $fsPath,                              // Relative to current directory (most reliable)
            $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($fsPath, '/'), // Absolute path from DOCUMENT_ROOT
            realpath(__DIR__) . '/' . $fsPath,                    // Real absolute path
        ];

        foreach ($checkPaths as $checkPath) {
            if (file_exists($checkPath)) {
                // File exists, add to srcset (use relative URL for output)
                $existingVariants[] = $path . ' ' . $width . 'w';
                break;
            }
        }
    }

    // Return srcset string or empty if no variants found
    return !empty($existingVariants) ? implode(', ', $existingVariants) : '';
}
