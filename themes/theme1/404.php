<?php
http_response_code(404);

// Попытка загрузить данные из конфига генератора (если доступен)
$data = [];
$configPath = __DIR__ . '/../../generator/config.php';
if (file_exists($configPath)) {
    $projects = require $configPath;
    // Ищем проект с текущим доменом
    $currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    foreach ($projects as $proj) {
        if (isset($proj['domain']) && strpos($currentDomain, $proj['domain']) !== false) {
            $data = $proj['data'] ?? [];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo isset($data['html_lang']) ? htmlspecialchars($data['html_lang']) : 'en'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="./styles/styles.css">
    <link rel="stylesheet" href="./styles/child.css">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <style>
        .not-found {
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }

        .not-found h1 {
            font-size: 32px;
            margin: 0 0 12px;
        }

        .not-found p {
            font-size: 16px;
            margin: 0 0 24px;
            color: #666;
        }

        .not-found a.btn {
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>

<body class="body">

    <header class="header" style="padding:20px;">
        <a href="/" style="display:inline-block">
            <img style="width: calc(<?php echo isset($data['logo_width']) ? (int)$data['logo_width'] : 180; ?>*1px)" <?php echo isset($data['logo']) ? $data['logo'] : 'src="./img/Logo.webp"'; ?> alt="Logo">
        </a>
    </header>

    <main class="main">

        <section class="not-found">
            <?php if (isset($data['html_lang']) && $data['html_lang'] === 'ru'): ?>
                <h1>Страница не найдена (404)</h1>
                <p>Похоже, вы перешли по несуществующей ссылке. Вернитесь на главную, пожалуйста.</p>
                <a class="btn sm-bgc-btn1 sm-color-btn1" href="/">На главную</a>
            <?php else: ?>
                <h1>Page Not Found (404)</h1>
                <p>It seems you've followed a broken link. Please return to the homepage.</p>
                <a class="btn sm-bgc-btn1 sm-color-btn1" href="/">Go to Homepage</a>
            <?php endif; ?>
        </section>

    </main>

    <footer class="footer" style="padding:20px; text-align:center;">
        <p><a href="https://<?php echo isset($data['domen_name']) ? htmlspecialchars($data['domen_name']) : ($_SERVER['HTTP_HOST'] ?? 'localhost'); ?>">
                <?php echo isset($data['domen_name']) ? htmlspecialchars($data['domen_name']) : ($_SERVER['HTTP_HOST'] ?? 'localhost'); ?></a> © <?php echo date('Y'); ?></p>
    </footer>

</body>

</html>
