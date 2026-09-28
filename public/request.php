<?php

declare(strict_types=1);

require __DIR__ . '/partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: /#request');
    exit;
}

try {
    verify_csrf();
    rate_limit('request', 20);

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $telegram = trim($_POST['telegram'] ?? '');
    $task = trim($_POST['task'] ?? '');
    $consent = $_POST['consent'] ?? '';
    $honeypot = trim($_POST['website'] ?? '');

    if ($honeypot !== '') {
        http_response_code(400);
        exit('Некорректный запрос.');
    }

    $isPhoneValid = $phone === ''
        || preg_match('/^[0-9+()\s-]{7,25}$/', $phone);

    $isTelegramValid = $telegram === ''
        || mb_strlen($telegram) <= 255;

    $hasContact = $phone !== '' || $telegram !== '';

    if (
        mb_strlen($name) < 2
        || mb_strlen($name) > 120
        || mb_strlen($task) < 5
        || mb_strlen($task) > 5000
        || !$hasContact
        || !$isPhoneValid
        || !$isTelegramValid
        || $consent !== '1'
    ) {
        http_response_code(422);
        exit('Проверьте имя, задачу и телефон/Telegram.');
    }

    $statement = db()->prepare(
        'INSERT INTO requests (user_id, name, phone, telegram, task) '
        . 'VALUES (?, ?, ?, ?, ?)'
    );

    $currentUser = user();

    $statement->execute([
        $currentUser['id'] ?? null,
        $name,
        $phone !== '' ? $phone : null,
        $telegram !== '' ? $telegram : null,
        $task,
    ]);

    set_flash('request_success', 'Заявка отправлена. Мы получили ваши данные и свяжемся с вами для уточнения деталей.');
    header('Location: /index.php#request');
    exit;
} catch (Throwable $e) {
    error_log(
        'Request submission failed: ' . $e->getMessage()
    );

    http_response_code(500);

    echo '<!doctype html>';
    echo '<html lang="ru">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Ошибка отправки заявки</title>';
    echo '<link rel="stylesheet" href="/style.css">';
    echo '</head>';
    echo '<body>';
    echo '<main class="section narrow">';
    echo '<h1>Не удалось отправить заявку</h1>';
    echo '<div class="error">';
    echo 'Не удалось сохранить заявку. Проверьте данные и попробуйте ещё раз.';
    echo '</div>';
    echo '<p><a class="btn" href="/index.php#request">Вернуться к форме</a></p>';
    echo '</main>';
    echo '</body>';
    echo '</html>';
}
