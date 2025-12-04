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
    return $html;
}
