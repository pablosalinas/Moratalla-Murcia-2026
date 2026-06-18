<?php
// admin/banners.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'upload') {
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
                $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']);
                
                if (!$isVideo && !in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $ext = 'jpg';
                }
                
                $baseFilename = 'banner_' . time();
                $targetDir = '../uploads/banners/';
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $tmpName = $_FILES['banner_image']['tmp_name'];
                
                if ($isVideo) {
                    $targetFile = $targetDir . $baseFilename . '.' . $ext;
                    if (move_uploaded_file($tmpName, $targetFile)) {
                        $dbPath = 'uploads/banners/' . $baseFilename . '.' . $ext;
                        $stmt = $pdo->prepare("INSERT INTO banners (image_path, title, sort_order, is_active) VALUES (?, ?, ?, 1)");
                        $stmt->execute([$dbPath, $_POST['title'] ?? '', (int)($_POST['sort_order'] ?? 0)]);
                        $message = '<div class="alert alert-success">Vídeo de banner subido con éxito.</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error al subir el vídeo del banner.</div>';
                    }
                } else {
                    require_once 'inc/image_helper.php';

                    $desktopFilename = $baseFilename . '_desktop.' . $ext;
                    $mobileFilename = $baseFilename . '_mobile.' . $ext;
                    
                    $desktopPath = $targetDir . $desktopFilename;
                    $mobilePath = $targetDir . $mobileFilename;
                    
                    // Hacer una copia del temp porque processUploadedImage borra el origen por defecto
                    $tmpNameMobile = $tmpName . '_mobile';
                    copy($tmpName, $tmpNameMobile);
                    
                    $successDesktop = processUploadedImage($tmpName, $desktopPath, true, 1920, 85);
                    $successMobile = processUploadedImage($tmpNameMobile, $mobilePath, true, 768, 80);
                    
                    // Si la principal falla, intentar un move básico (fallback extremo)
                    if (!$successDesktop && @move_uploaded_file($tmpName, $desktopPath)) {
                        $successDesktop = true;
                        @copy($desktopPath, $mobilePath);
                    }

                    if ($successDesktop) {
                        $dbPath = 'uploads/banners/' . $baseFilename . '.' . $ext;
                        
                        $stmt = $pdo->prepare("INSERT INTO banners (image_path, title, sort_order, is_active) VALUES (?, ?, ?, 1)");
                        $stmt->execute([$dbPath, $_POST['title'] ?? '', (int)($_POST['sort_order'] ?? 0)]);
                        $message = '<div class="alert alert-success">Banner subido y optimizado (Desktop/Móvil).</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error al procesar la imagen del banner.</div>';
                    }
                }
            }
        } elseif ($_POST['action'] === 'update_field') {
            $id = (int)$_POST['id'];
            $field = $_POST['field'];
            $value = $_POST['value'];
            
            if ($field === 'is_active' || $field === 'sort_order' || $field === 'title') {
                $stmt = $pdo->prepare("UPDATE banners SET $field = ? WHERE id = ?");
                $stmt->execute([$value, $id]);
                echo json_encode(['success' => true]);
                exit;
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT image_path FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            $banner = $stmt->fetch();
            if ($banner) {
                // Intentar borrar archivo físico solo si la ruta no está vacía
                if (!empty($banner['image_path'])) {
                    $fullPath = '../' . $banner['image_path'];
                    $baseExt = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $baseName = pathinfo($fullPath, PATHINFO_FILENAME);
                    $dirName = pathinfo($fullPath, PATHINFO_DIRNAME);
                    
                    $desktopPath = $dirName . '/' . $baseName . '_desktop.' . $baseExt;
                    $mobilePath = $dirName . '/' . $baseName . '_mobile.' . $baseExt;
                    
                    if (file_exists($fullPath) && is_file($fullPath)) @unlink($fullPath);
                    if (file_exists($desktopPath) && is_file($desktopPath)) @unlink($desktopPath);
                    if (file_exists($mobilePath) && is_file($mobilePath)) @unlink($mobilePath);
                }
                
                // Siempre proceder a borrar el registro de la base de datos
                $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
                $stmt->execute([$id]);
                $message = '<div class="alert alert-success">Registro de banner eliminado correctamente.</div>';
            }
        } elseif ($_POST['action'] === 'save_settings') {
            $speed = (int)$_POST['banner_speed'];
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('banner_speed', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$speed, $speed]);
            $message = '<div class="alert alert-success">Configuración del slider actualizada.</div>';
        }
    }
}

