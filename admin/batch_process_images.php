<?php
// admin/batch_process_images.php
require_once '../config.php';
require_once 'inc/auth.php';

// Verificación de seguridad
$isCLI = (php_sapi_name() === 'cli');
if (!$isCLI) {
    checkAuth();
}

require_once 'inc/image_helper.php';
require_once 'inc/layout.php';

// Limite de tiempo infinito para procesos largos
set_time_limit(0);
ini_set('memory_limit', '512M');

if (!$isCLI) adminHeader("Procesamiento por Lotes de Imágenes");

if (!$isCLI): ?>
<div class="card">
    <h3>Procesamiento por Lotes de Imágenes</h3>
    <p>Esta herramienta recorre toda la carpeta <code>uploads/</code> y aplica la nueva optimización de tamaño y la marca de agua a las imágenes que ya estaban subidas al servidor.</p>
    
    <?php if (!isset($_POST['start'])): ?>
    <div style="background: #fff3cd; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #ffeeba;">
        <strong><i class="fas fa-exclamation-triangle"></i> Atención:</strong> Este proceso sobreescribirá las imágenes originales. Puede tardar un rato dependiendo de la cantidad de imágenes.
    </div>
    
    <form method="POST">
        <button type="submit" name="start" value="1" class="btn btn-primary" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Procesando... por favor espera';">
            <i class="fas fa-play"></i> Iniciar Procesamiento Masivo
        </button>
    </form>
    <?php endif; ?>
</div>
<?php endif;

if (isset($_POST['start']) || $isCLI) {
    if (!$isCLI) echo '<div class="card" style="margin-top: 2rem;"><h4>Log de proceso:</h4><pre style="background: #f8f9fa; padding: 1rem; border: 1px solid #dee2e6; border-radius: 4px; max-height: 400px; overflow-y: auto; font-size: 0.85rem;">';
    
    $processedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;

    function processDirectory($dir) {
        global $processedCount, $skippedCount, $errorCount, $isCLI;
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                processDirectory($path);
            } else {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $maxWidth = 1200;
                    if (strpos($path, 'banners') !== false && strpos($path, '_desktop') !== false) {
                        $maxWidth = 1920;
                    } elseif (strpos($path, 'banners') !== false && strpos($path, '_mobile') !== false) {
                        $maxWidth = 768;
                    }
                    
                    $msg = "Procesando: " . str_replace(realpath(__DIR__ . '/../'), '', $path) . " ... ";
                    
                    // Hacer una copia en temp por si acaso processUploadedImage necesita un source separado del target
                    // aunque en nuestra implementación, si source y target son iguales, GD lo machaca bien en el guardado.
                    // Para ser seguros con GD:
                    $tempCopy = sys_get_temp_dir() . '/' . uniqid() . '_' . $file;
                    copy($path, $tempCopy);
                    
                    $result = processUploadedImage($tempCopy, $path, true, $maxWidth, 85);
                    @unlink($tempCopy); // Limpiar el temp
                    
                    if ($result) {
                        $msg .= "<span style='color:green'>OK</span>\n";
                        $processedCount++;
                    } else {
                        $msg .= "<span style='color:red'>ERROR</span>\n";
                        $errorCount++;
                    }
                    if (!$isCLI) { echo $msg; flush(); } else { echo strip_tags($msg); }
                } else {
                    $skippedCount++;
                }
            }
        }
    }

    $uploadsDir = realpath(__DIR__ . '/../uploads');
    processDirectory($uploadsDir);

    $summary = "\n--- Resumen Final ---\n";
    $summary .= "Imágenes procesadas y optimizadas: $processedCount\n";
    $summary .= "Errores: $errorCount\n";
    $summary .= "Archivos omitidos (no imagen): $skippedCount\n";
    
    if (!$isCLI) {
        echo "</pre><h4>Resultado:</h4>";
        echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; white-space: pre-line;'>" . htmlspecialchars($summary) . "</div>";
        echo "</div>";
    } else {
        echo $summary;
    }
}

if (!$isCLI) adminFooter();
?>
