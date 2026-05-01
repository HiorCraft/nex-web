<?php

declare(strict_types=1);

require __DIR__ . '/../Admin/auth.php';
require __DIR__ . '/../Admin/db.php';

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

requireAuth('/News/admin.php');

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
        redirectTo('/Admin/login.php?redirect=' . rawurlencode('/News/admin.php'));
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
        }
    } catch (Throwable $e) {
        flash('err', $e->getMessage());
    }

    redirectSelf();
}

$pdo = db();
$news = $pdo->query('SELECT * FROM news ORDER BY id DESC')->fetchAll();
$token = csrf();
$flash = popFlash();
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>News Admin - Hexoria</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
<header id="header"></header>
<script src="/header.js"></script>

<main class="container admin-wrap">
    <h1 class="section__title">News Admin</h1>
    <p class="small muted">Nur News verwalten.</p>

    <?php if ($flash): ?>
        <div class="<?= ($flash['type'] ?? '') === 'ok' ? 'flash-ok' : 'flash-err' ?>"><?= h((string)($flash['message'] ?? '')) ?></div>
    <?php endif; ?>

    <div class="admin-actions admin-toolbar">
        <div class="admin-actions admin-actions--tight">
            <a href="/Admin/">Admin Start</a>
            <a href="/Mods/admin.php">Zu Mods Admin</a>
            <a href="/News/">News-Seite</a>
        </div>
        <form method="post" class="admin-actions admin-actions--tight">
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
                <div class="admin-field--full"><label>Description *</label><textarea class="admin-textarea" name="description" required></textarea></div>
                <div class="admin-field--full"><label>Image</label><input class="admin-input" name="image" placeholder="/images/news/example.png"></div>
                <div class="admin-field--full"><label>Longtext</label><textarea class="admin-textarea" name="longtext" placeholder="Hier normalen Text schreiben."></textarea></div>
            </div>
            <div class="admin-actions"><button class="admin-btn" type="submit">Erstellen</button></div>
        </form>
    </section>

    <section class="admin-card">
        <h2>News bearbeiten</h2>
        <?php foreach ($news as $n): ?>
            <div class="admin-card admin-card--nested">
                <form method="post">
                    <input type="hidden" name="action" value="news_update">
                    <input type="hidden" name="csrf" value="<?= h($token) ?>">
                    <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                    <div class="admin-grid">
                        <div><label>Title *</label><input class="admin-input" name="title" value="<?= h((string)$n['title']) ?>" required></div>
                        <div><label>Slug</label><input class="admin-input" name="slug" value="<?= h((string)$n['slug']) ?>"></div>
                        <div><label>Category *</label><input class="admin-input" name="category" value="<?= h((string)$n['category']) ?>" required></div>
                        <div><label>Date *</label><input class="admin-input" name="date" value="<?= h((string)$n['date']) ?>" required></div>
                        <div class="admin-field--full"><label>Description *</label><textarea class="admin-textarea" name="description" required><?= h((string)$n['description']) ?></textarea></div>
                        <div class="admin-field--full"><label>Image</label><input class="admin-input" name="image" value="<?= h((string)$n['image']) ?>"></div>
                        <div class="admin-field--full"><label>Longtext</label><textarea class="admin-textarea" name="longtext"><?= h((string)$n['longtext']) ?></textarea></div>
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
</main>

<footer id="footer"></footer>
<script src="/footer.js"></script>
</body>
</html>
