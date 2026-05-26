<?php
require __DIR__ . '/../config.php';
$pdo = getDB();

echo "Page 410:\n";
$stmt = $pdo->query("SELECT id, title, category_id FROM pages WHERE id = 410");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\nCategories named '%Tamborista%':\n";
$stmt2 = $pdo->query("SELECT id, name, parent_id, is_visible FROM categories WHERE name LIKE '%Tamborista%'");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

echo "\nPages in category 24 (if it's the tamboristas one):\n";
$stmt3 = $pdo->query("SELECT id, title, category_id FROM pages WHERE category_id = (SELECT id FROM categories WHERE name LIKE '%Tamborista%' LIMIT 1)");
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));

