<?php
require_once 'config.php';
$pdo = getDB();
$stmt = $pdo->query('SELECT content FROM pages WHERE id = 143');
file_put_contents('scratch/dump_143.html', $stmt->fetchColumn());
echo "Dumped ID 143";
?>
