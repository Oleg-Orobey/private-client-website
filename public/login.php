<?php

require __DIR__ . '/partials/bootstrap.php';
$db = db();

if (user()) {
    header(
        'Location: '
        . (user()['role'] === 'admin' ? '/admin.php' : '/account.php')
    );
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    rate_limit('login', 10);

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Неверный email или пароль.';
    } else {
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $userData = $stmt->fetch();

        if ($userData && password_verify($password, $userData['password_hash'])) {
            if (password_needs_rehash($userData['password_hash'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);

                $update = $db->prepare(
                    'UPDATE users SET password_hash = ? WHERE id = ?'
                );
                $update->execute([$newHash, $userData['id']]);

                $userData['password_hash'] = $newHash;
            }

            session_regenerate_id(true);
            $_SESSION['user'] = $userData;

            header(
                'Location: '
                . ($userData['role'] === 'admin' ? '/admin.php' : '/account.php')
            );
            exit;
        }

        $msg = 'Неверный email или пароль.';
    }
}

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="/style.css">
    <title>Вход</title>
</head>
<body>
    <main class="section narrow">
        <h1>Вход</h1>

        <?php if ($msg): ?>
            <p class="error"><?= e($msg) ?></p>
        <?php endif; ?>

        <form class="form" method="post">
            <?= csrf_field() ?>

            <input
                type="email"
                name="email"
                placeholder="Email"
                required
            >

            <input
                type="password"
                name="password"
                placeholder="Пароль"
                required
            >

            <button class="btn" type="submit">Войти</button>
        </form>

        <p>
            <a href="/register.php">Регистрация</a>
            ·
            <a href="/">На главную</a>
        </p>
    </main>
</body>
</html>
