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
                <a target="_blank" href="<?php echo $data['btn_link1'] ?>">
                    <button class="btn sm-bgc-btn1 sm-color-btn1">
                        <?php echo $data['btn_text1'] ?>
                    </button>
                </a>

                <a target="_blank" href="<?php echo $data['btn_link2'] ?>">
                    <button class="btn sm-bgc-btn2 sm-color-btn2">
                        <?php echo $data['btn_text2'] ?>
                    </button>
                </a>
            </div>
        </div>

    </header>

    <!-- 3 br только для сайтов без HERO -->
    <br>
    <br>
    <br>

    <!-- тут можно вставить HERO шорткодом -->

    <main class="main">
