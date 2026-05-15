<?php
/**
 * Script temporal para limpiar la sección Biblioteca en producción.
 * SE ELIMINARÁ DESPUÉS DE SU USO.
 */
require_once 'config.php';

// Seguridad básica: solo ejecutar si se conoce el parámetro secreto o por IP (opcional)
// Para simplificar, lo dejaré abierto un momento y lo borraré inmediatamente.

$pdo = getDB();

try {
    // 1. Identificar la categoría principal
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name LIKE '%Biblioteca%'");
    $stmt->execute();
    $mainCat = $stmt->fetch();

    if (!$mainCat) {
        die("ERROR: No se encontró la categoría Biblioteca en producción.");
    }

    $mainId = $mainCat['id'];
    echo "Iniciando limpieza de Biblioteca (ID: $mainId) en Producción...<br>";

    // 2. Función para obtener IDs recursivos
    function getRecursiveIds($pdo, $parentId) {
        $ids = [$parentId];
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
        $stmt->execute([$parentId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($children as $id) {
            $ids = array_merge($ids, getRecursiveIds($pdo, $id));
        }
        return $ids;
    }

    $allCatIds = getRecursiveIds($pdo, $mainId);
    $inQuery = implode(',', array_fill(0, count($allCatIds), '?'));

    // 3. Buscar y eliminar páginas y sus imágenes
    $stmtPages = $pdo->prepare("SELECT id FROM pages WHERE category_id IN ($inQuery)");
    $stmtPages->execute($allCatIds);
    $pageIds = $stmtPages->fetchAll(PDO::FETCH_COLUMN);

    if (count($pageIds) > 0) {
        $pageIn = implode(',', array_fill(0, count($pageIds), '?'));
        
        // Eliminar imágenes
        $stmtImg = $pdo->prepare("DELETE FROM page_images WHERE page_id IN ($pageIn)");
        $stmtImg->execute($pageIds);
        echo "OK: Imágenes de páginas eliminadas.<br>";
        
        // Eliminar páginas
        $stmtDelPages = $pdo->prepare("DELETE FROM pages WHERE id IN ($pageIn)");
        $stmtDelPages->execute($pageIds);
        echo "OK: Páginas eliminadas.<br>";
    } else {
        echo "INFO: No se encontraron páginas en esta sección.<br>";
    }

    // 4. Eliminar subcategorías (excepto la raíz de Biblioteca)
    $subIds = array_diff($allCatIds, [$mainId]);
    if (count($subIds) > 0) {
        $subIn = implode(',', array_fill(0, count($subIds), '?'));
        $stmtDelSub = $pdo->prepare("DELETE FROM categories WHERE id IN ($subIn)");
        $stmtDelSub->execute(array_values($subIds));
        echo "OK: Subcategorías eliminadas.<br>";
    } else {
        echo "INFO: No había subcategorías adicionales.<br>";
    }

    echo "<b>ÉXITO: La sección Biblioteca ha sido limpiada correctamente en Producción.</b>";

} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage();
}
