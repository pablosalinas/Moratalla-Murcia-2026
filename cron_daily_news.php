<?php
/**
 * cron_daily_news.php
 * Proceso desatendido para la búsqueda diaria programada de noticias de Moratalla y Banda de Música,
 * generación de resumen con imagen y envío por correo electrónico al administrador.
 *
 * Puede ejecutarse:
 * 1) Por tarea programada del sistema (Windows Task Scheduler / Linux Crontab): `php cron_daily_news.php`
 * 2) Por llamada HTTP desde GitHub Actions Cron / Ionos Cron: `cron_daily_news.php`
 * 3) De forma transparente en segundo plano (sin bloquear usuarios) si aún no se hubiera disparado.
 */

@set_time_limit(180);
@ignore_user_abort(true);
date_default_timezone_set('Europe/Madrid');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/admin/inc/ai_news_helper.php';

$pdo = getDB();

// Evitar ejecuciones simultáneas con un archivo de bloqueo temporal (máx 3 minutos)
$lockFile = sys_get_temp_dir() . '/mm_auto_news_cron.lock';
if (file_exists($lockFile) && (time() - @filemtime($lockFile)) < 180) {
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['status' => 'locked', 'message' => 'Ejecución en curso.']);
    exit;
}
@touch($lockFile);

try {
    $force = false;
    if (php_sapi_name() === 'cli') {
        global $argv;
        if (!empty($argv) && in_array('--force', $argv, true)) {
            $force = true;
        }
    } elseif (isset($_GET['force']) && $_GET['force'] === '1') {
        $providedSecret = $_GET['secret'] ?? '';
        if (!empty(MIGRATE_SECRET) && $providedSecret === MIGRATE_SECRET) {
            $force = true;
        }
    }

    $result = checkAndRunScheduledNewsDigest($pdo, $force);

    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status'    => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
        'result'    => $result
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'error',
        'error'  => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    @unlink($lockFile);
}
