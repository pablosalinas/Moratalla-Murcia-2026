<?php
// Script para exportar TODO el contenido rescatado a una migración SQL
require_once 'config.php';
$pdo = getDB();

$tables = ['categories', 'pages', 'page_images', 'settings', 'users'];
$migrationBaseId = 27;

foreach ($tables as $index => $table) {
    $currentMigrationId = sprintf("%03d", $migrationBaseId + $index);
    $filename = "migrations/{$currentMigrationId}_sync_{$table}.sql";
    
    $sql = "-- Migración {$currentMigrationId}: Sincronización de tabla {$table}\n";
    $sql .= "-- Generada: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $sql .= "TRUNCATE TABLE `{$table}`;\n\n";

    echo "Exportando tabla $table...\n";
    $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "Saltando $table (vacía)\n";
        continue;
    }

    foreach ($rows as $row) {
        $cols = array_keys($row);
        $vals = array_map(function($v) use ($pdo) {
            return ($v === null) ? 'NULL' : $pdo->quote($v);
        }, array_values($row));
        
        $sql .= "REPLACE INTO `$table` (`" . implode("`, `", $cols) . "`) VALUES (" . implode(", ", $vals) . ");\n";
    }
    
    $sql .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
    file_put_contents($filename, $sql);
    echo "✅ ARCHIVO GENERADO: $filename\n";
}
?>
