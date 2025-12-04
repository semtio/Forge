# Техническое задание для верстальщика

## Цель
Сверстать HTML/CSS шаблон сайта, который будет совместим с нашим PHP-генератором статических сайтов.

---

## Структура шаблона

Создайте папку с названием темы (например, `theme3`, `theme4` и т.д.) со следующей структурой:

```
themeN/
├── header.php          # Шапка сайта (обязательно)
├── footer.php          # Подвал сайта (обязательно)
├── index.php           # Главная страница (обязательно)
├── robots.txt          # SEO-файл (можно скопировать из theme1)
├── sitemap.xml         # SEO-файл (можно скопировать из theme1)
├── shema1.php          # Микроразметка (можно скопировать из theme1)
├── shema2.php          # Микроразметка (можно скопировать из theme1)
├── encrypted.php       # Редирект для ссылок (можно скопировать из theme1)
├── .htaccess           # Настройки сервера (можно скопировать из theme1)
├── favicon.svg         # Иконка сайта
├── img/                # Папка с картинками
│   ├── logo.webp       # Логотип сайта (обязательно)
│   ├── 1.webp          # Фоновое изображение для hero-блока
│   └── ...             # Другие изображения
├── styles/
│   ├── styles.css      # Основные стили (обязательно)
│   ├── child.css       # Дополнительные стили (опционально)
│   └── tinymce.css     # Стили для контента из редактора (обязательно)
└── scripts/
    └── app.js          # JavaScript (опционально)
```

---

## Обязательные файлы и их содержимое

### 1. `header.php` — Шапка сайта

**Структура:**
```php
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
    <meta property="og:image" content="<?php echo $data['page_img']; ?>" />
    <link rel="stylesheet" href="./styles/styles.css">
    <link rel="stylesheet" href="./styles/child.css">
    <link rel="stylesheet" href="./styles/tinymce.css">
</head>

<body class="body">

    <header id="header" class="header">
        <div class="logo">
            <img style="width: calc(<?php echo $data['logo_width']; ?>*1px)" <?php echo $data['logo']; ?> alt="Logo">

            <!-- КНОПКИ В ШАПКЕ -->
            <!-- Если нужна 1 кнопка - оставьте только первый блок -->
            <!-- Если 3 кнопки - добавьте третий блок по аналогии -->
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

                <!-- Пример третьей кнопки (если нужна): -->
                <!--
                <a target="_blank" href="<?php echo $data['btn_link3'] ?>">
                    <button class="btn sm-bgc-btn3 sm-color-btn3">
                        <?php echo $data['btn_text3'] ?>
                    </button>
                </a>
                -->
            </div>
        </div>
    </header>

    <!-- HERO-БЛОК (опционально) -->
    <!-- Если нужен hero-блок с большим заголовком/кнопкой, вставьте: -->
    [content id="1"]

    <!-- Если hero НЕ нужен - просто удалите строку выше -->

    <main class="main">
```

**Важно:**
- **Не трогайте PHP-теги** `<?php echo $data['...'] ?>` — они заменяются генератором автоматически
- **Классы с префиксом `sm-`** (например, `sm-bgc-btn1`, `sm-color-btn1`) — это специальные классы для динамического управления цветами через генератор
- `[content id="1"]` — это **шорткод**, который генератор заменит на содержимое из админки TinyMCE (обычно hero-блок с заголовком и кнопкой)

---

### 2. `footer.php` — Подвал сайта

```php
    </main>

    <footer class="footer">
        <img style="width: calc(<?php echo $data['logo_width']; ?>*1px - 30px)" <?php echo $data['logo']?> alt="Logo">
        <p><a href="https://<?php echo $data['domen_name']; ?>"><?php echo $data['domen_name']; ?></a> © 2025</p>
        <p>Contact: <a href="mailto:info@<?php echo $data['domen_name']; ?>">info@<?php echo $data['domen_name']; ?></a></p>
    </footer>

    <script src="./scripts/app.js"></script>

</body>
</html>
```

---

