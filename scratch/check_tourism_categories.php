<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();

$stmt = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY id ASC");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "CATEGORÍAS DE LA BASE DE DATOS:\n";
foreach ($all as $c) {
    echo "ID: {$c['id']} | Nombre: '{$c['name']}' | Padre: " . ($c['parent_id'] !== null ? $c['parent_id'] : 'NULL') . "\n";
}
