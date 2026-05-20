<?php
/**
 * Helper para procesar, redimensionar y añadir marca de agua a las imágenes.
 */

function processUploadedImage($sourcePath, $targetPath, $addWatermark = true, $maxWidth = 1200, $quality = 85) {
    // Verificar si el archivo existe
    if (!file_exists($sourcePath)) return false;
    
    $info = @getimagesize($sourcePath);
    if (!$info) {
        // Si no es imagen (o no se puede leer), hacer move normal si es upload
        return @move_uploaded_file($sourcePath, $targetPath);
    }

    $width = $info[0];
    $height = $info[1];
    $mime = $info['mime'];

    // Cargar imagen según tipo
    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return @move_uploaded_file($sourcePath, $targetPath);
    }

    if (!$image) return @move_uploaded_file($sourcePath, $targetPath);

    // Arreglar orientación EXIF (solo para JPEG)
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($sourcePath);
        if ($exif && isset($exif['Orientation'])) {
            $ort = $exif['Orientation'];
            if ($ort == 3 || $ort == 13) $image = imagerotate($image, 180, 0);
            if ($ort == 6 || $ort == 14) { $image = imagerotate($image, -90, 0); list($width, $height) = array($height, $width); }
            if ($ort == 8 || $ort == 15) { $image = imagerotate($image, 90, 0); list($width, $height) = array($height, $width); }
        }
    }

    // Calcular nuevas dimensiones
    $newWidth = $width;
    $newHeight = $height;
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int)(($height / $width) * $maxWidth);
    }

    // Crear nueva imagen optimizada
    $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Configurar transparencia para PNG y WebP
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($optimizedImage, false);
        imagesavealpha($optimizedImage, true);
        $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
        imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
    } else {
        // Rellenar fondo blanco para JPGs
        $white = imagecolorallocate($optimizedImage, 255, 255, 255);
        imagefill($optimizedImage, 0, 0, $white);
    }

    // Resample
    imagecopyresampled($optimizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($image);

    // Añadir marca de agua
    if ($addWatermark) {
        $watermarkText = "moratalla-murcia.com";
        $fontFile = __DIR__ . '/fonts/Roboto-Regular.ttf';
        
        // Tamaño de fuente dinámico: ~1.5% del ancho de la imagen (mínimo 10px) - Reducido a la mitad a petición
        $fontSize = max(10, intval($newWidth * 0.015));
        
        // Colores
        $white = imagecolorallocate($optimizedImage, 255, 255, 255);
        $shadow = imagecolorallocatealpha($optimizedImage, 0, 0, 0, 40); // Negro semitransparente para sombra
        
        if (file_exists($fontFile)) {
            // Calcular caja de texto
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $watermarkText);
            $textWidth = abs($bbox[2] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[3]);
            
            // Margen (1.5% del ancho, mínimo 10px) - También reducido para que no quede flotando muy arriba
            $margin = max(10, intval($newWidth * 0.015));
            
            // Posición (esquina inferior derecha)
            $x = $newWidth - $textWidth - $margin;
            $y = $newHeight - $margin; // $y is base line for TTF

            // Dibujar sombra / borde sutil (offset 1px y 2px para asegurar visibilidad en fondos blancos)
            imagettftext($optimizedImage, $fontSize, 0, $x + 2, $y + 2, $shadow, $fontFile, $watermarkText);
            imagettftext($optimizedImage, $fontSize, 0, $x - 1, $y - 1, $shadow, $fontFile, $watermarkText);
            imagettftext($optimizedImage, $fontSize, 0, $x + 1, $y - 1, $shadow, $fontFile, $watermarkText);
            imagettftext($optimizedImage, $fontSize, 0, $x - 1, $y + 1, $shadow, $fontFile, $watermarkText);
            
            // Dibujar texto principal (blanco puro)
            imagettftext($optimizedImage, $fontSize, 0, $x, $y, $white, $fontFile, $watermarkText);
        }
    }

    // Guardar imagen según extensión
    $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    $success = false;
    
    if ($ext === 'png') {
        $pngQuality = 9 - round(($quality / 100) * 9);
        $success = imagepng($optimizedImage, $targetPath, $pngQuality);
    } elseif ($ext === 'webp') {
        $success = imagewebp($optimizedImage, $targetPath, $quality);
    } else {
        $success = imagejpeg($optimizedImage, $targetPath, $quality);
    }

    imagedestroy($optimizedImage);

    // Borrar el temporal
    if ($success && is_uploaded_file($sourcePath)) {
        @unlink($sourcePath);
    }

    return $success;
}
?>