// Obtener velocidad actual
$stmtSpeed = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner_speed'");
$currentBannerSpeed = $stmtSpeed->fetchColumn() ?: '5000';

adminHeader("Gestión de Banners");
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3>Añadir Nuevo Banner</h3>
    </div>

    <form method="POST" enctype="multipart/form-data" class="form-horizontal">
        <input type="hidden" name="action" value="upload">
        <div style="display: grid; grid-template-columns: 1fr 1fr 100px auto; gap: 1rem; align-items: end;">
            <div class="form-group">
                <label>Imagen del Banner</label>
                <input type="file" name="banner_image" class="form-control" required accept="image/*,video/*">
            </div>
            <div class="form-group">
                <label>Título (Opcional)</label>
                <input type="text" name="title" class="form-control" placeholder="Ej: Paisaje de Moratalla">
            </div>
            <div class="form-group">
                <label>Orden</label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>
            <button type="submit" class="btn btn-primary" style="height: 42px;">
                <i class="fas fa-upload"></i> Subir Banner
            </button>
        </div>
    </form>
</div>

<div class="card" style="margin-top: 2rem;">
    <h3>Configuración del Slider</h3>
    <form method="POST" style="display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1rem;">
        <input type="hidden" name="action" value="save_settings">
        <div class="form-group" style="flex: 1;">
            <label style="display: flex; justify-content: space-between; align-items: center;">
                <span>Tiempo entre transiciones</span>
                <span style="background: var(--bg-alt); padding: 4px 12px; border-radius: 20px; font-weight: bold; color: var(--primary);" id="speedValueText"><?php echo number_format((int)$currentBannerSpeed / 1000, 1); ?> segundos</span>
            </label>
            <input type="range" name="banner_speed" id="bannerSpeedSlider" min="1000" max="15000" step="500" value="<?php echo htmlspecialchars($currentBannerSpeed); ?>" style="width: 100%; margin-top: 15px; cursor: pointer; accent-color: var(--primary);">
            <div style="display: flex; justify-content: space-between; margin-top: 8px; font-size: 0.8rem; color: #888;">
                <span>1s (Rápido)</span>
                <span>Recomendado: 5s</span>
                <span>15s (Lento)</span>
            </div>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Tiempo
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('bannerSpeedSlider').addEventListener('input', function() {
    const seconds = (this.value / 1000).toFixed(1);
    document.getElementById('speedValueText').textContent = seconds + ' segundos';
});
</script>

<?php echo $message; ?>

