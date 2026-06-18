<?php
// admin/restaurantes.php - Panel de administración de Bares y Restaurantes
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';
require_once 'inc/image_helper.php';

$pdo = getDB();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// ─── TOGGLE VISIBILIDAD RÁPIDA (AJAX) ─────────────────────────────────────
if ($action === 'toggle_visible' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE restaurantes SET is_visible = 1 - is_visible WHERE id = ?")->execute([$id]);
    $row = $pdo->prepare("SELECT is_visible FROM restaurantes WHERE id = ?")->execute([$id]);
    $vis = $pdo->prepare("SELECT is_visible FROM restaurantes WHERE id = ?")->execute([$id]);
    $pdo->prepare("SELECT is_visible FROM restaurantes WHERE id = ?")->execute([$id]);
    $stmt2 = $pdo->prepare("SELECT is_visible FROM restaurantes WHERE id = ?"); $stmt2->execute([$id]);
    $v = $stmt2->fetchColumn();
    header('Content-Type: application/json');
    echo json_encode(['visible' => (int)$v]);
    exit;
}

// ─── GUARDAR / ACTUALIZAR RESTAURANTE ─────────────────────────────────────
if ($action === 'save') {
    $id          = isset($_POST['id'])          ? (int)$_POST['id']                     : 0;
    $nombre      = trim($_POST['nombre']     ?? '');
    $calle       = trim($_POST['calle']      ?? '');
    $poblacion   = trim($_POST['poblacion']  ?? '');
    $es_pedania  = isset($_POST['es_pedania'])  ? 1 : 0;
    $municipio   = trim($_POST['municipio']  ?? 'Moratalla');
    $provincia   = trim($_POST['provincia']  ?? 'Murcia');
    $cp          = trim($_POST['codigo_postal'] ?? '');
    $tel1        = trim($_POST['telefono1']  ?? '');
    $tel2        = trim($_POST['telefono2']  ?? '');
    $web         = trim($_POST['web']        ?? '');
    $facebook    = trim($_POST['facebook']   ?? '');
    $tripadvisor = trim($_POST['tripadvisor']?? '');
    $gmap_url    = trim($_POST['gmap_url']   ?? '');
    $descripcion = trim($_POST['descripcion']?? '');
    $sort_order  = (int)($_POST['sort_order'] ?? 0);
    $is_visible  = isset($_POST['is_visible'])  ? 1 : 0;

    if ($id) {
        $pdo->prepare("
            UPDATE restaurantes SET nombre=?, calle=?, poblacion=?, es_pedania=?, municipio=?, provincia=?,
            codigo_postal=?, telefono1=?, telefono2=?, web=?, facebook=?, tripadvisor=?, gmap_url=?,
            descripcion=?, sort_order=?, is_visible=? WHERE id=?
        ")->execute([$nombre, $calle, $poblacion, $es_pedania, $municipio, $provincia, $cp,
                     $tel1, $tel2, $web ?: null, $facebook ?: null, $tripadvisor ?: null, $gmap_url ?: null,
                     $descripcion ?: null, $sort_order, $is_visible, $id]);
        $msg = "Restaurante actualizado correctamente.";
    } else {
        $pdo->prepare("
            INSERT INTO restaurantes (nombre, calle, poblacion, es_pedania, municipio, provincia,
            codigo_postal, telefono1, telefono2, web, facebook, tripadvisor, gmap_url,
            descripcion, sort_order, is_visible)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([$nombre, $calle, $poblacion, $es_pedania, $municipio, $provincia, $cp,
                     $tel1, $tel2, $web ?: null, $facebook ?: null, $tripadvisor ?: null, $gmap_url ?: null,
                     $descripcion ?: null, $sort_order, $is_visible]);
        $id = $pdo->lastInsertId();
        $msg = "Restaurante creado correctamente.";
    }

    // Subir foto a galería (si se seleccionó)
    if (isset($_FILES['foto_nueva']) && $_FILES['foto_nueva']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/restaurantes/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename   = uniqid('rest_') . '_' . basename($_FILES['foto_nueva']['name']);
        $targetFile = $uploadDir . $filename;
        if (processUploadedImage($_FILES['foto_nueva']['tmp_name'], $targetFile, true, 1200, 85)) {
            $dbPath = 'uploads/restaurantes/' . $filename;
            // ¿Es la primera imagen? Si sí, poner como portada
            $countImg = $pdo->prepare("SELECT COUNT(*) FROM restaurante_images WHERE restaurante_id=?"); $countImg->execute([$id]);
            $esCover  = ($countImg->fetchColumn() == 0) ? 1 : 0;
            $pdo->prepare("INSERT INTO restaurante_images (restaurante_id, image_path, is_cover, is_visible, sort_order) VALUES (?,?,?,1,0)")
                ->execute([$id, $dbPath, $esCover]);
        }
    }

    header("Location: restaurantes.php?action=edit&id=$id&msg=" . urlencode($msg));
    exit;
}

