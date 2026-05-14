<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT title FROM pages WHERE title LIKE '%Bartolom%' LIMIT 1");
$row = $stmt->fetch();
if ($row) {
    echo "Title: " . $row['title'] . "\n";
    echo "Hex: " . bin2hex($row['title']) . "\n";
} else {
    echo "Not found\n";
}
