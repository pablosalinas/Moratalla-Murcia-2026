<?php
require_once 'config.php';
$pdo = getDB();

echo "Iniciando LIMPIEZA TOTAL de usuarios...\n";

// 1. Borrar cualquier rastro de usuarios 'admin'
$pdo->exec("DELETE FROM users WHERE username = 'admin'");
echo "✅ Usuarios 'admin' antiguos eliminados.\n";

// 2. Crear el nuevo admin limpio
$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (username, password, role, failed_attempts, locked_until) VALUES (?, ?, 'admin', 0, NULL)");
$stmt->execute([$username, $hash]);

echo "✅ Nuevo usuario 'admin' creado con éxito.\n";
echo "--- DATOS DE ACCESO ---\n";
echo "Usuario: admin\n";
echo "Pass: admin123\n";
echo "-----------------------\n";

// 3. Verificación final
$check = $pdo->query("SELECT * FROM users WHERE username = 'admin'")->fetch();
echo "Verificación en BD: " . ($check ? "OK (ID: {$check['id']})" : "ERROR") . "\n";
?>
