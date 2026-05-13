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
$pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

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
    
    $sql = file_get_contents($file);
    
    try {
        $pdo->beginTransaction();
        
        // Limpiar el SQL de comentarios y espacios innecesarios al inicio/final
        // Pero preservar los saltos de línea dentro de las comillas
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $sql = preg_replace('/^\s*\/\*.*\*\/;?$/m', '', $sql);
        
        // Dividir por punto y coma seguido de un salto de línea (estándar de nuestro exportador)
        // Esto es mucho más seguro que el split por líneas simple
        $statements = preg_split('/;\s*[\r\n]+/', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
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
        // No detenemos el proceso completo, intentamos con la siguiente
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
