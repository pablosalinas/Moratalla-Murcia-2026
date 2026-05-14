<?php
// config.php - Configuración multi-entorno robusta

/**
 * Carga variables desde el archivo .env
 */
function loadEnv() {
    $env_file = __DIR__ . '/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

// Cargar el entorno al iniciar
loadEnv();

/**
 * Obtiene una variable de entorno con fallback
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}

// Definir constantes globales basadas en el entorno
// Autodetectar entorno basado en el host si no hay variable de entorno
$currentHost = $_SERVER['HTTP_HOST'] ?? '';
$isProdHost = (strpos($currentHost, 'moratalla-murcia.com') !== false);
$environment = env('APP_ENV', $isProdHost ? 'production' : 'local');


if ($environment === 'production') {
    define('DB_HOST', env('DB_HOST'));
    define('DB_PORT', env('DB_PORT', '3306'));
    define('DB_USER', env('DB_USER'));
    define('DB_PASS', env('DB_PASS'));
    define('DB_NAME', env('DB_NAME'));
    define('BASE_URL', env('BASE_URL', ''));
} else {
    // Valores por defecto para XAMPP local
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'moratalla_web_2026');
    define('BASE_URL', '/moratalla-murcia_2026');
}

define('MIGRATE_SECRET', env('MIGRATE_SECRET', 'local-dev'));

/**
 * Retorna la conexión PDO única
 */
function getDB() {
    static $pdo;
    if ($pdo === null) {
        $host = DB_HOST;
        $port = DB_PORT;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbname = DB_NAME;

        if (!$host && env('APP_ENV') === 'production') {
            die("Error: El archivo .env en el servidor está vacío o no contiene DB_HOST.");
        }

        try {
            // Primero conectamos sin base de datos para asegurar que podemos crearla si falta (útil en local)
            $dsn_no_db = "mysql:host=$host;port=$port;charset=utf8mb4";
            $temp_pdo = new PDO($dsn_no_db, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Ahora conectamos a la base de datos real
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
            ]);
        } catch (PDOException $e) {
            // Si falla la conexión directa (común en hostings compartidos que no permiten CREATE DATABASE), intentamos conexión directa
            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
                ]);
            } catch (PDOException $e2) {
                $display_host = (env('APP_ENV') === 'production') ? substr($host, 0, 5) . "..." : $host;
                die("Error de conexión (Host: $display_host): " . $e2->getMessage());
            }
        }
    }
    return $pdo;
}
