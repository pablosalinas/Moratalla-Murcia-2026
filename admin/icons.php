<?php
// admin/icons.php - Gestor de Iconos e Imágenes para el Menú
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';
require_once 'inc/icon_helper.php';

$pdo = getDB();
$msg = $_GET['msg'] ?? '';
$error = '';

$iconsDir = dirname(__DIR__) . '/uploads/icons';
if (!is_dir($iconsDir)) {
    @mkdir($iconsDir, 0777, true);
}

// Procesar Subida de Nuevo Icono
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_icon') {
    $uploadError = null;
    $uploaded = handleIconUpload('icon_file', $uploadError);
    if ($uploaded) {
        header("Location: icons.php?msg=" . urlencode("Icono '" . $uploaded . "' subido con éxito a la biblioteca."));
        exit;
    } else {
        $error = $uploadError ?: "No se seleccionó ningún archivo para subir.";
    }
}

// Procesar Eliminación de Icono
if (isset($_GET['delete'])) {
    $delFile = basename($_GET['delete']);
    $filePath = $iconsDir . '/' . $delFile;
    if (is_file($filePath)) {
        @unlink($filePath);
        header("Location: icons.php?msg=" . urlencode("Icono '" . $delFile . "' eliminado correctamente."));
        exit;
    } else {
        $error = "El archivo no existe o ya fue eliminado.";
    }
}

// Obtener lista de archivos existentes
$files = getUploadedCustomIcons();

// Obtener estadísticas de uso de cada icono en la base de datos
$usedInCats = $pdo->query("SELECT id, name, icon FROM categories WHERE icon IS NOT NULL AND icon != ''")->fetchAll();
$usedInPages = $pdo->query("SELECT id, title, icon FROM pages WHERE icon IS NOT NULL AND icon != ''")->fetchAll();
$usedInExt = $pdo->query("SELECT id, title, icon FROM external_links WHERE icon IS NOT NULL AND icon != ''")->fetchAll();
$usedInNews = $pdo->query("SELECT id, title, icon FROM news_events WHERE icon IS NOT NULL AND icon != ''")->fetchAll();