// ─── GUARDAR GALERÍA ──────────────────────────────────────────────────────
if ($action === 'save_gallery') {
    $rid = (int)($_POST['restaurante_id'] ?? 0);
    $imgs_data = $_POST['images'] ?? [];
    if ($rid) {
        $stmt = $pdo->prepare("UPDATE restaurante_images SET caption=?, sort_order=?, is_visible=?, is_cover=? WHERE id=? AND restaurante_id=?");
        foreach ($imgs_data as $img_id => $data) {
            $caption    = $data['caption']    ?? '';
            $sort_order = (int)($data['sort_order'] ?? 0);
            $is_visible = isset($data['is_visible']) ? 1 : 0;
            $is_cover   = isset($data['is_cover'])   ? 1 : 0;
            // Si se marca como portada, desmarcar las demás
            if ($is_cover) {
                $pdo->prepare("UPDATE restaurante_images SET is_cover=0 WHERE restaurante_id=?")->execute([$rid]);
            }
            $stmt->execute([$caption, $sort_order, $is_visible, $is_cover, (int)$img_id, $rid]);
        }
    }
    header("Location: restaurantes.php?action=edit&id=$rid&msg=" . urlencode("Galería actualizada."));
    exit;
}

// ─── ELIMINAR IMAGEN ──────────────────────────────────────────────────────
if ($action === 'delete_img') {
    $img_id = (int)($_GET['img_id'] ?? 0);
    $rid    = (int)($_GET['rid']    ?? 0);
    if ($img_id && $rid) {
        $stmt = $pdo->prepare("SELECT image_path FROM restaurante_images WHERE id=?"); $stmt->execute([$img_id]);
        $img  = $stmt->fetch();
        if ($img && !empty($img['image_path']) && is_file('../' . $img['image_path'])) {
            @unlink('../' . $img['image_path']);
        }
        $pdo->prepare("DELETE FROM restaurante_images WHERE id=?")->execute([$img_id]);
    }
    header("Location: restaurantes.php?action=edit&id=$rid");
    exit;
}

// ─── ELIMINAR RESTAURANTE ─────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare("SELECT image_path FROM restaurante_images WHERE restaurante_id=?"); $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $img) {
            if (!empty($img['image_path']) && is_file('../' . $img['image_path'])) @unlink('../' . $img['image_path']);
        }
        $pdo->prepare("DELETE FROM restaurantes WHERE id=?")->execute([$id]);
    }
    header("Location: restaurantes.php?msg=" . urlencode("Establecimiento eliminado."));
    exit;
}

adminHeader("Bares y Restaurantes");
?>

<?php if ($action === 'list'): ?>
<!-- ════════════════════════════════════════════
     LISTADO
     ════════════════════════════════════════════ -->
