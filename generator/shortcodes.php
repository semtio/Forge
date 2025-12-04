<?php
/**
 * Shortcode processing system
 */

class ShortcodeProcessor {
    private $shortcodes = [];
    private $currentFile = null;

    public function setCurrentFile($file) {
        $this->currentFile = $file;
    }

    public function register($tag, $callback) {
        $this->shortcodes[$tag] = $callback;
    }

    public function process($content) {
        // Pattern: [shortcode attr="value" attr2="value2"]
        $pattern = '/\[(\w+)([^\]]*)\]/';

        return preg_replace_callback($pattern, function($matches) {
            $tag = $matches[1];
            $attrString = trim($matches[2] ?? '');

            if (!isset($this->shortcodes[$tag])) {
                return $matches[0]; // Return unchanged if shortcode not found
            }

            // Parse attributes
            $attrs = $this->parseAttributes($attrString);

            // Execute callback with current file info
            $callback = $this->shortcodes[$tag];
            if (is_callable($callback)) {
                return call_user_func($callback, $attrs, $this->currentFile);
            }

            return $matches[0];
        }, $content);
    }

    private function parseAttributes($attrString) {
        $attrs = [];

        // Pattern: attr="value" or attr='value' or attr=value
        $pattern = '/(\w+)\s*=\s*(["\'])([^"\']*)\2|(\w+)\s*=\s*([^\s]+)/';

        if (preg_match_all($pattern, $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (!empty($match[1])) {
                    $attrs[$match[1]] = $match[3];
                } elseif (!empty($match[4])) {
                    $attrs[$match[4]] = $match[5];
                }
            }
        }

        return $attrs;
    }
}

function loadPlugins($pluginsDir, ShortcodeProcessor $processor) {
    if (!is_dir($pluginsDir)) {
        return;
    }

    $plugins = scandir($pluginsDir);
    if ($plugins === false) {
        return;
    }

    foreach ($plugins as $plugin) {
        if ($plugin === '.' || $plugin === '..') continue;

        $pluginFile = $pluginsDir . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR . 'plugin.php';

        if (is_file($pluginFile)) {
            $config = include $pluginFile;

            if (is_array($config) && isset($config['shortcodes']) && is_array($config['shortcodes'])) {
                foreach ($config['shortcodes'] as $tag => $callback) {
                    $processor->register($tag, $callback);
                }
            }
        }
    }
}