### 3. `index.php` — Главная страница

```php
<?php include_once 'header.php'; ?>

[content id="2"]

<?php include_once 'footer.php'; ?>
```

**Важно:**
- `[content id="2"]` — это основной контент страницы, редактируется через админку TinyMCE
- **Не добавляйте** сюда HTML-разметку — весь контент будет вставляться из админки

---

## CSS: Специальные классы для генератора

Генератор автоматически подставляет цвета и фоны в ваш CSS. Для этого используйте **классы с префиксом `sm-`**.

### Обязательные классы в `styles/styles.css`:

```css
/* === HEADER & FOOTER === */
.header {
    background: #ffffff; /* Будет заменено генератором */
}

.footer {
    background: #000000; /* Будет заменено генератором */
}

/* === BODY & MAIN === */
.body {
    background: #f5f5f5; /* Фон страницы */
}

.main {
    background: #ffffff; /* Фон основного контента */
    color: #333333; /* Цвет текста */
}

/* === КНОПКИ В ШАПКЕ === */
.sm-bgc-btn1 {
    background: #ff6600; /* Фон первой кнопки */
}

.sm-color-btn1 {
    color: #ffffff; /* Цвет текста первой кнопки */
}

.sm-bgc-btn2 {
    background: #0066ff; /* Фон второй кнопки */
}

.sm-color-btn2 {
    color: #ffffff; /* Цвет текста второй кнопки */
}

/* Если есть третья кнопка: */
.sm-bgc-btn3 {
    background: #00cc66;
}

.sm-color-btn3 {
    color: #ffffff;
}

/* === HERO-БЛОК (если есть) === */
.sm-hero {
    background: linear-gradient(rgba(0, 0, 0, 0.65), rgba(0, 0, 0, 0.8)), url('../img/1.webp');
    background-size: cover;
    background-position: center;
}

/* === ЦВЕТ ОСНОВНОГО ТЕКСТА === */
.sm-color-maine {
    color: #333333;
}
```

### Стили для кнопок в контенте (TinyMCE) в `styles/tinymce.css`:

```css
/* Кнопка в hero-блоке или в контенте */
.sm-hero-button {
    background: #ff6600; /* Фон кнопки */
    color: #ffffff; /* Цвет текста кнопки */
    padding: 15px 40px;
    border: none;
    border-radius: 5px;
    font-size: 18px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.sm-hero-button:hover {
    opacity: 0.9;
}
```

---

## Как добавить больше кнопок в шапку

### Если нужно 3 кнопки:

**1. В `header.php` добавьте третью кнопку:**
```php
<a target="_blank" href="<?php echo $data['btn_link3'] ?>">
    <button class="btn sm-bgc-btn3 sm-color-btn3">
        <?php echo $data['btn_text3'] ?>
    </button>
</a>
```

**2. В `styles/styles.css` добавьте стили:**
```css
.sm-bgc-btn3 {
    background: #00cc66;
}

.sm-color-btn3 {
    color: #ffffff;
}
```

**3. В `generator/config.php` добавьте данные:**
```php
'data' => [
    // ... существующие поля ...
    'btn_link3' => './encrypted.php?to=header_btns',
    'btn_text3' => 'Третья кнопка',
],
```

**4. В секции `'styles'` добавьте:**
```php
['class' => 'sm-bgc-btn3', 'background' => '#00cc66', 'file' => 'styles/styles.css'],
['class' => 'sm-color-btn3', 'color' => '#ffffff', 'file' => 'styles/styles.css'],
```

---

## Переменные данных, доступные в шаблоне

Эти переменные автоматически подставляются генератором:

