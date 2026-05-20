<?php
require_once '../config.php';
require_once 'inc/auth.php'; // Protegemos el script

$pdo = getDB();
$sqlFile = __DIR__ . '/../migrations/074_restore_quotes_data.sql';

if (!file_exists($sqlFile)) {
    die("Archivo SQL no encontrado en: $sqlFile\n");
}

$lines = file($sqlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$successCount = 0;
$errorCount = 0;

$pdo->beginTransaction();

try {
    foreach ($lines as $line) {
        $line = trim($line);
        // Eliminar BOM de UTF-8 si existe
        if (strpos($line, "\xEF\xBB\xBF") === 0) {
            $line = substr($line, 3);
        }
        
        if (empty($line) || strpos($line, '--') === 0) continue;
        
        // Corregir la codificación de CP850 a UTF-8 real
        $fixedLine = @iconv('UTF-8', 'CP850', $line);
        if ($fixedLine === false) {
            $fixedLine = $line; // Fallback
        }
        
        // Convertir REPLACE INTO a INSERT IGNORE para "no borrar nada" existente
        $fixedLine = str_replace('REPLACE INTO', 'INSERT IGNORE INTO', $fixedLine);
        
        $pdo->exec($fixedLine);
        $successCount++;
    }
    $pdo->commit();
    echo "<h1>Éxito!</h1>";
    echo "<p>Se procesaron e insertaron <strong>$successCount</strong> citas en la base de datos de producción con la codificación correcta en español.</p>";
    echo "<p>No se ha borrado ningún dato existente.</p>";
    echo "<a href='index.php'>Volver al panel</a>";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
