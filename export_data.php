<?php
// Script para exportar TODO el contenido rescatado a una migración SQL
require_once 'config.php';
$pdo = getDB();

$tables = ['categories', 'pages', 'page_images', 'settings', 'users'];
$sql = "-- Migración 004: Restauración de Datos (Estado Lunes)\n";
$sql .= "-- Generada: " . date('Y-m-d H:i:s') . "\n\n";

// Añadir limpieza de tablas para evitar duplicados
$sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$sql .= "TRUNCATE TABLE `page_images`;\n";
$sql .= "TRUNCATE TABLE `pages`;\n";
$sql .= "TRUNCATE TABLE `categories`;\n\n";

foreach ($tables as $table) {
    echo "Exportando tabla $table...\n";
    $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) continue;
    
    $sql .= "-- Datos para la tabla `$table`\n";
    foreach ($rows as $row) {
        $cols = array_keys($row);
        $vals = array_map(function($v) use ($pdo) {
            return ($v === null) ? 'NULL' : $pdo->quote($v);
        }, array_values($row));
        
        $sql .= "REPLACE INTO `$table` (`" . implode("`, `", array_keys($row)) . "`) VALUES (" . implode(", ", $vals) . ");\n";
    }
    $sql .= "\n";
}

$sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents('migrations/013_menu_cleanup_sync.sql', $sql);
echo "✅ ARCHIVO GENERADO: migrations/013_menu_cleanup_sync.sql\n";
?>
