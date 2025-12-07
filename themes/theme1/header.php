<?php
// Auto-add alt attributes to images dynamically
include_once __DIR__ . '/auto-alt.php';

// Start output buffering to inject encrypted links into any <a class="btn-link*">
if (!defined('BTN_LINK_RUNTIME_OB')) {
    define('BTN_LINK_RUNTIME_OB', true);
    // runtime.php живет в корне /plugins/tinymce/, а header.php лежит в /themes/theme1/
    $runtimeFile = dirname(__DIR__) . '/plugins/tinymce/runtime.php';
    if (file_exists($runtimeFile)) {
        require_once $runtimeFile;
        if (function_exists('injectButtonLinksRuntime')) {
            ob_start(function ($buffer) {
                return injectButtonLinksRuntime($buffer);
            });
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $data['html_lang']; ?>">

<head>
    <?php
    if ($data['shema1']) include_once 'shema1.php';
    if ($data['shema2']) include_once 'shema2.php';
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $data['title']; ?></title>
    <meta name="description" content="<?php echo $data['description'] ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://'; echo $_SERVER['HTTP_HOST']; ?>/" />
    <meta property="og:title" content="<?php echo $data['title']; ?>" />
    <meta property="og:description" content="<?php echo $data['description']; ?>" />
    <meta property="og:image" content="<?php echo $data['page_img']; ?>" />
    <meta property="og:site_name" content="<?php echo $data['site_name']; ?>" />

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image" />
    <meta property="twitter:url" content="<?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://'; echo $_SERVER['HTTP_HOST']; ?>/" />
    <meta property="twitter:title" content="<?php echo $data['title']; ?>" />
    <meta property="twitter:description" content="<?php echo $data['description']; ?>" />
    <meta property="twitter:image" content="<?php echo $data['page_img']; ?>" />

    <link rel="stylesheet" href="./styles/styles.css">
    <link rel="stylesheet" href="./styles/child.css">
    <link rel="stylesheet" href="./styles/tinymce.css">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
</head>

<style>
    footer img {
        width: calc(var(--logo-width) * 1px - 30px);
    }
</style>



<body class="body">

    <header id="header" class="header">
        <div class="logo">
            <img style="width: calc(<?php echo $data['logo_width']; ?>*1px)" id="logoImg" <?php echo $data['logo']; ?> alt="Logo">

            <div class="buttons">
                <a rel="noreferrer nofollow noopener" target="_blank" href="<?php echo $data['btn_link1'] ?>">
                    <button class="btn sm-bgc-btn1 sm-color-btn1">
                        <?php echo $data['btn_text1'] ?>
                    </button>
                </a>

                <a rel="noreferrer nofollow noopener" target="_blank" href="<?php echo $data['btn_link2'] ?>">
                    <button class="btn sm-bgc-btn2 sm-color-btn2">
                        <?php echo $data['btn_text2'] ?>
                    </button>
                </a>
            </div>
        </div>

    </header>

    [content id="1"]

    <main class="main">
