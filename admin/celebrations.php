<?php
// admin/celebrations.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name'] ?? '');
            $html = trim($_POST['html_content'] ?? '');
            $css = trim($_POST['css_content'] ?? '');
            $js = trim($_POST['js_content'] ?? '');
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($name) {
                $stmt = $pdo->prepare("INSERT INTO celebrations (name, is_active, start_date, end_date, html_content, css_content, js_content) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $is_active, $start_date, $end_date, $html, $css, $js])) {
                    $message = '<div class="alert alert-success">Celebración añadida correctamente.</div>';
                }
            } else {
                $message = '<div class="alert alert-danger">El nombre es obligatorio.</div>';
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $html = trim($_POST['html_content'] ?? '');
            $css = trim($_POST['css_content'] ?? '');
            $js = trim($_POST['js_content'] ?? '');
            $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
            $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
            
            if ($id && $name) {
                $stmt = $pdo->prepare("UPDATE celebrations SET name = ?, start_date = ?, end_date = ?, html_content = ?, css_content = ?, js_content = ? WHERE id = ?");
                if ($stmt->execute([$name, $start_date, $end_date, $html, $css, $js, $id])) {
                    $message = '<div class="alert alert-success">Celebración actualizada correctamente.</div>';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM celebrations WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = '<div class="alert alert-success">Celebración eliminada correctamente.</div>';
                }
            }
        } elseif ($_POST['action'] === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $is_active = (int)($_POST['is_active'] ?? 0);
            if ($id) {
                $stmt = $pdo->prepare("UPDATE celebrations SET is_active = ? WHERE id = ?");
                $stmt->execute([$is_active, $id]);
                echo json_encode(['success' => true]);
                exit;
            }
        }
    }
}

adminHeader("Acontecimientos y Celebraciones");

$editMode = false;
$editRow = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM celebrations WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $editRow = $stmt->fetch();
    if ($editRow) {
        $editMode = true;
    }
}
?>

