<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT id, title FROM pages WHERE title LIKE '%??%' LIMIT 50");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    echo $row['id'] . " | TITLE | " . $row['title'] . "\n";
}

$stmt = $pdo->query("SELECT id, name FROM categories WHERE name LIKE '%??%' LIMIT 50");
$rows = $stmt->fetchAll();
foreach ($rows as $row) {
    echo $row['id'] . " | CAT | " . $row['name'] . "\n";
}
