<?php
// admin/force_activate.php
require_once '../config.php';
$pdo = getDB();

$username = 'Pablo';
$password = 'p1s2m3';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Forzando activación de Admin en Producción (Versión Compatible)...</h2>";

try {
    // 1. Verificar y añadir columnas una a una (sin IF NOT EXISTS)
    $columns = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('failed_attempts', $columns)) {
        $pdo->exec("ALTER TABLE `users` ADD `failed_attempts` INT DEFAULT 0 AFTER `role` ");
        echo "<p>✅ Columna 'failed_attempts' añadida.</p>";
    }
    
    if (!in_array('locked_until', $columns)) {
        $pdo->exec("ALTER TABLE `users` ADD `locked_until` DATETIME NULL DEFAULT NULL AFTER `failed_attempts` ");
        echo "<p>✅ Columna 'locked_until' añadida.</p>";
    }

    // 2. Limpiar y recrear usuario Pablo
    $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$username]);
    $pdo->prepare("INSERT INTO users (username, password, role, failed_attempts, locked_until) VALUES (?, ?, 'admin', 0, NULL)")
        ->execute([$username, $hash]);
    echo "<p>✅ Usuario '$username' reactivado con éxito.</p>";
    
    echo "<h3>¡Ya puedes entrar! <a href='login.php'>Ir al Login</a></h3>";
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . $e->getMessage() . "</p>";
}
?>
