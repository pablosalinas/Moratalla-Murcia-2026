<?php
// admin/pages.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';

// Procesado de guardado
if ($action == 'save') {
    $id = $_POST['id'] ?? '';
    $title = $_POST['title'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $content = $_POST['content'] ?? '';
    
    if (empty($category_id)) $category_id = null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE pages SET title=?, category_id=?, content=? WHERE id=?");
        $stmt->execute([$title, $category_id, $content, $id]);
        $msg = "Página actualizada.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO pages (title, category_id, content, original_file) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $category_id, $content, 'nuevo_admin.html']);
        $id = $pdo->lastInsertId();
        $msg = "Página creada.";
    }
    
    // Subida de imagen para galería (si hay)
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/galerias/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $filename = uniqid() . '_' . basename($_FILES['gallery_image']['name']);
        $targetFile = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['gallery_image']['tmp_name'], $targetFile)) {
            // Aplicar marca de agua
            $watermarkText = 'www.moratalla-murcia.com';
            $info = getimagesize($targetFile);
            if ($info !== false) {
                $mime = $info['mime'];
                $imgRes = null;
                switch ($mime) {
                    case 'image/jpeg': $imgRes = imagecreatefromjpeg($targetFile); break;
                    case 'image/png': $imgRes = imagecreatefrompng($targetFile); break;
                    case 'image/gif': $imgRes = imagecreatefromgif($targetFile); break;
                }
                
                if ($imgRes) {
                    $fontSize = 5; // Fuente más grande nativa de GD (1 a 5)
                    $width = imagesx($imgRes);
                    $height = imagesy($imgRes);
                    $textColor = imagecolorallocate($imgRes, 255, 255, 255); // Blanco
                    $shadowColor = imagecolorallocate($imgRes, 0, 0, 0); // Sombra negra
                    
                    // Posición esquina inferior derecha
                    $textWidth = imagefontwidth($fontSize) * strlen($watermarkText);
                    $textHeight = imagefontheight($fontSize);
                    $x = $width - $textWidth - 10;
                    $y = $height - $textHeight - 10;
                    
                    // Sombra
                    imagestring($imgRes, $fontSize, $x + 1, $y + 1, $watermarkText, $shadowColor);
                    // Texto blanco
                    imagestring($imgRes, $fontSize, $x, $y, $watermarkText, $textColor);
                    
                    switch ($mime) {
                        case 'image/jpeg': imagejpeg($imgRes, $targetFile, 90); break;
                        case 'image/png': imagepng($imgRes, $targetFile); break;
                        case 'image/gif': imagegif($imgRes, $targetFile); break;
                    }
                    imagedestroy($imgRes);
                }
            }

            $dbPath = 'uploads/galerias/' . $filename;
            $stmtImg = $pdo->prepare("INSERT INTO page_images (page_id, image_path, is_cover) VALUES (?, ?, 0)");
            $stmtImg->execute([$id, $dbPath]);
        }
    }
    
    header("Location: pages.php?action=edit&id=$id&msg=" . urlencode($msg));
    exit;
}

if ($action == 'save_gallery') {
    $page_id = $_POST['page_id'] ?? null;
    $images_data = $_POST['images'] ?? [];
    
    if ($page_id) {
        $stmtUpdate = $pdo->prepare("UPDATE page_images SET caption = ?, sort_order = ?, is_visible = ? WHERE id = ? AND page_id = ?");
        foreach ($images_data as $img_id => $data) {
            $caption = $data['caption'] ?? '';
            $sort_order = (int)($data['sort_order'] ?? 0);
            $is_visible = isset($data['is_visible']) ? 1 : 0;
            $stmtUpdate->execute([$caption, $sort_order, $is_visible, $img_id, $page_id]);
        }
    }
    header("Location: pages.php?action=edit&id=$page_id&msg=" . urlencode("Galería actualizada"));
    exit;
}

if ($action == 'delete_img') {
    $img_id = $_GET['img_id'] ?? null;
    $page_id = $_GET['page_id'] ?? null;
    if ($img_id && $page_id) {
        $stmt = $pdo->prepare("SELECT image_path FROM page_images WHERE id=?");
        $stmt->execute([$img_id]);
        $img = $stmt->fetch();
        if ($img && file_exists('../' . $img['image_path'])) {
            unlink('../' . $img['image_path']);
        }
        $pdo->prepare("DELETE FROM page_images WHERE id=?")->execute([$img_id]);
    }
    header("Location: pages.php?action=edit&id=$page_id");
    exit;
}

adminHeader("Gestión de Páginas (y Artesanos)");

