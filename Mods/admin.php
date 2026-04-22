<?php

declare(strict_types=1);

require __DIR__ . '/../Admin/auth.php';
require __DIR__ . '/../Admin/db.php';

function redirectSelf(): void
{
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

requireAuth('/Mods/admin.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if (!csrfOk()) {
        flash('err', 'CSRF-Token ungueltig.');
        redirectSelf();
    }

    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('ok', 'Abgemeldet.');
        redirectTo('/Admin/login.php?redirect=' . rawurlencode('/Mods/admin.php'));
    }

    $pdo = db();

    try {
        if ($action === 'mod_create') {
            $slug = trim((string)($_POST['slug'] ?? ''));
            $type = (string)($_POST['type'] ?? 'allowed');
            if ($slug === '') {
                throw new RuntimeException('Mod-Slug fehlt.');
            }
            if (!in_array($type, ['allowed', 'banned'], true)) {
                throw new RuntimeException('Typ ungueltig.');
            }

            $stmt = $pdo->prepare('INSERT INTO mods (slug, type) VALUES (:slug, :type)');
            $stmt->execute([':slug' => $slug, ':type' => $type]);
            flash('ok', 'Mod hinzugefuegt.');
        } elseif ($action === 'mod_update') {
            $id = (int)($_POST['id'] ?? 0);
            $slug = trim((string)($_POST['slug'] ?? ''));
            $type = (string)($_POST['type'] ?? 'allowed');
            if ($id <= 0 || $slug === '') {
                throw new RuntimeException('Mod-Daten unvollstaendig.');
            }
            if (!in_array($type, ['allowed', 'banned'], true)) {
                throw new RuntimeException('Typ ungueltig.');
            }

            $stmt = $pdo->prepare('UPDATE mods SET slug = :slug, type = :type WHERE id = :id');
            $stmt->execute([':id' => $id, ':slug' => $slug, ':type' => $type]);
            flash('ok', 'Mod aktualisiert.');
        } elseif ($action === 'mod_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM mods WHERE id = :id');
            $stmt->execute([':id' => $id]);
            flash('ok', 'Mod geloescht.');
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }

    redirectSelf();
}

$pdo = db();
$mods = $pdo->query('SELECT * FROM mods ORDER BY type ASC, id ASC')->fetchAll();
$token = csrf();
$flash = popFlash();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mods Admin - Hexoria</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<header id="header"></header>
<script src="/header.js"></script>

<main class="container admin-wrap">
    <h1 class="section__title">Mods Admin</h1>
    <p class="small muted">Nur Mods verwalten.</p>

    <?php if ($flash): ?>
        <div class="<?= ($flash['type'] ?? '') === 'ok' ? 'flash-ok' : 'flash-err' ?>"><?= h((string)($flash['message'] ?? '')) ?></div>
    <?php endif; ?>

    <div class="admin-actions admin-toolbar">
        <div class="admin-actions admin-actions--tight">
            <a href="/Admin/">Admin Start</a>
            <a href="/News/admin.php">Zu News Admin</a>
            <a href="/Mods/">Mods-Seite</a>
        </div>
        <form method="post" class="admin-actions admin-actions--tight">
            <input type="hidden" name="action" value="logout">
            <input type="hidden" name="csrf" value="<?= h($token) ?>">
            <button class="admin-btn admin-btn--danger" type="submit">Logout</button>
        </form>
    </div>

    <section class="admin-card">
        <h2>Mod hinzufuegen</h2>
        <form method="post">
            <input type="hidden" name="action" value="mod_create">
            <input type="hidden" name="csrf" value="<?= h($token) ?>">
            <div class="admin-grid">
                <div><label>Slug *</label><input class="admin-input" name="slug" required></div>
                <div>
                    <label>Typ *</label>
                    <select class="admin-select" name="type">
                        <option value="allowed">allowed</option>
                        <option value="banned">banned</option>
                    </select>
                </div>
            </div>
            <div class="admin-actions"><button class="admin-btn" type="submit">Hinzufuegen</button></div>
        </form>
    </section>

    <section class="admin-card">
        <h2>Mods bearbeiten</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Slug</th>
                    <th>Typ</th>
                    <th>Aktionen</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($mods as $m): ?>
                    <?php $id = (int)$m['id']; ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td>
                            <input
                                class="admin-input admin-input--compact"
                                form="mod-update-<?= $id ?>"
                                name="slug"
                                value="<?= h((string)$m['slug']) ?>"
                                required
                            >
                        </td>
                        <td>
                            <select class="admin-select admin-select--compact" form="mod-update-<?= $id ?>" name="type">
                                <option value="allowed" <?= $m['type'] === 'allowed' ? 'selected' : '' ?>>allowed</option>
                                <option value="banned" <?= $m['type'] === 'banned' ? 'selected' : '' ?>>banned</option>
                            </select>
                        </td>
                        <td class="admin-table-actions">
                            <form id="mod-update-<?= $id ?>" method="post">
                                <input type="hidden" name="action" value="mod_update">
                                <input type="hidden" name="csrf" value="<?= h($token) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button class="admin-btn" type="submit">Speichern</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Mod wirklich loeschen?');">
                                <input type="hidden" name="action" value="mod_delete">
                                <input type="hidden" name="csrf" value="<?= h($token) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button class="admin-btn admin-btn--danger" type="submit">Loeschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer id="footer"></footer>
<script src="/footer.js"></script>
</body>
</html>

