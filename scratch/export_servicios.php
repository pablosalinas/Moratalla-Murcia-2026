<?php
require_once 'config.php';
$pdo = getDB();
$stmt = $pdo->query('SELECT content FROM pages WHERE id = 143');
$content = $stmt->fetchColumn();
file_put_contents('migrations/079_update_servicios_page.sql', 'UPDATE pages SET content = ' . $pdo->quote($content) . ' WHERE title LIKE "%Contactos de interes%";');
echo "Exported SQL";
?>
