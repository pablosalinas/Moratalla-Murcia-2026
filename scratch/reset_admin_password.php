<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->execute([$hash, 'admin']);
echo "Password reset to 'admin123' for user 'admin'.\n";