<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem;">
        <h3><i class="fas fa-utensils"></i> Bares y Restaurantes</h3>
        <div style="display:flex; gap:0.75rem;">
            <a href="../restaurantes.php" target="_blank" class="btn btn-sm" style="background:#eee;"><i class="fas fa-eye"></i> Ver en web</a>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Añadir Nuevo</a>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div style="background:#e8f5e9; color:#2e7d32; padding:1rem; border-radius:8px; margin-bottom:1rem;">
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom:1rem;">
        <input type="text" id="searchInput" placeholder="Buscar por nombre, población..." style="width:100%; padding:0.8rem; border:1px solid var(--gray-300); border-radius:6px; font-size:1rem;">
    </div>

    <table id="restTable" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="border-bottom:2px solid var(--gray-200); text-align:left;">
                <th style="padding:0.75rem 1rem;">Nombre</th>
                <th>Población</th>
                <th>CP</th>
                <th>Teléfono</th>
                <th>Visible</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tableBody">
        <?php
        $rows = $pdo->query("SELECT * FROM restaurantes ORDER BY sort_order ASC, nombre ASC")->fetchAll();
        foreach ($rows as $r):
            $visBadge = $r['is_visible']
                ? '<span class="badge" style="background:#e8f5e9;color:#2e7d32;font-size:0.75rem;"><i class="fas fa-eye"></i> Visible</span>'
                : '<span class="badge" style="background:#ffebee;color:#c62828;font-size:0.75rem;"><i class="fas fa-eye-slash"></i> Oculto</span>';
        ?>
        <tr style="border-bottom:1px solid var(--gray-100);" id="tr-<?php echo $r['id']; ?>">
            <td style="padding:0.8rem 1rem;"><strong><?php echo htmlspecialchars($r['nombre']); ?></strong></td>
            <td>
                <?php echo htmlspecialchars($r['poblacion']); ?>
                <?php if ($r['es_pedania']): ?><span class="badge" style="background:#fff3e0;color:#e65100;font-size:0.7rem;">Pedanía</span><?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($r['codigo_postal']); ?></td>
            <td><?php echo htmlspecialchars($r['telefono1']); ?></td>
            <td>
                <button onclick="toggleVisible(<?php echo $r['id']; ?>)" id="vis-btn-<?php echo $r['id']; ?>" class="btn btn-sm" style="background:none;border:none;cursor:pointer;padding:0;">
                    <?php echo $visBadge; ?>
                </button>
            </td>
            <td style="white-space:nowrap;">
                <a href="?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Editar</a>
                <a href="?action=delete&id=<?php echo $r['id']; ?>" onclick="return confirm('¿Eliminar este establecimiento y todas sus fotos?');" class="btn btn-sm" style="background:#ef4444;color:white;"><i class="fas fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#tableBody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
});

async function toggleVisible(id) {
    const resp = await fetch('restaurantes.php?action=toggle_visible&id=' + id);
    const data = await resp.json();
    const btn = document.getElementById('vis-btn-' + id);
    if (data.visible) {
        btn.innerHTML = '<span class="badge" style="background:#e8f5e9;color:#2e7d32;font-size:0.75rem;"><i class="fas fa-eye"></i> Visible</span>';
    } else {
        btn.innerHTML = '<span class="badge" style="background:#ffebee;color:#c62828;font-size:0.75rem;"><i class="fas fa-eye-slash"></i> Oculto</span>';
    }
}
</script>

<?php elseif ($action === 'edit' || $action === 'add'):
    $r = ['id'=>'','nombre'=>'','calle'=>'','poblacion'=>'Moratalla','es_pedania'=>0,'municipio'=>'Moratalla','provincia'=>'Murcia',
          'codigo_postal'=>'30440','telefono1'=>'','telefono2'=>'','web'=>'','facebook'=>'','tripadvisor'=>'','gmap_url'=>'',
          'descripcion'=>'','sort_order'=>0,'is_visible'=>1];
    if ($action === 'edit') {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM restaurantes WHERE id=?"); $stmt->execute([$id]);
        $r = $stmt->fetch();
    }
?>
<!-- ════════════════════════════════════════════
     FORMULARIO EDICIÓN / ALTA
     ════════════════════════════════════════════ -->
<?php if (isset($_GET['msg'])): ?>
    <div style="background:#e8f5e9;color:#2e7d32;padding:1rem;border-radius:8px;margin-bottom:1rem;">
        <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
<?php endif; ?>

