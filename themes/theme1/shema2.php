<?php
/**
 * JSON-LD микроразметка:
 * - Person (для плагина "Автор")
 * - ImageObject (для плагина "Таблица")
 *
 * Файл подключается в <head>:
 * <?php include __DIR__ . '/schema-person-image.php'; ?>
 */

/**
 * Получить базовый URL сайта (с http/https и завершающим слэшем)
 */
function get_site_url_person(): string
{
    $scheme = 'http';
    if (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    ) {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($scheme . '://' . $host, '/') . '/';
}

/**
 * Получить название сайта:
 * 1) Пытаемся прочитать <title> из index.php / index.html
 * 2) Если не получилось — берём домен без www
 */
function get_site_name_person(string $siteUrl): string
{
    $siteName = '';

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    if ($docRoot) {
        $indexPathPhp  = rtrim($docRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
        $indexPathHtml = rtrim($docRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';

        $fileToRead = null;
        if (is_readable($indexPathPhp)) {
            $fileToRead = $indexPathPhp;
        } elseif (is_readable($indexPathHtml)) {
            $fileToRead = $indexPathHtml;
        }

        if ($fileToRead) {
            $content = @file_get_contents($fileToRead);
            if ($content !== false && preg_match('~<title>(.*?)</title>~is', $content, $m)) {
                $title = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $parts = preg_split('~\s[\|\-–]\s~u', $title);
                if (!empty($parts[0])) {
                    $siteName = trim($parts[0]);
                }
            }
        }
    }

    if ($siteName === '') {
        $host = parse_url($siteUrl, PHP_URL_HOST) ?: 'localhost';
        $host = preg_replace('~^www\.~i', '', $host);
        $siteName = $host;
    }

    return $siteName;
}

/**
 * Логотип/картинка (для ImageObject) — статический путь ./img/logo.webp
 */
function get_image_url_person(string $siteUrl): string
{
    $relativePath = 'img/logo.webp';
    return $siteUrl . ltrim($relativePath, '/');
}

/* ==========================
 * СБОР ДАННЫХ
 * ========================== */

$siteUrl  = get_site_url_person();
$siteName = get_site_name_person($siteUrl);

// Person:
$personName           = $siteName;                        // name: Иванов Иван Иванович — здесь используем название сайта или позже впишешь ФИО
$personExpertComment  = 'Тут описание от эксперта';       // follows.name — текстовое описание, можно поменять под проект

// ImageObject:
$imageObjectUrl = get_image_url_person($siteUrl);         // по умолчанию ./img/logo.webp

/* ==========================
 * ФОРМИРОВАНИЕ JSON-LD
 * ========================== */

$schemaData = [
    '@context' => 'https://schema.org',
    '@graph'   => [

        // 1. Person (для плагина "Автор")
        [
            '@type' => 'Person',
            'name'  => $personName,
            'follows' => [
                '@type' => 'Person',
                'name'  => $personExpertComment, // здесь именно текст-описание эксперта
            ],
        ],

        // 2. ImageObject (для плагина "Таблица")
        [
            '@type' => 'ImageObject',
            'url'   => $imageObjectUrl,
        ],

    ],
];

?>
<script type="application/ld+json">
<?= json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>

</script>
