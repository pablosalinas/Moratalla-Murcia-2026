<?php
// admin/news.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';
require_once 'inc/image_helper.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$msg = "";
$error = "";

// PROCESAR AJAX UPLOAD
if (isset($_POST['ajax_upload']) && isset($_POST['news_id'])) {
    $news_id = (int)$_POST['news_id'];
    $response = ['success' => false, 'files' => []];
    
    if (isset($_FILES['gallery_images'])) {
        $files = $_FILES['gallery_images'];
        $uploadDir = '../uploads/news/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] == UPLOAD_ERR_OK) {
                $filename = uniqid('newsg_') . '_' . basename($files['name'][$i]);
                $targetFile = $uploadDir . $filename;
                $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                $success = false;
                if ($fileExt === 'pdf') {
                    $success = move_uploaded_file($files['tmp_name'][$i], $targetFile);
                } else {
                    $success = processUploadedImage($files['tmp_name'][$i], $targetFile, true, 1200, 85);
                }
                
                if ($success) {
                    $dbPath = 'uploads/news/' . $filename;
                    $stmtImg = $pdo->prepare("INSERT INTO news_images (news_id, image_path, sort_order) VALUES (?, ?, ?)");
                    $stmtImg->execute([$news_id, $dbPath, 0]);
                    $newId = $pdo->lastInsertId();
                    
                    $response['files'][] = [
                        'id' => $newId,
                        'path' => $dbPath,
                        'is_pdf' => ($fileExt === 'pdf')
                    ];
                }
            }
        }
        if (count($response['files']) > 0) {
            $response['success'] = true;
        }
    }
    
    ob_clean(); // Limpiar cualquier output previo
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// PROCESAR ACCIONES DE GUARDADO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $id = $_POST['id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $image_caption = trim($_POST['image_caption'] ?? '');
        $event_date = $_POST['event_date'] ?? null;
        $is_active_home = isset($_POST['is_active_home']) ? 1 : 0;
        $category_id = $_POST['category_id'] ?? null;
        $is_active_category = isset($_POST['is_active_category']) ? 1 : 0;
        
        if (empty($event_date)) {
            $event_date = null;
        }
        if (empty($category_id)) {
            $category_id = null;
        }
        
        if (empty($title) || empty($content)) {
            $error = "El título y el contenido son obligatorios.";
        } else {
            // Guardar o Actualizar
            $image_path = null;
            
            // Si es edición, obtener la imagen existente primero
            if ($action == 'edit') {
                $stmt = $pdo->prepare("SELECT image_path FROM news_events WHERE id = ?");
                $stmt->execute([$id]);
                $image_path = $stmt->fetchColumn();
            }
            
            // Subida de imagen principal
            if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/news/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                // Si ya había imagen, borrarla
                if (!empty($image_path) && is_file('../' . $image_path)) {
                    @unlink('../' . $image_path);
                }
                
                $filename = uniqid('news_') . '_' . basename($_FILES['news_image']['name']);
                $targetFile = $uploadDir . $filename;
                
                $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if ($fileExt === 'pdf') {
                    if (move_uploaded_file($_FILES['news_image']['tmp_name'], $targetFile)) {
                        $image_path = 'uploads/news/' . $filename;
                    }
                } else {
                    if (processUploadedImage($_FILES['news_image']['tmp_name'], $targetFile, true, 1200, 85)) {
                        $image_path = 'uploads/news/' . $filename;
                    }
                }
            }
            
            if ($action == 'add') {
                $stmt = $pdo->prepare("INSERT INTO news_events (title, content, image_path, image_caption, event_date, is_active_home, category_id, is_active_category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $content, $image_path, $image_caption, $event_date, $is_active_home, $category_id, $is_active_category]);
                $news_id = $pdo->lastInsertId();
                $msg = "Noticia/Evento creado con éxito.";
            } else {
                $stmt = $pdo->prepare("UPDATE news_events SET title = ?, content = ?, image_path = ?, image_caption = ?, event_date = ?, is_active_home = ?, category_id = ?, is_active_category = ? WHERE id = ?");
                $stmt->execute([$title, $content, $image_path, $image_caption, $event_date, $is_active_home, $category_id, $is_active_category, $id]);
                $news_id = $id;
                $msg = "Noticia/Evento actualizado con éxito.";
            }

            // Procesar imágenes adicionales de la galería (para add y edit)
            if (isset($_FILES['gallery_images'])) {
                $files = $_FILES['gallery_images'];
                $uploadDir = '../uploads/news/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] == UPLOAD_ERR_OK) {
                        $filename = uniqid('newsg_') . '_' . basename($files['name'][$i]);
                        $targetFile = $uploadDir . $filename;
                        
                        $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        
                        $success = false;
                        if ($fileExt === 'pdf') {
                            $success = move_uploaded_file($files['tmp_name'][$i], $targetFile);
                        } else {
                            $success = processUploadedImage($files['tmp_name'][$i], $targetFile, true, 1200, 85);
                        }
                        
                        if ($success) {
                            $dbPath = 'uploads/news/' . $filename;
                            
                            $stmtImg = $pdo->prepare("INSERT INTO news_images (news_id, image_path, sort_order) VALUES (?, ?, ?)");
                            $stmtImg->execute([$news_id, $dbPath, 0]);
                        }
                    }
                }
            }

            // Actualizar órdenes y descripciones de la galería existente
            if (isset($_POST['sort_order']) && is_array($_POST['sort_order'])) {
                foreach ($_POST['sort_order'] as $imgId => $orderVal) {
                    $captionVal = isset($_POST['captions'][$imgId]) ? trim($_POST['captions'][$imgId]) : null;
                    $stmtOrder = $pdo->prepare("UPDATE news_images SET sort_order = ?, caption = ? WHERE id = ?");
                    $stmtOrder->execute([(int)$orderVal, $captionVal, (int)$imgId]);
                }
            }

            $action = 'list';
        }
    }
}

