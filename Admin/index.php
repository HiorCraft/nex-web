<?php

declare(strict_types=1);

session_start();
require __DIR__ . '/db.php';

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
    return $_SESSION[CSRF_KEY];
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

function redirectSelf(): void
{
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

function validateNewsInput(array $input): array
{
    $title = trim((string)($input['title'] ?? ''));
    $slug = trim((string)($input['slug'] ?? ''));
    $category = trim((string)($input['category'] ?? ''));
    $date = trim((string)($input['date'] ?? ''));
    $description = trim((string)($input['description'] ?? ''));
    $image = trim((string)($input['image'] ?? ''));
    $longtext = trim((string)($input['longtext'] ?? ''));

    if ($title === '' || $category === '' || $date === '' || $description === '') {
        throw new RuntimeException('title, category, date und description sind Pflicht.');
    }

    return [
        'title' => $title,
        'slug' => $slug,
        'category' => $category,
        'date' => $date,
        'description' => $description,
        'image' => $image,
        'longtext' => $longtext,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'login') {
        $password = (string)($_POST['password'] ?? '');
        if (hash_equals(adminPassword(), $password)) {
            $_SESSION[AUTH_KEY] = true;
            flash('ok', 'Login erfolgreich.');
        } else {
            flash('err', 'Falsches Passwort.');
        }
        redirectSelf();
    }

    if ($action === 'logout' && isAuth() && csrfOk()) {
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('ok', 'Abgemeldet.');
        redirectSelf();
    }

    if (isAuth()) {
        if (!csrfOk()) {
            flash('err', 'CSRF-Token ungueltig.');
            redirectSelf();
        }

        $pdo = db();

        try {
            if ($action === 'news_create') {
                $item = validateNewsInput($_POST);
                $slug = safeSlug($item['slug'] !== '' ? $item['slug'] : $item['title'], $pdo);
                $stmt = $pdo->prepare(
                    'INSERT INTO news (title, slug, category, date, description, image, longtext)
                     VALUES (:title, :slug, :category, :date, :description, :image, :longtext)'
                );
                $stmt->execute([
                    ':title' => $item['title'],
                    ':slug' => $slug,
                    ':category' => $item['category'],
                    ':date' => $item['date'],
                    ':description' => $item['description'],
                    ':image' => $item['image'],
                    ':longtext' => $item['longtext'],
                ]);
                flash('ok', 'News erstellt.');
            } elseif ($action === 'news_update') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('News-ID fehlt.');
                }
                $item = validateNewsInput($_POST);
                $slug = safeSlug($item['slug'] !== '' ? $item['slug'] : $item['title'], $pdo, $id);
                $stmt = $pdo->prepare(
                    'UPDATE news
                     SET title = :title, slug = :slug, category = :category, date = :date,
                         description = :description, image = :image, longtext = :longtext
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':id' => $id,
                    ':title' => $item['title'],
                    ':slug' => $slug,
                    ':category' => $item['category'],
                    ':date' => $item['date'],
                    ':description' => $item['description'],
                    ':image' => $item['image'],
                    ':longtext' => $item['longtext'],
                ]);
                flash('ok', 'News aktualisiert.');
            } elseif ($action === 'news_delete') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $pdo->prepare('DELETE FROM news WHERE id = :id');
                $stmt->execute([':id' => $id]);
                flash('ok', 'News geloescht.');
            } elseif ($action === 'mod_create') {
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
}

