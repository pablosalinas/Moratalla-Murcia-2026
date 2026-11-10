<?php
// restaurar_estado_original.php
// Restaura al 100% los archivos originales desde .backup_pre_seo_90dias/

$backupDir = __DIR__ . '/.backup_pre_seo_90dias';
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/inc/seo_90dias_data.php';
    if (!canManageSeo90()) {
        http_response_code(404);
        $_GET['code'] = 404;
        require __DIR__ . '/error.php';
        exit;
    }
}

if (!is_dir($backupDir)) {
    $msg = "No se encontró el directorio de respaldo (.backup_pre_seo_90dias).";
    if ($isCli) { echo $msg . PHP_EOL; exit(1); }
    die($msg);
}

$doRestore = $isCli || (isset($_POST['confirm_restore']) && $_POST['confirm_restore'] === '1');

if ($doRestore) {
    $filesToRestore = [
        '.htaccess',
        'alojamientos.php',
        'category.php',
        'contacto.php',
        'index.php',
        'page.php',
        'restaurantes.php',
        'robots.txt',
        'search_ajax.php',
        'sitemap.php',
        'style.css',
        'inc/header.php',
        'inc/footer.php'
    ];

    $restoredCount = 0;
    foreach ($filesToRestore as $relPath) {
        $src = $backupDir . '/' . $relPath;
        $dst = __DIR__ . '/' . $relPath;
        if (file_exists($src)) {
            if (@copy($src, $dst)) {
                $restoredCount++;
            }
        }
    }

    // Opcionalmente eliminar archivos nuevos si se solicita restauración completa
    if ($isCli || !empty($_POST['remove_new_files'])) {
        @unlink(__DIR__ . '/.seo_90dias_state.json');
    }

    if ($isCli) {
        echo "OK: Se han restaurado {$restoredCount} archivos a su estado original exacto." . PHP_EOL;
        exit(0);
    }

    header("Location: index.php?restored=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restaurar Estado Original · moratalla-murcia.com</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
        .card { background: white; max-width: 560px; width: 100%; padding: 2.2rem; border-radius: 18px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); border-top: 5px solid #1b4332; }
        h1 { margin-top: 0; color: #1b4332; font-size: 1.4rem; }
        p { color: #475569; line-height: 1.6; }
        .btn { display: inline-block; padding: 0.85rem 1.5rem; border-radius: 10px; font-weight: 700; text-decoration: none; border: none; cursor: pointer; font-size: 0.95rem; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-secondary { background: #e2e8f0; color: #1e293b; margin-left: 0.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>⏪ Restaurar Archivos Originales (Pre-SEO 90 Días)</h1>
        <p>Si pulsas en <strong>Restaurar Ahora</strong>, se copiarán de vuelta todos los archivos originales guardados en <code>.backup_pre_seo_90dias/</code> dejando el proyecto exactamente como estaba antes.</p>
        <form method="POST">
            <input type="hidden" name="confirm_restore" value="1">
            <label style="display:block; margin-bottom: 1.2rem; color:#334155; font-size:0.9rem;">
                <input type="checkbox" name="remove_new_files" value="1" checked> Resetear también el estado de configuración local
            </label>
            <button type="submit" class="btn btn-danger">Restaurar Estado Original Ahora</button>
            <a href="index.php" class="btn btn-secondary">Cancelar y Volver</a>
        </form>
    </div>
    <a href="index.php" style="position:fixed;bottom:24px;left:24px;z-index:99990;background:linear-gradient(135deg,#1b4332 0%,#2d6a4f 100%);color:#fff;text-decoration:none;border:2px solid #d4af37;border-radius:50px;padding:0.72rem 1.25rem;font-size:0.92rem;font-weight:800;display:inline-flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(27,67,50,0.38);">
        🏠 <span>Página Principal</span>
    </a>
</body>
</html>