| Переменная | Описание | Пример |
|------------|----------|--------|
| `$data['domen_name']` | Домен сайта | `squidgamebler.org` |
| `$data['html_lang']` | Язык страницы | `en` |
| `$data['title']` | Заголовок страницы | `Squid Gamebler - Play Now` |
| `$data['description']` | Мета-описание | `Best online casino game...` |
| `$data['logo']` | HTML атрибут для лого | `src="./img/logo.webp"` |
| `$data['page_img']` | OG-изображение | `./img/logo.webp` |
| `$data['logo_width']` | Ширина лого в px | `180` |
| `$data['btn_link1']` | Ссылка 1-й кнопки | `./encrypted.php?to=header_btns` |
| `$data['btn_text1']` | Текст 1-й кнопки | `Play Now` |
| `$data['btn_link2']` | Ссылка 2-й кнопки | `./encrypted.php?to=header_btns` |
| `$data['btn_text2']` | Текст 2-й кнопки | `Sign Up` |
| `$data['shema1']` | Включить микроразметку 1 | `true`/`false` |
| `$data['shema2']` | Включить микроразметку 2 | `true`/`false` |

---

## Шорткоды для контента

В файлах `.php` можно использовать специальные шорткоды:

| Шорткод | Описание |
|---------|----------|
| `[content id="1"]` | Hero-блок (редактируется в админке) |
| `[content id="2"]` | Основной контент страницы |

---

## Чек-лист перед сдачей шаблона

- [ ] Файлы `header.php`, `footer.php`, `index.php` созданы
- [ ] В header.php используются PHP-переменные `$data['...']`
- [ ] Все классы с динамическими цветами имеют префикс `sm-`
- [ ] Папка `img/` содержит `logo.webp`
- [ ] Файл `styles/styles.css` содержит все классы `sm-*`
- [ ] Файл `styles/tinymce.css` содержит `.sm-hero-button`
- [ ] Шаблон адаптивный (мобильная версия)
- [ ] Все пути к файлам относительные (`./img/`, `./styles/`)
- [ ] Если hero-блок не нужен — строка `[content id="1"]` удалена

---

## Пример: Тема с одной кнопкой без hero-блока

**header.php:**
```php
<header class="header">
    <div class="logo">
        <img style="width: calc(<?php echo $data['logo_width']; ?>*1px)" <?php echo $data['logo']; ?> alt="Logo">

        <div class="buttons">
            <a target="_blank" href="<?php echo $data['btn_link1'] ?>">
                <button class="btn sm-bgc-btn1 sm-color-btn1">
                    <?php echo $data['btn_text1'] ?>
                </button>
            </a>
        </div>
    </div>
</header>

<main class="main">
```

**config.php (только одна кнопка):**
```php
'data' => [
    'btn_link1' => './encrypted.php?to=casino1',
    'btn_text1' => 'Play Now',
    // btn_link2 и btn_text2 можно удалить или оставить пустыми
],

'styles' => [
    ['class' => 'sm-bgc-btn1', 'background' => '#ff0066', 'file' => 'styles/styles.css'],
    ['class' => 'sm-color-btn1', 'color' => '#ffffff', 'file' => 'styles/styles.css'],
],
```

---

## Часто задаваемые вопросы

**Q: Можно ли использовать любые классы CSS?**
A: Да, используйте любые классы для вёрстки. Только классы с префиксом `sm-` будут управляться генератором.

**Q: Как добавить больше контентных блоков?**
A: Используйте `[content id="3"]`, `[content id="4"]` и т.д. в любом `.php` файле. Они будут редактироваться через админку TinyMCE.

**Q: Можно ли использовать JavaScript?**
A: Да, размещайте скрипты в `scripts/app.js`. Он автоматически подключится через `footer.php`.

**Q: Нужно ли заботиться о SEO?**
A: Нет, файлы `robots.txt`, `sitemap.xml`, `shema1.php`, `shema2.php` можно скопировать из `theme1` — они работают автоматически.

**Q: Как тестировать шаблон?**
A:
1. Скопируйте папку темы в `themes/themeN`
2. Добавьте конфигурацию в `generator/config.php`
3. Запустите: `php ./generator/cli.php --clean`
4. Проверьте результат в `build/ВАШ_ДОМЕН/`

---

## Контакты для вопросов

Если что-то непонятно — присылайте макет/дизайн, мы поможем адаптировать его под генератор.
