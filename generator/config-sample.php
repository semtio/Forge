<?php
// Generator config: projects model
// Для запуска генерации используйте команду: php .\generator\cli.php

// Модель проектов: список сайтов и набор требуемых файлов/папок
$projects = [
    [
        // HEADER
        // Микроразметка WebPage, Organization, Article, WebSite
        'shema1' => true,
        // Микроразметка Person, ImageObject
        'shema2' => false,
        // canonical Если пусто, то вставляет домен
        'canonical' => '',

        // Имя папки проекта и тема оформления
        'domain' => '',
        'theme' => './themes/#?',

        // Данные
        'data' => [
            'domen_name' => '',
            'html_lang' => '',
            'site_name' => '',
            'title' => '',
            'description' => '',
            'logo' => 'src="./img/logo.webp"',
            'page_img' => 'src="./img/logo.webp"',
            'logo_width' => '180',

            // Add your real partner links here - they will be automatically encrypted
            'btn_link1' => 'https://google.com',
            'btn_link2' => 'https://google.com',

            'btn_text1' => 'btn 1',
            'btn_text2' => 'btn 2',
        ],
        // какие файлы обрабатывать для подстановки данных
        'postprocess' => ['/*.php'],

        // Стили
        'styles' => [
            // ОСНОВНЫЕ СТИЛИ

            // Header-footer
            ['class' => 'header', 'background' => '', 'file' => 'styles/styles.css'],
            ['class' => 'footer', 'background' => '', 'file' => 'styles/styles.css'],

            // Child стили
            // Hero блок background
            ['class' => 'sm-hero', 'background' => 'linear-gradient(rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.8)), url(\'../img/1.webp\')', 'file' => 'styles/styles.css'],

            // Фон страницы
            ['class' => 'body', 'background' => '', 'file' => 'styles/styles.css'],
            ['class' => 'main', 'background' => '', 'file' => 'styles/styles.css'],
            // Цвет текста страницы
            ['class' => 'main', 'color' => '', 'file' => 'styles/styles.css'],

            // Фон кнопок в шапке
            ['class' => 'sm-bgc-btn1', 'background' => '', 'file' => 'styles/styles.css'],
            ['class' => 'sm-bgc-btn2', 'background' => '', 'file' => 'styles/styles.css'],
            // Цвет текста кнопок в шапке
            ['class' => 'sm-color-btn1', 'color' => '', 'file' => 'styles/styles.css'],
            ['class' => 'sm-color-btn2', 'color' => '', 'file' => 'styles/styles.css'],
            // цвет основного текста
            ['class' => 'sm-color-maine', 'color' => '', 'file' => 'styles/styles.css'],
        ],

    ],
];

return $projects;