$pdo = db();
$news = $pdo->query('SELECT * FROM news ORDER BY id DESC')->fetchAll();
$mods = $pdo->query('SELECT * FROM mods ORDER BY type ASC, id ASC')->fetchAll();
$token = csrf();
$flash = popFlash();
$defaultPassword = adminPassword() === 'change-me';
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin - Hexoria</title>
    <link rel="stylesheet" href="/styles.css">
    <style>
        .admin-wrap { max-width: 1100px; margin: 2rem auto; }
        .admin-card { background: #171717; border: 1px solid #333; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .7rem; }
        .admin-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: .7rem; }
        .admin-input, .admin-textarea, .admin-select { width: 100%; box-sizing: border-box; padding: .5rem; border-radius: 6px; border: 1px solid #444; background: #0f0f0f; color: #fff; }
        .admin-textarea { min-height: 100px; }
        .admin-btn { border: 0; border-radius: 6px; padding: .55rem .85rem; cursor: pointer; font-weight: 700; background: #00c853; color: #041908; }
        .admin-btn--danger { background: #ff5b5b; color: #290000; }
        .flash-ok { border-left: 4px solid #00c853; background: #0b2515; padding: .65rem .8rem; margin-bottom: 1rem; }
        .flash-err { border-left: 4px solid #ff5b5b; background: #2a1111; padding: .65rem .8rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<header id="header"></header>
<script src="/header.js"></script>

<main class="container admin-wrap">
    <h1 class="section__title">Admin</h1>
    <p class="small muted">News und Mods bearbeiten (SQLite, keine JSON-Datei als Datenquelle).</p>

    <?php if ($flash): ?>
        <div class="<?= ($flash['type'] ?? '') === 'ok' ? 'flash-ok' : 'flash-err' ?>"><?= h((string)($flash['message'] ?? '')) ?></div>
    <?php endif; ?>

    <?php if (!isAuth()): ?>
        <section class="admin-card">
            <h2>Login</h2>
            <?php if ($defaultPassword): ?>
                <p class="small muted">Warnung: Standardpasswort aktiv. Setze <code>NEX_ADMIN_PASSWORD</code>.</p>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <label>Passwort</label>
                <input class="admin-input" type="password" name="password" required>
                <div class="admin-actions">
                    <button class="admin-btn" type="submit">Einloggen</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <div class="admin-actions" style="justify-content: space-between;">
            <a href="/News">Zur News-Seite</a>
            <form method="post">
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="csrf" value="<?= h($token) ?>">
                <button class="admin-btn admin-btn--danger" type="submit">Logout</button>
            </form>
        </div>

        <section class="admin-card">
            <h2>News erstellen</h2>
            <form method="post">
                <input type="hidden" name="action" value="news_create">
                <input type="hidden" name="csrf" value="<?= h($token) ?>">
                <div class="admin-grid">
                    <div><label>Title *</label><input class="admin-input" name="title" required></div>
                    <div><label>Slug</label><input class="admin-input" name="slug" placeholder="optional"></div>
                    <div><label>Category *</label><input class="admin-input" name="category" required></div>
                    <div><label>Date *</label><input class="admin-input" name="date" placeholder="DD.MM.YYYY" required></div>
                    <div style="grid-column: 1 / -1;"><label>Description *</label><textarea class="admin-textarea" name="description" required></textarea></div>
                    <div style="grid-column: 1 / -1;"><label>Image</label><input class="admin-input" name="image" placeholder="/images/news/example.png"></div>
                    <div style="grid-column: 1 / -1;"><label>Longtext (Text, Leerzeilen werden uebernommen)</label><textarea class="admin-textarea" name="longtext" placeholder="Hier normalen Text schreiben. Leerzeilen bleiben erhalten."></textarea></div>
                </div>
                <div class="admin-actions"><button class="admin-btn" type="submit">Erstellen</button></div>
            </form>
        </section>

        <section class="admin-card">
            <h2>News bearbeiten</h2>
            <?php foreach ($news as $n): ?>
                <div class="admin-card" style="margin-bottom: .8rem;">
                    <form method="post">
                        <input type="hidden" name="action" value="news_update">
                        <input type="hidden" name="csrf" value="<?= h($token) ?>">
                        <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                        <div class="admin-grid">
                            <div><label>Title *</label><input class="admin-input" name="title" value="<?= h((string)$n['title']) ?>" required></div>
                            <div><label>Slug</label><input class="admin-input" name="slug" value="<?= h((string)$n['slug']) ?>"></div>
                            <div><label>Category *</label><input class="admin-input" name="category" value="<?= h((string)$n['category']) ?>" required></div>
                            <div><label>Date *</label><input class="admin-input" name="date" value="<?= h((string)$n['date']) ?>" required></div>
                            <div style="grid-column: 1 / -1;"><label>Description *</label><textarea class="admin-textarea" name="description" required><?= h((string)$n['description']) ?></textarea></div>
                            <div style="grid-column: 1 / -1;"><label>Image</label><input class="admin-input" name="image" value="<?= h((string)$n['image']) ?>"></div>
                            <div style="grid-column: 1 / -1;"><label>Longtext (Text, Leerzeilen werden uebernommen)</label><textarea class="admin-textarea" name="longtext" placeholder="Hier normalen Text schreiben. Leerzeilen bleiben erhalten."><?= h((string)$n['longtext']) ?></textarea></div>
                        </div>
                        <div class="admin-actions"><button class="admin-btn" type="submit">Speichern</button></div>
                    </form>
                    <form method="post" onsubmit="return confirm('Eintrag wirklich loeschen?');">
                        <input type="hidden" name="action" value="news_delete">
                        <input type="hidden" name="csrf" value="<?= h($token) ?>">
                        <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                        <div class="admin-actions"><button class="admin-btn admin-btn--danger" type="submit">Loeschen</button></div>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>

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
            <?php foreach ($mods as $m): ?>
                <div class="admin-card" style="margin-bottom: .8rem;">
                    <form method="post">
                        <input type="hidden" name="action" value="mod_update">
                        <input type="hidden" name="csrf" value="<?= h($token) ?>">
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <div class="admin-grid">
                            <div><label>Slug *</label><input class="admin-input" name="slug" value="<?= h((string)$m['slug']) ?>" required></div>
                            <div>
                                <label>Typ *</label>
                                <select class="admin-select" name="type">
                                    <option value="allowed" <?= $m['type'] === 'allowed' ? 'selected' : '' ?>>allowed</option>
                                    <option value="banned" <?= $m['type'] === 'banned' ? 'selected' : '' ?>>banned</option>
                                </select>
                            </div>
                        </div>
                        <div class="admin-actions"><button class="admin-btn" type="submit">Speichern</button></div>
                    </form>
                    <form method="post" onsubmit="return confirm('Mod wirklich loeschen?');">
                        <input type="hidden" name="action" value="mod_delete">
                        <input type="hidden" name="csrf" value="<?= h($token) ?>">
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <div class="admin-actions"><button class="admin-btn admin-btn--danger" type="submit">Loeschen</button></div>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<footer id="footer"></footer>
<script src="/footer.js"></script>
</body>
</html>

