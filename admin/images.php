<?php
// admin/images.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();

adminHeader("Galería de Imágenes");
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3>Imágenes del Proyecto</h3>
        <span class="badge badge-info">Total: <?php echo $pdo->query("SELECT COUNT(*) FROM page_images")->fetchColumn(); ?></span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
        <?php
        $stmt = $pdo->query("SELECT pi.*, p.title as page_title FROM page_images pi JOIN pages p ON pi.page_id = p.id ORDER BY pi.id DESC LIMIT 60");
        while ($row = $stmt->fetch()) {
            ?>
            <div class="image-item" style="background: white; border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; position: relative;">
                <img src="../<?php echo $row['image_path']; ?>" style="width: 100%; height: 120px; object-fit: cover;">
                <div style="padding: 0.5rem; font-size: 0.7rem; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo $row['page_title']; ?>
                </div>
                <?php if ($row['is_cover']) : ?>
                    <span style="position: absolute; top: 5px; right: 5px; background: var(--accent); color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px;">Portada</span>
                <?php endif; ?>
            </div>
            <?php
        }
        ?>
    </div>
</div>

<?php adminFooter(); ?>