adminHeader("Biblioteca y Gestor de Iconos");
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h2 style="color: var(--primary); margin-bottom: 0.3rem;"><i class="fas fa-icons"></i> Biblioteca de Iconos y Emojis</h2>
            <p style="color: #64748b; font-size: 0.95rem;">Gestiona y sube nuevos archivos de imagen (<code>.svg</code> o <code>.png</code>) para utilizarlos en categorías, páginas y accesos externos.</p>
        </div>
        <a href="#subir-nuevo" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Subir Nuevo Icono</a>
    </div>

    <?php if ($msg): ?>
        <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #2e7d32;">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div style="background: #ffebee; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #c62828;">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Formulario para subir archivo nuevo -->
    <div id="subir-nuevo" style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 1.8rem; margin-bottom: 2.5rem;">
        <h3 style="color: var(--primary); font-size: 1.15rem; margin-bottom: 0.5rem;"><i class="fas fa-cloud-upload-alt"></i> Subir Archivo de Icono Personalizado</h3>
        <p style="color: #64748b; font-size: 0.88rem; margin-bottom: 1.2rem;">
            Formatos recomendados: <strong>.SVG</strong> (vectorial, máxima nitidez) o <strong>.PNG</strong> (con fondo transparente). Se guardará en la carpeta <code>uploads/icons/</code> y aparecerá automáticamente en todos los desplegables.
        </p>

        <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="action" value="upload_icon">
            <input type="file" name="icon_file" accept=".svg,.png,.webp,.jpg,.jpeg" required style="padding: 0.6rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; background: white; font-size: 0.95rem;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Subir a la Lista</button>
        </form>
    </div>

    <!-- Lista de archivos subidos -->
    <h3 style="color: var(--primary); margin-bottom: 1rem; font-size: 1.2rem;"><i class="fas fa-folder-open"></i> Iconos de Archivo Disponibles (<?php echo count($files); ?>)</h3>
    
    <?php if (count($files) === 0): ?>
        <div style="text-align: center; padding: 3rem; background: #f8fafc; border-radius: 10px; color: #94a3b8;">
            <i class="far fa-images" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
            No hay archivos de iconos subidos todavía. ¡Sube el primero arriba o desde el formulario de categorías/páginas!
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.2rem; margin-bottom: 3rem;">
            <?php foreach ($files as $f): 
                $ext = strtoupper(pathinfo($f, PATHINFO_EXTENSION));
                $name = pathinfo($f, PATHINFO_FILENAME);
                $filePath = '../uploads/icons/' . $f;
                $size = is_file($filePath) ? round(filesize($filePath) / 1024, 1) : 0;

                // Contar usos
                $uses = 0;
                foreach ($usedInCats as $c) if ($c['icon'] === $f) $uses++;
                foreach ($usedInPages as $p) if ($p['icon'] === $f) $uses++;
                foreach ($usedInExt as $e) if ($e['icon'] === $f) $uses++;
                foreach ($usedInNews as $n) if ($n['icon'] === $f) $uses++;
            ?>
                <div style="background: white; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.2rem; display: flex; flex-direction: column; align-items: center; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.02); transition: transform 0.2s;">
                    <div style="width: 54px; height: 54px; border-radius: 8px; background: #f1f5f9; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; margin-bottom: 0.8rem;">
                        <img src="<?php echo htmlspecialchars($filePath); ?>" alt="<?php echo htmlspecialchars($f); ?>" style="max-width: 36px; max-height: 36px; object-fit: contain;">
                    </div>
                    
                    <strong style="font-size: 0.88rem; color: #1e293b; word-break: break-all; margin-bottom: 0.3rem;" title="<?php echo htmlspecialchars($f); ?>">
                        <?php echo htmlspecialchars(mb_strimwidth($f, 0, 22, '...')); ?>
                    </strong>
                    
                    <div style="font-size: 0.75rem; color: #64748b; margin-bottom: 0.8rem;">
                        <span class="badge" style="background: #e2e8f0; color: #334155;"><?php echo $ext; ?></span>
                        <span><?php echo $size; ?> KB</span>
                        <span class="badge" style="background: <?php echo $uses > 0 ? '#e8f5e9' : '#f1f5f9'; ?>; color: <?php echo $uses > 0 ? '#2e7d32' : '#64748b'; ?>;">
                            <?php echo $uses; ?> en uso
                        </span>
                    </div>

                    <div style="display: flex; gap: 6px; margin-top: auto;">
                        <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank" class="btn btn-sm" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #f1f5f9; color: #334155;" title="Ver archivo"><i class="fas fa-eye"></i></a>
                        <a href="icons.php?delete=<?php echo urlencode($f); ?>" onclick="return confirm('¿Seguro que deseas eliminar el icono \'<?php echo htmlspecialchars($f); ?>\'? Si algún elemento lo está usando, dejará de verse.');" class="btn btn-sm" style="padding: 0.3rem 0.6rem; font-size: 0.75rem; background: #fee2e2; color: #b91c1c;" title="Eliminar"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Guía informativa -->
    <div style="background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 8px; padding: 1.2rem;">
        <h4 style="color: #1e40af; margin-bottom: 0.5rem;"><i class="fas fa-info-circle"></i> ¿Cómo funciona el sistema de iconos?</h4>
        <ul style="margin-left: 1.2rem; font-size: 0.9rem; color: #1e3a8a; line-height: 1.6;">
            <li><strong>Archivos subidos (.SVG / .PNG):</strong> Se guardan en <code>uploads/icons/</code>. El nombre del archivo se guarda como referencia y se muestra automáticamente como miniatura en todos los menús y listados.</li>
            <li><strong>Emojis de texto:</strong> Puedes escribir o pegar cualquier emoji (pulsando <code>Windows + .</code> en el teclado) como ⛪, 🌲, 🏰 o 🥁.</li>
            <li><strong>FontAwesome:</strong> Puedes escribir cualquier clase CSS de FontAwesome (ejemplo: <code>fas fa-hiking</code> o <code>fas fa-utensils</code>).</li>
        </ul>
    </div>
</div>

<?php adminFooter(); ?>