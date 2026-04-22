<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const AUTH_KEY = 'admin_auth';
const CSRF_KEY = 'admin_csrf';
const FLASH_KEY = 'admin_flash';

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function adminPassword(): string
{
    $password = getenv('NEX_ADMIN_PASSWORD');
    return ($password !== false && $password !== '') ? $password : 'change-me';
}

function isAuth(): bool
{
    return !empty($_SESSION[AUTH_KEY]);
}

function csrf(): string
{
    if (empty($_SESSION[CSRF_KEY])) {
        $_SESSION[CSRF_KEY] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION[CSRF_KEY];
}

function csrfOk(): bool
{
    $posted = (string)($_POST['csrf'] ?? '');
    $token = (string)($_SESSION[CSRF_KEY] ?? '');
    return $posted !== '' && $token !== '' && hash_equals($token, $posted);
}

function flash(string $type, string $message): void
{
    $_SESSION[FLASH_KEY] = ['type' => $type, 'message' => $message];
}

function popFlash(): ?array
{
    $value = $_SESSION[FLASH_KEY] ?? null;
    unset($_SESSION[FLASH_KEY]);
    return is_array($value) ? $value : null;
}

function normalizeRedirectPath(?string $raw, string $fallback): string
{
    $path = trim((string)$raw);
    if ($path === '') {
        return $fallback;
    }

    if ($path[0] !== '/') {
        return $fallback;
    }

    if (strpos($path, '//') === 0) {
        return $fallback;
    }

    return $path;
}

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function requireAuth(string $redirectBackTo): void
{
    if (isAuth()) {
        return;
    }

    $target = rawurlencode($redirectBackTo);
    redirectTo('/Admin/login.php?redirect=' . $target);
}

