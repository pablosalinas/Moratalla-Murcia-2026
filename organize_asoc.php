<?php
require_once 'config.php';
$pdo = getDB();

echo "Reorganizando Asociaciones...\n";

// 1. Crear categorías puente
$pdo->prepare("INSERT INTO categories (parent_id, name, slug) VALUES (1, 'Asociaciones Culturales', 'asociaciones-culturales')")->execute();
$culturalesId = $pdo->lastInsertId();

$pdo->prepare("INSERT INTO categories (parent_id, name, slug) VALUES (1, 'Asociaciones Deportivas', 'asociaciones-deportivas')")->execute();
$deportivasId = $pdo->lastInsertId();

// 2. Mover las existentes a su sitio correcto
$culturales = ['Nazareno', 'Tamboristas', 'Grupos'];
foreach ($culturales as $name) {
    $pdo->prepare("UPDATE categories SET parent_id = ? WHERE name = ? AND parent_id = 1")->execute([$culturalesId, $name]);
}

$deportivas = ['Automovilismo', 'Ciclista', 'Futbol', 'Baloncesto'];
foreach ($deportivas as $name) {
    $pdo->prepare("UPDATE categories SET parent_id = ? WHERE name = ? AND parent_id = 1")->execute([$deportivasId, $name]);
}

echo "✅ Jerarquía de Asociaciones organizada.\n";
?>
