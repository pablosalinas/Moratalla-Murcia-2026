<?php
require_once 'config.php';
$pdo = getDB();
$stmt = $pdo->query('SELECT content FROM pages WHERE id = 279');
$content = $stmt->fetchColumn();
file_put_contents('migrations/077_update_situacion_page.sql', 'UPDATE pages SET content = ' . $pdo->quote($content) . ' WHERE id = 279;');
echo "Exported to SQL\n";
?>
