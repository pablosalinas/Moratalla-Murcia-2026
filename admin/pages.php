<?php
// admin/pages.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';
require_once 'inc/image_helper.php';

$pdo = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Función para generar slugs limpios
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = str_replace(
        ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ü','Ü'],
        ['a','e','i','o','u','a','e','i','o','u','n','n','u','u'],
        $text
    );
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

// Procesado de guardado
if ($action == 'save') {
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    
    if (empty($category_id)) $category_id = null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE pages SET title=?, category_id=?, content=?, sort_order=?, is_visible=? WHERE id=?");
        $stmt->execute([$title, $category_id, $content, $sort_order, $is_visible, $id]);
        $msg = "Página actualizada.";
    } else {
        $slug = slugify($title);
        // Evitar duplicados
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE slug = ?");
        $stmtCheck->execute([$slug]);
        if ($stmtCheck->fetchColumn() > 0) {
            $slug .= '-' . rand(100, 999);
        }
        
        $stmt = $pdo->prepare("INSERT INTO pages (title, category_id, content, original_file, slug, sort_order, is_visible) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category_id, $content, 'nuevo_admin.html', $slug, $sort_order, $is_visible]);
        $id = $pdo->lastInsertId();
        $msg = "Página creada.";
    }
    
    // Subida de foto o vídeo para galería (si hay)
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/galerias/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        $filename = uniqid() . '_' . basename($_FILES['gallery_image']['name']);
        $targetFile = $uploadDir . $filename;
        
        $ext = strtolower(pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION));
        $isVid = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']) ? 1 : 0;
        
        if ($isVid) {
            $uploaded = processUploadedVideo($_FILES['gallery_image']['tmp_name'], $targetFile, true);
        } else {
            $uploaded = processUploadedImage($_FILES['gallery_image']['tmp_name'], $targetFile, true, 1200, 85);
        }

        if ($uploaded) {
            $dbPath = 'uploads/galerias/' . $filename;
            $stmtImg = $pdo->prepare("INSERT INTO page_images (page_id, image_path, is_cover, is_video) VALUES (?, ?, 0, ?)");
            $stmtImg->execute([$id, $dbPath, $isVid]);
        }
    }
    
    header("Location: pages.php?action=edit&id=$id&msg=" . urlencode($msg));
    exit;
}

if ($action == 'save_gallery') {
    $page_id = isset($_POST['page_id']) ? $_POST['page_id'] : null;
    $images_data = isset($_POST['images']) ? $_POST['images'] : [];
    
    if ($page_id) {
        $stmtUpdate = $pdo->prepare("UPDATE page_images SET caption = ?, sort_order = ?, is_visible = ? WHERE id = ? AND page_id = ?");
        foreach ($images_data as $img_id => $data) {
            $caption = isset($data['caption']) ? $data['caption'] : '';
            $sort_order = (int)(isset($data['sort_order']) ? $data['sort_order'] : 0);
            $is_visible = isset($data['is_visible']) ? 1 : 0;
            $stmtUpdate->execute([$caption, $sort_order, $is_visible, $img_id, $page_id]);
        }
    }
    header("Location: pages.php?action=edit&id=$page_id&msg=" . urlencode("Galería actualizada"));
    exit;
}

if ($action == 'delete_img') {
    $img_id = isset($_GET['img_id']) ? $_GET['img_id'] : null;
    $page_id = isset($_GET['page_id']) ? $_GET['page_id'] : null;
    if ($img_id && $page_id) {
        $stmt = $pdo->prepare("SELECT image_path FROM page_images WHERE id=?");
        $stmt->execute([$img_id]);
        $img = $stmt->fetch();
        if ($img && !empty($img['image_path']) && is_file('../' . $img['image_path'])) {
            @unlink('../' . $img['image_path']);
        }
        $pdo->prepare("DELETE FROM page_images WHERE id=?")->execute([$img_id]);
    }
    header("Location: pages.php?action=edit&id=$page_id");
    exit;
}

