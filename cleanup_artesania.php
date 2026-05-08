<?php
require_once 'config.php';
$pdo = getDB();

echo "Limpiando restos de importación automática...\n";

// Borrar slugs genéricos o incorrectos que se colaron
$slugs = ['almagroana', 'vilaplana', 'bartolome', 'vilaplana-7885'];
foreach ($slugs as $slug) {
    $pdo->prepare("DELETE FROM categories WHERE slug = ?")->execute([$slug]);
    $pdo->prepare("DELETE FROM pages WHERE slug = ?")->execute([$slug]);
    echo " - Rastro de '$slug' eliminado.\n";
}

echo "✅ Limpieza completada.\n";
?>