<div class="card" style="margin-top: 2rem;">
    <h3>Banners Existentes</h3>
    <p style="color: var(--text-light); margin-bottom: 2rem;">Activa o desactiva los banners que se mostrarán en la página principal.</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
        <?php
        $stmt = $pdo->query("SELECT * FROM banners ORDER BY sort_order ASC, id DESC");
        while ($row = $stmt->fetch()) {
            ?>
            <div class="banner-card" style="background: white; border: 1px solid var(--gray-200); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <div style="position: relative; height: 180px; background: #000;">
                    <?php
                    $previewPath = $row['image_path'];
                    $baseExt = pathinfo($previewPath, PATHINFO_EXTENSION);
                    $isVideo = in_array($baseExt, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']);
                    if (!$isVideo) {
                        $baseName = pathinfo($previewPath, PATHINFO_FILENAME);
                        $dirName = pathinfo($previewPath, PATHINFO_DIRNAME);
                        $desktopPath = $dirName . '/' . $baseName . '_desktop.' . $baseExt;
                        if (file_exists('../' . $desktopPath)) {
                            $previewPath = $desktopPath;
                        }
                    }
                    ?>
                    <?php if ($isVideo): ?>
                        <video src="../<?php echo $previewPath; ?>" style="width: 100%; height: 100%; object-fit: cover;" controls preload="metadata"></video>
                    <?php else: ?>
                        <img src="../<?php echo $previewPath; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php endif; ?>
                    <div style="position: absolute; top: 10px; right: 10px; display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.9); padding: 5px 10px; border-radius: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <small style="font-weight: 700; font-size: 0.7rem; color: var(--primary);"><?php echo $row['is_active'] ? 'VISIBLE' : 'OCULTO'; ?></small>
                        <label class="switch">
                            <input type="checkbox" class="toggle-banner" data-id="<?php echo $row['id']; ?>" <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                            <span class="slider-toggle"></span>
                        </label>
                    </div>
                </div>
                <div style="padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; gap: 10px;">
                        <input type="text" class="update-title" data-id="<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['title']); ?>" placeholder="Añadir título/descripción..." style="flex: 1; padding: 4px; border: 1px solid transparent; border-radius: 4px; font-weight: 600; font-size: 0.9rem; background: transparent; transition: all 0.3s;" onfocus="this.style.border='1px solid var(--primary)'; this.style.background='white';" onblur="this.style.border='1px solid transparent'; this.style.background='transparent';">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <label style="font-size: 0.7rem; color: #888;">Orden:</label>
                            <input type="number" class="update-order" data-id="<?php echo $row['id']; ?>" value="<?php echo $row['sort_order']; ?>" style="width: 50px; padding: 2px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;">
                        </div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <small style="color: var(--text-light);"><?php echo $row['is_active'] ? '<span style="color: #10b981;">Activo</span>' : '<span style="color: #ef4444;">Inactivo</span>'; ?></small>
                        <button type="button" class="btn btn-sm btn-delete-banner" data-id="<?php echo $row['id']; ?>" style="color: #ef4444; background: #fef2f2; border: none; padding: 8px 12px; border-radius: 6px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
</div>

<style>
    .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider-toggle { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
    .slider-toggle:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider-toggle { background-color: var(--primary); }
    input:checked + .slider-toggle:before { transform: translateX(24px); }

    /* Modal de confirmación */
    #deleteModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; }
    #deleteModal.active { display:flex; }
    #deleteModal .modal-box { background:white; padding:2rem; border-radius:16px; max-width:400px; width:90%; text-align:center; box-shadow:0 20px 60px rgba(0,0,0,0.3); }
    #deleteModal h4 { margin:0 0 1rem; font-size:1.1rem; }
    #deleteModal p { color:#666; margin-bottom:1.5rem; font-size:0.9rem; }
    #deleteModal .modal-actions { display:flex; gap:1rem; justify-content:center; }
</style>

<!-- Modal de confirmación de borrado -->
<div id="deleteModal">
    <div class="modal-box">
        <h4>¿Eliminar este banner?</h4>
        <p>Esta acción borrará el registro de la base de datos. Si el archivo físico existe, también se eliminará.</p>
        <div class="modal-actions">
            <button id="cancelDelete" class="btn" style="background:#f3f4f6;">Cancelar</button>
            <form id="deleteForm" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId" value="">
                <button type="submit" class="btn btn-primary" style="background:#ef4444; border-color:#ef4444;">Sí, eliminar</button>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-banner').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const id = this.getAttribute('data-id');
        const value = this.checked ? 1 : 0;
        updateBannerField(id, 'is_active', value);
    });
});

document.querySelectorAll('.update-order').forEach(input => {
    input.addEventListener('change', function() {
        const id = this.getAttribute('data-id');
        const value = this.value;
        updateBannerField(id, 'sort_order', value);
    });
});

document.querySelectorAll('.update-title').forEach(input => {
    input.addEventListener('change', function() {
        const id = this.getAttribute('data-id');
        const value = this.value;
        updateBannerField(id, 'title', value);
        this.style.background = '#e6ffed';
        setTimeout(() => this.style.background = 'transparent', 1000);
    });
});

// Modal de confirmación de borrado (reemplaza window.confirm que puede ser bloqueado)
const deleteModal = document.getElementById('deleteModal');
const cancelDelete = document.getElementById('cancelDelete');
const deleteId = document.getElementById('deleteId');

document.querySelectorAll('.btn-delete-banner').forEach(btn => {
    btn.addEventListener('click', function() {
        deleteId.value = this.getAttribute('data-id');
        deleteModal.classList.add('active');
    });
});

cancelDelete.addEventListener('click', function() {
    deleteModal.classList.remove('active');
});

deleteModal.addEventListener('click', function(e) {
    if (e.target === deleteModal) deleteModal.classList.remove('active');
});

function updateBannerField(id, field, value) {
    const formData = new FormData();
    formData.append('action', 'update_field');
    formData.append('id', id);
    formData.append('field', field);
    formData.append('value', value);

    fetch('banners.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
          if (data.success) {
              // location.reload(); // Evitamos recarga para mejor UX, solo si el orden cambia mucho podrías recargar
              if (field === 'is_active') location.reload(); 
          }
      });
}
</script>

<?php adminFooter(); ?>
