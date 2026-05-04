<?php
// admin/categories.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();

adminHeader("Gestión de Categorías");

function renderCategoryTree($parentId = null, $depth = 0) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?"));
    if ($parentId === null) $stmt->execute();
    else $stmt->execute([$parentId]);
    
    while ($cat = $stmt->fetch()) {
        echo "<div class='tree-item'>";
        echo "<i class='fas fa-folder' style='color: var(--accent); margin-right: 8px;'></i>";
        echo "<strong>{$cat['name']}</strong> ";
        echo "<small style='color: #888;'>({$cat['slug']})</small>";
        echo " <div style='float: right;'>
                <a href='?action=edit&id={$cat['id']}' class='btn btn-sm'><i class='fas fa-edit'></i></a>
                <a href='?action=delete&id={$cat['id']}' class='btn btn-sm' style='color: #ff4757;' onclick='return confirm(\"¿Seguro?\")'><i class='fas fa-trash'></i></a>
               </div>";
        
        renderCategoryTree($cat['id'], $depth + 1);
        echo "</div>";
    }
}
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3>Estructura de Carpetas (Categorías)</h3>
        <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Categoría</a>
    </div>

    <div class="tree-container" style="background: var(--gray-100); padding: 2rem; border-radius: 8px;">
        <?php renderCategoryTree(); ?>
    </div>
</div>

<?php adminFooter(); ?>