if ($action == 'delete') {
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    if ($id) {
        // Borrar imágenes físicas y de base de datos
        $stmt = $pdo->prepare("SELECT image_path FROM page_images WHERE page_id=?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        foreach ($images as $img) {
            if (!empty($img['image_path']) && is_file('../' . $img['image_path'])) {
                @unlink('../' . $img['image_path']);
            }
        }
        $pdo->prepare("DELETE FROM page_images WHERE page_id=?")->execute([$id]);
        
        // Borrar página
        $pdo->prepare("DELETE FROM pages WHERE id=?")->execute([$id]);
    }
    header("Location: pages.php?msg=" . urlencode("Página borrada correctamente."));
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

        <div style="margin-bottom: 1rem;">
            <input type="text" id="searchInput" placeholder="Buscar por título o categoría..." style="width: 100%; padding: 0.8rem; border: 1px solid var(--gray-300); border-radius: 6px; font-size: 1rem;">
        </div>

        <table id="pagesTable" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--gray-200); text-align: left;">
                    <th style="padding: 1rem;">Título</th>
                    <th>Categoría</th>
                    <th>Visitas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php
                $stmt = $pdo->query("SELECT p.*, c.name as cat_name FROM pages p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC, p.title ASC");
                while ($row = $stmt->fetch()) {
                    $visBadge = $row['is_visible'] ? '<span class="badge" style="background: #e8f5e9; color: #2e7d32; font-size: 0.75rem;"><i class="fas fa-eye"></i> Visible</span>' : '<span class="badge" style="background: #ffebee; color: #c62828; font-size: 0.75rem;"><i class="fas fa-eye-slash"></i> Oculta</span>';
                    echo "<tr style='border-bottom: 1px solid var(--gray-100);'>";
                    echo "<td style='padding: 1rem;'><strong>{$row['title']}</strong><br>{$visBadge}</td>";
                    echo "<td><span class='badge badge-info'>{$row['cat_name']}</span></td>";
                    echo "<td>" . number_format((int)$row['views'], 0, ',', '.') . "</td>";
                    echo "<td>
                            <a href='?action=edit&id={$row['id']}' class='btn btn-sm btn-primary'>Editar y Galería</a>
                            <a href='../page.php?id={$row['id']}' target='_blank' class='btn btn-sm' style='background: #eee;'><i class='fas fa-eye'></i> Ver</a>
                            <a href='?action=delete&id={$row['id']}' onclick=\"return confirm('¿Estás totalmente seguro de que quieres borrar esta página y todas sus fotos? Esta acción no se puede deshacer.');\" class='btn btn-sm' style='background: #ef4444; color: white;'><i class='fas fa-trash'></i> Borrar</a>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const term = this.value.toLowerCase();
                const rows = document.querySelectorAll('#tableBody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        }
    });
    </script>
    <?php
} else if ($action == 'edit' || $action == 'add') {
    $page = ['id' => '', 'title' => '', 'category_id' => '', 'content' => '', 'sort_order' => 0, 'is_visible' => 1];
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
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Orden de Visualización</label>
                    <input type="number" name="sort_order" required value="<?php echo (int)($page['sort_order']); ?>" style="width: 100%; padding: 0.8rem; border: 1px solid var(--gray-300); border-radius: 6px;">
                    <small style="color: #666; display: block; margin-top: 0.4rem;">Define la posición de esta página en el menú desplegable. Se ordena de menor a mayor.</small>
                </div>
                
                <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_visible" id="is_visible" value="1" <?php echo ($page['is_visible'] ? 'checked' : ''); ?> style="transform: scale(1.3); cursor: pointer;">
                    <label for="is_visible" style="font-weight: 600; color: var(--primary); cursor: pointer; user-select: none;">¿Página visible al público?</label>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label style="font-weight: 600; margin: 0;">Contenido (HTML) / Biografía</label>
                        <button type="button" class="btn btn-sm" style="background: var(--gray-200); color: #333; font-size: 0.85rem;" onclick="loadTemplate()">
                            <i class="fas fa-file-code"></i> Cargar Plantilla Base
                        </button>
                    </div>
                    <textarea id="page_content" name="content" style="width: 100%; height: 300px; padding: 1rem; border: 1px solid var(--gray-300); border-radius: 6px; font-family: monospace;"><?php echo htmlspecialchars($page['content']); ?></textarea>
                </div>
                
                <hr style="border: 0; border-top: 1px solid #eee; margin: 2rem 0;">
                
                <h4><i class="fas fa-images"></i> Añadir Foto o Vídeo a la Galería</h4>
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Si seleccionas un archivo aquí, se subirá y se adjuntará automáticamente a la galería de esta página al guardar.</p>
                <div style="margin-bottom: 1.5rem;">
                    <input type="file" name="gallery_image" accept="image/*,video/*" style="padding: 0.5rem;">
                </div>

                <button type="submit" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;"><i class="fas fa-save"></i> Guardar Todo</button>
                <a href="pages.php" class="btn" style="background: var(--gray-200);">Volver</a>
            </form>
        </div>

        <!-- Galería Existente -->
        <?php if ($action == 'edit'): ?>
            <div class="card" style="flex: 1; min-width: 400px; background: #f9f9f9;">
                <h3><i class="fas fa-camera"></i> Galería Multimedia Actual</h3>
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Obras, cuadros, fotos o vídeos adjudicados a esta página.</p>
                
                <form method="POST" action="?action=save_gallery">
                    <input type="hidden" name="page_id" value="<?php echo $id; ?>">
                    <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
                        <?php
                        $iStmt = $pdo->prepare("SELECT * FROM page_images WHERE page_id = ? ORDER BY sort_order ASC, id ASC");
                        $iStmt->execute([$id]);
                        $images = $iStmt->fetchAll();
                        
                        if (count($images) == 0) {
                            echo "<p style='color: #888; font-style: italic;'>No hay archivos en la galería.</p>";
                        }
                        
                        foreach ($images as $img) {
                            $isVisible = $img['is_visible'] ? 'checked' : '';
                            ?>
                            <div style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                                <div style="display: flex; gap: 1rem;">
                                    <div style="display: flex; flex-direction: column; align-items: center; max-width: 120px; flex-shrink: 0;">
                                        <?php if ($img['is_video']): ?>
                                            <video src="../<?php echo htmlspecialchars($img['image_path']); ?>" style="width: 120px; height: 80px; object-fit: cover; border-radius: 4px; background: #000;" controls preload="metadata"></video>
                                        <?php else: ?>
                                            <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                                        <?php endif; ?>
                                        <div style="font-family: monospace; font-size: 0.65rem; color: #666; margin-top: 0.3rem; word-break: break-all; text-align: center; line-height: 1.1;">
                                            /<?php echo htmlspecialchars($img['image_path']); ?>
                                        </div>
                                    </div>
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
                                    <label style="font-size: 0.8rem; font-weight: 600; display: block;">Descripción / Pie de foto</label>
                                    <textarea name="images[<?php echo $img['id']; ?>][caption]" style="width: 100%; height: 60px; padding: 0.4rem; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; font-size: 0.9rem;"><?php echo htmlspecialchars(isset($img['caption']) ? $img['caption'] : ''); ?></textarea>
                                </div>
                                <div style="text-align: right; padding-top: 0.5rem; border-top: 1px solid #f0f0f0; margin-top: 0.5rem;">
                                    <a href="?action=delete_img&img_id=<?php echo $img['id']; ?>&page_id=<?php echo $id; ?>" onclick="return confirm('¿Eliminar este archivo?');" style="color: #d32f2f; font-size: 0.85rem; text-decoration: none; font-weight: 600;">
                                        <i class="fas fa-trash"></i> Eliminar Archivo
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

    <script>
    function loadTemplate() {
        const ta = document.getElementById('page_content');
        if (ta.value.trim() !== '') {
            if (!confirm('El área de contenido no está vacía. ¿Sobrescribir con la plantilla base? Perderás lo que hayas escrito.')) {
                return;
            }
        }
        
        const template = `<div style="max-width: 800px; margin: 0 auto;">
    <h2 style="color: var(--primary); font-size: 2rem; margin-bottom: 1rem; border-bottom: 2px solid var(--accent); padding-bottom: 0.5rem;">[TÍTULO PRINCIPAL]</h2>
    
    <div style="font-size: 1.1rem; line-height: 1.8; color: #444; margin-bottom: 2rem;">
        <p>[Escribe aquí el primer párrafo de descripción o biografía...]</p>
        <p>[Escribe aquí el segundo párrafo...]</p>
    </div>
    
    <div style="background: var(--bg-alt); padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--primary); margin-bottom: 2rem;">
        <h4 style="margin-top: 0; color: var(--primary); margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> Información de Interés</h4>
        <p style="margin: 0;">[Añade aquí detalles técnicos, ubicación o información extra]</p>
    </div>
    
    <div style="text-align: center; margin-top: 3rem;">
        <a href="[URL_DEL_ENLACE]" target="_blank" rel="noopener" class="btn-nav" style="display: inline-block; padding: 1rem 2rem; background: var(--primary); color: white; border-radius: 30px; text-decoration: none; font-weight: 600;">
            <i class="fas fa-external-link-alt"></i> [TEXTO DEL BOTÓN]
        </a>
    </div>
</div>`;
        
        ta.value = template;
    }
    </script>
    <?php
}
adminFooter(); ?>
