<?php
// admin/images.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();

// Procesar actualización de imagen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_image') {
    $img_id = (int)$_POST['image_id'];
    $caption = isset($_POST['caption']) ? $_POST['caption'] : '';
    $sort_order = (int)(isset($_POST['sort_order']) ? $_POST['sort_order'] : 0);
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $is_cover = isset($_POST['is_cover']) ? 1 : 0;
    $current_page = (int)(isset($_POST['current_page']) ? $_POST['current_page'] : 1);

    // Si se marca como portada, desmarcar las demás de ESA MISMA PÁGINA
    if ($is_cover) {
        $stmtPageId = $pdo->prepare("SELECT page_id FROM page_images WHERE id = ?");
        $stmtPageId->execute([$img_id]);
        $pageId = $stmtPageId->fetchColumn();
        
        if ($pageId) {
            $stmtReset = $pdo->prepare("UPDATE page_images SET is_cover = 0 WHERE page_id = ?");
            $stmtReset->execute([$pageId]);
        }
    }

    $stmt = $pdo->prepare("UPDATE page_images SET caption = ?, sort_order = ?, is_visible = ?, is_cover = ? WHERE id = ?");
    $stmt->execute([$caption, $sort_order, $is_visible, $is_cover, $img_id]);
    
    header("Location: images.php?page=" . $current_page . "&msg=updated");
    exit;
}

// Procesar eliminación de imagen (Opcional, pero útil)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $current_page = (int)(isset($_GET['page']) ? $_GET['page'] : 1);
    
    $stmt = $pdo->prepare("SELECT image_path FROM page_images WHERE id = ?");
    $stmt->execute([$id]);
    $img = $stmt->fetch();
    
    if ($img) {
        if (!empty($img['image_path']) && is_file('../' . $img['image_path'])) {
            @unlink('../' . $img['image_path']);
        }
        $pdo->prepare("DELETE FROM page_images WHERE id = ?")->execute([$id]);
    }
    header("Location: images.php?page=" . $current_page . "&msg=deleted");
    exit;
}

// Consulta
$page = 1;
$totalImages = $pdo->query("SELECT COUNT(*) FROM page_images")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT pi.*, p.title as page_title, p.id as real_page_id 
    FROM page_images pi 
    LEFT JOIN pages p ON pi.page_id = p.id 
    ORDER BY pi.id DESC
");
$stmt->execute();
$images = $stmt->fetchAll();

adminHeader("Galería General");
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h3>Gestor Global de Imágenes</h3>
        <span class="badge badge-info" style="font-size: 1.1rem; padding: 0.5rem 1rem;">Total: <?php echo $totalImages; ?></span>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert alert-success">Imagen actualizada correctamente.</div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success">Imagen eliminada permanentemente.</div>
    <?php endif; ?>
    <div style="margin-bottom: 1rem;">
        <input type="text" id="searchInput" placeholder="Buscar por título de página, ID o descripción de foto..." style="width: 100%; padding: 0.8rem; border: 1px solid var(--gray-300); border-radius: 6px; font-size: 1rem;">
    </div>

    <div id="imageGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
        <?php foreach ($images as $row): ?>
            <div class="image-item" style="background: var(--bg-alt); border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; position: relative; display: flex; flex-direction: column;">
                
                <!-- Imagen -->
                <div style="position: relative; height: 180px; background: #eee;">
                    <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php if ($row['is_cover']) : ?>
                        <span style="position: absolute; top: 10px; right: 10px; background: var(--accent); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-star"></i> Portada</span>
                    <?php endif; ?>
                    <?php if (!$row['is_visible']) : ?>
                        <span style="position: absolute; top: 10px; left: 10px; background: #666; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><i class="fas fa-eye-slash"></i> Oculta</span>
                    <?php endif; ?>
                </div>

                <!-- Info Origen y Ruta -->
                <div style="padding: 0.75rem; border-bottom: 1px solid var(--gray-200); font-size: 0.85rem; background: white;">
                    <div style="margin-bottom: 0.5rem;">
                        <?php if (empty($row['real_page_id'])): ?>
                            <span style="color: #d9534f; font-weight: bold;"><i class="fas fa-exclamation-triangle"></i> Huérfana (Página <?php echo $row['page_id']; ?> borrada)</span>
                        <?php else: ?>
                            <i class="fas fa-file-alt" style="color: var(--primary);"></i> 
                            <a href="pages.php?edit=<?php echo $row['real_page_id']; ?>" style="color: var(--primary); text-decoration: none; font-weight: 600;" title="Editar página a la que pertenece">
                                <?php echo htmlspecialchars($row['page_title']); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div style="font-family: monospace; font-size: 0.75rem; color: #666; background: #f8f9fa; padding: 0.4rem; border-radius: 4px; border: 1px solid #e9ecef; word-break: break-all;">
                        <i class="fas fa-hdd" style="color: #999;"></i> /<?php echo htmlspecialchars($row['image_path']); ?>
                    </div>
                </div>

                <!-- Formulario de Edición Rápida -->
                <form action="images.php" method="POST" style="padding: 1rem; flex: 1; display: flex; flex-direction: column; gap: 0.8rem;">
                    <input type="hidden" name="action" value="update_image">
                    <input type="hidden" name="image_id" value="<?php echo $row['id']; ?>">
                    <input type="hidden" name="current_page" value="<?php echo $page; ?>">

                    <div>
                        <label style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 0.2rem; display: block;">Descripción (Pie de foto)</label>
                        <input type="text" name="caption" value="<?php echo htmlspecialchars(isset($row['caption']) ? $row['caption'] : ''); ?>" style="width: 100%; padding: 0.5rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.9rem;" placeholder="Sin descripción...">
                    </div>

                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 0.2rem; display: block;">Orden</label>
                            <input type="number" name="sort_order" value="<?php echo $row['sort_order']; ?>" style="width: 100%; padding: 0.5rem; border: 1px solid var(--gray-300); border-radius: 4px; font-size: 0.9rem;">
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1;">
                            <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="is_visible" value="1" <?php echo $row['is_visible'] ? 'checked' : ''; ?>>
                                Visible
                            </label>
                            <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="is_cover" value="1" <?php echo $row['is_cover'] ? 'checked' : ''; ?>>
                                Portada
                            </label>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-top: auto; padding-top: 1rem;">
                        <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;"><i class="fas fa-save"></i> Guardar</button>
                        <a href="images.php?delete=<?php echo $row['id']; ?>&page=<?php echo $page; ?>" onclick="return confirm('¿Eliminar esta imagen para siempre?');" class="btn-danger" style="padding: 0.5rem; font-size: 0.85rem; background: transparent; color: #d9534f; border: 1px solid #d9534f; border-radius: 4px; text-decoration: none;" title="Eliminar Imagen">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            const items = document.querySelectorAll('#imageGrid .image-item');
            items.forEach(item => {
                // Para capturar también los inputs (caption)
                const textInputs = Array.from(item.querySelectorAll('input[type="text"]')).map(i => i.value).join(' ').toLowerCase();
                const textContent = item.textContent.toLowerCase();
                const text = textContent + ' ' + textInputs;
                
                item.style.display = text.includes(term) ? 'flex' : 'none';
            });
        });
    }
});
</script>

<?php adminFooter(); ?>
