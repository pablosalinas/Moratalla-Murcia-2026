<?php
// scratch/rollback_quotes.php
require_once __DIR__ . '/../config.php';
$pdo = getDB();

try {
    // Vaciar la tabla quotes
    $pdo->exec("TRUNCATE TABLE quotes");
    echo "Tabla quotes vaciada.\n";
    
    // Eliminar el registro de la migración 072
    $pdo->exec("DELETE FROM _migrations WHERE migration = '072_sync_quotes_data.sql'");
    echo "Registro de migración 072 eliminado de _migrations.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
