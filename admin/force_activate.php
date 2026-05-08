<?php
// admin/force_activate.php
require_once '../config.php';
$pdo = getDB();

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Forzando activación de Admin en Producción...</h2>";

try {
    // 1. Asegurar columnas de seguridad
    $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `failed_attempts` INT DEFAULT 0 AFTER `role` ");
    $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL DEFAULT NULL AFTER `failed_attempts` ");
    echo "<p>✅ Estructura de tabla verificada.</p>";

    // 2. Limpiar y recrear usuario
    $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$username]);
    $pdo->prepare("INSERT INTO users (username, password, role, failed_attempts, locked_until) VALUES (?, ?, 'admin', 0, NULL)")
        ->execute([$username, $hash]);
    echo "<p>✅ Usuario '$username' reactivado con éxito.</p>";
    
    echo "<h3>¡Ya puedes entrar! <a href='login.php'>Ir al Login</a></h3>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
}
?>
