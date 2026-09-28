<?php

require __DIR__ . '/partials/bootstrap.php';
$db = db();

if (user()) {
    header('Location: /account.php');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    rate_limit('register', 30);

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $isValid = (
        mb_strlen($name) >= 2
        && mb_strlen($name) <= 120
        && filter_var($email, FILTER_VALIDATE_EMAIL)
        && strlen($password) >= 8
    );

    if (!$isValid) {
        $msg = 'Проверьте данные: пароль минимум 8 символов.';
    } else {
        try {
            $stmt = $db->prepare(
                'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)'
            );

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);

            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id' => (int) $db->lastInsertId(),
                'name' => $name,
                'email' => $email,
                'role' => 'user',
            ];

            header('Location: /account.php');
            exit;
        } catch (PDOException $e) {
            $msg = 'Такой email уже зарегистрирован.';
        }
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
    <title>Регистрация</title>
</head>
<body>
    <main class="section narrow">
        <h1>Регистрация</h1>

        <?php if ($msg): ?>
            <p class="error"><?= e($msg) ?></p>
        <?php endif; ?>

        <form class="form" method="post">
            <?= csrf_field() ?>

            <input
                name="name"
                maxlength="120"
                placeholder="Имя"
                required
            >

            <input
                type="email"
                name="email"
                maxlength="190"
                placeholder="Email"
                required
            >

            <input
                type="password"
                name="password"
                minlength="8"
                placeholder="Пароль от 8 символов"
                required
            >

            <button class="btn" type="submit">Создать аккаунт</button>
        </form>

        <p>
            <a href="/login.php">Уже есть аккаунт?</a>
        </p>
    </main>
</body>
</html>
