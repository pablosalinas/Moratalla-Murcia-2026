<?php
// config.php - Configuración multi-entorno (local / producción)

// Detectar entorno: si existe .env.production en el servidor, estamos en producción
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envVars = parse_ini_file($envFile);
    foreach ($envVars as $key => $value) {
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

$environment = getenv('APP_ENV') ?: 'local';

if ($environment === 'production') {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'moratalla-murcia-2026');
    define('BASE_URL', getenv('BASE_URL') ?: '');
    define('MIGRATE_SECRET', getenv('MIGRATE_SECRET') ?: '');
} else {
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'moratalla-murcia-2026');
    define('BASE_URL', '/moratalla-murcia_2026');
    define('MIGRATE_SECRET', 'local-dev');
}

function getDB() {
    static $pdo;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            
            // Intenta crear la DB y seleccionarla si es necesario
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");
            
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos (Puerto ".DB_PORT."): " . $e->getMessage() . "<br>Asegúrate de que MySQL está encendido.");
        }
    }
    return $pdo;
}
