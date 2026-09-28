<?php

declare(strict_types=1);

require __DIR__ . '/partials/bootstrap.php';

$db = db();
require_admin();

$statuses = [
    'new' => 'Новая',
    'process' => 'В процессе',
    'postponed' => 'Отложено',
    'done' => 'Выполнено',
    'cancelled' => 'Отменено',
];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    rate_limit('admin_action', 2);

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'update_request_status':
                $requestId = filter_input(
                    INPUT_POST,
                    'request_id',
                    FILTER_VALIDATE_INT
                );
                $status = $_POST['status'] ?? '';

                if (!$requestId || !isset($statuses[$status])) {
                    throw new RuntimeException('Некорректные данные заявки.');
                }

                $statement = $db->prepare(
                    'UPDATE requests SET status = ? WHERE id = ?'
                );
                $statement->execute([$status, $requestId]);

                $message = 'Статус заявки обновлён.';
                break;

            case 'delete_request':
                $requestId = filter_input(
                    INPUT_POST,
                    'request_id',
                    FILTER_VALIDATE_INT
                );

                if (!$requestId) {
                    throw new RuntimeException('Некорректный идентификатор заявки.');
                }

                $statement = $db->prepare(
                    "DELETE FROM requests
                     WHERE id = ?
                     AND status IN ('done', 'cancelled')"
                );
                $statement->execute([$requestId]);

                if ($statement->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Удалять можно только заявки из архива.'
                    );
                }

                $message = 'Заявка удалена из архива.';
                break;

            case 'upload_work':
                upload_work($db);
                $message = 'Фотография работы добавлена.';
                break;

            case 'delete_work':
                $workId = filter_input(
                    INPUT_POST,
                    'work_id',
                    FILTER_VALIDATE_INT
                );

                if (!$workId) {
                    throw new RuntimeException(
                        'Некорректный идентификатор фотографии.'
                    );
                }

                delete_work($db, $workId);
                $message = 'Фотография удалена.';
                break;

            case 'delete_review':
                $reviewId = filter_input(
                    INPUT_POST,
                    'review_id',
                    FILTER_VALIDATE_INT
                );

                if (!$reviewId) {
                    throw new RuntimeException(
                        'Некорректный идентификатор отзыва.'
                    );
                }

                $statement = $db->prepare(
                    'DELETE FROM reviews WHERE id = ?'
                );
                $statement->execute([$reviewId]);

                $message = 'Отзыв удалён.';
                break;

            default:
                throw new RuntimeException('Неизвестное действие.');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$activeRequests = $db
    ->query(
        "SELECT * FROM requests
         WHERE status NOT IN ('done', 'cancelled')
         ORDER BY created_at DESC"
    )
    ->fetchAll();

$closedRequests = $db
    ->query(
        "SELECT * FROM requests
         WHERE status IN ('done', 'cancelled')
         ORDER BY created_at DESC"
    )
    ->fetchAll();

$works = $db
    ->query('SELECT * FROM works ORDER BY created_at DESC')
    ->fetchAll();

$reviews = $db
    ->query(
        'SELECT reviews.*, users.name '
        . 'FROM reviews '
        . 'JOIN users ON users.id = reviews.user_id '
        . 'ORDER BY reviews.created_at DESC'
    )
    ->fetchAll();

function upload_work(PDO $db): void
{
    $title = trim($_POST['title'] ?? '');

    if (mb_strlen($title) < 2 || mb_strlen($title) > 190) {
        throw new RuntimeException(
            'Название работы должно содержать от 2 до 190 символов.'
        );
    }

    if (
        !isset($_FILES['photo'])
        || $_FILES['photo']['error'] !== UPLOAD_ERR_OK
    ) {
        throw new RuntimeException('Не удалось получить файл.');
    }

    $file = $_FILES['photo'];
    $maxSize = 5 * 1024 * 1024;

    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Максимальный размер изображения — 5 МБ.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Некорректная загрузка файла.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimeTypes[$mime])) {
        throw new RuntimeException(
            'Разрешены только JPG, PNG и WEBP.'
        );
    }

    $imageInfo = getimagesize($file['tmp_name']);

    if ($imageInfo === false) {
        throw new RuntimeException('Файл не является корректным изображением.');
    }

    [$width, $height] = $imageInfo;

    if ($width > 6000 || $height > 6000) {
        throw new RuntimeException(
            'Слишком большое разрешение изображения. Максимум 6000×6000.'
        );
    }

    $uploadDirectory = __DIR__ . '/uploads';

    if (!is_dir($uploadDirectory)) {
        mkdir($uploadDirectory, 0750, true);
    }

    $extension = $allowedMimeTypes[$mime];
    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
    $uploadPath = $uploadDirectory . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new RuntimeException('Не удалось сохранить изображение.');
    }

    try {
        $statement = $db->prepare(
            'INSERT INTO works (title, image_path) VALUES (?, ?)'
        );
        $statement->execute([
            $title,
            '/uploads/' . $fileName,
        ]);
    } catch (Throwable $exception) {
        @unlink($uploadPath);
        throw $exception;
    }
}

function delete_work(PDO $db, int $workId): void
{
    $statement = $db->prepare(
        'SELECT image_path FROM works WHERE id = ? LIMIT 1'
    );
    $statement->execute([$workId]);
    $work = $statement->fetch();

    if (!$work) {
        throw new RuntimeException('Фотография не найдена.');
    }

    $relativePath = (string) $work['image_path'];

    if (!str_starts_with($relativePath, '/uploads/')) {
        throw new RuntimeException('Некорректный путь к файлу.');
    }

    $fileName = basename($relativePath);
    $filePath = __DIR__ . '/uploads/' . $fileName;

    $deleteStatement = $db->prepare(
        'DELETE FROM works WHERE id = ?'
    );
    $deleteStatement->execute([$workId]);

    if (is_file($filePath)) {
        @unlink($filePath);
    }
}

