<?php

declare(strict_types=1);

require __DIR__ . '/auth.php';

$redirect = normalizeRedirectPath((string)($_GET['redirect'] ?? $_POST['redirect'] ?? ''), '/Admin/');
$defaultPassword = adminPassword() === 'change-me';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'login') {
        $password = (string)($_POST['password'] ?? '');

        if (hash_equals(adminPassword(), $password)) {
            $_SESSION[AUTH_KEY] = true;
            flash('ok', 'Login erfolgreich.');
            redirectTo($redirect);
        }

        flash('err', 'Falsches Passwort.');
        redirectTo('/Admin/login.php?redirect=' . rawurlencode($redirect));
    }

    if ($action === 'logout' && isAuth() && csrfOk()) {
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('ok', 'Abgemeldet.');
        redirectTo('/Admin/login.php?redirect=' . rawurlencode($redirect));
    }
}

if (isAuth() && $redirect !== '/Admin/login.php') {
    redirectTo($redirect);
}

$token = csrf();
$flash = popFlash();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - Hexoria</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<header id="header"></header>
<script src="/header.js"></script>

<main class="container admin-wrap">
    <h1 class="section__title">Admin Login</h1>

    <?php if ($flash): ?>
        <div class="<?= ($flash['type'] ?? '') === 'ok' ? 'flash-ok' : 'flash-err' ?>"><?= h((string)($flash['message'] ?? '')) ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <?php if ($defaultPassword): ?>
            <p class="small muted">Warnung: Standardpasswort aktiv. Setze <code>NEX_ADMIN_PASSWORD</code>.</p>
        <?php endif; ?>

        <?php if (!isAuth()): ?>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="redirect" value="<?= h($redirect) ?>">
                <label>Passwort</label>
                <input class="admin-input" type="password" name="password" required>
                <div class="admin-actions">
                    <button class="admin-btn" type="submit">Einloggen</button>
                </div>
            </form>
        <?php else: ?>
            <p>Du bist bereits eingeloggt.</p>
            <div class="admin-actions">
                <a href="<?= h($redirect) ?>">Weiter</a>
                <a href="/Admin/">Admin Start</a>
            </div>
            <form method="post" class="admin-actions">
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="csrf" value="<?= h($token) ?>">
                <input type="hidden" name="redirect" value="<?= h($redirect) ?>">
                <button class="admin-btn" type="submit">Logout</button>
            </form>
        <?php endif; ?>
    </section>
</main>

<footer id="footer"></footer>
<script src="/footer.js"></script>
</body>
</html>

