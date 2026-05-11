<?php
require_once 'config.php';
$pdo = getDB();

echo "=== DIAGNÓSTICO DE CATEGORÍAS PRINCIPALES (Ionos) ===\n";
$stmt = $pdo->query("SELECT id, name, parent_id, slug FROM categories WHERE parent_id IS NULL");
echo "Categorías de Nivel Superior:\n";
while ($row = $stmt->fetch()) {
    echo " - [ID: {$row['id']}] {$row['name']} (Slug: {$row['slug']})\n";
}

echo "\nBusqueda específica por nombre 'Asociaciones':\n";
$stmt = $pdo->query("SELECT id, name, parent_id FROM categories WHERE name LIKE '%Asociaciones%'");
while ($row = $stmt->fetch()) {
    $parent = $row['parent_id'] ?: 'NULL';
    echo " - [ID: {$row['id']}] {$row['name']} (Parent ID: $parent)\n";
}
?>
