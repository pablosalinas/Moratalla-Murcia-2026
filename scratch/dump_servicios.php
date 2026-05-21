<?php
require_once 'config.php';
$pdo = getDB();

$stmt = $pdo->prepare("SELECT title, content FROM pages WHERE title LIKE '%servicio%' OR title LIKE '%telefono%' OR title LIKE '%teléfono%' LIMIT 1");
$stmt->execute();
$page = $stmt->fetch(PDO::FETCH_ASSOC);

if ($page) {
    file_put_contents('scratch/servicios.html', $page['content']);
    echo "Dumped " . $page['title'];
} else {
    echo "Not found";
}
?>
