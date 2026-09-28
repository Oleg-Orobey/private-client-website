<?php

require_once __DIR__ . '/partials/bootstrap.php';
require_once __DIR__ . '/../config/site.php';

$works = [];
$reviews = [];
$requestSuccess = get_flash('request_success');



try {
    $works = db()
        ->query('SELECT * FROM works ORDER BY created_at DESC')
        ->fetchAll();

    $reviews = db()
        ->query(
            'SELECT r.*, u.name '
            . 'FROM reviews r '
            . 'JOIN users u ON u.id = r.user_id '
            . 'ORDER BY r.created_at DESC '
            . 'LIMIT 6'
        )
        ->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    
}

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ремонт рядом — мелкие бытовые услуги</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>

<header class="header">
    <a class="logo" href="#top">
        Ремонт<span>рядом</span>
    </a>

    <nav>
        <a href="#services">Услуги</a>
        <a href="#works">Работы</a>
        <a href="#reviews">Отзывы</a>
    </nav>

    <a
        class="btn btn-outline"
        href="<?= user()
            ? (user()['role'] === 'admin' ? '/admin.php' : '/account.php')
            : '/login.php' ?>"
    >
        <?= user() ? 'Кабинет' : 'Войти / кабинет' ?>
    </a>

    <button class="menu" type="button" aria-label="Меню">
        ☰
    </button>
</header>

