<?php

declare(strict_types=1);

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
}

define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME', 'remont_site'));
define('DB_USER', env_value('DB_USER'));
define('DB_PASS', env_value('DB_PASS'));

function pdo_options(): array
{
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (DB_USER === '') {
        throw new RuntimeException('Переменная окружения DB_USER не задана.');
    }

    $pdo = new PDO(
        'mysql:host=' . DB_HOST
        . ';port=' . DB_PORT
        . ';dbname=' . DB_NAME
        . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        pdo_options()
    );

    return $pdo;
}

function database_is_ready(): bool
{
    try {
        db()->query('SELECT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}
