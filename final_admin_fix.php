<?php
require_once 'config.php';
$pdo = getDB();

try {
    echo "1. Reparando tabla 'users' (añadiendo columnas de seguridad)...\n";
    $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `failed_attempts` INT DEFAULT 0 AFTER `role` ");
    $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL DEFAULT NULL AFTER `failed_attempts` ");
    echo "✅ Tabla 'users' reparada.\n";
} catch (Exception $e) {
    echo "ℹ️ Nota: " . $e->getMessage() . "\n";
}

echo "\n2. Reseteando contraseña de 'admin' a 'admin123'...\n";
$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user) {
    $pdo->prepare("UPDATE users SET password = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?")
        ->execute([$hash, $user['id']]);
} else {
    $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')")
        ->execute([$username, $hash]);
}

echo "✅ CONTRASEÑA RESETEADA.\n";
echo "\nYa puedes entrar en: http://localhost/moratalla-murcia_2026/admin/\n";
?>
