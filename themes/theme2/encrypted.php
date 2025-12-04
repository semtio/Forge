<?php
// как пользоваться:
// пример ссылок: <a href="encrypted.php?to=header_btns">Go to Header Buttons</a>
// пример ссылок: <a href="encrypted.php?to=other_btns">Go to Other Buttons</a>

$links = [
    'header_btns' => 'https://google.com',
    'other_btns' =>  'https://google.com',
];

$id = $_GET['to'] ?? '';

if (isset($links[$id])) {
    header('Location: ' . $links[$id], true, 302);
    exit;
}

// Если ссылка не найдена - перенаправить на главную
header('Location: /', true, 302);
exit;
