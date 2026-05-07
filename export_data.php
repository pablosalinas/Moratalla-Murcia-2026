<?php
// Script para exportar TODO el contenido rescatado a una migración SQL
require_once 'config.php';
$pdo = getDB();

$tables = ['categories', 'pages', 'page_images', 'settings', 'users'];
$sql = "-- Migración 004: Restauración de Datos (Estado Lunes)\n";
$sql .= "-- Generada: " . date('Y-m-d H:i:s') . "\n\n";

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
        
        $sql .= "INSERT IGNORE INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
    }
    $sql .= "\n";
}

file_put_contents('migrations/004_legacy_data_restore.sql', $sql);
echo "✅ ARCHIVO GENERADO: migrations/004_legacy_data_restore.sql\n";
?>
