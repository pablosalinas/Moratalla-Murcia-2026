<?php
// config.php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'moratalla-murcia-2026');
define('BASE_URL', '/moratalla-murcia_2026'); // Dependiendo de tu instalación localhost

function getDB() {
    static $pdo;
    if ($pdo === null) {
        // Enlazar al puerto 3307 configurado mediante XAMPP patch
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
            die("Error de conexión a la base de datos (Puerto ".DB_PORT."): " . $e->getMessage() . "<br>Asegúrate de que MySQL en XAMPP está encendido.");
        }
    }
    return $pdo;
}
