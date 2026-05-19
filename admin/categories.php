<?php
// admin/categories.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$msg = "";
$error = "";

// Función para generar slugs limpios
function slugify($text) {
    // Reemplazar caracteres no alfanuméricos por guiones
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliteración de tildes y caracteres especiales de español
    $text = str_replace(
        ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ü','Ü'],
        ['a','e','i','o','u','a','e','i','o','u','n','n','u','u'],
        $text
    );
    // Eliminar caracteres no deseados
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Quitar guiones sobrantes de los extremos
    $text = trim($text, '-');
    // Eliminar guiones duplicados
    $text = preg_replace('~-+~', '-', $text);
    // Convertir a minúsculas
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

// PROCESAR ACCIONES DE GUARDADO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $id = isset($_POST['id']) ? $_POST['id'] : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $parent_id = isset($_POST['parent_id']) ? $_POST['parent_id'] : '';
        $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
        $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $is_visible = isset($_POST['is_visible']) ? 1 : 0;
        
        if (empty($parent_id)) {
            $parent_id = null;
        }
        
        if (empty($name)) {
            $error = "El nombre de la categoría es obligatorio.";
        } else {
            if (empty($slug)) {
                $slug = slugify($name);
            } else {
                $slug = slugify($slug);
            }
            
            // Verificar slugs duplicados
            if ($action == 'add') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
                $stmt->execute([$slug]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $id]);
            }
            
            if ($stmt->fetchColumn() > 0) {
                // Si está duplicado, le añadimos un sufijo aleatorio para evitar fallos de base de datos
                $slug .= '-' . rand(10, 99);
            }
            
            if ($action == 'add') {
                $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id, slug, sort_order, is_visible) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $parent_id, $slug, $sort_order, $is_visible]);
                $msg = "Categoría creada con éxito.";
                $action = 'list';
            } else {
                // Asegurarse de que no sea hija de sí misma
                if ($parent_id == $id) {
                    $parent_id = null;
                }
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, parent_id = ?, slug = ?, sort_order = ?, is_visible = ? WHERE id = ?");
                $stmt->execute([$name, $parent_id, $slug, $sort_order, $is_visible, $id]);
                $msg = "Categoría modificada con éxito.";
                $action = 'list';
            }
        }
    }
}

// PROCESAR ELIMINACIÓN (GET)
if ($action == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Categoría eliminada con éxito.";
    $action = 'list';
}

adminHeader("Gestión de Categorías");

// Función recursiva para renderizar el árbol de categorías en la lista
function renderCategoryTree($parentId = null, $depth = 0) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?") . " ORDER BY sort_order ASC, name ASC");
    if ($parentId === null) $stmt->execute();
    else $stmt->execute([$parentId]);
    
    $hasItems = false;
    while ($cat = $stmt->fetch()) {
        $hasItems = true;
        $padding = $depth * 25;
        ?>
        <div class="tree-item" style="padding-left: <?php echo $padding; ?>px; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: space-between; background: white; padding: 0.8rem 1rem; border-radius: 8px; border: 1px solid var(--gray-200); box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: all 0.2s;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <?php if ($depth > 0): ?>
                    <span style="color: var(--gray-300); font-weight: bold;">&mdash;</span>
                <?php endif; ?>
                <i class="fas fa-folder" style="color: var(--accent); font-size: 1.1rem;"></i>
                <strong style="color: var(--primary);"><?php echo htmlspecialchars($cat['name']); ?></strong>
                <small style="color: #7f8c8d; font-family: monospace;">(<?php echo htmlspecialchars($cat['slug']); ?>)</small>
                <span class="badge badge-info" style="font-size: 0.7rem; background: var(--gray-100);">Orden: <?php echo $cat['sort_order']; ?></span>
                <?php if (!$cat['is_visible']): ?>
                    <span class="badge" style="font-size: 0.7rem; background: #ffebee; color: #c62828;"><i class="fas fa-eye-slash"></i> Invisible</span>
                <?php else: ?>
                    <span class="badge" style="font-size: 0.7rem; background: #e8f5e9; color: #2e7d32;"><i class="fas fa-eye"></i> Visible</span>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-edit"></i> Modificar</a>
                <a href="?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm" style="color: white; background: #e74c3c; padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="return confirm('¿Seguro que deseas eliminar esta categoría? Si tiene subcategorías, se eliminarán en cascada de acuerdo a las directrices de la base de datos.')"><i class="fas fa-trash"></i> Borrar</a>
            </div>
        </div>
        <?php
        renderCategoryTree($cat['id'], $depth + 1);
    }
    
    if ($parentId === null && !$hasItems && $depth === 0) {
        echo "<p style='color: #888; font-style: italic; text-align: center; padding: 2rem;'>No hay categorías creadas todavía.</p>";
    }
}