<style>
    .switch { position: relative; display: inline-block; width: 50px; height: 26px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider-toggle { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
    .slider-toggle:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
    input:checked + .slider-toggle { background-color: var(--primary); }
    input:checked + .slider-toggle:before { transform: translateX(24px); }
</style>

<div class="header-admin" style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; color: var(--primary);">Acontecimientos y Celebraciones</h1>
    <p style="color: var(--text-light);">Gestiona efectos visuales para fechas o eventos especiales (Mundiales, Navidad, Fiestas Locales, etc).</p>
</div>

<?php echo $message; ?>

<?php if ($editMode): ?>
    <div class="card">
        <h3>Editar Celebración: <?php echo htmlspecialchars($editRow['name']); ?></h3>
        <form method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?php echo $editRow['id']; ?>">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Nombre del evento</label>
                <input type="text" name="name" class="form-control" style="width: 100%; padding: 0.5rem;" value="<?php echo htmlspecialchars($editRow['name']); ?>" required>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label>Fecha y hora de inicio (Opcional)</label>
                    <input type="datetime-local" name="start_date" class="form-control" style="width: 100%; padding: 0.5rem;" value="<?php echo $editRow['start_date'] ? date('Y-m-d\TH:i', strtotime($editRow['start_date'])) : ''; ?>">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Fecha y hora de fin (Opcional)</label>
                    <input type="datetime-local" name="end_date" class="form-control" style="width: 100%; padding: 0.5rem;" value="<?php echo $editRow['end_date'] ? date('Y-m-d\TH:i', strtotime($editRow['end_date'])) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Código HTML</label>
                <textarea name="html_content" class="form-control" style="width: 100%; height: 150px; padding: 0.5rem; font-family: monospace;"><?php echo htmlspecialchars($editRow['html_content']); ?></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Código CSS (Sin etiquetas &lt;style&gt;)</label>
                <textarea name="css_content" class="form-control" style="width: 100%; height: 150px; padding: 0.5rem; font-family: monospace;"><?php echo htmlspecialchars($editRow['css_content']); ?></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Código JS (Sin etiquetas &lt;script&gt;)</label>
                <textarea name="js_content" class="form-control" style="width: 100%; height: 150px; padding: 0.5rem; font-family: monospace;"><?php echo htmlspecialchars($editRow['js_content']); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
                <a href="celebrations.php" class="btn" style="background: #f3f4f6; color: #333;">Cancelar</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="card">
        <h3>Añadir Nuevo Evento</h3>
        <form method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="action" value="add">
            
            <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label>Nombre del evento</label>
                    <input type="text" name="name" class="form-control" style="width: 100%; padding: 0.5rem;" placeholder="Ej. Navidad 2026" required>
                </div>
                <div class="form-group" style="margin-top: 1.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1">
                        Activar inmediatamente
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label>Fecha y hora de inicio (Opcional)</label>
                    <input type="datetime-local" name="start_date" class="form-control" style="width: 100%; padding: 0.5rem;">
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Fecha y hora de fin (Opcional)</label>
                    <input type="datetime-local" name="end_date" class="form-control" style="width: 100%; padding: 0.5rem;">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Código HTML (Opcional)</label>
                <textarea name="html_content" class="form-control" style="width: 100%; height: 100px; padding: 0.5rem; font-family: monospace;" placeholder="<div id='navidad'>Feliz Navidad</div>"></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Código CSS (Opcional - Sin etiquetas &lt;style&gt;)</label>
                <textarea name="css_content" class="form-control" style="width: 100%; height: 100px; padding: 0.5rem; font-family: monospace;" placeholder="#navidad { color: red; }"></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label>Código JS (Opcional - Sin etiquetas &lt;script&gt;)</label>
                <textarea name="js_content" class="form-control" style="width: 100%; height: 100px; padding: 0.5rem; font-family: monospace;" placeholder="console.log('Navidad activa');"></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Crear Evento</button>
        </form>
    </div>

    <div class="card" style="margin-top: 2rem;">
        <h3>Eventos y Celebraciones Registrados</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 1rem; border-bottom: 2px solid var(--gray-200);">Nombre</th>
                    <th style="text-align: center; padding: 1rem; border-bottom: 2px solid var(--gray-200);">Estado (On/Off)</th>
                    <th style="text-align: right; padding: 1rem; border-bottom: 2px solid var(--gray-200);">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM celebrations ORDER BY id DESC");
                while ($row = $stmt->fetch()):
                ?>
                <tr>
                    <td style="padding: 1rem; border-bottom: 1px solid var(--gray-200); font-weight: 600;">
                        <?php echo htmlspecialchars($row['name']); ?>
                        <div style="font-size: 0.8rem; color: #6b7280; font-weight: normal; margin-top: 4px;">
                            <?php 
                            if ($row['start_date'] || $row['end_date']) {
                                echo "Rango: ";
                                echo $row['start_date'] ? date('d/m/Y H:i', strtotime($row['start_date'])) : 'Siempre';
                                echo " - ";
                                echo $row['end_date'] ? date('d/m/Y H:i', strtotime($row['end_date'])) : 'Siempre';
                            } else {
                                echo "Rango: Sin límite";
                            }
                            ?>
                        </div>
                    </td>
                    <td style="padding: 1rem; border-bottom: 1px solid var(--gray-200); text-align: center;">
                        <label class="switch">
                            <input type="checkbox" class="toggle-celebration" data-id="<?php echo $row['id']; ?>" <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                            <span class="slider-toggle"></span>
                        </label>
                    </td>
                    <td style="padding: 1rem; border-bottom: 1px solid var(--gray-200); text-align: right;">
                        <a href="?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm" style="background: #3b82f6; color: white; padding: 6px 12px; margin-right: 5px;"><i class="fas fa-edit"></i> Editar</a>
                        
                        <form method="POST" style="display: inline-block;" onsubmit="return confirm('¿Seguro que deseas eliminar este evento?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn btn-sm" style="background: #ef4444; color: white; padding: 6px 12px; border: none; cursor: pointer;"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
    document.querySelectorAll('.toggle-celebration').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const id = this.getAttribute('data-id');
            const value = this.checked ? 1 : 0;
            
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('id', id);
            formData.append('is_active', value);
            
            fetch('celebrations.php', {
                method: 'POST',
                body: formData
            }).then(response => response.json())
              .then(data => {
                  if(!data.success) {
                      alert('Error al actualizar el estado.');
                      this.checked = !this.checked; // revert
                  }
              });
        });
    });
    </script>
<?php endif; ?>

<?php adminFooter(); ?>
