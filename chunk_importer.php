<?php
require_once 'config.php';
$pdo = getDB();

$file = 'migrations/004_legacy_data_restore.sql';

if (!file_exists($file)) {
    die("Error: No se encuentra el archivo de datos $file\n");
}

echo "Iniciando importación fraccionada (esto puede tardar un poco)...\n";

$handle = fopen($file, "r");
$count = 0;
$errors = 0;

if ($handle) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if (empty($line) || strpos($line, '--') === 0) continue;
        
        try {
            $pdo->exec($line);
            $count++;
            if ($count % 100 === 0) echo " - Procesadas $count líneas...\n";
        } catch (PDOException $e) {
            $errors++;
            // echo "Error en línea $count: " . $e->getMessage() . "\n";
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    fclose($handle);
}

echo "\n--- FINALIZADO ---\n";
echo "✅ Líneas procesadas: $count\n";
echo "❌ Errores saltados: $errors\n";

// Marcar como completada en la tabla de migraciones
$pdo->prepare("INSERT IGNORE INTO migrations (migration) VALUES ('004_legacy_data_restore.sql')")->execute();
?>
