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
            $recurrence = $_POST['recurrence'] ?? 'none';
            $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
            $easter_offset = ($_POST['recurrence'] === 'easter') ? (int)($_POST['easter_offset'] ?? 0) : null;
            
            if ($name) {
                $stmt = $pdo->prepare("INSERT INTO celebrations (name, is_active, start_date, end_date, html_content, css_content, js_content, recurrence, event_date, easter_offset) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $is_active, $start_date, $end_date, $html, $css, $js, $recurrence, $event_date, $easter_offset])) {
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
            $recurrence = $_POST['recurrence'] ?? 'none';
            $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;
            $easter_offset = ($_POST['recurrence'] === 'easter') ? (int)($_POST['easter_offset'] ?? 0) : null;
            
            if ($id && $name) {
                $stmt = $pdo->prepare("UPDATE celebrations SET name = ?, start_date = ?, end_date = ?, html_content = ?, css_content = ?, js_content = ?, recurrence = ?, event_date = ?, easter_offset = ? WHERE id = ?");
                if ($stmt->execute([$name, $start_date, $end_date, $html, $css, $js, $recurrence, $event_date, $easter_offset, $id])) {
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

$stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('celebration_days_before', 'celebration_days_after')");
$settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
$daysBefore = isset($settings['celebration_days_before']) ? (int)$settings['celebration_days_before'] : 5;
$daysAfter = isset($settings['celebration_days_after']) ? (int)$settings['celebration_days_after'] : 2;
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

<div class="alert" style="background-color: #e0f2fe; border-left: 4px solid #0284c7; padding: 1rem; margin-bottom: 2rem; border-radius: 4px; color: #0369a1;">
    <h4 style="margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center;"><i class="fas fa-info-circle" style="margin-right: 8px;"></i> Sobre la programación automática</h4>
    <p style="margin: 0; font-size: 0.95rem; line-height: 1.5;">
        Los eventos marcados con repetición (Anual, Mensual o Semana Santa) se calculan <strong>"al vuelo"</strong> por el sistema. 
        El acontecimiento aparecerá automáticamente en la web <strong>desde <?php echo $daysBefore; ?> días antes hasta <?php echo $daysAfter; ?> días después</strong> de la fecha central calculada para el año actual. De esta forma, no necesitas estar pendiente de reprogramar las fechas anualmente.
    </p>
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
                    <label>¿Se repite periódicamente?</label>
                    <select name="recurrence" class="form-control recurrence-select" style="width: 100%; padding: 0.5rem;">
                        <option value="none" <?php echo (empty($editRow['recurrence']) || $editRow['recurrence'] === 'none') ? 'selected' : ''; ?>>No se repite (Fechas manuales)</option>
                        <option value="annual" <?php echo ($editRow['recurrence'] === 'annual') ? 'selected' : ''; ?>>Anualmente (Mismo día y mes)</option>
                        <option value="monthly" <?php echo ($editRow['recurrence'] === 'monthly') ? 'selected' : ''; ?>>Mensualmente (Mismo día)</option>
                        <option value="easter" <?php echo ($editRow['recurrence'] === 'easter') ? 'selected' : ''; ?>>Variable (Respecto a Semana Santa)</option>
                    </select>
                </div>
                <div class="form-group date-field event-date-field" style="flex: 1; <?php echo ($editRow['recurrence'] === 'annual' || $editRow['recurrence'] === 'monthly') ? '' : 'display: none;'; ?>">
                    <label>Día del Evento</label>
                    <input type="date" name="event_date" class="form-control" style="width: 100%; padding: 0.5rem;" value="<?php echo htmlspecialchars($editRow['event_date']); ?>">
                    <small style="color: #666; font-size: 0.8rem;">El sistema lo mostrará automáticamente <?php echo $daysBefore; ?> días antes y <?php echo $daysAfter; ?> días después de esta fecha.</small>
                </div>
                <div class="form-group date-field easter-offset-field" style="flex: 1; <?php echo ($editRow['recurrence'] === 'easter') ? '' : 'display: none;'; ?>">
                    <label>Días respecto al Domingo de Resurrección</label>
                    <input type="number" name="easter_offset" class="form-control" style="width: 100%; padding: 0.5rem;" value="<?php echo htmlspecialchars($editRow['easter_offset']); ?>" placeholder="Ej. -3 para Jueves Santo, 0 para Domingo">
                    <small style="color: #666; font-size: 0.8rem;">-3: Jueves Santo, -2: Viernes Santo, 0: Dom Resurrección.</small>
                </div>
            </div>
            
            <div class="manual-dates-container" style="display: flex; gap: 1rem; margin-bottom: 1rem; <?php echo (empty($editRow['recurrence']) || $editRow['recurrence'] === 'none') ? '' : 'display: none !important;'; ?>">
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
                <label style="display: flex; justify-content: space-between;">
                    <span>Código HTML</span>
                    <select class="form-control html-effect-selector" data-target="htmlFieldEdit" style="width: auto; padding: 0.2rem; font-size: 0.9rem;">
                        <option value="">Añadir plantilla HTML...</option>
                        <option value="historico">📜 Acontecimiento Histórico</option>
                        <option value="aniversario">🎉 Aniversario</option>
                        <option value="conmemoracion">🏛️ Conmemoración</option>
                        <option value="general_discreto">📣 Aviso General</option>
                        <option value="luto">🖤 Luto Oficial</option>
                        <option value="musica">🎸 Música / Concierto</option>
                        <option value="sorteo">🎁 Sorteo</option>
                        <option value="procesion">🕯️ Procesión</option>
                        <option value="misa">⛪ Misa</option>
                    </select>
                </label>
                <textarea id="htmlFieldEdit" name="html_content" class="form-control" style="width: 100%; height: 150px; padding: 0.5rem; font-family: monospace;"><?php echo htmlspecialchars($editRow['html_content']); ?></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: flex; justify-content: space-between;">
                    <span>Código CSS (Sin etiquetas &lt;style&gt;)</span>
                    <select class="form-control css-effect-selector" data-target="cssFieldEdit" style="width: auto; padding: 0.2rem; font-size: 0.9rem;">
                        <option value="">Añadir plantilla CSS...</option>
                        <option value="historico">📜 Acontecimiento Histórico</option>
                        <option value="aniversario">🎉 Aniversario</option>
                        <option value="conmemoracion">🏛️ Conmemoración</option>
                        <option value="general_discreto">📣 Aviso General</option>
                        <option value="luto">🖤 Luto Oficial</option>
                        <option value="musica">🎸 Música / Concierto</option>
                        <option value="sorteo">🎁 Sorteo</option>
                        <option value="procesion">🕯️ Procesión</option>
                        <option value="misa">⛪ Misa</option>
                    </select>
                </label>
                <textarea id="cssFieldEdit" name="css_content" class="form-control" style="width: 100%; height: 150px; padding: 0.5rem; font-family: monospace;"><?php echo htmlspecialchars($editRow['css_content']); ?></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: flex; justify-content: space-between;">
                    <span>Código JS (Sin etiquetas &lt;script&gt;)</span>
                    <select class="form-control js-effect-selector" data-target="jsFieldEdit" style="width: auto; padding: 0.2rem; font-size: 0.9rem;">
                        <option value="">Añadir efecto visual...</option>
                        <option value="confetti">Confeti</option>
                        <option value="fireworks">Cohetes / Fuegos Artificiales</option>
                        <option value="lightning">Rayos / Tormenta</option>
                        <option value="rainbow">Arcoíris Flotante</option>
                        <option value="snow">Nieve</option>
                        <option value="balloons">Globos</option>
                        <option value="music_notes">Notas Musicales</option>
                        <option value="sorteo">Confeti Sorteo</option>
                        <option value="candle">Vela Procesión</option>
                    </select>
                </label>
                <textarea id="jsFieldEdit" name="js_content" class="form-control" style="width: 100%; height: 150px; padding: 0.5rem; font-family: monospace;"><?php echo htmlspecialchars($editRow['js_content']); ?></textarea>
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
        
        <form method="POST" id="addEventForm">
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
                    <label>¿Se repite periódicamente?</label>
                    <select name="recurrence" class="form-control recurrence-select" style="width: 100%; padding: 0.5rem;">
                        <option value="none" selected>No se repite (Fechas manuales)</option>
                        <option value="annual">Anualmente (Mismo día y mes)</option>
                        <option value="monthly">Mensualmente (Mismo día)</option>
                        <option value="easter">Variable (Respecto a Semana Santa)</option>
                    </select>
                </div>
                <div class="form-group date-field event-date-field" style="flex: 1; display: none;">
                    <label>Día del Evento</label>
                    <input type="date" name="event_date" class="form-control" style="width: 100%; padding: 0.5rem;">
                    <small style="color: #666; font-size: 0.8rem;">El sistema lo mostrará automáticamente <?php echo $daysBefore; ?> días antes y <?php echo $daysAfter; ?> días después de esta fecha.</small>
                </div>
                <div class="form-group date-field easter-offset-field" style="flex: 1; display: none;">
                    <label>Días respecto al Domingo de Resurrección</label>
                    <input type="number" name="easter_offset" class="form-control" style="width: 100%; padding: 0.5rem;" placeholder="Ej. -3 para Jueves Santo, 0 para Domingo">
                    <small style="color: #666; font-size: 0.8rem;">-3: Jueves Santo, -2: Viernes Santo, 0: Dom Resurrección.</small>
                </div>
            </div>
            
            <div class="manual-dates-container" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
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
                <label style="display: flex; justify-content: space-between;">
                    <span>Código HTML (Opcional)</span>
                    <select class="form-control html-effect-selector" data-target="htmlField" style="width: auto; padding: 0.2rem; font-size: 0.9rem;">
                        <option value="">Añadir plantilla HTML...</option>
                        <option value="historico">📜 Acontecimiento Histórico</option>
                        <option value="aniversario">🎉 Aniversario</option>
                        <option value="conmemoracion">🏛️ Conmemoración</option>
                        <option value="general_discreto">📣 Aviso General</option>
                        <option value="luto">🖤 Luto Oficial</option>
                        <option value="musica">🎸 Música / Concierto</option>
                        <option value="sorteo">🎁 Sorteo</option>
                        <option value="procesion">🕯️ Procesión</option>
                        <option value="misa">⛪ Misa</option>
                    </select>
                </label>
                <textarea id="htmlField" name="html_content" class="form-control" style="width: 100%; height: 100px; padding: 0.5rem; font-family: monospace;" placeholder="<div id='navidad'>Feliz Navidad</div>"></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: flex; justify-content: space-between;">
                    <span>Código CSS (Opcional - Sin etiquetas &lt;style&gt;)</span>
                    <select class="form-control css-effect-selector" data-target="cssField" style="width: auto; padding: 0.2rem; font-size: 0.9rem;">
                        <option value="">Añadir plantilla CSS...</option>
                        <option value="historico">📜 Acontecimiento Histórico</option>
                        <option value="aniversario">🎉 Aniversario</option>
                        <option value="conmemoracion">🏛️ Conmemoración</option>
                        <option value="general_discreto">📣 Aviso General</option>
                        <option value="luto">🖤 Luto Oficial</option>
                        <option value="musica">🎸 Música / Concierto</option>
                        <option value="sorteo">🎁 Sorteo</option>
                        <option value="procesion">🕯️ Procesión</option>
                        <option value="misa">⛪ Misa</option>
                    </select>
                </label>
                <textarea id="cssField" name="css_content" class="form-control" style="width: 100%; height: 100px; padding: 0.5rem; font-family: monospace;" placeholder="#navidad { color: red; }"></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="display: flex; justify-content: space-between;">
                    <span>Código JS (Opcional - Sin etiquetas &lt;script&gt;)</span>
                    <select class="form-control js-effect-selector" data-target="jsField" style="width: auto; padding: 0.2rem; font-size: 0.9rem;">
                        <option value="">Añadir efecto visual...</option>
                        <option value="confetti">Confeti</option>
                        <option value="fireworks">Cohetes / Fuegos Artificiales</option>
                        <option value="lightning">Rayos / Tormenta</option>
                        <option value="rainbow">Arcoíris Flotante</option>
                        <option value="snow">Nieve</option>
                        <option value="balloons">Globos</option>
                        <option value="music_notes">Notas Musicales</option>
                        <option value="sorteo">Confeti Sorteo</option>
                        <option value="candle">Vela Procesión</option>
                    </select>
                </label>
                <textarea id="jsField" name="js_content" class="form-control" style="width: 100%; height: 100px; padding: 0.5rem; font-family: monospace;" placeholder="console.log('Navidad activa');"></textarea>
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
                            $recur = $row['recurrence'] ?? 'none';
                            if ($recur === 'annual') {
                                echo "Repetición Anual: " . ($row['event_date'] ? date('d/m', strtotime($row['event_date'])) : 'Sin fecha configurada');
                            } elseif ($recur === 'monthly') {
                                echo "Repetición Mensual: Día " . ($row['event_date'] ? date('d', strtotime($row['event_date'])) : 'Sin fecha configurada');
                            } elseif ($recur === 'easter') {
                                $offset = (int)$row['easter_offset'];
                                $offsetText = $offset === 0 ? "Domingo de Resurrección" : ($offset > 0 ? "+{$offset} días" : "{$offset} días");
                                echo "Repetición Variable: Semana Santa (" . $offsetText . ")";
                            } else {
                                if ($row['start_date'] || $row['end_date']) {
                                    echo "Fechas: ";
                                    echo $row['start_date'] ? date('d/m/Y H:i', strtotime($row['start_date'])) : 'Siempre';
                                    echo " - ";
                                    echo $row['end_date'] ? date('d/m/Y H:i', strtotime($row['end_date'])) : 'Siempre';
                                } else {
                                    echo "Fechas: Sin límite";
                                }
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
<?php endif; ?>

    <script>
    const htmlTemplates = {
        'historico': '<div id="historical-banner">\n    <div class="historical-content">\n        📜 <strong>Hoy en la historia:</strong> Se conmemora el acontecimiento de [Texto del acontecimiento]\n    </div>\n</div>',
        'aniversario': '<div id="anniversary-banner">\n    🎉 ¡Feliz Aniversario de [Motivo]! 🎉\n</div>',
        'conmemoracion': '<div id="commemoration-banner">\n    En conmemoración de [Motivo de la conmemoración]\n</div>',
        'general_discreto': '<div id="general-banner-container">\n    <div class="general-flag-banner">\n        [Título o Motivo del Acontecimiento]\n    </div>\n</div>',
        'luto': '<div id="mourning-banner">\n    🖤 En señal de luto oficial. Descanse en paz.\n</div>',
        'musica': '<div id="music-banner">\n    🎸 Gran Concierto / Festival de Música: [Nombre]\n</div>',
        'sorteo': '<div id="raffle-banner">\n    🎁 ¡Participa en el Gran Sorteo! 🎁\n</div>',
        'procesion': '<div id="procession-banner">\n    🕯️ Solemne Procesión de [Nombre]\n</div>',
        'misa': '<div id="mass-banner">\n    ⛪ Misa en honor a [Nombre]\n</div>'
    };
    
    const cssTemplates = {
        'historico': '#historical-banner {\n    position: fixed; bottom: 0; left: 0; width: 100%;\n    background: rgba(44, 24, 16, 0.95); border-top: 3px solid #d4af37;\n    color: #fdf5e6; padding: 15px; text-align: center;\n    z-index: 9999; font-family: Georgia, serif; font-size: 1.2rem;\n    box-shadow: 0 -5px 15px rgba(0,0,0,0.5);\n}\n.historical-content { max-width: 800px; margin: 0 auto; line-height: 1.5; }',
        'aniversario': '#anniversary-banner {\n    background: linear-gradient(90deg, #ff9a9e 0%, #fecfef 99%, #fecfef 100%);\n    color: #333; font-weight: bold; font-size: 1.5rem; text-align: center;\n    padding: 15px; border-bottom: 2px solid #ff758c; box-shadow: 0 4px 6px rgba(0,0,0,0.1);\n}',
        'conmemoracion': '#commemoration-banner {\n    background: #2b3a42; color: #fff; text-align: center;\n    padding: 15px; font-size: 1.2rem; letter-spacing: 1px;\n    border-bottom: 2px solid #3f5765;\n}',
        'general_discreto': '#general-banner-container {\n    position: fixed; top: 0; left: 0; width: 100%; z-index: 9999; pointer-events: none; display: flex; justify-content: center; animation: slideDown 1s ease-out forwards; transform: translateY(-100%);\n}\n.general-flag-banner {\n    background: linear-gradient(to right, #1b4332 0%, #081c15 100%); color: white; font-size: clamp(1rem, 3vw, 1.3rem); font-weight: 600; padding: 10px 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); border-radius: 0 0 15px 15px; border-bottom: 3px solid #d4af37;\n}\n@keyframes slideDown { 0% { transform: translateY(-100%); } 100% { transform: translateY(0); } }',
        'luto': '/* html { filter: grayscale(100%); } */\n#mourning-banner {\n    background: #000; color: #fff; text-align: center; padding: 10px; font-weight: bold; font-size: 1.1rem; border-bottom: 1px solid #333;\n}',
        'musica': '#music-banner {\n    background: linear-gradient(45deg, #000428, #004e92);\n    color: #fff; font-weight: bold; font-size: 1.5rem; text-align: center;\n    padding: 15px; border-bottom: 3px solid #ff0055;\n    text-shadow: 0 0 10px rgba(255,0,85,0.7);\n}',
        'sorteo': '#raffle-banner {\n    background: #ffd700; color: #d32f2f; font-weight: 900; font-size: 1.4rem; text-align: center; padding: 15px;\n    border: 4px dashed #d32f2f; box-shadow: inset 0 0 15px rgba(255,255,255,0.5);\n    animation: pulseRaffle 2s infinite;\n}\n@keyframes pulseRaffle { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }',
        'procesion': '#procession-banner {\n    background: #1a0033; color: #d4af37; font-family: "Times New Roman", serif; font-size: 1.3rem; text-align: center; padding: 15px; border-bottom: 2px solid #5c3a21; letter-spacing: 2px;\n}',
        'misa': '#mass-banner {\n    background: #fdfdfd; color: #333; font-family: Georgia, serif; font-size: 1.2rem; text-align: center; padding: 15px; border-bottom: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);\n}'
    };

    const jsEffects = {
        'confetti': `if (typeof confetti === "undefined") {
    var script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js";
    script.onload = function() { confetti({ particleCount: 150, spread: 180, origin: { y: 0.1 } }); };
    document.head.appendChild(script);
} else {
    confetti({ particleCount: 150, spread: 180, origin: { y: 0.1 } });
}`,
        'fireworks': `if (typeof confetti === "undefined") {
    var script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js";
    script.onload = function() { launchFireworks(); };
    document.head.appendChild(script);
} else {
    launchFireworks();
}
function launchFireworks() {
    var duration = 5 * 1000;
    var animationEnd = Date.now() + duration;
    var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 99999 };
    var interval = setInterval(function() {
        var timeLeft = animationEnd - Date.now();
        if (timeLeft <= 0) { return clearInterval(interval); }
        var particleCount = 50 * (timeLeft / duration);
        confetti(Object.assign({}, defaults, { particleCount, origin: { x: Math.random(), y: Math.random() - 0.2 } }));
    }, 250);
}`,
        'lightning': `var flash = document.createElement('div');
flash.style.position = 'fixed';
flash.style.top = '0'; flash.style.left = '0'; flash.style.width = '100vw'; flash.style.height = '100vh';
flash.style.backgroundColor = 'white'; flash.style.opacity = '0'; flash.style.pointerEvents = 'none';
flash.style.zIndex = '99999';
document.body.appendChild(flash);
function strike() {
    flash.style.opacity = '0.8';
    setTimeout(() => flash.style.opacity = '0', 50);
    setTimeout(() => { flash.style.opacity = '0.5'; setTimeout(() => flash.style.opacity = '0', 50); }, 150);
}
strike(); var lightningInt = setInterval(strike, 4000);
document.addEventListener('celebrationChange', function() { clearInterval(lightningInt); flash.remove(); });`,
        'rainbow': `var r = document.createElement('div');
r.style.position = 'fixed'; r.style.top = '0'; r.style.left = '0'; r.style.width = '100vw'; r.style.height = '10px';
r.style.background = 'linear-gradient(to right, red, orange, yellow, green, blue, indigo, violet)';
r.style.zIndex = '99999'; r.style.animation = 'rainbowShift 2s linear infinite';
document.body.appendChild(r);
if(!document.getElementById('rainbowStyle')) {
    var s = document.createElement('style'); s.id = 'rainbowStyle';
    s.innerHTML = '@keyframes rainbowShift { 0% { filter: hue-rotate(0deg); } 100% { filter: hue-rotate(360deg); } }';
    document.head.appendChild(s);
}
document.addEventListener('celebrationChange', function() { r.remove(); });`,
        'snow': `if (typeof confetti === "undefined") {
    var script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js";
    script.onload = function() { launchSnow(); };
    document.head.appendChild(script);
} else {
    launchSnow();
}
function launchSnow() {
    var duration = 5 * 1000;
    var animationEnd = Date.now() + duration;
    var skew = 1;
    function frame() {
        var timeLeft = animationEnd - Date.now();
        var ticks = Math.max(200, 500 * (timeLeft / duration));
        skew = Math.max(0.8, skew - 0.001);
        confetti({ particleCount: 1, startVelocity: 0, ticks: ticks, origin: { x: Math.random(), y: Math.random() * skew - 0.2 }, colors: ['#ffffff'], shapes: ['circle'], gravity: Math.random() * 0.4 + 0.6, scalar: Math.random() * 0.4 + 0.4, drift: Math.random() - 0.5, zIndex: 99999 });
        if (timeLeft > 0) { requestAnimationFrame(frame); }
    }
    frame();
}`,
        'balloons': `var emojis = ['🎈','🎈','🎊','🎉'];
for(var i=0; i<15; i++) {
    let b = document.createElement('div');
    b.innerText = emojis[Math.floor(Math.random()*emojis.length)];
    b.style.position = 'fixed'; b.style.bottom = '-50px'; b.style.left = Math.random()*100 + 'vw';
    b.style.fontSize = (Math.random()*2+2)+'rem'; b.style.zIndex = '99999'; b.style.pointerEvents = 'none';
    b.style.transition = 'bottom 4s linear, transform 4s ease-in-out';
    document.body.appendChild(b);
    setTimeout(() => { b.style.bottom = '120vh'; b.style.transform = 'rotate('+(Math.random()*360-180)+'deg)'; }, 100);
    setTimeout(() => b.remove(), 4100);
}`,
        'music_notes': `var emojis = ['🎵','🎶','🎸','🥁'];
for(var i=0; i<15; i++) {
    let b = document.createElement('div');
    b.innerText = emojis[Math.floor(Math.random()*emojis.length)];
    b.style.position = 'fixed'; b.style.top = '-50px'; b.style.left = Math.random()*100 + 'vw';
    b.style.fontSize = (Math.random()*2+1.5)+'rem'; b.style.zIndex = '99999'; b.style.pointerEvents = 'none';
    b.style.transition = 'top 5s linear, transform 5s ease-in-out';
    document.body.appendChild(b);
    setTimeout(() => { b.style.top = '120vh'; b.style.transform = 'rotate('+(Math.random()*360-180)+'deg)'; }, 100);
    setTimeout(() => b.remove(), 5100);
}`,
        'sorteo': `if (typeof confetti === "undefined") {
    var script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js";
    script.onload = function() { confetti({ particleCount: 200, spread: 360, origin: { y: 0.5 }, colors: ['#ffd700', '#d32f2f'] }); };
    document.head.appendChild(script);
} else {
    confetti({ particleCount: 200, spread: 360, origin: { y: 0.5 }, colors: ['#ffd700', '#d32f2f'] });
}`,
        'candle': `var c = document.createElement('div');
c.innerText = '🕯️'; c.style.position = 'fixed'; c.style.bottom = '20px'; c.style.right = '20px';
c.style.fontSize = '3rem'; c.style.zIndex = '99999'; c.style.pointerEvents = 'none';
c.style.animation = 'flicker 2s infinite alternate';
document.body.appendChild(c);
if(!document.getElementById('candleStyle')) {
    var s = document.createElement('style'); s.id = 'candleStyle';
    s.innerHTML = '@keyframes flicker { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.8; transform: scale(1.05); } 100% { opacity: 1; transform: scale(0.95); } }';
    document.head.appendChild(s);
}
document.addEventListener('celebrationChange', function() { c.remove(); });`
    };

    document.querySelectorAll('.html-effect-selector').forEach(select => {
        select.addEventListener('change', function() {
            if (this.value && htmlTemplates[this.value]) {
                const targetTextarea = document.getElementById(this.getAttribute('data-target'));
                if (targetTextarea.value) targetTextarea.value += '\n\n' + htmlTemplates[this.value];
                else targetTextarea.value = htmlTemplates[this.value];
                this.value = ''; 
            }
        });
    });

    document.querySelectorAll('.css-effect-selector').forEach(select => {
        select.addEventListener('change', function() {
            if (this.value && cssTemplates[this.value]) {
                const targetTextarea = document.getElementById(this.getAttribute('data-target'));
                if (targetTextarea.value) targetTextarea.value += '\n\n' + cssTemplates[this.value];
                else targetTextarea.value = cssTemplates[this.value];
                this.value = ''; 
            }
        });
    });

    document.querySelectorAll('.js-effect-selector').forEach(select => {
        select.addEventListener('change', function() {
            if (this.value && jsEffects[this.value]) {
                const targetTextarea = document.getElementById(this.getAttribute('data-target'));
                const currentVal = targetTextarea.value;
                if (currentVal) {
                    targetTextarea.value = currentVal + '\n\n' + jsEffects[this.value];
                } else {
                    targetTextarea.value = jsEffects[this.value];
                }
                this.value = ''; // reset
            }
        });
    });

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

    document.querySelectorAll('.recurrence-select').forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const manualDates = form.querySelector('.manual-dates-container');
            const eventDate = form.querySelector('.event-date-field');
            const easterOffset = form.querySelector('.easter-offset-field');
            
            if (this.value === 'none') {
                if(manualDates) manualDates.style.setProperty('display', 'flex', 'important');
                if(eventDate) eventDate.style.setProperty('display', 'none', 'important');
                if(easterOffset) easterOffset.style.setProperty('display', 'none', 'important');
            } else if (this.value === 'easter') {
                if(manualDates) manualDates.style.setProperty('display', 'none', 'important');
                if(eventDate) eventDate.style.setProperty('display', 'none', 'important');
                if(easterOffset) easterOffset.style.setProperty('display', 'block', 'important');
            } else {
                if(manualDates) manualDates.style.setProperty('display', 'none', 'important');
                if(eventDate) eventDate.style.setProperty('display', 'block', 'important');
                if(easterOffset) easterOffset.style.setProperty('display', 'none', 'important');
            }
        });
    });
    </script>

<?php adminFooter(); ?>
