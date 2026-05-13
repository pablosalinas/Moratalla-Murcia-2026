<?php
/**
 * migrate_runner.php — Sistema de Migraciones Automáticas
 * 
 * Ejecuta migraciones SQL pendientes de forma segura.
 * Se puede invocar:
 *   - Por HTTP con token secreto: migrate_runner.php?secret=TU_TOKEN
 *   - Por CLI: php migrate_runner.php
 * 
 * Las migraciones ejecutadas se registran en la tabla `_migrations`
 * para que nunca se ejecuten dos veces.
 */
require_once __DIR__ . '/config.php';

// Aumentar límites para scripts grandes
set_time_limit(0);
ini_set('memory_limit', '512M');

// === Seguridad ===
$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI) {
    // Acceso por HTTP: requiere token secreto
    $providedSecret = $_GET['secret'] ?? $_POST['secret'] ?? '';
    if (empty(MIGRATE_SECRET) || $providedSecret !== MIGRATE_SECRET) {
        http_response_code(403);
        die('Acceso denegado. Token de migración inválido.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// === Conexión BD ===
$pdo = getDB();

// === Crear tabla de control de migraciones ===
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `_migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// === Obtener migraciones ya ejecutadas ===
$executedStmt = $pdo->query("SELECT migration FROM `_migrations`");
$executed = $executedStmt->fetchAll(PDO::FETCH_COLUMN);

// === Leer archivos de migración ===
$migrationsDir = __DIR__ . '/migrations';
if (!is_dir($migrationsDir)) {
    output("No se encontró el directorio de migraciones: $migrationsDir");
    exit(1);
}

$files = glob($migrationsDir . '/*.sql');
sort($files); // Orden alfabético = orden de ejecución

$pending = 0;
$errors = 0;

output("=== Ejecutando Migraciones ===");
output("Fecha: " . date('Y-m-d H:i:s'));
output(str_repeat("-", 50));

foreach ($files as $file) {
    $migrationName = basename($file);
    
    if (in_array($migrationName, $executed)) {
        output("[OK] $migrationName — ya ejecutada, saltando.");
        continue;
    }
    
    $pending++;
    output("[>>] Ejecutando: $migrationName ...");
    
    $sql = file_get_contents($file);
    
    try {
        $pdo->beginTransaction();
        
        // Dividir por punto y coma, pero ignorando los que están dentro de comillas (simplificado)
        // Para mayor seguridad en archivos gigantes, leemos línea a línea
        $lines = file($file);
        $query = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) continue;
            
            $query .= $line . " ";
            if (substr($line, -1) === ';') {
                $pdo->exec($query);
                $query = '';
            }
        }
        
        // Registrar como ejecutada
        $stmt = $pdo->prepare("INSERT INTO `_migrations` (migration) VALUES (?)");
        $stmt->execute([$migrationName]);
        
        $pdo->commit();
        output("[✓] $migrationName — ejecutada correctamente.");
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors++;
        output("[✗] ERROR en $migrationName: " . $e->getMessage());
    }
}

output(str_repeat("-", 50));
if ($pending === 0) {
    output("No hay migraciones pendientes. Todo está actualizado.");
} else {
    output("Migraciones ejecutadas: " . ($pending - $errors) . " | Errores: $errors");
}

if ($errors > 0) {
    if (!$isCLI) http_response_code(500);
    exit(1);
}

// === Función de salida ===
function output($message) {
    global $isCLI;
    echo $message . ($isCLI ? "\n" : "\n");
    if (!$isCLI) flush();
}
