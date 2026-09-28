<?php

declare(strict_types=1);



if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Strict');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure' => !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off',
    ]);

    session_start();
}


header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('X-XSS-Protection: 0');

require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="'
        . htmlspecialchars(
            $_SESSION['csrf'],
            ENT_QUOTES,
            'UTF-8'
        )
        . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';

    if (
        !is_string($token)
        || !isset($_SESSION['csrf'])
        || !hash_equals($_SESSION['csrf'], $token)
    ) {
        http_response_code(419);
        exit('Сессия истекла. Обновите страницу и повторите действие.');
    }
}

function user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function require_login(): void
{
    if (!user()) {
        header('Location: /login.php');
        exit;
    }
}

function require_admin(): void
{
    $currentUser = user();

    if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Доступ запрещён.');
    }
}

function e(mixed $value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}


function get_flash(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = (string) $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $message;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function rate_limit(string $key, int $seconds = 20): void
{
    $now = time();
    $sessionKey = 'rate_' . hash('sha256', $key . '|' . client_ip());

    if (
        isset($_SESSION[$sessionKey])
        && $now - (int) $_SESSION[$sessionKey] < $seconds
    ) {
        http_response_code(429);
        exit('Слишком часто. Попробуйте позже.');
    }

    $_SESSION[$sessionKey] = $now;
}
