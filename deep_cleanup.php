<?php
require_once 'config.php';
$pdo = getDB();

echo "=== INICIANDO LIMPIEZA PROFUNDA DE LA WEB ===\n";

// 1. Reasignar páginas de carpetas técnicas a sus padres
$techNames = ['Pages', 'Thumbnails', '_vti_cnf', 'images', 'Fotos', 'thumbnails'];

foreach ($techNames as $name) {
    echo "Procesando categorías técnicas llamadas '$name'...\n";
    
    // Buscar categorías con este nombre que tengan un padre
    $stmt = $pdo->prepare("SELECT id, parent_id FROM categories WHERE name = ? AND parent_id IS NOT NULL");
    $stmt->execute([$name]);
    $cats = $stmt->fetchAll();
    
    foreach ($cats as $cat) {
        $catId = $cat['id'];
        $parentId = $cat['parent_id'];
        
        // Mover páginas al padre
        $updatePages = $pdo->prepare("UPDATE pages SET category_id = ? WHERE category_id = ?");
        $updatePages->execute([$parentId, $catId]);
        $moved = $updatePages->rowCount();
        
        if ($moved > 0) {
            echo " - Movidas $moved páginas de la categoría ID $catId a su padre ID $parentId.\n";
        }
        
        // Borrar la categoría técnica (esto también borrará subcategorías técnicas por el ON DELETE CASCADE si existen)
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$catId]);
    }
}

// 2. Eliminar páginas que parecen ser solo marcadores de posición de imágenes antiguas
// (Por ejemplo, títulos que empiezan por IMGP, DSC, etc. y tienen poco contenido)
echo "Limpiando páginas de imágenes individuales redundantes...\n";
$stmt = $pdo->query("DELETE FROM pages WHERE title REGEXP '^(IMGP|DSC|IMG|PIC)[0-9_-]+' AND (content IS NULL OR LENGTH(content) < 200)");
echo " - Eliminadas " . $stmt->rowCount() . " páginas de imágenes individuales.\n";

// 3. Eliminar categorías vacías de nivel profundo (limpieza de ramas muertas)
echo "Eliminando ramas de categorías vacías...\n";
do {
    $stmt = $pdo->query("
        DELETE c FROM categories c
        LEFT JOIN pages p ON c.id = p.category_id
        LEFT JOIN categories child ON c.id = child.parent_id
        WHERE p.id IS NULL AND child.id IS NULL AND c.parent_id IS NOT NULL
    ");
    $deleted = $stmt->rowCount();
    echo " - Eliminadas $deleted categorías vacías.\n";
} while ($deleted > 0);

echo "=== LIMPIEZA COMPLETADA CON ÉXITO ===\n";
?>
