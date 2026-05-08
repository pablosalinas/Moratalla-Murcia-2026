<?php
require_once 'config.php';
$pdo = getDB();

echo "=== BÚSQUEDA POR PALABRAS CLAVE ===\n";
$keywords = ['%esparto%', '%pintura%', '%abuelo%', '%granados%'];
foreach ($keywords as $kw) {
    echo "\nBuscando: $kw\n";
    $stmt = $pdo->prepare("SELECT id, title, slug FROM pages WHERE content LIKE ? OR title LIKE ?");
    $stmt->execute([$kw, $kw]);
    while ($row = $stmt->fetch()) {
        echo " - ID: {$row['id']} | TÍTULO: {$row['title']} | SLUG: {$row['slug']}\n";
    }
}
?>
