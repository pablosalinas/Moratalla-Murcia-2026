<?php
/**
 * Script de limpieza exhaustiva V2 para Biblioteca en producción.
 */
require_once 'config.php';
$pdo = getDB();

try {
    // 1. Identificar Biblioteca
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name LIKE '%Biblioteca%'");
    $stmt->execute();
    $mainCat = $stmt->fetch();

    if (!$mainCat) {
        die("ERROR: No se encontró Biblioteca.");
    }

    $mainId = $mainCat['id'];
    echo "ID Principal Biblioteca: $mainId<br>";

    // 2. Obtener TODAS las categorías del sistema para procesar localmente la jerarquía
    // Esto es más seguro que la recursividad si hay problemas de DB
    $stmtAll = $pdo->query("SELECT id, parent_id, name FROM categories");
    $allCats = $stmtAll->fetchAll();

    $toDelete = [];
    function findChildren($parentId, $cats, &$list) {
        foreach ($cats as $c) {
            if ($c['parent_id'] == $parentId) {
                $list[] = $c['id'];
                echo "Encontrada subcategoría para borrar: " . $c['name'] . " (ID: " . $c['id'] . ")<br>";
                findChildren($c['id'], $cats, $list);
            }
        }
    }

    findChildren($mainId, $allCats, $toDelete);

    if (count($toDelete) > 0) {
        $inQuery = implode(',', array_fill(0, count($toDelete), '?'));
        
        // Buscar páginas en estas categorías
        $stmtPages = $pdo->prepare("SELECT id FROM pages WHERE category_id IN ($inQuery)");
        $stmtPages->execute($toDelete);
        $pageIds = $stmtPages->fetchAll(PDO::FETCH_COLUMN);

        if (count($pageIds) > 0) {
            $pageIn = implode(',', array_fill(0, count($pageIds), '?'));
            // Imágenes
            $pdo->prepare("DELETE FROM page_images WHERE page_id IN ($pageIn)")->execute($pageIds);
            // Páginas
            $pdo->prepare("DELETE FROM pages WHERE id IN ($pageIn)")->execute($pageIds);
            echo "OK: " . count($pageIds) . " páginas eliminadas.<br>";
        }

        // Eliminar categorías
        $pdo->prepare("DELETE FROM categories WHERE id IN ($inQuery)")->execute($toDelete);
        echo "OK: " . count($toDelete) . " subcategorías eliminadas.<br>";
    } else {
        echo "INFO: No se encontraron subcategorías adicionales mediante búsqueda jerárquica.<br>";
    }

    // 3. Limpieza de seguridad: cualquier página que apunte directamente a Biblioteca
    $stmtPagesMain = $pdo->prepare("DELETE FROM pages WHERE category_id = ?");
    $stmtPagesMain->execute([$mainId]);
    echo "OK: Limpieza de páginas directas completada.<br>";

    echo "<b>LIMPIEZA V2 FINALIZADA.</b>";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
