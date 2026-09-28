<?php

require __DIR__ . '/partials/bootstrap.php';
$db = db();
require_login();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    rate_limit('review', 30);

    $rating = (int) ($_POST['rating'] ?? 0);
    $text = trim($_POST['text'] ?? '');

    if ($rating < 1 || $rating > 5 || mb_strlen($text) < 5 || mb_strlen($text) > 2000) {
        $msg = 'Укажите оценку и отзыв от 5 до 2000 символов.';
    } else {
        $stmt = $db->prepare(
            'INSERT INTO reviews (user_id, rating, text) VALUES (?, ?, ?)'
        );

        $stmt->execute([
            user()['id'],
            $rating,
            $text,
        ]);

        $msg = 'Отзыв добавлен.';
    }
}

$stmt = $db->prepare(
    'SELECT * FROM reviews WHERE user_id = ? ORDER BY created_at DESC'
);
$stmt->execute([user()['id']]);
$reviews = $stmt->fetchAll();

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="/style.css">
    <title>Личный кабинет</title>
</head>
<body>
    <main class="section narrow">
        <h1>Здравствуйте, <?= e(user()['name']) ?></h1>
        <p><?= e(user()['email']) ?></p>

        <h2>Оставить отзыв</h2>

        <?php if ($msg): ?>
            <p class="notice"><?= e($msg) ?></p>
        <?php endif; ?>

        <form class="form" method="post">
            <?= csrf_field() ?>

            <select name="rating" required>
                <option value="">Оценка</option>

                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>">
                        <?= $i ?> / 5
                    </option>
                <?php endfor; ?>
            </select>

            <textarea
                name="text"
                maxlength="2000"
                placeholder="Ваш отзыв"
                required
            ></textarea>

            <button class="btn" type="submit">Опубликовать</button>
        </form>

        <h2>Мои отзывы</h2>

        <?php foreach ($reviews as $review): ?>
            <article class="review">
                <b><?= str_repeat('★', (int) $review['rating']) ?></b>
                <p><?= e($review['text']) ?></p>
            </article>
        <?php endforeach; ?>

        <p>
            <a href="/">На сайт</a>
            ·
            <a href="/logout.php">Выйти</a>
        </p>
    </main>
</body>
</html>