function render_requests(array $requests, array $statuses): void
{
    if (!$requests) {
        echo '<p class="notice">Заявок в этом разделе нет.</p>';
        return;
    }

    foreach ($requests as $request):
        ?>
        <article class="admin-item">
            <div class="admin-item__head">
                <b>
                    #<?= e($request['id']) ?>
                    <?= e($request['name']) ?>
                </b>

                <small><?= e($request['created_at']) ?></small>
            </div>

            <p><?= nl2br(e($request['task'])) ?></p>

            <p>
                <?php if (!empty($request['phone'])): ?>
                    Телефон: <?= e($request['phone']) ?>
                <?php endif; ?>

                <?php if (!empty($request['telegram'])): ?>
                    <br>
                    Telegram: <?= e($request['telegram']) ?>
                <?php endif; ?>
            </p>

            <form method="post" class="admin-inline-form">
                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action"
                    value="update_request_status"
                >

                <input
                    type="hidden"
                    name="request_id"
                    value="<?= e($request['id']) ?>"
                >

                <select name="status" required>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option
                            value="<?= e($key) ?>"
                            <?= $request['status'] === $key ? 'selected' : '' ?>
                        >
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="btn" type="submit">
                    Сохранить
                </button>
            </form>

            <?php if (in_array($request['status'], ['done', 'cancelled'], true)): ?>
                <form method="post" class="danger-form">
                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="action"
                        value="delete_request"
                    >

                    <input
                        type="hidden"
                        name="request_id"
                        value="<?= e($request['id']) ?>"
                    >

                    <button
                        class="btn btn-danger"
                        type="submit"
                        onclick="return confirm('Удалить заявку из архива?');"
                    >
                        Удалить из архива
                    </button>
                </form>
            <?php endif; ?>
        </article>
        <?php
    endforeach;
}

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta
        http-equiv="Content-Security-Policy"
        content="default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
    >
    <link rel="stylesheet" href="/style.css">
    <title>Администрирование</title>
</head>
<body>
    <main class="section admin-page">
        <div class="section-head">
            <p class="eyebrow">Закрытая зона</p>
            <h1>Администратор</h1>
        </div>

        <?php if ($message): ?>
            <div class="notice">
                <?= e($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <section>
            <h2>Активные заявки</h2>
            <?php render_requests($activeRequests, $statuses); ?>
        </section>

        <section>
            <h2>Архив заявок</h2>
            <?php render_requests($closedRequests, $statuses); ?>
        </section>

        <section>
            <h2>Добавить фотографию работы</h2>

            <form
                class="form upload"
                method="post"
                enctype="multipart/form-data"
            >
                <?= csrf_field() ?>

                <input
                    type="hidden"
                    name="action"
                    value="upload_work"
                >

                <input
                    name="title"
                    maxlength="190"
                    placeholder="Название работы"
                    required
                >

                <input
                    type="file"
                    name="photo"
                    accept="image/jpeg,image/png,image/webp"
                    required
                >

                <small>JPG, PNG или WEBP, до 5 МБ, максимум 6000×6000.</small>

                <button class="btn" type="submit">
                    Загрузить
                </button>
            </form>
        </section>

        <section>
            <h2>Фотографии работ</h2>

            <div class="admin-work-grid">
                <?php if (!$works): ?>
                    <p class="notice">Фотографий пока нет.</p>
                <?php endif; ?>

                <?php foreach ($works as $work): ?>
                    <article class="work-card">
                        <img
                            class="work-thumb"
                            src="<?= e($work['image_path']) ?>"
                            alt="<?= e($work['title']) ?>"
                            loading="lazy"
                        >

                        <strong><?= e($work['title']) ?></strong>

                        <form method="post">
                            <?= csrf_field() ?>

                            <input
                                type="hidden"
                                name="action"
                                value="delete_work"
                            >

                            <input
                                type="hidden"
                                name="work_id"
                                value="<?= e($work['id']) ?>"
                            >

                            <button
                                class="btn btn-danger"
                                type="submit"
                                onclick="return confirm('Удалить эту фотографию?');"
                            >
                                Удалить
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h2>Отзывы</h2>

            <?php if (!$reviews): ?>
                <p class="notice">Отзывов пока нет.</p>
            <?php endif; ?>

            <?php foreach ($reviews as $review): ?>
                <article class="admin-item">
                    <div class="admin-item__head">
                        <b><?= e($review['name']) ?></b>
                        <span class="stars">
                            <?= str_repeat('★', (int) $review['rating']) ?>
                        </span>
                    </div>

                    <p><?= nl2br(e($review['text'])) ?></p>

                    <form method="post">
                        <?= csrf_field() ?>

                        <input
                            type="hidden"
                            name="action"
                            value="delete_review"
                        >

                        <input
                            type="hidden"
                            name="review_id"
                            value="<?= e($review['id']) ?>"
                        >

                        <button
                            class="btn btn-danger"
                            type="submit"
                            onclick="return confirm('Удалить этот отзыв?');"
                        >
                            Удалить отзыв
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>

        <p>
            <a href="/">На сайт</a>
            ·
            <a href="/logout.php">Выйти</a>
        </p>
    </main>
</body>
</html>