// PROCESAR ELIMINACIÓN DE IMAGEN DE GALERÍA (GET)
if ($action == 'delete_img' && isset($_GET['img_id']) && isset($_GET['news_id'])) {
    $img_id = $_GET['img_id'];
    $news_id = $_GET['news_id'];
    
    // Obtener ruta de la imagen
    $stmt = $pdo->prepare("SELECT image_path FROM news_images WHERE id = ?");
    $stmt->execute([$img_id]);
    $path = $stmt->fetchColumn();
    
    if (!empty($path) && is_file('../' . $path)) {
        @unlink('../' . $path);
    }
    
    $stmt = $pdo->prepare("DELETE FROM news_images WHERE id = ?");
    $stmt->execute([$img_id]);
    
    $msg = "Imagen eliminada de la galería.";
    header("Location: news.php?action=edit&id=" . $news_id);
    exit;
}

// PROCESAR ELIMINACIÓN (GET)
if ($action == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Obtener la imagen para borrarla físicamente
    $stmt = $pdo->prepare("SELECT image_path FROM news_events WHERE id = ?");
    $stmt->execute([$id]);
    $image_path = $stmt->fetchColumn();
    if (!empty($image_path) && is_file('../' . $image_path)) {
        @unlink('../' . $image_path);
    }
    
    // Borrar imágenes de la galería asociadas de la base de datos y disco
    $stmt = $pdo->prepare("SELECT image_path FROM news_images WHERE news_id = ?");
    $stmt->execute([$id]);
    $gallery_imgs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($gallery_imgs as $gpath) {
        if (!empty($gpath) && is_file('../' . $gpath)) {
            @unlink('../' . $gpath);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM news_events WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Noticia/Evento eliminado con éxito.";
    $action = 'list';
}

adminHeader("Noticias y Eventos");
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3>Gestor de Noticias y Eventos</h3>
                <p style="color: #666; font-size: 0.9rem; margin-top: 0.3rem;">Administra las noticias y eventos que aparecen en la página de inicio o en las subsecciones.</p>
            </div>
            <a href="?action=add" class="btn btn-primary" style="border: 2px solid var(--primary-dark);"><i class="fas fa-plus"></i> Nueva Noticia / Evento</a>
        </div>

        <div style="margin-bottom: 1rem;">
            <input type="text" id="searchInput" placeholder="Buscar por título, fecha o sección..." style="width: 100%; padding: 0.8rem; border: 1px solid var(--gray-300); border-radius: 6px; font-size: 1rem;">
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--gray-200); text-align: left;">
                        <th style="padding: 1rem;">Imagen</th>
                        <th style="padding: 1rem;">Título</th>
                        <th style="padding: 1rem;">Fecha Evento</th>
                        <th style="padding: 1rem;">Activa Inicio</th>
                        <th style="padding: 1rem;">Sección Asociada</th>
                        <th style="padding: 1rem;">Activa Sección</th>
                        <th style="padding: 1rem; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT ne.*, c.name as category_name FROM news_events ne LEFT JOIN categories c ON ne.category_id = c.id ORDER BY ne.id DESC");
                    $hasItems = false;
                    while ($row = $stmt->fetch()) {
                        $hasItems = true;
                        ?>
                        <tr style="border-bottom: 1px solid var(--gray-200); transition: background-color 0.2s;">
                            <td style="padding: 1rem;">
                                <?php if ($row['image_path']): ?>
                                    <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                        <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
                                        <div style="font-family: monospace; font-size: 0.6rem; color: #777; word-break: break-all; max-width: 120px; line-height: 1.1;">
                                            /<?php echo htmlspecialchars($row['image_path']); ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div style="width: 60px; height: 40px; background: var(--gray-200); display: flex; align-items: center; justify-content: center; border-radius: 4px; color: var(--text-light); font-size: 0.7rem;">Sin foto</div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; font-weight: 600; color: var(--primary);">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </td>
                            <td style="padding: 1rem; color: var(--text-light); font-size: 0.9rem;">
                                <?php echo $row['event_date'] ? date('d/m/Y', strtotime($row['event_date'])) : '<span style="color: var(--gray-400); font-style: italic;">No es evento</span>'; ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?php if ($row['is_active_home']): ?>
                                    <span class="badge" style="background: #e8f5e9; color: #2e7d32;"><i class="fas fa-check"></i> Sí</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #ffebee; color: #c62828;"><i class="fas fa-times"></i> No</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; font-size: 0.9rem;">
                                <?php echo $row['category_name'] ? htmlspecialchars($row['category_name']) : '<span style="color: var(--gray-400); font-style: italic;">Ninguna</span>'; ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?php if ($row['is_active_category']): ?>
                                    <span class="badge" style="background: #e8f5e9; color: #2e7d32;"><i class="fas fa-check"></i> Sí</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #ffebee; color: #c62828;"><i class="fas fa-times"></i> No</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; text-align: right; white-space: nowrap;">
                                <a href="?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-edit"></i> Modificar</a>
                                <a href="?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm" style="color: white; background: #e74c3c; padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="return confirm('¿Seguro que deseas eliminar esta noticia/evento?')"><i class="fas fa-trash"></i> Borrar</a>
                            </td>
                        </tr>
                        <?php
                    }
                    if (!$hasItems) {
                        echo "<tr><td colspan='7' style='text-align: center; padding: 3rem; color: var(--text-light); font-style: italic;'>No hay noticias o eventos creados todavía.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const term = this.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    // Solo ocultar si la fila tiene celdas de datos, no el mensaje de vacío
                    if(row.cells.length > 1) {
                        row.style.display = text.includes(term) ? '' : 'none';
                    }
                });
            });
        }
    });
    </script>

