<?php
require_once 'config.php';
$pdo = getDB();

echo "=== RESTAURACIÓN PREMIUM DE ASOCIACIONES ===\n";

$culturalesId = 141;
$deportivasId = 142;

$asociaciones = [
    ['dir' => 'Automovilismo', 'parent' => $deportivasId, 'title' => 'Club Automovilismo Moratalla'],
    ['dir' => 'baloncesto', 'parent' => $deportivasId, 'title' => 'Club Baloncesto Moratalla'],
    ['dir' => 'Ciclista', 'parent' => $deportivasId, 'title' => 'Club Ciclista Moratalla'],
    ['dir' => 'Futbol', 'parent' => $deportivasId, 'title' => 'Moratalla Club de Fútbol'],
    ['dir' => 'grupos', 'parent' => $culturalesId, 'title' => 'Grupos y Peñas'],
    ['dir' => 'Nazareno', 'parent' => $culturalesId, 'title' => 'Padre Jesús Nazareno'],
    ['dir' => 'Tamboristas', 'parent' => $culturalesId, 'title' => 'Asociación de Tamboristas']
];

$baseLegacy = 'E:/00 PARTICULAR/moratalla-murcia/moratalla/Asociaciones/';
$uploadDir = 'uploads/asociaciones/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

foreach ($asociaciones as $asoc) {
    echo "\nProcesando: {$asoc['title']}\n";
    
    // 1. Asegurar Categoría de la Asociación
    $stmtCat = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
    $stmtCat->execute([$asoc['title'], $asoc['parent']]);
    $catAsoc = $stmtCat->fetch();
    
    if (!$catAsoc) {
        $pdo->prepare("INSERT INTO categories (parent_id, name, slug) VALUES (?, ?, ?)")
            ->execute([$asoc['parent'], $asoc['title'], strtolower(str_replace(' ', '-', $asoc['title']))]);
        $catAsocId = $pdo->lastInsertId();
    } else {
        $catAsocId = $catAsoc['id'];
    }

    $dirPath = $baseLegacy . $asoc['dir'];
    if (!is_dir($dirPath)) {
        echo " ⚠️ Carpeta no encontrada: $dirPath\n";
        continue;
    }

    // 2. Buscar Subcarpetas (Años/Temporadas)
    $subDirs = glob($dirPath . '/*', GLOB_ONLYDIR);
    foreach ($subDirs as $sd) {
        $yearName = basename($sd);
        if ($yearName == 'images' || $yearName == '_vti_cnf') continue;

        echo " - Restaurando Temporada/Año: $yearName\n";
        
        // Crear subcategoría para el año
        $stmtYear = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
        $stmtYear->execute([$yearName, $catAsocId]);
        $catYear = $stmtYear->fetch();
        
        if (!$catYear) {
            $pdo->prepare("INSERT INTO categories (parent_id, name, slug) VALUES (?, ?, ?)")
                ->execute([$catAsocId, $yearName, strtolower($asoc['dir'].'-'.$yearName)]);
            $catYearId = $pdo->lastInsertId();
        } else {
            $catYearId = $catYear['id'];
        }

        // 3. Crear Página con Texto Real si existe
        $pageTitle = $asoc['title'] . " (" . $yearName . ")";
        $content = "<p>Crónica y galería de imágenes de la actividad de <strong>{$asoc['title']}</strong> en el periodo <strong>$yearName</strong>.</p>";
        
        // Intentar buscar un archivo htm en la carpeta del año para extraer texto
        $htmlFiles = glob($sd . '/*.htm*');
        if (!empty($htmlFiles)) {
            $htmlContent = file_get_contents($htmlFiles[0]);
            // Limpieza básica de HTML
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $htmlContent, $matches)) {
                $content = $matches[1];
                // Quitar scripts y estilos
                $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $content);
                $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $content);
            }
        }

        $pdo->prepare("DELETE FROM pages WHERE category_id = ?")->execute([$catYearId]);
        $pdo->prepare("INSERT INTO pages (category_id, title, slug, content) VALUES (?, ?, ?, ?)")
            ->execute([$catYearId, $pageTitle, strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $pageTitle)), $content]);
        $pageId = $pdo->lastInsertId();

        // 4. Migrar Fotos (Alta Resolución si es posible)
        $files = glob($sd . '/*.{jpg,jpeg,png}', GLOB_BRACE);
        $imgCount = 0;
        foreach ($files as $file) {
            if (filesize($file) < 5000 || strpos(strtolower($file), 'thumb') !== false) continue;
            
            $newFilename = "asoc_".strtolower($asoc['dir'])."_".$yearName."_".$imgCount.".jpg";
            $destPath = $uploadDir . $newFilename;
            copy($file, $destPath);
            
            $pdo->prepare("INSERT INTO page_images (page_id, image_path, is_cover) VALUES (?, ?, ?)")
                ->execute([$pageId, $destPath, ($imgCount === 0 ? 1 : 0)]);
            $imgCount++;
            if ($imgCount > 20) break; // Límite para no saturar
        }
        echo "   ✅ $imgCount fotos y texto restaurados.\n";
    }
}

echo "\n🏆 PROCESO COMPLETADO CON ÉXITO.\n";
?>