<main id="top">

    <?php if ($requestSuccess): ?>
        <section class="section">
            <div class="notice">
                <strong>Готово.</strong> <?= e($requestSuccess) ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="hero">
        <div>
            <p class="eyebrow">
                Мелкий бытовой ремонт без лишних хлопот
            </p>

            <h1>
                Дом в порядке.<br>
                <span>Задачи решены.</span>
            </h1>

            <p class="lead">
                Соберём мебель, повесим полки, заменим фурнитуру и исправим
                мелкие неисправности. Оставьте заявку — согласуем удобное время.
            </p>

            <div class="actions">
                <a class="btn" href="#request">
                    Оставить заявку
                </a>

                <a class="text-link" href="#services">
                    Посмотреть услуги →
                </a>
            </div>

            <div class="trust">
                <span>✓ Аккуратно</span>
                <span>✓ Прозрачно</span>
                <span>✓ С гарантией</span>
            </div>
        </div>

        <div class="hero-card">
            <div class="hero-icon">⌂</div>

            <h3>Мастер на час</h3>

            <p>
                Один визит — несколько бытовых задач.
            </p>

            <div class="mini-list">
                <span>Сборка и установка</span>
                <span>Крепёж и навеска</span>
                <span>Мелкий ремонт</span>
            </div>
        </div>
    </section>

    <section id="services" class="section">
        <div class="section-head">
            <p class="eyebrow">Что мы делаем</p>
            <h2>Помощь по дому — от одной задачи до списка</h2>
        </div>

        <div class="service-grid">
            <article>
                🔧
                <h3>Мелкий ремонт</h3>
                <p>
                    Замена ручек, замков, смесителей, устранение мелких
                    неисправностей.
                </p>
            </article>

            <article>
                🪑
                <h3>Мебель</h3>
                <p>
                    Сборка, разборка, регулировка фасадов и фурнитуры.
                </p>
            </article>

            <article>
                🧰
                <h3>Монтаж</h3>
                <p>
                    Полки, карнизы, зеркала, картины и другие крепления.
                </p>
            </article>
        </div>
    </section>

    <section id="works" class="section soft">
        <div class="section-head">
            <p class="eyebrow">Примеры работ</p>
            <h2>Результат, который можно увидеть</h2>
        </div>

        <?php for ($i = 1; $i <= 3; $i++): ?>
            <div class="carousel" data-carousel>
                <button class="prev" type="button" aria-label="Предыдущая работа">
                    ‹
                </button>

                <div class="slides">
                    <div class="slide">
                        <div class="placeholder">
                            <?php if (isset($works[$i - 1])): ?>
                                <img
                                    src="<?= e($works[$i - 1]['image_path']) ?>"
                                    alt="<?= e($works[$i - 1]['title']) ?>"
                                >
                            <?php else: ?>
                                Добавьте фотографии в кабинете администратора
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="slide">
                        <div class="placeholder">
                            Фото работ <?= $i ?>.2
                        </div>
                    </div>

                    <div class="slide">
                        <div class="placeholder">
                            Фото работ <?= $i ?>.3
                        </div>
                    </div>
                </div>

                <button class="next" type="button" aria-label="Следующая работа">
                    ›
                </button>

                <div class="dots"></div>
            </div>
        <?php endfor; ?>
    </section>

    <section class="section process">
        <div class="section-head">
            <p class="eyebrow">Как всё проходит</p>
            <h2>Три понятных шага</h2>
        </div>

        <div class="steps">
            <article>
                <b>01</b>
                <h3>Заявка</h3>
                <p>
                    Опишите задачу и оставьте телефон или Telegram.
                </p>
            </article>

            <article>
                <b>02</b>
                <h3>Согласование</h3>
                <p>
                    Уточним детали, материалы и удобное время.
                </p>
            </article>

            <article>
                <b>03</b>
                <h3>Выполнение</h3>
                <p>
                    Выполним работу, проверим результат и наведём порядок.
                </p>
            </article>
        </div>
    </section>

    <section id="request" class="request section">
        <div class="request-info">
            <p class="eyebrow">Быстрая заявка</p>

            <h2>Расскажите, что нужно сделать</h2>

            <p>
                Обязательное поле связи: телефон или ссылка на Telegram.
            </p>

            <div class="contact-card">
                <div>
                    <span class="contact-label">Telegram</span>

                    <a
                        class="telegram-link"
                        href="<?= e(TELEGRAM_URL) ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <?= e(TELEGRAM_LABEL) ?>
                    </a>

                    <p>
                        Напишите нам напрямую, если удобнее обсудить задачу
                        в Telegram.
                    </p>
                </div>

                <div class="qr-card">
                    <img
                        src="<?= e(TELEGRAM_QR) ?>"
                        alt="QR-код для перехода в Telegram"
                        width="150"
                        height="150"
                    >

                    <span>Наведите камеру телефона</span>
                </div>
            </div>
        </div>

        <form action="/request.php" method="post" class="form">
            <?= csrf_field() ?>

            <input
                name="name"
                maxlength="120"
                placeholder="Ваше имя"
                autocomplete="name"
                required
            >

            <input
                name="phone"
                maxlength="25"
                placeholder="Телефон"
                autocomplete="tel"
            >

            <input
                name="telegram"
                maxlength="255"
                placeholder="Telegram @username или ссылка"
            >

            <textarea
                name="task"
                maxlength="5000"
                placeholder="Опишите задачу"
                required
            ></textarea>

            <div class="honeypot" aria-hidden="true">
                <label>
                    Не заполняйте это поле
                    <input
                        type="text"
                        name="website"
                        tabindex="-1"
                        autocomplete="off"
                    >
                </label>
            </div>

            <label>
                <input
                    type="checkbox"
                    name="consent"
                    value="1"
                    required
                >
                Согласен(на) на обработку данных
            </label>

            <button class="btn" type="submit">
                Отправить заявку
            </button>
        </form>
    </section>

    <section id="reviews" class="section">
        <div class="section-head">
            <p class="eyebrow">Отзывы</p>
            <h2>Мнения клиентов</h2>
        </div>

        <div class="review-grid">
            <?php foreach ($reviews as $review): ?>
                <article>
                    <div class="stars">
                        <?= str_repeat('★', (int) $review['rating']) ?>
                    </div>

                    <p><?= e($review['text']) ?></p>
                    <strong><?= e($review['name']) ?></strong>
                </article>
            <?php endforeach; ?>

            <?php if (!$reviews): ?>
                <article>
                    <p>
                        Отзывы появятся после первых выполненных заказов.
                    </p>
                </article>
            <?php endif; ?>
        </div>
    </section>

</main>

<footer>
    <div class="logo">
        Ремонт<span>рядом</span>
    </div>

    <p>
        Приём заявок ежедневно · Личный кабинет для клиентов и администратора
    </p>
</footer>

<script src="/app.js"></script>
</body>
</html>
