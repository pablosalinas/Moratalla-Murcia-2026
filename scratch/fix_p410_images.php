<?php
require __DIR__ . '/../config.php';
$pdo = getDB();
$id = 410;
$stmt = $pdo->prepare("SELECT content FROM pages WHERE id = ?");
$stmt->execute([$id]);
$content = $stmt->fetchColumn();

// 1. Obtener los archivos físicos de assets/images/p126/
$files = glob(__DIR__ . '/../assets/images/p126/*.*');
$fileMap = [];
foreach ($files as $file) {
    $basename = basename($file);
    // El formato es img_ID_nombreoriginal.ext
    if (preg_match('/^img_[^_]+_(.+)$/', $basename, $matches)) {
        $originalName = strtolower($matches[1]);
        $fileMap[$originalName] = $basename;
    }
}

// 2. Reemplazar todas las rutas viejas "Images/tamborada2004/loquesea.jpg" por "assets/images/p126/nuevo_nombre.jpg"
// Usaremos una expresión regular robusta para encontrar todo lo que contenga Images/tamborada2004/
$content = preg_replace_callback('/(src|href)=["\']?(?:[^"\']*Images\/tamborada2004\/)([^"\'>\s]+)["\']?/i', function($matches) use ($fileMap) {
    $attr = $matches[1]; // src o href
    $imgName = strtolower(basename($matches[2]));
    
    // Si es un src, debería coincidir directamente.
    // Si es un href (ej. calanda104.jpg), quizás queramos apuntarlo a la misma imagen P04.
    // Para simplificar, buscamos si coincide total o parcialmente en nuestro map.
    
    if (isset($fileMap[$imgName])) {
        return $attr . '="assets/images/p126/' . $fileMap[$imgName] . '"';
    } else {
        // Fallback: tratar de buscar parcial (por ejemplo, si imgName es calanda104.jpg y la foto es calandaP04.jpg)
        // Sustituir "10", "11", "12" por "P0", "P1", "P1"
        $mappedName = str_replace(['10', '11', '12'], ['P0', 'P1', 'P1'], $imgName);
        foreach ($fileMap as $orig => $newPath) {
            if (strpos($orig, $imgName) !== false || strpos($imgName, $orig) !== false || strpos($orig, $mappedName) !== false) {
                return $attr . '="assets/images/p126/' . $newPath . '"';
            }
        }
    }
    
    // Si no se encuentra, dejar la ruta tal cual
    return $matches[0];
}, $content);

// 3. Forzar el reemplazo directo de ciertas imágenes si las hay, por si el regex anterior falló en pasadas anteriores
foreach ($fileMap as $orig => $newPath) {
    // Si en el HTML actual hay una imagen apuntando a Images/.../monumentoP01.jpg
    $content = preg_replace('/(src|href)=["\'][^"\']*' . preg_quote($orig, '/') . '["\']/i', '$1="assets/images/p126/' . $newPath . '"', $content);
}

// Guardar
$stmtUpdate = $pdo->prepare("UPDATE pages SET content = ? WHERE id = ?");
$stmtUpdate->execute([$content, $id]);

echo "Página 410 re-procesada y rutas arregladas.\n";
