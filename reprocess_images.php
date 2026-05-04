<?php
require 'config.php';
$pdo = getDB();

$images = $pdo->query("SELECT id, image_path FROM page_images WHERE page_id = 305")->fetchAll();

echo "Re-procesando imágenes de Bartolomé...\n";

foreach ($images as $img) {
    $path = $img['image_path'];
    if (file_exists($path)) {
        $src = @imagecreatefromjpeg($path);
        if (!$src) {
            $data = file_get_contents($path);
            $src = @imagecreatefromstring($data);
        }
        
        if ($src) {
            // Guardar como JPEG limpio
            imagejpeg($src, $path, 90);
            imagedestroy($src);
            echo " - Reprocesada: $path\n";
        } else {
            echo " - [ERROR] No se pudo procesar: $path\n";
        }
    }
}
echo "¡Listo! Por favor, vuelve a cargar la página de Bartolomé.";
?>
