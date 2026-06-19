<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();
$users = $pdo->query("SELECT id, username, failed_attempts, locked_until FROM users")->fetchAll();
print_r($users);
