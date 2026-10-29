<?php
$currentHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$isProduction = (strpos($currentHost, 'moratalla-murcia.com') !== false) || (stripos(PHP_OS, 'WIN') !== 0);

if ($isProduction) {
    $host = 'db5020045022.hosting-data.io';
    $db   = 'dbs15457960';
    $user = 'dbu4210147';
    $pass = 'Tortas01.56196798-Salinas_2026';
} else {
    $host = '127.0.0.1';
    $db   = 'biblioteca';
    $user = 'root'; // Usuario por defecto de XAMPP
    $pass = '';     // Sin contraseña por defecto en XAMPP
}
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Si falla la conexión, mostramos el error
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

if (!function_exists('ensureUsuariosActivoColumn')) {
    function ensureUsuariosActivoColumn($pdo) {
        if (!empty($_SESSION['schema_activo_checked'])) {
            return;
        }
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `usuarios` LIKE 'activo'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE `usuarios` ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1");
            }
            if (function_exists('session_status') && session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['schema_activo_checked'] = true;
            }
        } catch (\Exception $e) {
            error_log("Error verificando columna activo en usuarios: " . $e->getMessage());
        }
    }
}
ensureUsuariosActivoColumn($pdo);
?>
