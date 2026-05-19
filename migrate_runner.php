<?php
/**
 * migrate_runner.php — Sistema de Migraciones Automáticas Profesional
 * 
 * Ejecuta migraciones SQL de forma robusta, manejando transacciones
 * y evitando bucles de error por comandos que auto-commitean (como TRUNCATE).
 */
require_once __DIR__ . '/config.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

$isCLI = (php_sapi_name() === 'cli');
$logFile = __DIR__ . '/migration_log.txt';

if (!$isCLI) {
    $providedSecret = isset($_GET['secret']) ? $_GET['secret'] : (isset($_POST['secret']) ? $_POST['secret'] : '');
    if (empty(MIGRATE_SECRET) || $providedSecret !== MIGRATE_SECRET) {
        http_response_code(403);
        die('Acceso denegado.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

function output($message) {
    global $isCLI, $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] $message";
    echo $formatted . "\n";
    file_put_contents($logFile, $formatted . "\n", FILE_APPEND);
    if (!$isCLI) flush();
}

$pdo = getDB();
$pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

// Asegurar tabla de control
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `_migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `status` ENUM('success', 'failed') DEFAULT 'success',
        `error_message` TEXT,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Verificar si existe la columna 'status' (para compatibilidad con versiones previas)
try {
    $pdo->query("SELECT status FROM `_migrations` LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE `_migrations` ADD COLUMN `status` ENUM('success', 'failed') DEFAULT 'success' AFTER `migration`, ADD COLUMN `error_message` TEXT AFTER `status` ");
}

$executed = $pdo->query("SELECT migration FROM `_migrations` WHERE status = 'success'")->fetchAll(PDO::FETCH_COLUMN);
$failed = $pdo->query("SELECT migration FROM `_migrations` WHERE status = 'failed'")->fetchAll(PDO::FETCH_COLUMN);

$migrationsDir = __DIR__ . '/migrations';
$files = is_dir($migrationsDir) ? glob($migrationsDir . '/*.sql') : [];
sort($files);

$pending = 0;
$errors = 0;

output("=== INICIANDO RUNNER DE MIGRACIONES ===");

foreach ($files as $file) {
    $migrationName = basename($file);
    
    if (in_array($migrationName, $executed)) {
        continue;
    }

    if (in_array($migrationName, $failed)) {
        output("[!] Omitiendo $migrationName porque falló anteriormente. Corrígelo o bórralo de _migrations.");
        continue;
    }

    $pending++;
    output("[>>] Ejecutando: $migrationName ...");
    
    $sql = file_get_contents($file);
    
    // Eliminar comentarios
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $sql = preg_replace('/^\s*\/\*.*\*\/;?$/m', '', $sql);

    // Separar por punto y coma al final de línea
    $statements = preg_split('/;\s*[\r\n]+/', $sql);
    
    try {
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            
            // Si el comando es un TRUNCATE o DROP, avisar de que el rollback no funcionará
            if (preg_match('/^\s*(TRUNCATE|DROP|CREATE|ALTER|RENAME)/i', $statement)) {
                // MySQL commiteará automáticamente aquí
            }
            
            $pdo->exec($statement);
        }
        
        $stmt = $pdo->prepare("REPLACE INTO `_migrations` (migration, status, error_message) VALUES (?, 'success', NULL)");
        $stmt->execute([$migrationName]);
        
        if ($pdo->inTransaction()) $pdo->commit();
        output("[✓] $migrationName — OK.");
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        $errors++;
        $errorMsg = $e->getMessage();
        output("[✗] ERROR en $migrationName: $errorMsg");
        
        // Registrar el fallo para evitar bucles infinitos
        $stmt = $pdo->prepare("REPLACE INTO `_migrations` (migration, status, error_message) VALUES (?, 'failed', ?)");
        $stmt->execute([$migrationName, $errorMsg]);
    }
}

output(str_repeat("-", 50));
if ($pending === 0) {
    output("Sin cambios pendientes.");
} else {
    output("Finalizado. Éxitos: " . ($pending - $errors) . " | Errores: $errors");
}

if ($errors > 0) {
    if (!$isCLI) http_response_code(500);
    exit(1);
}

