<?php
require_once 'config.php';
$pdo = getDB();
$stmt = $pdo->query("SELECT title, content FROM pages WHERE slug = 'ana-maria-almagro' OR title LIKE '%Ana%'");
$pages = $stmt->fetchAll();
foreach ($pages as $p) {
    echo "TITULO: " . $p['title'] . "\n";
    echo "CONTENIDO (primeros 500 chars):\n" . substr(strip_tags($p['content']), 0, 500) . "\n";
    echo "-------------------\n";
}
?>