// Función recursiva para renderizar las opciones del dropdown de categoría padre
function renderCategoryOptions($excludeId = null, $parentId = null, $depth = 0, $selectedId = null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?") . " ORDER BY name ASC");
    if ($parentId === null) $stmt->execute();
    else $stmt->execute([$parentId]);
    
    while ($cat = $stmt->fetch()) {
        // Evitamos que una categoría sea seleccionada como padre de sí misma (bucle infinito)
        if ($excludeId !== null && $cat['id'] == $excludeId) {
            continue;
        }
        
        $indent = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $depth);
        $prefix = $depth > 0 ? "↳ " : "";
        $selected = ($cat['id'] == $selectedId) ? "selected" : "";
        echo "<option value='{$cat['id']}' {$selected}>{$indent}{$prefix}" . htmlspecialchars($cat['name']) . "</option>";
        
        renderCategoryOptions($excludeId, $cat['id'], $depth + 1, $selectedId);
    }
}
?>

<?php if ($msg): ?>
    <div class="card" style="background: #e8f5e9; color: #2e7d32; padding: 1rem; margin-bottom: 2rem; border-top: 4px solid #2e7d32;">
        <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="card" style="background: #ffebee; color: #c62828; padding: 1rem; margin-bottom: 2rem; border-top: 4px solid #c62828;">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div>
                <h3>Estructura de Carpetas (Categorías)</h3>
                <p style="color: #666; font-size: 0.9rem; margin-top: 0.3rem;">Visualiza, crea, edita y organiza la estructura jerárquica de la web.</p>
            </div>
            <a href="?action=add" class="btn btn-primary" style="border: 2px solid var(--primary-dark);"><i class="fas fa-plus"></i> Nueva Categoría</a>
        </div>

        <div class="tree-container" style="background: var(--gray-100); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--gray-200);">
            <?php renderCategoryTree(); ?>
        </div>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): 
    $cat_data = ['id' => '', 'name' => '', 'parent_id' => '', 'slug' => '', 'sort_order' => 0, 'is_visible' => 1];
    if ($action == 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $cat_data = $stmt->fetch();
        if (!$cat_data) {
            header("Location: categories.php");
            exit;
        }
    }
?>
    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <h3 style="color: var(--primary); margin-bottom: 0.5rem;">
            <?php echo $action == 'add' ? '<i class="fas fa-folder-plus"></i> Añadir Nueva Categoría' : '<i class="fas fa-folder-open"></i> Modificar Categoría'; ?>
        </h3>
        <p style="color: #666; font-size: 0.85rem; margin-bottom: 2rem;">Rellena los datos para configurar la categoría del sistema.</p>
        
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($cat_data['id']); ?>">
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Nombre de la Categoría</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($cat_data['name']); ?>" style="width:100%; padding:0.8rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Categoría Padre (Ubicación)</label>
                <select name="parent_id" style="width:100%; padding:0.8rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem; background: white;">
                    <option value="">-- Sin categoría padre (Categoría Raíz) --</option>
                    <?php renderCategoryOptions($action == 'edit' ? $cat_data['id'] : null, null, 0, $cat_data['parent_id']); ?>
                </select>
                <small style="color: #666; display: block; margin-top: 0.4rem;">Si seleccionas una categoría padre, esta categoría actuará como una subsección de la misma.</small>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Slug (URL amigable - Opcional)</label>
                <input type="text" name="slug" value="<?php echo htmlspecialchars($cat_data['slug']); ?>" placeholder="autogenerado-si-se-deja-vacio" style="width:100%; padding:0.8rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem; font-family: monospace;">
                <small style="color: #666; display: block; margin-top: 0.4rem;">Identificador único para la URL. Si lo dejas en blanco se creará solo en base al nombre.</small>
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Orden de Visualización</label>
                <input type="number" name="sort_order" required value="<?php echo (int)($cat_data['sort_order']); ?>" style="width:100%; padding:0.8rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem;">
                <small style="color: #666; display: block; margin-top: 0.4rem;">Define la posición de esta categoría en los listados y menús. Las categorías raíz con orden mayor a 0 se listan en el Inicio.</small>
            </div>

            <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="is_visible" id="is_visible" value="1" <?php echo ($cat_data['is_visible'] ? 'checked' : ''); ?> style="transform: scale(1.3); cursor: pointer;">
                <label for="is_visible" style="font-weight: 600; color: var(--primary); cursor: pointer; user-select: none;">¿Categoría visible en la web?</label>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 2.5rem; border-top: 1px solid var(--gray-200); padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="border: 2px solid var(--primary-dark);"><i class="fas fa-save"></i> Guardar Cambios</button>
                <a href="categories.php" class="btn" style="background: var(--gray-200); color: var(--text);"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php adminFooter(); ?>
