<?php
// migrate.php
require_once 'config.php';

$pdo = getDB();

// Limpiamos las tablas para una migración fresca y jerárquica
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE page_images; TRUNCATE TABLE pages; TRUNCATE TABLE categories; SET FOREIGN_KEY_CHECKS = 1;");

// Directorio origen y destino de imágenes
$sourceDir = 'E:\00 particular\moratalla-murcia\moratalla';
$assetsDir = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images';

if (!is_dir($sourceDir)) {
    die("Error: El directorio origen no existe ($sourceDir).\n");
}
if (!file_exists($assetsDir)) {
    mkdir($assetsDir, 0777, true);
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) return 'n-a';
    return $text;
}

/**
 * Función Recursiva de Migración
 */
function processDirectory($dirPath, $parentId = null, $depth = 0) {
    global $pdo, $sourceDir, $assetsDir;

    $items = scandir($dirPath);
    foreach ($items as $item) {
        // Omitir carpetas ocultas, de sistema o técnicas de FrontPage/ASP
        if ($item == '.' || $item == '..' || strpos($item, '_') === 0 || strtolower($item) == 'images' || strtolower($item) == 'cgi-bin' || strtolower($item) == 'botones_menu' || strtolower($item) == 'sonido') {
            continue;
        }

        $fullPath = $dirPath . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath)) {
            // ES UNA CATEGORÍA / CARPETA
            $categoryName = ucfirst(str_replace(['_', '-'], ' ', $item));
            $categorySlug = slugify($item);
            
            // Si tiene padre, el slug puede ser una combinación o simplemente único
            // Para evitar colisiones de slugs iguales en distintas ramas (ej. "images")
            if ($parentId) {
                // Buscamos el slug del padre para prefijar si es necesario, o solo añadimos un ID
                $categorySlug .= "-" . rand(10, 99);
            }
            
            // Insertar categoría
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, parent_id) VALUES (?, ?, ?)");
            try {
                $stmt->execute([$categoryName, $categorySlug, $parentId]);
                $lastId = $pdo->lastInsertId();
                echo str_repeat("  ", $depth) . "[DIR] $categoryName\n";
                
                // Procesar contenido dentro de esta carpeta recursivamente
                processDirectory($fullPath, $lastId, $depth + 1);
            } catch (Exception $e) {
                // Probablemente slug duplicado
                $categorySlug .= "-" . rand(100, 999);
                $stmt->execute([$categoryName, $categorySlug, $parentId]);
                $lastId = $pdo->lastInsertId();
                processDirectory($fullPath, $lastId, $depth + 1);
            }

        } else if (is_file($fullPath) && preg_match('#\.(htm|html)$#i', $item)) {
            // ES UNA PÁGINA HTML
            // Ignoramos archivos genéricos de navegación
            if (preg_match('/^(menu_|index|principall|footer|header|bottom|left|right|top|_)/i', $item)) continue;
            
            processSingleFile($fullPath, $parentId, $depth);
        }
    }
}

function processSingleFile($file, $categoryId, $depth) {
    global $pdo, $sourceDir, $assetsDir;
    
    $contentRaw = file_get_contents($file);
    if(mb_detect_encoding($contentRaw, 'UTF-8', true) === false) {
        $contentRaw = mb_convert_encoding($contentRaw, 'UTF-8', 'Windows-1252');
    }
    
    // Título desde <title> o nombre de archivo
    $title = basename($file, '.htm');
    $title = basename($title, '.html');
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $contentRaw, $matches)) {
        $extracted = trim(strip_tags($matches[1]));
        if (!empty($extracted) && strlen($extracted) < 150) {
            $title = $extracted;
        }
    }
    $title = str_replace(['Moratalla, ', 'Murcia, '], '', $title);
    $pageSlug = slugify($title) . '-' . rand(1000, 9999);
    
    // Body extraction
    $body = $contentRaw;
    if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $contentRaw, $bodyMatches)) {
        $body = $bodyMatches[1];
    }
    
    // Cleanup high-level FrontPage junk
    $body = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $body);
    $body = preg_replace('#<object(.*?)>(.*?)</object>#is', '', $body);
    $body = preg_replace('#<!--(.*?)-->#is', '', $body);
    $body = preg_replace('/style="[^"]*"/i', '', $body);
    $body = preg_replace('/bgcolor="[^"]*"/i', '', $body);
    $body = preg_replace('/background="[^"]*"/i', '', $body);
    $body = preg_replace('/face="[^"]*"/i', '', $body);
    $body = preg_replace('/color="[^"]*"/i', '', $body);
    $body = preg_replace('/width="[^"]*"/i', '', $body);
    $body = preg_replace('/height="[^"]*"/i', '', $body);

    // Insert Page
    $stmt = $pdo->prepare("INSERT INTO pages (category_id, title, slug, content, original_file) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$categoryId, $title, $pageSlug, $body, basename($file)]);
    $pageId = $pdo->lastInsertId();

    echo str_repeat("  ", $depth) . "  [*] Pag: $title\n";
    
    // Extract and Copy Images
    extractImages($body, $file, $pageId);
}

function extractImages($html, $currentFile, $pageId) {
    global $pdo, $sourceDir, $assetsDir;
    
    if (preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/i', $html, $matches)) {
        $first = true;
        foreach ($matches[1] as $rawImgPath) {
            // Ignorar botones e iconos comunes de FrontPage
            if (preg_match('/(boton|icon|nav|bullet|log|animate|hover)/i', $rawImgPath)) continue;
            
            $imgName = basename($rawImgPath);
            $testPaths = [
                dirname($currentFile) . DIRECTORY_SEPARATOR . $rawImgPath,
                $sourceDir . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $imgName,
                dirname($currentFile) . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . $imgName,
                $sourceDir . DIRECTORY_SEPARATOR . str_replace(['../', './'], '', $rawImgPath)
            ];
            
            $foundFile = false;
            foreach ($testPaths as $tp) {
                if (is_file($tp)) {
                    $foundFile = $tp; break;
                }
            }
            
            if ($foundFile) {
                $subFolder = 'p' . $pageId;
                $destDir = $assetsDir . DIRECTORY_SEPARATOR . $subFolder;
                if (!file_exists($destDir)) mkdir($destDir, 0777, true);
                
                $newName = uniqid('img_') . '_' . $imgName;
                $destination = $destDir . DIRECTORY_SEPARATOR . $newName;
                
                if (copy($foundFile, $destination)) {
                    $webPath = 'assets/images/' . $subFolder . '/' . $newName;
                    $isCover = $first ? 1 : 0;
                    $first = false;
                    
                    $iStmt = $pdo->prepare("INSERT INTO page_images (page_id, image_path, is_cover) VALUES (?, ?, ?)");
                    $iStmt->execute([$pageId, $webPath, $isCover]);
                }
            }
        }
    }
}

// Configuración de visualización
header('Content-Type: text/plain; charset=utf-8');
echo "Iniciando Migración Profesional Recursiva...\n";
echo "Origen: $sourceDir\n";
echo str_repeat("=", 50) . "\n";

processDirectory($sourceDir);

echo "\n" . str_repeat("=", 50) . "\n";
echo "Migración completada con éxito.\n";
