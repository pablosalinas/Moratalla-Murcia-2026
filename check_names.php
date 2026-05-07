<?php
require_once 'config.php';
$pdo = getDB();
echo "--- CATEGORIAS ---\n";
$stmt = $pdo->query("SELECT id, name, slug FROM categories WHERE name LIKE '%Ana%' OR name LIKE '%Bartolo%'");
print_r($stmt->fetchAll());

echo "--- PAGINAS ---\n";
$stmt = $pdo->query("SELECT id, title, slug FROM pages WHERE title LIKE '%Ana%' OR title LIKE '%Bartolo%'");
print_r($stmt->fetchAll());
?>
