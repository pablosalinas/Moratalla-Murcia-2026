<?php
require_once 'config.php';
$pdo = getDB();

echo "=== ESTRUCTURA DE CATEGORÍAS ===\n";
$stmt = $pdo->query("SELECT id, name, parent_id, slug FROM categories ORDER BY parent_id, id");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | PARENT: {$row['parent_id']} | NOMBRE: {$row['name']} | SLUG: {$row['slug']}\n";
}
?>
