<?php
require_once 'config.php';
$pdo = getDB();

echo "=== INSPECCIÓN DE CONTENIDO (Artesanía) ===\n";

$terms = ['%Bartolome%', '%Ana%', '%Almagro%'];
foreach ($terms as $term) {
    $stmt = $pdo->prepare("SELECT id, title, slug, content FROM pages WHERE title LIKE ? OR slug LIKE ?");
    $stmt->execute([$term, str_replace('%', '', $term)]);
    $results = $stmt->fetchAll();
    
    foreach ($results as $row) {
        echo "\nID: {$row['id']}\n";
        echo "TÍTULO: {$row['title']}\n";
        echo "SLUG: {$row['slug']}\n";
        echo "CONTENIDO (500 chars): " . substr(strip_tags($row['content']), 0, 500) . "...\n";
        echo "------------------------------------------\n";
    }
}

echo "\n=== IMÁGENES ASOCIADAS ===\n";
$stmt = $pdo->query("SELECT p.title, pi.image_path, pi.caption FROM page_images pi JOIN pages p ON pi.page_id = p.id WHERE p.title LIKE '%Ana%' OR p.title LIKE '%Bartolome%'");
while ($img = $stmt->fetch()) {
    echo "PÁGINA: {$img['title']} | IMAGEN: {$img['image_path']} | PIE: {$img['caption']}\n";
}
?>
