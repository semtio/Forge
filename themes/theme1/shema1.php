<?php
/**
 * Автоматическая генерация JSON-LD микроразметки:
 * WebPage, Organization, Article, WebSite
 * Логотип всегда берётся из ./img/logo.web
 */

/**
 * Получить базовый URL сайта (с http/https и завершающим слэшем)
 */
function get_site_url(): string
{
    $scheme = 'http';
    if (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    ) {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Убираем лишние слэши и добавляем финальный
    $url = rtrim($scheme . '://' . $host, '/') . '/';

    return $url;
}

/**
 * Получить название сайта:
 * 1) Пытаемся прочитать <title> из index.php
 * 2) Если не получилось — берём домен без www
 */
function get_site_name(string $siteUrl): string
{
    $siteName = '';

    // Попробуем достать <title> из index.php в DOCUMENT_ROOT
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    if ($docRoot) {
        $indexPathPhp = rtrim($docRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
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
                // Отрезаем всё после | или – (длинное тире)
                $parts = preg_split('~\s[\|\-–]\s~u', $title);
                if (!empty($parts[0])) {
                    $siteName = trim($parts[0]);
                }
            }
        }
    }

    // Если не удалось — берём домен
    if ($siteName === '') {
        $host = parse_url($siteUrl, PHP_URL_HOST) ?: 'localhost';
        $host = preg_replace('~^www\.~i', '', $host);
        $siteName = $host;
    }

    return $siteName;
}

/**
 * Получить URL текущей страницы (пока можно не использовать в Article)
 */
function get_current_url(string $siteUrl): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = ltrim($uri, '/');
    return $siteUrl . $uri;
}

/**
 * Получить URL логотипа (по ТЗ — всегда статический путь ./img/logo.web)
 */
function get_logo_url(string $siteUrl): string
{
    // Статический относительный путь
    $relativePath = 'img/logo.web';

    // Делаем абсолютный URL
    return $siteUrl . ltrim($relativePath, '/');
}

/**
 * Попробовать найти favicon в стандартных местах.
 * Если не найден — используем логотип.
 */
function get_favicon_url(string $siteUrl, string $fallbackLogoUrl): string
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    if ($docRoot) {
        $candidates = [
            '/favicon.ico',
            '/favicon.png',
            '/favicon.webp',
            '/img/favicon.ico',
            '/img/favicon.png',
            '/img/favicon.webp',
        ];

        foreach ($candidates as $rel) {
            $fullPath = rtrim($docRoot, DIRECTORY_SEPARATOR) . $rel;
            if (is_readable($fullPath)) {
                return $siteUrl . ltrim($rel, '/');
            }
        }
    }

    // Если ничего не нашли — используем логотип
    return $fallbackLogoUrl;
}

/**
 * Получить размеры изображения по URL, если возможно.
 * Возвращает [width, height] или [512, 512] по умолчанию.
 */
function get_image_size_from_url(string $imageUrl, int $defaultWidth = 512, int $defaultHeight = 512): array
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    if (!$docRoot) {
        return [$defaultWidth, $defaultHeight];
    }

    $siteUrl = get_site_url();
    // Преобразуем URL в файловый путь
    if (strpos($imageUrl, $siteUrl) === 0) {
        $relativePath = '/' . ltrim(substr($imageUrl, strlen($siteUrl)), '/');
        $fullPath = rtrim($docRoot, DIRECTORY_SEPARATOR) . $relativePath;

        if (is_readable($fullPath)) {
            $info = @getimagesize($fullPath);
            if ($info && isset($info[0], $info[1])) {
                return [(int)$info[0], (int)$info[1]];
            }
        }
    }

    return [$defaultWidth, $defaultHeight];
}

/**
 * Получить даты публикации и изменения (по index.php)
 */
function get_article_dates(): array
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? null;
    $nowIso  = date('c');

    if (!$docRoot) {
        return [$nowIso, $nowIso];
    }

    $indexPath = rtrim($docRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    if (!is_readable($indexPath)) {
        // fallback на index.html
        $indexPathHtml = rtrim($docRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
        if (is_readable($indexPathHtml)) {
            $indexPath = $indexPathHtml;
        } else {
            return [$nowIso, $nowIso];
        }
    }

    $ctime = @filectime($indexPath) ?: time();
    $mtime = @filemtime($indexPath) ?: time();

    $published = date('c', $ctime);
    $modified  = date('c', $mtime);

    return [$published, $modified];
}

/* ==========================
 * СБОР ДАННЫХ ДЛЯ СХЕМЫ
 * ========================== */

$siteUrl  = get_site_url();
// Get site name from $data['site_name'] if available, otherwise use auto-generated name
$siteName = (!empty($data['site_name']) && is_string($data['site_name']))
    ? $data['site_name']
    : get_site_name($siteUrl);

$logoUrl = get_logo_url($siteUrl);

$faviconUrl = get_favicon_url($siteUrl, $logoUrl);
list($publisherLogoWidth, $publisherLogoHeight) = get_image_size_from_url($faviconUrl, 512, 512);

// Article
$articleHeadline         = $siteName;
$articleUrl              = $siteUrl; // пока для главной
$articleMainEntityOfPage = $siteUrl;
list($articleDatePublished, $articleDateModified) = get_article_dates();

// Search URL template
$searchUrlTemplate = $siteUrl . '?s={search_term_string}';

/* ==========================
 * ФОРМИРОВАНИЕ JSON-LD
 * ========================== */

$schemaData = [
    '@context' => 'https://schema.org',
    '@graph'   => [

        // 1. WebPage
        [
            '@type' => 'WebPage',
            'name'  => $siteName,
            'url'   => $siteUrl,
            'primaryImageOfPage' => [
                '@type' => 'ImageObject',
                'url'   => $logoUrl,
            ],
        ],

        // 2. Organization
        [
            '@type' => 'Organization',
            'name'  => $siteName,
            'url'   => $siteUrl,
            'logo'  => [
                '@type' => 'ImageObject',
                'url'   => $logoUrl,
            ],
        ],

        // 3. Article
        [
            '@type'            => 'Article',
            'headline'         => $articleHeadline,
            'mainEntityOfPage' => $articleMainEntityOfPage,
            'url'              => $articleUrl,
            'datePublished'    => $articleDatePublished,
            'dateModified'     => $articleDateModified,
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => $siteName,
                'logo'  => [
                    '@type'  => 'ImageObject',
                    'url'    => $faviconUrl,
                    'width'  => $publisherLogoWidth,
                    'height' => $publisherLogoHeight,
                ],
            ],
        ],

        // 4. WebSite
        [
            '@type' => 'WebSite',
            'name'  => $siteName,
            'url'   => $siteUrl,
            'potentialAction' => [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $searchUrlTemplate,
                ],
                'query-input' => [
                    '@type'        => 'PropertyValueSpecification',
                    'valueRequired'=> true,
                    'valueName'    => 'search_term_string',
                ],
            ],
        ],

    ],
];

?>
<script type="application/ld+json">
<?= json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>

</script>
