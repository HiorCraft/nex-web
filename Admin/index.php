<?php

declare(strict_types=1);

require __DIR__ . '/auth.php';

requireAuth('/Admin/');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'logout') {
        if (!csrfOk()) {
            flash('err', 'CSRF-Token ungueltig.');
            redirectTo('/Admin/');
        }

        $_SESSION = [];
        session_destroy();
        session_start();
        flash('ok', 'Abgemeldet.');
        redirectTo('/Admin/login.php');
    }
}

$token = csrf();
$flash = popFlash();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Hexoria</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<header id="header"></header>
<script src="/header.js"></script>

<main class="container admin-wrap">
    <h1 class="section__title">Admin</h1>
    <p class="small muted">Waehle den Bereich, den du bearbeiten willst.</p>

    <?php if ($flash): ?>
        <div class="<?= ($flash['type'] ?? '') === 'ok' ? 'flash-ok' : 'flash-err' ?>"><?= h((string)($flash['message'] ?? '')) ?></div>
    <?php endif; ?>

    <section class="admin-card">
        <h2>Bereiche</h2>
        <div class="admin-actions">
            <a href="/News/admin.php">News verwalten</a>
            <a href="/Mods/admin.php">Mods verwalten</a>
            <a href="/News/">News-Seite</a>
            <a href="/Mods/">Mods-Seite</a>
        </div>
    </section>

    <section class="admin-card">
        <h2>Session</h2>
        <form method="post" class="admin-actions">
            <input type="hidden" name="action" value="logout">
            <input type="hidden" name="csrf" value="<?= h($token) ?>">
            <button class="admin-btn admin-btn--danger" type="submit">Logout</button>
        </form>
    </section>
</main>

<footer id="footer"></footer>
<script src="/footer.js"></script>
</body>
</html>