<div style="display:flex; gap:2rem; flex-wrap:wrap;">

    <!-- ── Formulario principal ── -->
    <div class="card" style="flex:2; min-width:400px;">
        <h3><?php echo $action === 'add' ? 'Añadir Establecimiento' : 'Editar: ' . htmlspecialchars($r['nombre']); ?></h3>
        <form method="POST" action="?action=save" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem;">
                <div style="grid-column:1/-1;">
                    <label class="flabel">Nombre del Establecimiento *</label>
                    <input type="text" name="nombre" required value="<?php echo htmlspecialchars($r['nombre']); ?>" class="finput">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="flabel">Calle / Dirección</label>
                    <input type="text" name="calle" value="<?php echo htmlspecialchars($r['calle']); ?>" class="finput">
                </div>
                <div>
                    <label class="flabel">Población *</label>
                    <input type="text" name="poblacion" required value="<?php echo htmlspecialchars($r['poblacion']); ?>" class="finput">
                </div>
                <div>
                    <label class="flabel">Código Postal</label>
                    <input type="text" name="codigo_postal" value="<?php echo htmlspecialchars($r['codigo_postal']); ?>" class="finput">
                </div>
                <div>
                    <label class="flabel">Municipio</label>
                    <input type="text" name="municipio" value="<?php echo htmlspecialchars($r['municipio']); ?>" class="finput">
                </div>
                <div>
                    <label class="flabel">Provincia</label>
                    <input type="text" name="provincia" value="<?php echo htmlspecialchars($r['provincia']); ?>" class="finput">
                </div>
                <div>
                    <label class="flabel">Teléfono 1</label>
                    <input type="text" name="telefono1" value="<?php echo htmlspecialchars($r['telefono1']); ?>" class="finput">
                </div>
                <div>
                    <label class="flabel">Teléfono 2</label>
                    <input type="text" name="telefono2" value="<?php echo htmlspecialchars($r['telefono2'] ?? ''); ?>" class="finput">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="flabel">Web oficial</label>
                    <input type="url" name="web" value="<?php echo htmlspecialchars($r['web'] ?? ''); ?>" class="finput" placeholder="https://...">
                </div>
                <div>
                    <label class="flabel">Facebook</label>
                    <input type="url" name="facebook" value="<?php echo htmlspecialchars($r['facebook'] ?? ''); ?>" class="finput" placeholder="https://facebook.com/...">
                </div>
                <div>
                    <label class="flabel">TripAdvisor</label>
                    <input type="url" name="tripadvisor" value="<?php echo htmlspecialchars($r['tripadvisor'] ?? ''); ?>" class="finput" placeholder="https://tripadvisor.es/...">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="flabel">URL Google Maps</label>
                    <input type="url" name="gmap_url" value="<?php echo htmlspecialchars($r['gmap_url'] ?? ''); ?>" class="finput" placeholder="https://maps.google.com/...">
                </div>
                <div style="grid-column:1/-1;">
                    <label class="flabel">Descripción</label>
                    <textarea name="descripcion" rows="4" class="finput" style="height:auto; resize:vertical;"><?php echo htmlspecialchars($r['descripcion'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label class="flabel">Orden de visualización</label>
                    <input type="number" name="sort_order" value="<?php echo (int)$r['sort_order']; ?>" class="finput">
                </div>
                <div style="display:flex; align-items:center; gap:10px; padding-top:1.5rem;">
                    <input type="checkbox" name="es_pedania" id="es_pedania" value="1" <?php echo $r['es_pedania'] ? 'checked' : ''; ?> style="transform:scale(1.3);">
                    <label for="es_pedania" class="flabel" style="margin:0; cursor:pointer;">Es Pedanía</label>
                </div>
            </div>

            <div style="margin-bottom:1.5rem; display:flex; align-items:center; gap:10px;">
                <input type="checkbox" name="is_visible" id="is_visible" value="1" <?php echo $r['is_visible'] ? 'checked' : ''; ?> style="transform:scale(1.3); cursor:pointer;">
                <label for="is_visible" style="font-weight:600; color:var(--primary); cursor:pointer;">Visible en la web</label>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin:1.5rem 0;">

            <h4><i class="fas fa-camera"></i> Subir foto a la galería</h4>
            <p style="font-size:0.85rem; color:#666; margin-bottom:1rem;">La primera foto subida se asigna automáticamente como portada.</p>
            <input type="file" name="foto_nueva" accept="image/*" style="padding:0.5rem; margin-bottom:1.5rem;">

            <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary" style="font-size:1.05rem; padding:0.9rem 2rem;"><i class="fas fa-save"></i> Guardar</button>
                <a href="restaurantes.php" class="btn" style="background:var(--gray-200);">Volver al listado</a>
                <?php if ($action === 'edit'): ?>
                    <a href="../restaurantes.php" target="_blank" class="btn" style="background:#e8f5e9; color:#2e7d32;"><i class="fas fa-eye"></i> Ver en web</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── Galería ── -->
    <?php if ($action === 'edit'): ?>
    <div class="card" style="flex:1; min-width:360px; background:#f9f9f9;">
        <h3><i class="fas fa-images"></i> Galería de Fotos</h3>
        <form method="POST" action="?action=save_gallery">
            <input type="hidden" name="restaurante_id" value="<?php echo $id; ?>">
            <div style="display:flex; flex-direction:column; gap:1rem; margin-bottom:1.5rem;">
                <?php
                $iStmt = $pdo->prepare("SELECT * FROM restaurante_images WHERE restaurante_id=? ORDER BY sort_order ASC, id ASC");
                $iStmt->execute([$id]);
                $images = $iStmt->fetchAll();
                if (count($images) === 0) {
                    echo '<p style="color:#888; font-style:italic;">No hay fotos todavía. Usa el formulario de la izquierda para subir la primera.</p>';
                }
                foreach ($images as $img):
                    $isVisible = $img['is_visible'] ? 'checked' : '';
                    $isCover   = $img['is_cover']   ? 'checked' : '';
                ?>
                <div style="background:white; border:1px solid #ddd; border-radius:8px; padding:0.9rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <div style="display:flex; gap:1rem;">
                        <div style="flex-shrink:0;">
                            <img src="../<?php echo htmlspecialchars($img['image_path']); ?>" style="width:80px; height:80px; object-fit:cover; border-radius:6px;">
                            <?php if ($img['is_cover']): ?><div style="text-align:center; font-size:0.7rem; color:#e65100; font-weight:700; margin-top:3px;">★ Portada</div><?php endif; ?>
                        </div>
                        <div style="flex:1; display:flex; flex-direction:column; gap:0.5rem;">
                            <div style="display:flex; gap:1rem;">
                                <div style="flex:1;">
                                    <label style="font-size:0.78rem; font-weight:600; display:block;">Orden</label>
                                    <input type="number" name="images[<?php echo $img['id']; ?>][sort_order]" value="<?php echo $img['sort_order']; ?>" style="width:100%; padding:0.35rem; border:1px solid #ccc; border-radius:4px;">
                                </div>
                                <div style="display:flex; flex-direction:column; gap:0.4rem; padding-top:1.2rem;">
                                    <label style="font-size:0.8rem; display:flex; align-items:center; gap:5px; cursor:pointer;">
                                        <input type="checkbox" name="images[<?php echo $img['id']; ?>][is_visible]" value="1" <?php echo $isVisible; ?>> Visible
                                    </label>
                                    <label style="font-size:0.8rem; display:flex; align-items:center; gap:5px; cursor:pointer;">
                                        <input type="checkbox" name="images[<?php echo $img['id']; ?>][is_cover]" value="1" <?php echo $isCover; ?>> Portada
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label style="font-size:0.78rem; font-weight:600; display:block;">Pie de foto</label>
                        <input type="text" name="images[<?php echo $img['id']; ?>][caption]" value="<?php echo htmlspecialchars($img['caption'] ?? ''); ?>" style="width:100%; padding:0.35rem; border:1px solid #ccc; border-radius:4px; font-size:0.88rem;" placeholder="Descripción...">
                    </div>
                    <div style="text-align:right; padding-top:0.4rem; border-top:1px solid #f0f0f0;">
                        <a href="?action=delete_img&img_id=<?php echo $img['id']; ?>&rid=<?php echo $id; ?>" onclick="return confirm('¿Eliminar esta foto definitivamente?');" style="color:#d32f2f; font-size:0.82rem; font-weight:600; text-decoration:none;">
                            <i class="fas fa-trash"></i> Eliminar foto
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (count($images) > 0): ?>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:0.8rem; background:#10b981;"><i class="fas fa-save"></i> Guardar cambios de galería</button>
            <?php endif; ?>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
.flabel { display:block; margin-bottom:0.35rem; font-weight:600; font-size:0.88rem; color:#444; }
.finput  { width:100%; padding:0.72rem 0.9rem; border:1px solid var(--gray-300); border-radius:6px; font-size:0.95rem; box-sizing:border-box; }
.finput:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(27,67,50,0.12); }
</style>

<?php endif;
adminFooter(); ?>
