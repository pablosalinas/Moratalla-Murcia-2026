<?php
// admin/external_links.php
require_once 'inc/auth.php';
require_once 'inc/layout.php';
require_once '../config.php';

$pdo = getDB();
$message = '';
$messageType = 'success';

// ─── Acciones POST ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $url         = trim($_POST['url'] ?? '');
        $is_visible  = isset($_POST['is_visible']) ? 1 : 0;
        $sort_order  = (int)($_POST['sort_order'] ?? 0);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $show_in_category = isset($_POST['show_in_category']) ? 1 : 0;

        if (empty($title) || empty($url)) {
            $message     = 'El título y la URL son obligatorios.';
            $messageType = 'error';
        } else {
            if ($action === 'create') {
                $pdo->prepare("INSERT INTO external_links (title, description, url, is_visible, sort_order, category_id, show_in_category) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$title, $description, $url, $is_visible, $sort_order, $category_id, $show_in_category]);
                $message = 'Acceso externo creado correctamente.';
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $pdo->prepare("UPDATE external_links SET title=?, description=?, url=?, is_visible=?, sort_order=?, category_id=?, show_in_category=? WHERE id=?")
                    ->execute([$title, $description, $url, $is_visible, $sort_order, $category_id, $show_in_category, $id]);
                $message = 'Acceso externo actualizado correctamente.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM external_links WHERE id=?")->execute([$id]);
        $message = 'Acceso externo eliminado.';
    }

    if ($action === 'toggle') {
        $id  = (int)($_POST['id'] ?? 0);
        $vis = (int)($_POST['current_visible'] ?? 1) ? 0 : 1;
        $pdo->prepare("UPDATE external_links SET is_visible=? WHERE id=?")->execute([$vis, $id]);
        header("Location: external_links.php");
        exit;
    }
}

// ─── Edición inline ───────────────────────────────────────────────────────────
$editItem = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM external_links WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $editItem = $stmt->fetch();
}

// ─── Obtener todos los registros ─────────────────────────────────────────────
$links = $pdo->query("SELECT el.*, c.name as cat_name FROM external_links el LEFT JOIN categories c ON el.category_id = c.id ORDER BY el.sort_order ASC, el.id ASC")->fetchAll();

// ─── Obtener categorías para el selector ─────────────────────────────────────
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

adminHeader("Accesos Externos / Curiosidades");
?>

