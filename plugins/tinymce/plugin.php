<?php
/**
 * TinyMCE Plugin
 *
 * Provides content loading through shortcodes.
 * Content is stored in data/ folder and loaded dynamically.
 */

if (!function_exists('tinymce_content_shortcode')) {
    function tinymce_content_shortcode($attrs, $currentFile = null) {
        // Новый формат: [content id="X"] -> data/content-X.html
        if (isset($attrs['id'])) {
            $id = preg_replace('/[^0-9]/', '', (string)$attrs['id']);
        } else {
            // Если id не указан - возврат комментария (теперь id обязателен)
            return '<!-- TinyMCE content missing id attribute -->';
        }
        if ($id === '') {
            return '<!-- TinyMCE invalid id -->';
        }
        $fileKey = 'content-' . $id; // будет искаться content-<id>.html
        // Формируем PHP блок для рантайма
        $php = "<?php\n" .
               "\t\$__rt = __DIR__ . '/plugins/tinymce/runtime.php';\n" .
               "\tif (file_exists(\$__rt)) { require_once \$__rt; }\n" .
               "\techo loadTinyContent('" . $fileKey . "');\n" .
               "?>";
        return $php;
    }
}

return [
    'shortcodes' => [
        'content' => 'tinymce_content_shortcode',
    ]
];
