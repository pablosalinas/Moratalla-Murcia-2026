<?php
// Script para exportar TODO el contenido rescatado a una migración SQL
require_once 'config.php';
$pdo = getDB();

$tables = ['categories', 'pages', 'page_images', 'settings', 'users'];

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
$maxId = 0;
foreach ($files as $file) {
    if (preg_match('/^(\d+)_/', basename($file), $matches)) {
        $id = (int)$matches[1];
        if ($id > $maxId) {
            $maxId = $id;
        }
    }
}
$migrationBaseId = $maxId + 1;
$chunkSize = 50;

foreach ($tables as $table) {
    echo "Exportando tabla $table...\n";
    $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY id ASC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) {
        echo "Saltando $table (vacía)\n";
        continue;
    }

    $chunks = array_chunk($rows, $chunkSize);
    
    foreach ($chunks as $index => $chunk) {
        $currentMigrationId = sprintf("%03d", $migrationBaseId++);
        $filename = "migrations/{$currentMigrationId}_sync_{$table}_part" . ($index + 1) . ".sql";
        
        $sql = "-- Migración {$currentMigrationId}: Sincronización de tabla {$table} (Parte " . ($index + 1) . ")\n";
        $sql .= "-- Generada: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        
        // Solo hacer TRUNCATE en la primera parte de cada tabla
        if ($index === 0) {
            $sql .= "TRUNCATE TABLE `{$table}`;\n\n";
        }

        foreach ($chunk as $row) {
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
}
?>
