<?php
require_once 'config.php';
$pdo = getDB();

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Reseteando contraseña para el usuario '$username'...\n";

// Verificamos si el usuario existe
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    // Actualizamos
    $pdo->prepare("UPDATE users SET password = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?")
        ->execute([$hash, $user['id']]);
    echo "✅ CONTRASEÑA ACTUALIZADA CON ÉXITO.\n";
} else {
    // Creamos
    $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')")
        ->execute([$username, $hash]);
    echo "✅ USUARIO CREADO CON ÉXITO.\n";
}

echo "Ya puedes intentar loguearte con: admin / admin123\n";
?>
