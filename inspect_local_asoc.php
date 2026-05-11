<?php
require_once 'config.php';
$pdo = getDB();

echo "=== DETALLE CATEGORÍA ASOCIACIONES (Local) ===\n";
$stmt = $pdo->query("SELECT * FROM categories WHERE name LIKE '%Asociaciones%'");
while ($row = $stmt->fetch()) {
    $parent = is_null($row['parent_id']) ? 'NULL' : $row['parent_id'];
    echo "ID: {$row['id']} | NOMBRE: '{$row['name']}' | PARENT: $parent | SLUG: {$row['slug']}\n";
}
?>