<?php elseif ($action == 'add' || $action == 'edit'): 
    $news_data = ['id' => '', 'title' => '', 'content' => '', 'image_path' => '', 'event_date' => '', 'is_active_home' => 1, 'category_id' => '', 'is_active_category' => 0];
    if ($action == 'edit' && isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM news_events WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $news_data = $stmt->fetch();
        if (!$news_data) {
            header("Location: news.php");
            exit;
        }
    }
    
    // Obtener categorías para dropdown
    $cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
?>
    <div class="card" style="max-width: 700px; margin: 0 auto;">
        <h3 style="color: var(--primary); margin-bottom: 0.5rem;">
            <?php echo $action == 'add' ? '<i class="fas fa-plus-circle"></i> Añadir Nueva Noticia / Evento' : '<i class="fas fa-edit"></i> Modificar Noticia / Evento'; ?>
        </h3>
        <p style="color: #666; font-size: 0.85rem; margin-bottom: 2rem;">Configura la información y dónde se mostrará este elemento.</p>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($news_data['id']); ?>">
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Título</label>
                <input type="text" name="title" required value="<?php echo htmlspecialchars($news_data['title']); ?>" style="width:100%; padding:0.8rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem;">
            </div>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Contenido</label>
                <textarea name="content" required style="width:100%; height: 200px; padding:0.8rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem; font-family: inherit;"><?php echo htmlspecialchars($news_data['content']); ?></textarea>
            </div>
            
            <div style="margin-bottom: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Fecha del Evento (Opcional)</label>
                    <input type="date" name="event_date" value="<?php echo htmlspecialchars($news_data['event_date'] ?? ''); ?>" style="width:100%; padding:0.8rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem; background: white;">
                    <small style="color: #666; display: block; margin-top: 0.4rem;">Indica la fecha si esta noticia corresponde a un evento futuro o pasado.</small>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Imagen Principal o Documento PDF</label>
                    <input type="file" name="news_image" accept="image/*,application/pdf" style="width:100%; padding:0.6rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem; background: white;">
                    <?php if ($news_data['image_path']): ?>
                        <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 10px;">
                            <?php if(strtolower(pathinfo($news_data['image_path'], PATHINFO_EXTENSION)) == 'pdf'): ?>
                                <div style="width: 80px; height: 50px; background: #e74c3c; color: white; display:flex; align-items:center; justify-content:center; border-radius: 4px;"><i class="fas fa-file-pdf fa-2x"></i></div>
                            <?php else: ?>
                                <img src="../<?php echo htmlspecialchars($news_data['image_path']); ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php endif; ?>
                            <small style="color: #666;">Archivo actual. Si subes otro, se reemplazará.</small>
                        </div>
                    <?php endif; ?>
                    <input type="text" name="image_caption" value="<?php echo htmlspecialchars($news_data['image_caption'] ?? ''); ?>" placeholder="Descripción o pie de foto (opcional)" style="width:100%; padding:0.6rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 0.9rem; margin-top: 0.8rem; background: white;">
                </div>
            </div>

            <div style="background: var(--gray-100); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--gray-200);">
                <h4 style="margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-globe"></i> Configuración de Visualización</h4>
                
                <div style="margin-bottom: 1.2rem; display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_active_home" id="is_active_home" value="1" <?php echo ($news_data['is_active_home'] ? 'checked' : ''); ?> style="transform: scale(1.3); cursor: pointer;">
                    <label for="is_active_home" style="font-weight: 600; color: var(--text); cursor: pointer; user-select: none;">Mostrar en la Página de Inicio</label>
                </div>
                
                <div style="border-top: 1px solid var(--gray-300); margin: 1rem 0; padding-top: 1rem;">
                    <div style="margin-bottom: 1rem;">
                        <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Vincular con una Categoría / Sección (Opcional)</label>
                        <select name="category_id" style="width:100%; padding:0.8rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem; background: white;">
                            <option value="">-- No vincular a ninguna categoría --</option>
                            <?php foreach($cats as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $news_data['category_id'] ? 'selected' : ''); ?>><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_active_category" id="is_active_category" value="1" <?php echo ($news_data['is_active_category'] ? 'checked' : ''); ?> style="transform: scale(1.3); cursor: pointer;">
                        <label for="is_active_category" style="font-weight: 600; color: var(--text); cursor: pointer; user-select: none;">Mostrar en la categoría seleccionada (cuando no esté visible o activa en Inicio)</label>
                    </div>
                </div>
            </div>
            
            <!-- Galería de Imágenes Adicionales -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--gray-200);">
                <h4 style="margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-images"></i> Galería de Imágenes Adicionales</h4>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Subir Imágenes o PDFs de Galería</label>
                    <input type="file" id="ajaxGalleryUpload" name="gallery_images[]" multiple accept="image/*,application/pdf" style="width:100%; padding:0.6rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem; background: white;">
                    <small style="color: #666; display: block; margin-top: 0.4rem;">Puedes seleccionar múltiples archivos para la galería de esta noticia (las imágenes serán procesadas con marca de agua, los PDFs se adjuntarán tal cual).</small>
                    <div id="ajaxUploadProgress" style="display:none; margin-top:10px; color:var(--primary); font-size:0.95rem; font-weight:bold;"><i class="fas fa-spinner fa-spin"></i> Subiendo y procesando archivos, por favor espere...</div>
                </div>
                
                <?php if ($action == 'edit' && !empty($news_data['id'])): ?>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Imágenes de la Galería Actual (Ajustar Orden y Borrar)</label>
                    <div id="galleryGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 1rem; margin-top: 1rem;">
                        <?php 
                        $stmtImg = $pdo->prepare("SELECT * FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id ASC");
                        $stmtImg->execute([$news_data['id']]);
                        $gallery = $stmtImg->fetchAll();
                        foreach ($gallery as $gimg): ?>
                            <div style="border: 1px solid var(--gray-200); border-radius: 8px; padding: 0.5rem; background: var(--gray-100); text-align: center; position: relative;">
                                <?php if(strtolower(pathinfo($gimg['image_path'], PATHINFO_EXTENSION)) == 'pdf'): ?>
                                    <div style="width: 100%; height: 80px; background: #e74c3c; color: white; display:flex; align-items:center; justify-content:center; border-radius: 4px; margin-bottom: 0.2rem;"><i class="fas fa-file-pdf fa-2x"></i></div>
                                <?php else: ?>
                                    <img src="../<?php echo htmlspecialchars($gimg['image_path']); ?>" style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 0.2rem;">
                                <?php endif; ?>
                                <div style="font-family: monospace; font-size: 0.6rem; color: #666; word-break: break-all; text-align: center; line-height: 1.1; margin-bottom: 0.5rem;">
                                    /<?php echo htmlspecialchars($gimg['image_path']); ?>
                                </div>
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 5px; margin-bottom: 0.3rem;">
                                    <span style="font-size: 0.75rem; color: var(--text-light);">Orden:</span>
                                    <input type="number" name="sort_order[<?php echo $gimg['id']; ?>]" value="<?php echo (int)$gimg['sort_order']; ?>" style="width: 50px; padding: 2px 4px; font-size: 0.75rem; border: 1px solid var(--gray-300); border-radius: 4px; text-align: center;">
                                </div>
                                <input type="text" name="captions[<?php echo $gimg['id']; ?>]" value="<?php echo htmlspecialchars($gimg['caption'] ?? ''); ?>" placeholder="Descripción" style="width: 100%; padding: 4px; font-size: 0.75rem; border: 1px solid var(--gray-300); border-radius: 4px; text-align: center;">
                                <a href="news.php?action=delete_img&img_id=<?php echo $gimg['id']; ?>&news_id=<?php echo $news_data['id']; ?>" 
                                   style="position: absolute; top: -8px; right: -8px; background: #e74c3c; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; text-decoration: none;" 
                                   onclick="return confirm('¿Eliminar esta imagen de la galería?')">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 2.5rem; border-top: 1px solid var(--gray-200); padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="border: 2px solid var(--primary-dark);"><i class="fas fa-save"></i> Guardar</button>
                <a href="news.php" class="btn" style="background: var(--gray-200); color: var(--text);"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
    
    <script>
    document.getElementById('ajaxGalleryUpload').addEventListener('change', function(e) {
        const newsId = '<?php echo $news_data['id'] ?? ''; ?>';
        // Si no hay newsId es que estamos creando una nueva noticia, en ese caso 
        // las imágenes se subirán al darle a Guardar Todo por el form normal.
        if (!newsId) return; 
        
        const files = e.target.files;
        if (files.length === 0) return;
        
        const formData = new FormData();
        formData.append('ajax_upload', '1');
        formData.append('news_id', newsId);
        
        for (let i = 0; i < files.length; i++) {
            formData.append('gallery_images[]', files[i]);
        }
        
        const progress = document.getElementById('ajaxUploadProgress');
        progress.style.display = 'block';
        
        fetch('news.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            progress.style.display = 'none';
            if (data.success) {
                // Insertar nuevas miniaturas al vuelo sin recargar la página
                const grid = document.getElementById('galleryGrid');
                if (grid) {
                    data.files.forEach(file => {
                        let content = '';
                        if (file.is_pdf) {
                            content = '<div style="width: 100%; height: 80px; background: #e74c3c; color: white; display:flex; align-items:center; justify-content:center; border-radius: 4px; margin-bottom: 0.5rem;"><i class="fas fa-file-pdf fa-2x"></i></div>';
                        } else {
                            content = '<img src="../' + file.path + '" style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem;">';
                        }
                        
                        const div = document.createElement('div');
                        div.style.cssText = 'border: 1px solid #10b981; border-radius: 8px; padding: 0.5rem; background: #ecfdf5; text-align: center; position: relative; animation: highlight 2s ease-out;';
                        div.innerHTML = content + 
                            '<div style="display: flex; align-items: center; justify-content: space-between; gap: 5px; margin-bottom: 0.3rem;">' +
                            '<span style="font-size: 0.75rem; color: var(--text-light);">Orden:</span>' +
                            '<input type="number" name="sort_order[' + file.id + ']" value="0" style="width: 50px; padding: 2px 4px; font-size: 0.75rem; border: 1px solid var(--gray-300); border-radius: 4px; text-align: center;">' +
                            '</div>' +
                            '<input type="text" name="captions[' + file.id + ']" value="" placeholder="Descripción" style="width: 100%; padding: 4px; font-size: 0.75rem; border: 1px solid var(--gray-300); border-radius: 4px; text-align: center;">' +
                            '<a href="news.php?action=delete_img&img_id=' + file.id + '&news_id=' + newsId + '" style="position: absolute; top: -8px; right: -8px; background: #e74c3c; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; text-decoration: none;" onclick="return confirm(\'¿Eliminar esta imagen de la galería?\')"><i class="fas fa-times"></i></a>';
                        
                        grid.appendChild(div);
                    });
                }
                
                // Limpiar input para permitir subir la misma imagen si hubo error
                e.target.value = '';
                
                // Mostrar alert opcional o feedback toast si hubiera
                alert('¡Archivos subidos y adjuntados con éxito a la galería!');
            } else {
                alert('Hubo un problema al subir los archivos.');
            }
        })
        .catch(err => {
            progress.style.display = 'none';
            console.error(err);
            alert('Error de conexión al intentar subir los archivos.');
        });
    });
    </script>
    <style>
    @keyframes highlight {
        from { background: #6ee7b7; border-color: #059669; }
        to { background: #ecfdf5; border-color: #10b981; }
    }
    </style>
<?php endif; ?>

<?php adminFooter(); ?>