<style>
    .el-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .el-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.2rem;
    }
    .el-form-grid .full { grid-column: 1 / -1; }
    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 0.4rem;
        font-size: 0.9rem;
        color: #444;
    }
    .form-group input[type="text"],
    .form-group input[type="url"],
    .form-group input[type="number"],
    .form-group textarea {
        width: 100%;
        padding: 0.85rem 1rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(27,67,50,0.1);
    }
    .toggle-switch {
        display: inline-flex;
        align-items: center;
        gap: 0.8rem;
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    .toggle-switch input { display: none; }
    .toggle-slider {
        width: 48px; height: 26px;
        background: #ccc;
        border-radius: 13px;
        position: relative;
        transition: background 0.3s;
        flex-shrink: 0;
    }
    .toggle-slider::after {
        content: '';
        position: absolute;
        left: 3px; top: 3px;
        width: 20px; height: 20px;
        background: white;
        border-radius: 50%;
        transition: left 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .toggle-switch input:checked + .toggle-slider { background: var(--primary); }
    .toggle-switch input:checked + .toggle-slider::after { left: 25px; }

    .links-table { width: 100%; border-collapse: collapse; }
    .links-table th {
        background: var(--primary);
        color: white;
        padding: 0.9rem 1rem;
        text-align: left;
        font-size: 0.88rem;
        font-weight: 600;
    }
    .links-table td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
        font-size: 0.93rem;
    }
    .links-table tr:hover td { background: #fafafa; }
    .links-table .url-cell {
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .links-table .url-cell a {
        color: var(--primary);
        text-decoration: none;
        font-size: 0.82rem;
    }
    .links-table .url-cell a:hover { text-decoration: underline; }
    .badge-visible   { background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .badge-hidden    { background: #fce4ec; color: #c62828; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
    .actions-cell { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .btn-icon {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.82rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s;
    }
    .btn-icon:hover { transform: translateY(-1px); }
    .btn-edit   { background: #e3f2fd; color: #1565c0; }
    .btn-toggle-on  { background: #fff9c4; color: #f57f17; }
    .btn-toggle-off { background: #e8f5e9; color: #2e7d32; }
    .btn-delete { background: #fce4ec; color: #c62828; }
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-light);
    }
    .empty-state i { font-size: 3rem; color: #ccc; display: block; margin-bottom: 1rem; }
    .msg-success { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #2e7d32; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
    .msg-error   { background: #fce4ec; color: #c62828; border-left: 4px solid #c62828; padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
</style>

<div class="card">
    <div class="el-header">
        <div>
            <h2><i class="fas fa-external-link-alt"></i> Accesos Externos / Curiosidades</h2>
            <p style="color: var(--text-light); margin-top: 0.3rem;">Gestiona los enlaces externos que aparecerán como «Curiosidades» en la web pública.</p>
        </div>
        <?php if (!$editItem): ?>
        <button class="btn btn-primary" onclick="document.getElementById('form-section').scrollIntoView({behavior:'smooth'})">
            <i class="fas fa-plus"></i> Nuevo Acceso
        </button>
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="msg-<?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- ─── Listado ───────────────────────────────────────────────────────── -->
    <?php if (count($links) > 0): ?>
    <div style="overflow-x: auto; margin-bottom: 2.5rem;">
        <table class="links-table">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>URL</th>
                    <th style="width: 100px;">Orden</th>
                    <th>Categoría</th>
                    <th style="width: 110px;">Visibilidad</th>
                    <th style="width: 200px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                <tr>
                    <td style="color: #aaa; font-weight: 600;"><?php echo $link['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($link['title']); ?></strong></td>
                    <td style="color: #666; font-size: 0.88rem;"><?php echo htmlspecialchars(mb_strimwidth($link['description'] ?? '', 0, 80, '...')); ?></td>
                    <td class="url-cell">
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" rel="noopener">
                            <i class="fas fa-external-link-alt" style="font-size: 0.75rem;"></i>
                            <?php echo htmlspecialchars($link['url']); ?>
                        </a>
                    </td>
                    <td style="text-align: center;"><?php echo (int)$link['sort_order']; ?></td>
                    <td>
                        <?php if ($link['category_id']): ?>
                            <span class="badge" style="background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem;"><?php echo htmlspecialchars($link['cat_name']); ?></span>
                            <?php if ($link['show_in_category']): ?>
                                <i class="fas fa-check-circle" style="color: #2e7d32; font-size: 0.85rem;" title="Visible en la categoría"></i>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #aaa; font-size: 0.85rem;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($link['is_visible']): ?>
                            <span class="badge-visible"><i class="fas fa-eye"></i> Visible</span>
                        <?php else: ?>
                            <span class="badge-hidden"><i class="fas fa-eye-slash"></i> Oculto</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions-cell">
                            <a href="external_links.php?action=edit&id=<?php echo $link['id']; ?>" class="btn-icon btn-edit">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                <input type="hidden" name="current_visible" value="<?php echo $link['is_visible']; ?>">
                                <button type="submit" class="btn-icon <?php echo $link['is_visible'] ? 'btn-toggle-on' : 'btn-toggle-off'; ?>">
                                    <i class="fas fa-<?php echo $link['is_visible'] ? 'eye-slash' : 'eye'; ?>"></i>
                                    <?php echo $link['is_visible'] ? 'Ocultar' : 'Mostrar'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este acceso externo?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                <button type="submit" class="btn-icon btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-link"></i>
        <p>No hay accesos externos definidos aún.</p>
        <p style="font-size: 0.9rem;">Usa el formulario de abajo para añadir el primero.</p>
    </div>
    <?php endif; ?>

    <!-- ─── Formulario ────────────────────────────────────────────────────── -->
    <div id="form-section" style="border-top: 2px solid var(--primary); padding-top: 2rem; margin-top: 1rem;">
        <h3 style="margin-bottom: 1.5rem; color: var(--primary);">
            <i class="fas fa-<?php echo $editItem ? 'edit' : 'plus-circle'; ?>"></i>
            <?php echo $editItem ? 'Editar Acceso Externo' : 'Nuevo Acceso Externo'; ?>
        </h3>

        <form method="POST" id="el-form">
            <input type="hidden" name="action" value="<?php echo $editItem ? 'update' : 'create'; ?>">
            <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
            <?php endif; ?>

            <div class="el-form-grid">
                <div class="form-group">
                    <label for="title"><i class="fas fa-heading"></i> Título *</label>
                    <input type="text" id="title" name="title" required maxlength="255"
                           value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>"
                           placeholder="Ej: Castillo de Moratalla en Wikipedia">
                </div>
                <div class="form-group">
                    <label for="sort_order"><i class="fas fa-sort-numeric-up"></i> Orden de aparición</label>
                    <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                           value="<?php echo (int)($editItem['sort_order'] ?? 0); ?>"
                           placeholder="0">
                    <small style="color: #888; display: block; margin-top: 0.3rem;">Menor número = aparece antes.</small>
                </div>
                <div class="form-group full">
                    <label for="url"><i class="fas fa-link"></i> URL del enlace *</label>
                    <input type="url" id="url" name="url" required maxlength="2048"
                           value="<?php echo htmlspecialchars($editItem['url'] ?? ''); ?>"
                           placeholder="https://ejemplo.com/pagina">
                </div>
                <div class="form-group full">
                    <label for="description"><i class="fas fa-align-left"></i> Descripción</label>
                    <textarea id="description" name="description" rows="3"
                              placeholder="Breve explicación de qué encontrará el visitante al hacer clic..."><?php echo htmlspecialchars($editItem['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category_id"><i class="fas fa-folder-open"></i> Asignar a Categoría (Opcional)</label>
                    <select name="category_id" id="category_id" style="width: 100%; padding: 0.85rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem;">
                        <option value="">-- Ninguna --</option>
                        <?php foreach($cats as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($editItem['category_id']) && $editItem['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="display: flex; align-items: center; margin-top: 1.5rem;">
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_in_category" id="show_in_category"
                               <?php echo (!isset($editItem) || !empty($editItem['show_in_category'])) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                        <span id="show-cat-label" style="font-weight: 600;">Mostrar en la categoría</span>
                    </label>
                </div>
                <div class="form-group full">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_visible" id="is_visible"
                               <?php echo (!$editItem || $editItem['is_visible']) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                        <span id="vis-label" style="font-weight: 600;">
                            <?php echo (!$editItem || $editItem['is_visible']) ? 'Visible en la web pública' : 'Oculto (no aparece en la web)'; ?>
                        </span>
                    </label>
                </div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    <?php echo $editItem ? 'Actualizar' : 'Guardar Acceso'; ?>
                </button>
                <?php if ($editItem): ?>
                    <a href="external_links.php" class="btn" style="background: #eee; color: #333;">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
// Etiqueta dinámica del toggle de visibilidad
const chk = document.getElementById('is_visible');
const lbl = document.getElementById('vis-label');
if (chk && lbl) {
    chk.addEventListener('change', () => {
        lbl.textContent = chk.checked ? 'Visible en la web pública' : 'Oculto (no aparece en la web)';
    });
}

// Si estamos en modo edición, hacer scroll al formulario
<?php if ($editItem): ?>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('form-section').scrollIntoView({ behavior: 'smooth' });
});
<?php endif; ?>
</script>

<?php adminFooter(); ?>
