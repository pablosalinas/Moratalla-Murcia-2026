<?php
require __DIR__ . '/config.php';
$pdo = getDB();

$catName = "%Tamborista%";
$stmt = $pdo->prepare("SELECT * FROM categories WHERE name LIKE ?");
$stmt->execute([$catName]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Diagnóstico de Categoría: Asociación de Tamboristas</h1>";

if (!$categories) {
    echo "<p>No se encontró ninguna categoría con ese nombre.</p>";
    exit;
}

foreach ($categories as $cat) {
    echo "<div style='border:1px solid #ccc; padding:15px; margin-bottom:20px;'>";
    echo "<h2>Categoría: " . htmlspecialchars($cat['name']) . " (ID: " . $cat['id'] . ")</h2>";
    echo "<b>Visible:</b> " . ($cat['is_visible'] ? 'Sí' : 'No') . "<br>";
    
    // Subcategories
    echo "<h3>Subcategorías dentro de esta categoría:</h3>";
    $stmtSub = $pdo->prepare("SELECT id, name, is_visible FROM categories WHERE parent_id = ?");
    $stmtSub->execute([$cat['id']]);
    $subs = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
    if ($subs) {
        echo "<ul>";
        foreach ($subs as $sub) {
            echo "<li>" . htmlspecialchars($sub['name']) . " (ID: " . $sub['id'] . ") - Visible: " . ($sub['is_visible'] ? 'Sí' : 'No') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tiene subcategorías.</p>";
    }
    
    // Pages
    echo "<h3>Páginas directas en esta categoría:</h3>";
    $stmtPage = $pdo->prepare("SELECT id, title FROM pages WHERE category_id = ?");
    $stmtPage->execute([$cat['id']]);
    $pages = $stmtPage->fetchAll(PDO::FETCH_ASSOC);
    if ($pages) {
        echo "<ul>";
        foreach ($pages as $p) {
            echo "<li>" . htmlspecialchars($p['title']) . " (ID: " . $p['id'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tiene páginas asignadas directamente a ella.</p>";
    }
    echo "</div>";
}
?>
