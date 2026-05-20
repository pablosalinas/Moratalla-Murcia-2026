<?php
// admin/news.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$msg = "";
$error = "";

// PROCESAR ACCIONES DE GUARDADO (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $id = $_POST['id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
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
            
            // Subida de imagen
            if (isset($_FILES['news_image']) && $_FILES['news_image']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/news/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                // Si ya había imagen, borrarla
                if ($image_path && file_exists('../' . $image_path)) {
                    @unlink('../' . $image_path);
                }
                
                $filename = uniqid('news_') . '_' . basename($_FILES['news_image']['name']);
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['news_image']['tmp_name'], $targetFile)) {
                    $image_path = 'uploads/news/' . $filename;
                    
                    // Aplicar marca de agua si GD está disponible
                    $watermarkText = 'www.moratalla-murcia.com';
                    $info = @getimagesize($targetFile);
                    if ($info !== false) {
                        $mime = $info['mime'];
                        $imgRes = null;
                        switch ($mime) {
                            case 'image/jpeg': $imgRes = @imagecreatefromjpeg($targetFile); break;
                            case 'image/png': $imgRes = @imagecreatefrompng($targetFile); break;
                            case 'image/gif': $imgRes = @imagecreatefromgif($targetFile); break;
                        }
                        
                        if ($imgRes) {
                            $fontSize = 5;
                            $width = imagesx($imgRes);
                            $height = imagesy($imgRes);
                            $textColor = imagecolorallocate($imgRes, 255, 255, 255);
                            $shadowColor = imagecolorallocate($imgRes, 0, 0, 0);
                            
                            $textWidth = imagefontwidth($fontSize) * strlen($watermarkText);
                            $textHeight = imagefontheight($fontSize);
                            $x = $width - $textWidth - 15;
                            $y = $height - $textHeight - 15;
                            
                            if ($x > 0 && $y > 0) {
                                imagestring($imgRes, $fontSize, $x + 1, $y + 1, $watermarkText, $shadowColor);
                                imagestring($imgRes, $fontSize, $x, $y, $watermarkText, $textColor);
                            }
                            
                            switch ($mime) {
                                case 'image/jpeg': @imagejpeg($imgRes, $targetFile, 90); break;
                                case 'image/png': @imagepng($imgRes, $targetFile); break;
                                case 'image/gif': @imagegif($imgRes, $targetFile); break;
                            }
                            @imagedestroy($imgRes);
                        }
                    }
                }
            }
            
            if ($action == 'add') {
                $stmt = $pdo->prepare("INSERT INTO news_events (title, content, image_path, event_date, is_active_home, category_id, is_active_category) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $content, $image_path, $event_date, $is_active_home, $category_id, $is_active_category]);
                $msg = "Noticia/Evento creado con éxito.";
                $action = 'list';
            } else {
                $stmt = $pdo->prepare("UPDATE news_events SET title = ?, content = ?, image_path = ?, event_date = ?, is_active_home = ?, category_id = ?, is_active_category = ? WHERE id = ?");
                $stmt->execute([$title, $content, $image_path, $event_date, $is_active_home, $category_id, $is_active_category, $id]);
                $msg = "Noticia/Evento actualizado con éxito.";
                $action = 'list';
            }
        }
    }
}

// PROCESAR ELIMINACIÓN (GET)
if ($action == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Obtener la imagen para borrarla físicamente
    $stmt = $pdo->prepare("SELECT image_path FROM news_events WHERE id = ?");
    $stmt->execute([$id]);
    $image_path = $stmt->fetchColumn();
    if ($image_path && file_exists('../' . $image_path)) {
        @unlink('../' . $image_path);
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
                                    <img src="../<?php echo htmlspecialchars($row['image_path']); ?>" style="width: 60px; height: 40px; object-fit: cover; border-radius: 4px;">
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
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary);">Imagen Principal</label>
                    <input type="file" name="news_image" accept="image/*" style="width:100%; padding:0.6rem; border:1px solid var(--gray-300); border-radius:8px; font-size: 1rem; background: white;">
                    <?php if ($news_data['image_path']): ?>
                        <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 10px;">
                            <img src="../<?php echo htmlspecialchars($news_data['image_path']); ?>" style="width: 80px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <small style="color: #666;">Imagen actual. Si subes otra, se reemplazará.</small>
                        </div>
                    <?php endif; ?>
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
            
            <div style="display: flex; gap: 10px; margin-top: 2.5rem; border-top: 1px solid var(--gray-200); padding-top: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="border: 2px solid var(--primary-dark);"><i class="fas fa-save"></i> Guardar</button>
                <a href="news.php" class="btn" style="background: var(--gray-200); color: var(--text);"><i class="fas fa-times"></i> Cancelar</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php adminFooter(); ?>
