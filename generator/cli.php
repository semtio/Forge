<?php
// Minimal CLI to run generator based on config.php
// Usage:
//   php generator/cli.php           — обычная сборка
//   php generator/cli.php --clean   — очистить build/ и выполнить чистую сборку

$cfg = __DIR__ . DIRECTORY_SEPARATOR . 'config.php';
if (!is_file($cfg)) {
    fwrite(STDERR, "Config not found: $cfg" . PHP_EOL);
    exit(1);
}

$projects = require $cfg;

$gen = __DIR__ . DIRECTORY_SEPARATOR . 'generate.php';
if (!is_file($gen)) {
    fwrite(STDERR, "Generator not found: $gen" . PHP_EOL);
    exit(1);
}

require $gen;

if (!function_exists('generate')) {
    fwrite(STDERR, "Function 'generate' not found in generate.php" . PHP_EOL);
    exit(1);
}

if (!is_array($projects)) {
    fwrite(STDERR, "Config must return array of projects" . PHP_EOL);
    exit(1);
}

// --- Args parsing
$args = $argv ?? [];
$clean = in_array('--clean', $args, true);

// --- Helper: recursive remove dir
$rrmdir = function (string $dir) use (&$rrmdir): void {
    if (!file_exists($dir)) {
        return;
    }
    if (!is_dir($dir)) {
        @unlink($dir);
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) {
            $rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
};

// --- Clean build if requested
if ($clean) {
    $base = dirname(__DIR__); // .../public
    $buildRoot = $base . DIRECTORY_SEPARATOR . 'build';
    if (is_dir($buildRoot)) {
        fwrite(STDOUT, "Cleaning build directory: {$buildRoot}" . PHP_EOL);
        $rrmdir($buildRoot);
    } else {
        // nothing to clean
    }
}

// --- Run generation
generate($projects);
