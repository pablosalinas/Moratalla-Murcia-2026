<?php
require_once 'config.php';
$pdo = getDB();

echo "Mejorando calidad de imágenes de Ana María Almagro...\n";

$sourceBase = 'E:/00 PARTICULAR/moratalla-murcia/moratalla/artesania/pintura/almagroana/images/images_gr/';
$destBase = 'uploads/artesania/ana-maria/';

$stmt = $pdo->query("SELECT id, image_path FROM page_images WHERE image_path LIKE 'uploads/artesania/ana-maria/%'");
while ($row = $stmt->fetch()) {
    $filename = basename($row['image_path']);
    // Volver a poner espacios si el script anterior los quitó para buscar el original
    $originalName = str_replace('_', ' ', $filename);
    $src = $sourceBase . $originalName;
    
    if (file_exists($src)) {
        copy($src, $row['image_path']);
        echo " - Imagen '{$filename}' actualizada a ALTA RESOLUCIÓN.\n";
    } else {
        // Probar con el nombre tal cual
        $src = $sourceBase . $filename;
        if (file_exists($src)) {
            copy($src, $row['image_path']);
            echo " - Imagen '{$filename}' actualizada a ALTA RESOLUCIÓN.\n";
        }
    }
}

echo "✅ Calidad de imagen mejorada con éxito.\n";
?>
