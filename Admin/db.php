<?php

declare(strict_types=1);

const DB_FILE = __DIR__ . '/../data/content.sqlite';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');
    initSchema($pdo);
    migrateJsonIfEmpty($pdo);

    return $pdo;
}

function initSchema(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS news (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            category TEXT NOT NULL,
            date TEXT NOT NULL,
            description TEXT NOT NULL,
            image TEXT NOT NULL DEFAULT "",
            longtext TEXT NOT NULL DEFAULT ""
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS mods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT NOT NULL,
            type TEXT NOT NULL CHECK(type IN ("allowed", "banned"))
        )'
    );
}

function migrateJsonIfEmpty(PDO $pdo): void
{
    $newsCount = (int)$pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();
    if ($newsCount === 0) {
        $newsPath = __DIR__ . '/../data/news.json';
        if (is_file($newsPath)) {
            $decoded = json_decode((string)file_get_contents($newsPath), true);
            if (is_array($decoded)) {
                $stmt = $pdo->prepare(
                    'INSERT INTO news (title, slug, category, date, description, image, longtext)
                     VALUES (:title, :slug, :category, :date, :description, :image, :longtext)'
                );
                foreach ($decoded as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $stmt->execute([
                        ':title' => (string)($item['title'] ?? ''),
                        ':slug' => safeSlug((string)($item['slug'] ?? $item['title'] ?? 'news'), $pdo),
                        ':category' => (string)($item['category'] ?? ''),
                        ':date' => (string)($item['date'] ?? ''),
                        ':description' => (string)($item['description'] ?? ''),
                        ':image' => (string)($item['image'] ?? ''),
                        ':longtext' => (string)($item['longtext'] ?? ''),
                    ]);
                }
            }
        }
    }

    $modsCount = (int)$pdo->query('SELECT COUNT(*) FROM mods')->fetchColumn();
    if ($modsCount === 0) {
        $modsPath = __DIR__ . '/../data/mods.json';
        if (is_file($modsPath)) {
            $decoded = json_decode((string)file_get_contents($modsPath), true);
            if (is_array($decoded)) {
                $stmt = $pdo->prepare('INSERT INTO mods (slug, type) VALUES (:slug, :type)');
                foreach (['allowed', 'banned'] as $type) {
                    $items = $decoded[$type] ?? [];
                    if (!is_array($items)) {
                        continue;
                    }
                    foreach ($items as $item) {
                        $slug = is_array($item) ? (string)($item['slug'] ?? '') : '';
                        if ($slug === '') {
                            continue;
                        }
                        $stmt->execute([
                            ':slug' => $slug,
                            ':type' => $type,
                        ]);
                    }
                }
            }
        }
    }
}

function slugifyValue(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    $value = trim($value, '-');
    return $value !== '' ? $value : 'news';
}

function safeSlug(string $raw, PDO $pdo, ?int $ignoreId = null): string
{
    $base = slugifyValue($raw);
    $candidate = $base;
    $suffix = 2;

    $query = 'SELECT COUNT(*) FROM news WHERE slug = :slug';
    if ($ignoreId !== null) {
        $query .= ' AND id != :id';
    }

    while (true) {
        $stmt = $pdo->prepare($query);
        $params = [':slug' => $candidate];
        if ($ignoreId !== null) {
            $params[':id'] = $ignoreId;
        }
        $stmt->execute($params);
        $exists = (int)$stmt->fetchColumn() > 0;
        if (!$exists) {
            return $candidate;
        }
        $candidate = $base . '-' . $suffix;
        $suffix++;
    }
}

