<?php
// admin/banners.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
$message = '';

// Procesar Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'upload') {
            if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
                $ext = pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION);
                $filename = 'banner_' . time() . '.' . $ext;
                $targetDir = '../uploads/banners/';
                $target = $targetDir . $filename;
                $dbPath = 'uploads/banners/' . $filename;

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target)) {
                    $stmt = $pdo->prepare("INSERT INTO banners (image_path, title, sort_order, is_active) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$dbPath, $_POST['title'] ?? '', (int)($_POST['sort_order'] ?? 0)]);
                    $message = '<div class="alert alert-success">Banner subido correctamente.</div>';
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
                // Intentar borrar archivo físico solo si la ruta no está vacía y el archivo existe
                if (!empty($banner['image_path'])) {
                    $fullPath = '../' . $banner['image_path'];
                    if (file_exists($fullPath) && is_file($fullPath)) {
                        @unlink($fullPath);
                    }
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
                <input type="file" name="banner_image" class="form-control" required accept="image/*">
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
    <form method="POST" style="display: flex; align-items: end; gap: 2rem; margin-top: 1rem;">
        <input type="hidden" name="action" value="save_settings">
        <div class="form-group" style="flex: 1;">
            <label>Tiempo entre transiciones (milisegundos)</label>
            <input type="number" name="banner_speed" class="form-control" value="<?php echo htmlspecialchars($currentBannerSpeed); ?>" step="500" min="1000">
            <small style="color: #666;">1000 ms = 1 segundo. Recomendado: 5000.</small>
        </div>
        <button type="submit" class="btn btn-primary" style="height: 42px;">
            <i class="fas fa-save"></i> Guardar Tiempo
        </button>
    </form>
</div>

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
                <div style="position: relative; height: 180px;">
                    <img src="../<?php echo $row['image_path']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
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
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que deseas eliminar este banner?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-sm" style="color: #ef4444; background: #fef2f2; border: none; padding: 8px 12px; border-radius: 6px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
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
</style>

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
        
        // Show subtle success indicator
        this.style.background = '#e6ffed';
        setTimeout(() => this.style.background = 'transparent', 1000);
    });
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
