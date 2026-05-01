<?php

declare(strict_types=1);

require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$entity = (string)($_GET['entity'] ?? '');
$pdo = db();

if ($entity === 'news') {
    $rows = $pdo->query('SELECT title, slug, category, date, description, image, longtext FROM news ORDER BY id DESC')->fetchAll();
    echo json_encode($rows, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($entity === 'mods') {
    $rows = $pdo->query('SELECT slug, type FROM mods ORDER BY id ASC')->fetchAll();
    $out = ['allowed' => [], 'banned' => []];

    foreach ($rows as $row) {
        $type = (string)($row['type'] ?? '');
        if (!isset($out[$type])) {
            continue;
        }
        $out[$type][] = ['slug' => (string)($row['slug'] ?? '')];
    }

    echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid entity']);

