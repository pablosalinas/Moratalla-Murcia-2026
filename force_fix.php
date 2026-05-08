<?php
require_once 'config.php';
$pdo = getDB();

try {
    echo "Intentando añadir la columna 'sort_order' a la tabla 'pages'...\n";
    $pdo->exec("ALTER TABLE `pages` ADD COLUMN `sort_order` INT DEFAULT 0 AFTER `original_file` ");
    echo "✅ COLUMNA AÑADIDA CON ÉXITO.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️ La columna ya existía.\n";
    } else {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\nLimpiando caché de migraciones...\n";
$pdo->prepare("INSERT IGNORE INTO migrations (migration) VALUES ('005_fix_pages_sort_order.sql')")->execute();

echo "Listo. El error fatal debería haber desaparecido.\n";
?>
