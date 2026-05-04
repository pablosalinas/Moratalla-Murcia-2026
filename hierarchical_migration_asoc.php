<?php
require 'config.php';
$pdo = getDB();

$asociaciones = [
    ['id' => 2, 'dir' => 'Automovilismo', 'title' => 'Automóvil Club Moratalla'],
    ['id' => 7, 'dir' => 'Ciclista', 'title' => 'Club Ciclista Moratalla'],
    ['id' => 11, 'dir' => 'Futbol', 'title' => 'Moratalla Club de Fútbol'],
    ['id' => 128, 'dir' => 'Tamboristas', 'title' => 'Asociación de Tamboristas'], // Usamos IDs correctos o buscamos por nombre
    ['id' => 20, 'dir' => 'Nazareno', 'title' => 'Asociación de Nazarenos'],
    ['id' => 28, 'dir' => 'Baloncesto', 'title' => 'Club Baloncesto Moratalla']
];

// Corregir ID de Tamboristas si es necesario (el script anterior dijo 25 pero en inspect salía 128 a veces o 25)
// Vamos a buscar el ID actual de las categorías principales para no fallar
foreach ($asociaciones as &$a) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name LIKE ? AND (parent_id = 1 OR id = ?)");
    $stmt->execute(['%' . $a['dir'] . '%', $a['id']]);
    $res = $stmt->fetch();
    if ($res) $a['id'] = $res['id'];
}

$baseLegacy = 'E:/00 PARTICULAR/moratalla-murcia/moratalla/Asociaciones/';
$uploadDir = 'uploads/galerias/';

echo "Iniciando re-estructuración por Años...\n";

foreach ($asociaciones as $asoc) {
    echo "Analizando carpetas de {$asoc['title']}...\n";
    $dirPath = $baseLegacy . $asoc['dir'];
    
    if (is_dir($dirPath)) {
        // Listar carpetas que parecen años
        $subDirs = glob($dirPath . '/*', GLOB_ONLYDIR);
        foreach ($subDirs as $sd) {
            $yearName = basename($sd);
            if (is_numeric($yearName) || strpos($yearName, 'Temporada') !== false) {
                echo " - Creando subsección para Año/Temporada: $yearName\n";
                
                // 1. Crear la categoría del año (Hija de la Asociación)
                $stmtCat = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
                $stmtCat->execute([$yearName, $asoc['id']]);
                $catYear = $stmtCat->fetch();
                
                if (!$catYear) {
                    $pdo->prepare("INSERT INTO categories (parent_id, name, slug) VALUES (?, ?, ?)")
                        ->execute([$asoc['id'], $yearName, strtolower($asoc['dir'].'-'.$yearName)]);
                    $catYearId = $pdo->lastInsertId();
                } else {
                    $catYearId = $catYear['id'];
                }
                
                // 2. Crear la Página para ese AÑO
                $pageTitle = $asoc['title'] . " - " . $yearName;
                $stmtPg = $pdo->prepare("SELECT id FROM pages WHERE category_id = ? LIMIT 1");
                $stmtPg->execute([$catYearId]);
                $page = $stmtPg->fetch();
                
                if (!$page) {
                    $content = "<p>Crónica y galería de imágenes de las actividades de <strong>{$asoc['title']}</strong> durante el año/temporada <strong>$yearName</strong>.</p>";
                    $pdo->prepare("INSERT INTO pages (category_id, title, slug, content) VALUES (?, ?, ?, ?)")
                        ->execute([$catYearId, $pageTitle, strtolower(str_replace(' ', '-', $pageTitle)), $content]);
                    $pageId = $pdo->lastInsertId();
                } else {
                    $pageId = $page['id'];
                }
                
                // 3. Migrar fotos de ESE AÑO a ESA PÁGINA
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sd));
                $imgCount = 0;
                foreach ($files as $file) {
                    if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
                        if ($file->getSize() > 10000 && strpos($file->getFilename(), 'thumb') === false) {
                            $newFilename = "asoc_year_{$catYearId}_" . $imgCount . ".jpg";
                            $destPath = $uploadDir . $newFilename;
                            if (!file_exists($destPath)) copy($file->getRealPath(), $destPath);
                            
                            $stmtImg = $pdo->prepare("SELECT id FROM page_images WHERE page_id = ? AND image_path = ?");
                            $stmtImg->execute([$pageId, $destPath]);
                            if (!$stmtImg->fetch()) {
                                $pdo->prepare("INSERT INTO page_images (page_id, image_path, is_cover) VALUES (?, ?, ?)")
                                    ->execute([$pageId, $destPath, ($imgCount === 0 ? 1 : 0)]);
                                $imgCount++;
                            }
                        }
                    }
                }
                echo "   -> $imgCount fotos migradas al año $yearName.\n";
            }
        }
    }
}

echo "\nEstructura jerárquica completada.";
?>
