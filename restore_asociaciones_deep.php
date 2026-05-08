<?php
require_once 'config.php';
$pdo = getDB();

echo "=== RESTAURACIÓN PROFUNDA DE ASOCIACIONES ===\n";

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
    
    // Buscar el ID de la categoría principal (ahora bajo Culturales o Deportivas)
    $stmtCat = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
    $stmtCat->execute([$asoc['title'], $asoc['parent']]);
    $catAsoc = $stmtCat->fetch();
    if (!$catAsoc) continue;
    $catAsocId = $catAsoc['id'];

    $dirPath = $baseLegacy . $asoc['dir'];
    if (!is_dir($dirPath)) continue;

    $subDirs = glob($dirPath . '/*', GLOB_ONLYDIR);
    foreach ($subDirs as $sd) {
        $yearName = basename($sd);
        if ($yearName == 'images' || $yearName == '_vti_cnf') continue;

        echo " - Restaurando: $yearName\n";
        
        $stmtYear = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
        $stmtYear->execute([$yearName, $catAsocId]);
        $catYear = $stmtYear->fetch();
        if (!$catYear) continue;
        $catYearId = $catYear['id'];

        // 1. Texto Real
        $pageTitle = $asoc['title'] . " (" . $yearName . ")";
        $content = "<p>Crónica y galería de imágenes de la actividad de <strong>{$asoc['title']}</strong> en el periodo <strong>$yearName</strong>.</p>";
        
        $htmlFiles = glob($sd . '/*.htm*');
        if (!empty($htmlFiles)) {
            $htmlContent = @file_get_contents($htmlFiles[0]);
            if ($htmlContent && preg_match('/<body[^>]*>(.*?)<\/body>/is', $htmlContent, $matches)) {
                $content = $matches[1];
                $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $content);
                $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $content);
            }
        }

        $pdo->prepare("DELETE FROM pages WHERE category_id = ?")->execute([$catYearId]);
        $pdo->prepare("INSERT INTO pages (category_id, title, slug, content) VALUES (?, ?, ?, ?)")
            ->execute([$catYearId, $pageTitle, strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $pageTitle)), $content]);
        $pageId = $pdo->lastInsertId();

        // 2. Fotos PROFUNDAS (Buscando en todas las subcarpetas del año)
        $imgCount = 0;
        $it = new RecursiveDirectoryIterator($sd);
        foreach (new RecursiveIteratorIterator($it) as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
                $filename = $file->getFilename();
                $filePath = $file->getRealPath();
                
                // Ignorar miniaturas (terminan en p.jpg) y carpetas de FrontPage
                if (strpos($filePath, '_vti_cnf') !== false) continue;
                if (preg_match('/p\.(jpg|jpeg|png)$/i', $filename)) continue;
                if ($file->getSize() < 5000) continue;

                $newFilename = "asoc_".strtolower($asoc['dir'])."_".preg_replace('/[^a-z0-9]/i', '', $yearName)."_".$imgCount.".jpg";
                $destPath = $uploadDir . $newFilename;
                
                if (copy($filePath, $destPath)) {
                    $pdo->prepare("INSERT INTO page_images (page_id, image_path, is_cover) VALUES (?, ?, ?)")
                        ->execute([$pageId, $destPath, ($imgCount === 0 ? 1 : 0)]);
                    $imgCount++;
                }
                if ($imgCount > 25) break; 
            }
        }
        echo "   ✅ $imgCount fotos y texto rescatados.\n";
    }
}

echo "\n🏆 MIGRACIÓN PROFUNDA COMPLETADA.\n";
?>
