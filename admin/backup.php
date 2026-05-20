<?php
require_once '../config.php';
require_once 'inc/auth.php';

if (isset($_POST['download_backup'])) {
    $pdo = getDB();
    
    // Preparar el archivo de backup en memoria
    $sqlDump = "-- =================================================\n";
    $sqlDump .= "-- Copia de seguridad de Base de Datos moratalla-murcia.com\n";
    $sqlDump .= "-- Fecha de generacion: " . date('Y-m-d H:i:s') . "\n";
    $sqlDump .= "-- =================================================\n\n";
    $sqlDump .= "SET NAMES 'utf8mb4';\n";
    $sqlDump .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

    try {
        set_time_limit(0); // Evitar timeout para zips grandes
        
        // Obtener todas las tablas
        $tables = [];
        $stmt = $pdo->query('SHOW TABLES');
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $createRow = $stmt->fetch(PDO::FETCH_NUM);
            $sqlDump .= "-- Estructura de la tabla `$table`\n";
            $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlDump .= $createRow[1] . ";\n\n";

            $stmt = $pdo->query("SELECT * FROM `$table`");
            if ($stmt->rowCount() > 0) {
                $sqlDump .= "-- Datos de la tabla `$table`\n";
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $keys = array_keys($row);
                    $keysString = '`' . implode('`, `', $keys) . '`';
                    
                    $values = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = $pdo->quote($val);
                        }
                    }
                    $valuesString = implode(', ', $values);
                    $sqlDump .= "INSERT INTO `$table` ($keysString) VALUES ($valuesString);\n";
                }
                $sqlDump .= "\n";
            }
        }
        $sqlDump .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        // Crear ZIP temporal
        $zipFile = sys_get_temp_dir() . '/backup_' . date('Y_m_d_His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("No se pudo crear el archivo ZIP.");
        }

        // Añadir base de datos al ZIP
        $zip->addFromString('database.sql', $sqlDump);

        // Añadir carpeta uploads al ZIP
        $uploadsDir = realpath(__DIR__ . '/../uploads');
        if ($uploadsDir !== false && is_dir($uploadsDir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($uploadsDir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = 'uploads/' . substr($filePath, strlen($uploadsDir) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();

        // Enviar ZIP al usuario
        $filename = 'backup_completo_moratalla_' . date('Y_m_d_His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zipFile));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        readfile($zipFile);
        unlink($zipFile); // Borrar temporal
        exit;

    } catch (Exception $e) {
        $error = "Error al generar la copia de seguridad: " . $e->getMessage();
    }
}

require_once 'inc/layout.php';
adminHeader("Copia de Seguridad");
?>

<div class="header-actions" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1><i class="fas fa-database"></i> Copia de Seguridad</h1>
</div>

<?php if (isset($error)): ?>
    <div class="message error" style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
    <h2 style="margin-top: 0; color: #1e293b;">Descargar Backup Completo (ZIP)</h2>
    <p style="color: #64748b; line-height: 1.6; margin-bottom: 1.5rem;">
        Esta herramienta te permite generar y descargar un archivo comprimido <strong>.zip</strong> que incluye toda la base de datos (<strong>database.sql</strong>) y todos los archivos subidos al servidor (carpeta <strong>uploads</strong> con imágenes de noticias, asociaciones, etc.). 
        Este archivo sirve para poder restaurar la web al completo en caso de emergencia.
    </p>
    
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid var(--primary); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
        <h4 style="margin-top: 0; margin-bottom: 0.5rem; color: #334155;"><i class="fas fa-info-circle"></i> Información del proceso</h4>
        <ul style="margin: 0; padding-left: 1.5rem; color: #475569;">
            <li>El proceso puede tardar unos segundos dependiendo del tamaño de las imágenes y datos (noticias, citas, etc.).</li>
            <li>No cierres la página hasta que la descarga del archivo haya comenzado.</li>
            <li>Guarda el archivo descargado en un lugar seguro.</li>
        </ul>
    </div>

    <form method="POST" action="backup.php" onsubmit="this.querySelector('button').innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> Generando ZIP...'; this.querySelector('button').style.opacity = '0.7';">
        <button type="submit" name="download_backup" class="btn btn-primary" style="font-size: 1.1rem; padding: 0.8rem 1.5rem; display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-file-archive"></i> Generar y Descargar Backup
        </button>
    </form>
</div>

<?php
adminFooter();
?>