if ($action == 'list') {
    ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h3>Todas las Páginas</h3>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Añadir Nueva Página</a>
        </div>

        <table id="pagesTable" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--gray-200); text-align: left;">
                    <th style="padding: 1rem;">Título</th>
                    <th>Categoría</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT p.*, c.name as cat_name FROM pages p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC, p.title ASC LIMIT 100");
                while ($row = $stmt->fetch()) {
                    echo "<tr style='border-bottom: 1px solid var(--gray-100);'>";
                    echo "<td style='padding: 1rem;'><strong>{$row['title']}</strong></td>";
                    echo "<td><span class='badge badge-info'>{$row['cat_name']}</span></td>";
                    echo "<td>
                            <a href='?action=edit&id={$row['id']}' class='btn btn-sm btn-primary'>Editar y Galería</a>
                            <a href='../page.php?id={$row['id']}' target='_blank' class='btn btn-sm' style='background: #eee;'><i class='fas fa-eye'></i> Ver</a>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
} else if ($action == 'edit' || $action == 'add') {
    $page = ['id' => '', 'title' => '', 'category_id' => '', 'content' => ''];
    if ($action == 'edit') {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$id]);
        $page = $stmt->fetch();
    }
    
    // Obtener categorías para el select
    $cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
    ?>
    
    <?php if (isset($_GET['msg'])): ?>
        <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
        <!-- Formulario Principal -->
        <div class="card" style="flex: 2; min-width: 400px;">
            <h3><?php echo $action == 'add' ? 'Añadir Nueva Página' : 'Editar Página: ' . htmlspecialchars($page['title']); ?></h3>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1.5rem;">Utiliza este formulario para crear páginas de contenido o registrar nuevos Artesanos.</p>
            
            <form method="POST" action="?action=save" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Título / Nombre del Artesano</label>
                    <input type="text" name="title" required value="<?php echo htmlspecialchars($page['title']); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid var(--gray-300); border-radius: 6px;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Categoría padre</label>
                    <select name="category_id" style="width: 100%; padding: 0.8rem; border: 1px solid var(--gray-300); border-radius: 6px;">
                        <option value="">-- Sin Categoría --</option>
                        <?php foreach($cats as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $page['category_id'] ? 'selected' : ''); ?>><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Contenido (HTML) / Biografía</label>
                    <textarea name="content" style="width: 100%; height: 300px; padding: 1rem; border: 1px solid var(--gray-300); border-radius: 6px; font-family: monospace;"><?php echo htmlspecialchars($page['content']); ?></textarea>
                </div>
                
                <hr style="border: 0; border-top: 1px solid #eee; margin: 2rem 0;">
                
                <h4><i class="fas fa-images"></i> Añadir Foto a la Galería</h4>
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Si seleccionas una imagen aquí, se subirá y se adjuntará automáticamente a la galería de esta página al guardar.</p>
                <div style="margin-bottom: 1.5rem;">
                    <input type="file" name="gallery_image" accept="image/*" style="padding: 0.5rem;">
                </div>

                <button type="submit" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;"><i class="fas fa-save"></i> Guardar Todo</button>
                <a href="pages.php" class="btn" style="background: var(--gray-200);">Volver</a>
            </form>
        </div>

        <!-- Galería Existente -->
        <?php if ($action == 'edit'): ?>
            <div class="card" style="flex: 1; min-width: 400px; background: #f9f9f9;">
                <h3><i class="fas fa-camera"></i> Galería Actual</h3>
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Obras, cuadros o fotos adjudicadas a esta página.</p>
                
                <form method="POST" action="?action=save_gallery">
                    <input type="hidden" name="page_id" value="<?php echo $id; ?>">
                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                        <?php
                        $iStmt = $pdo->prepare("SELECT * FROM page_images WHERE page_id = ? ORDER BY sort_order ASC, id ASC");
                        $iStmt->execute([$id]);
                        $images = $iStmt->fetchAll();
                        
                        if (count($images) == 0) {
                            echo "<p style='color: #888; font-style: italic;'>No hay fotos en la galería.</p>";
                        }
                        
                        foreach ($images as $img) {
                            $isVisible = $img['is_visible'] ? 'checked' : '';
                            ?>
                            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                <div style="display: flex; gap: 1rem;">
                                    <img src="../<?php echo $img['image_path']; ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                                    <div style="flex: 1; display: flex; flex-direction: column; gap: 0.5rem;">
                                        <div style="display: flex; gap: 1rem; align-items: center;">
                                            <div style="flex: 1;">
                                                <label style="font-size: 0.8rem; font-weight: 600; display: block;">Orden</label>
                                                <input type="number" name="images[<?php echo $img['id']; ?>][sort_order]" value="<?php echo $img['sort_order']; ?>" style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px;">
                                            </div>
                                            <div style="flex: 1; text-align: center;">
                                                <label style="font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.2rem;">Visible</label>
                                                <input type="checkbox" name="images[<?php echo $img['id']; ?>][is_visible]" value="1" <?php echo $isVisible; ?> style="transform: scale(1.5);">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size: 0.8rem; font-weight: 600; display: block;">Descripción / Título de la obra</label>
                                    <textarea name="images[<?php echo $img['id']; ?>][caption]" style="width: 100%; height: 60px; padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; font-size: 0.9rem;"><?php echo htmlspecialchars($img['caption'] ?? ''); ?></textarea>
                                </div>
                                <div style="text-align: right; padding-top: 0.5rem; border-top: 1px solid #f0f0f0; margin-top: 0.5rem;">
                                    <a href="?action=delete_img&img_id=<?php echo $img['id']; ?>&page_id=<?php echo $id; ?>" onclick="return confirm('¿Eliminar esta foto?');" style="color: #d32f2f; font-size: 0.85rem; text-decoration: none; font-weight: 600;">
                                        <i class="fas fa-trash"></i> Eliminar Foto
                                    </a>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php if (count($images) > 0): ?>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.8rem; background: #10b981; border: none;"><i class="fas fa-save"></i> Guardar Cambios de Galería</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
adminFooter(); ?>
