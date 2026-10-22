<?php
// admin/settings.php
require_once 'inc/auth.php';
require_once 'inc/layout.php';
require_once '../config.php';
require_once '../inc/mailer.php';
require_once 'inc/ai_news_helper.php';

$pdo = getDB();
// Asegurar que la contraseña encriptada y el correo info@moratalla-murcia.com estén inicializados en la BD
$mailConfig = getMailConfig($pdo);
$message = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_auto_news_digest']) && $_POST['test_auto_news_digest'] === '1') {
        // Guardar primero los horarios y correo si se modificaron en el formulario antes de probar
        $autoTime1 = isset($_POST['auto_news_time_1']) && preg_match('/^\d{2}:\d{2}$/', trim($_POST['auto_news_time_1'])) ? trim($_POST['auto_news_time_1']) : '00:00';
        $autoTime2 = isset($_POST['auto_news_time_2']) && preg_match('/^\d{2}:\d{2}$/', trim($_POST['auto_news_time_2'])) ? trim($_POST['auto_news_time_2']) : '00:00';
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_news_time_1', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$autoTime1, $autoTime1]);
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_news_time_2', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$autoTime2, $autoTime2]);

        $adminEmail = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
        if (!empty($adminEmail)) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_email', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$adminEmail, $adminEmail]);
        }

        $resTest = checkAndRunScheduledNewsDigest($pdo, true);
        if (!empty($resTest['success'])) {
            $message = "✅ Boletín de prueba de noticias de Moratalla y Banda de Música enviado correctamente al correo configurado (" . htmlspecialchars($resTest['message']) . ").";
        } else {
            $errorMessage = "❌ No se pudo enviar el boletín de prueba: " . htmlspecialchars($resTest['message']);
        }
    } else {
        $tickerText = isset($_POST['ticker_text']) ? $_POST['ticker_text'] : '';
        $tickerSpeed = isset($_POST['ticker_speed']) ? $_POST['ticker_speed'] : '30';
        $adminEmail = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
        $adminEmailPass = isset($_POST['admin_email_pass']) ? $_POST['admin_email_pass'] : '';
        $daysBefore = isset($_POST['celebration_days_before']) ? (int)$_POST['celebration_days_before'] : 5;
        $daysAfter = isset($_POST['celebration_days_after']) ? (int)$_POST['celebration_days_after'] : 2;
        
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('ticker_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$tickerText, $tickerText]);
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('ticker_speed', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$tickerSpeed, $tickerSpeed]);
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('celebration_days_before', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$daysBefore, $daysBefore]);
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('celebration_days_after', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$daysAfter, $daysAfter]);
        
        $cycleTime = isset($_POST['celebration_cycle_time']) ? (int)$_POST['celebration_cycle_time'] : 5;
        $cycleLimit = isset($_POST['celebration_cycle_limit']) ? (int)$_POST['celebration_cycle_limit'] : 0;
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('celebration_cycle_time', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$cycleTime, $cycleTime]);
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('celebration_cycle_limit', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$cycleLimit, $cycleLimit]);
        
        if (!empty($adminEmail)) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_email', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$adminEmail, $adminEmail]);
            $encUser = encryptSecret($adminEmail);
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('smtp_user_enc', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$encUser, $encUser]);
        }
        
        if ($adminEmailPass !== '') {
            $encPass = encryptSecret($adminEmailPass);
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('smtp_pass_enc', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$encPass, $encPass]);
        }
        
        $geminiApiKey = isset($_POST['gemini_api_key']) ? trim($_POST['gemini_api_key']) : '';
        if ($geminiApiKey !== '') {
            $encGemini = encryptSecret($geminiApiKey);
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gemini_api_key_enc', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$encGemini, $encGemini]);
        }

        // Guardar los 2 horarios de búsqueda automática desatendida de noticias (00:00 = desactivado)
        $autoTime1 = isset($_POST['auto_news_time_1']) && preg_match('/^\d{2}:\d{2}$/', trim($_POST['auto_news_time_1'])) ? trim($_POST['auto_news_time_1']) : '00:00';
        $autoTime2 = isset($_POST['auto_news_time_2']) && preg_match('/^\d{2}:\d{2}$/', trim($_POST['auto_news_time_2'])) ? trim($_POST['auto_news_time_2']) : '00:00';

        // Si el usuario cambió la hora respecto a la anterior, permitir que pueda ejecutarse hoy en la nueva hora
        $prevStmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('auto_news_time_1', 'auto_news_time_2')");
        $prevTimes = $prevStmt ? $prevStmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
        if (($prevTimes['auto_news_time_1'] ?? '00:00') !== $autoTime1) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_news_last_run_1', '') ON DUPLICATE KEY UPDATE setting_value = ''")->execute();
        }
        if (($prevTimes['auto_news_time_2'] ?? '00:00') !== $autoTime2) {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_news_last_run_2', '') ON DUPLICATE KEY UPDATE setting_value = ''")->execute();
        }

        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_news_time_1', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$autoTime1, $autoTime1]);
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('auto_news_time_2', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$autoTime2, $autoTime2]);
        
        $message = "Configuración actualizada con éxito.";
    }
}

// Obtener valores actuales
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$currentTicker = isset($settings['ticker_text']) ? $settings['ticker_text'] : '';
$currentSpeed = isset($settings['ticker_speed']) ? $settings['ticker_speed'] : '30';
$currentAdminEmail = isset($settings['admin_email']) && !empty($settings['admin_email']) ? $settings['admin_email'] : 'info@moratalla-murcia.com';
$hasEncryptedPass = !empty($settings['smtp_pass_enc']);
$hasEncryptedGemini = !empty($settings['gemini_api_key_enc']);
$currentDaysBefore = isset($settings['celebration_days_before']) ? $settings['celebration_days_before'] : '5';
$currentDaysAfter = isset($settings['celebration_days_after']) ? $settings['celebration_days_after'] : '2';
$currentCycleTime = isset($settings['celebration_cycle_time']) ? $settings['celebration_cycle_time'] : '5';
$currentCycleLimit = isset($settings['celebration_cycle_limit']) ? $settings['celebration_cycle_limit'] : '0';

$currentAutoNewsTime1 = isset($settings['auto_news_time_1']) && preg_match('/^\d{2}:\d{2}$/', $settings['auto_news_time_1']) ? $settings['auto_news_time_1'] : '00:00';
$currentAutoNewsTime2 = isset($settings['auto_news_time_2']) && preg_match('/^\d{2}:\d{2}$/', $settings['auto_news_time_2']) ? $settings['auto_news_time_2'] : '00:00';
$currentAutoNewsStatus = $settings['auto_news_last_status'] ?? 'Sin envíos registrados todavía.';

adminHeader("Configuración General");
?>

<div class="card">
    <h2><i class="fas fa-cog"></i> Configuración General</h2>
    <p>Ajusta los parámetros globales de la web moratalla-murcia.com</p>
    
    <?php if ($message): ?>
        <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div style="background: #ffebee; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <form method="POST" style="margin-top: 2rem;" autocomplete="off">
        <div style="margin-bottom: 2.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Correo Electrónico del Administrador</label>
            <input type="email" name="admin_email" value="<?php echo htmlspecialchars($currentAdminEmail); ?>" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="info@moratalla-murcia.com">
            <small style="color: #666; display: block; margin-top: 0.5rem;">Este es el correo donde se recibirán los mensajes de contacto, los resúmenes diarios automáticos de noticias y el que aparecerá en la política de privacidad.</small>
        </div>

        <div style="margin-bottom: 2.5rem;">
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; font-weight: 600;">
                <span>Contraseña del Correo Electrónico (Ionos SMTP)</span>
                <?php if ($hasEncryptedPass): ?>
                    <span style="background: #d1fae5; color: #065f46; font-size: 0.75rem; padding: 3px 10px; border-radius: 20px; font-weight: 700;">
                        <i class="fas fa-lock"></i> Encriptada (AES-256)
                    </span>
                <?php endif; ?>
            </label>
            <input type="password" name="admin_email_pass" value="" autocomplete="new-password" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="<?php echo $hasEncryptedPass ? '•••••••••••••••••••••••• (Oculta y protegida en BD — escribe solo si deseas cambiarla)' : 'Introduce la contraseña del correo...'; ?>">
            <small style="color: #666; display: block; margin-top: 0.5rem;">Por seguridad, la contraseña nunca se muestra en pantalla ni en el código fuente. Se almacena cifrada con AES-256 en la base de datos. Déjalo en blanco para mantener la contraseña actual.</small>
        </div>

        <div style="margin-bottom: 2.5rem; background: #f5f3ff; padding: 1.2rem; border-radius: 10px; border: 1px solid #ddd6fe;">
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 0.5rem; font-weight: 600; color: #4c1d95;">
                <span><i class="fas fa-robot"></i> Clave API de Google Gemini (IA para Noticias Automáticas)</span>
                <?php if ($hasEncryptedGemini): ?>
                    <span style="background: #d1fae5; color: #065f46; font-size: 0.75rem; padding: 3px 10px; border-radius: 20px; font-weight: 700;">
                        <i class="fas fa-lock"></i> Configurada y Encriptada (AES-256)
                    </span>
                <?php else: ?>
                    <span style="background: #fef3c7; color: #92400e; font-size: 0.75rem; padding: 3px 10px; border-radius: 20px; font-weight: 700;">
                        <i class="fas fa-info-circle"></i> Opcional (Recomendado)
                    </span>
                <?php endif; ?>
            </label>
            <input type="password" name="gemini_api_key" value="" autocomplete="new-password" style="width: 100%; padding: 1rem; border: 1px solid #c4b5fd; border-radius: 8px; font-size: 1rem; background: white;" placeholder="<?php echo $hasEncryptedGemini ? '•••••••••••••••••••••••• (Clave Gemini guardada y encriptada — escribe solo si deseas actualizarla)' : 'Pega aquí tu API Key de Google Gemini (AIzaSy...)'; ?>">
            <small style="color: #5b21b6; display: block; margin-top: 0.5rem;">
                Permite redactar noticias supervisadas con IA en la sección <strong>Noticias / Eventos</strong>. Puedes obtener una clave gratuita en <a href="https://aistudio.google.com/app/apikey" target="_blank" style="color: #6d28d9; font-weight: 700; text-decoration: underline;">Google AI Studio</a>. Se guarda cifrada con AES-256.
            </small>
        </div>

        <!-- NUEVO BLOQUE: BÚSQUEDA DESATENDIDA Y ENVÍO DIARIO DE NOTICIAS POR CORREO -->
        <div style="margin-bottom: 2.5rem; background: #ecfdf5; padding: 1.4rem; border-radius: 10px; border: 2px solid #6ee7b7;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 0.8rem;">
                <label style="display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.05rem; color: #065f46; margin: 0;">
                    <i class="fas fa-clock"></i>
                    <span>Búsqueda Desatendida Diaria de Noticias (Moratalla y Banda de Música)</span>
                </label>
                <?php if ($currentAutoNewsTime1 !== '00:00' || $currentAutoNewsTime2 !== '00:00'): ?>
                    <span style="background: #059669; color: white; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; font-weight: 700;">
                        <i class="fas fa-check-circle"></i> Programación Activa
                    </span>
                <?php else: ?>
                    <span style="background: #e2e8f0; color: #475569; font-size: 0.78rem; padding: 4px 12px; border-radius: 20px; font-weight: 700;">
                        <i class="fas fa-pause-circle"></i> Desactivado (00:00)
                    </span>
                <?php endif; ?>
            </div>
            <p style="color: #047857; font-size: 0.9rem; margin-top: 0; margin-bottom: 1.1rem; line-height: 1.5;">
                Indica hasta <strong>2 horarios diarios</strong> (hora de España). Al llegar cualquiera de las horas indicadas —siempre que sea distinta de <code>00:00</code>—, el sistema buscará de forma <strong>100% desatendida</strong> noticias sobre <strong>Moratalla</strong> y sobre la <strong>Banda de Música de Moratalla</strong>, elaborará un resumen con imagen de cada noticia y lo enviará al <strong>Correo Electrónico del Administrador</strong> (<code><?php echo htmlspecialchars($currentAdminEmail); ?></code>).
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.2rem; margin-bottom: 1.1rem;">
                <div style="background: white; padding: 1rem; border-radius: 8px; border: 1px solid #a7f3d0;">
                    <label style="display: block; font-weight: 700; color: #065f46; margin-bottom: 0.4rem;">
                        <i class="far fa-bell"></i> Horario 1 de Búsqueda Diaria
                    </label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="time" name="auto_news_time_1" id="auto_news_time_1" value="<?php echo htmlspecialchars($currentAutoNewsTime1); ?>" style="flex: 1; padding: 0.7rem; border: 1px solid #6ee7b7; border-radius: 6px; font-size: 1rem; font-weight: 700; color: #064e3b;">
                        <button type="button" onclick="document.getElementById('auto_news_time_1').value='00:00'" class="btn btn-sm" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 0.65rem 0.8rem; font-size: 0.8rem;" title="Poner en 00:00 (Desactivar Horario 1)">
                            00:00 (Off)
                        </button>
                    </div>
                    <small style="color: #047857; display: block; margin-top: 0.4rem;">Ej.: <code>09:00</code> para primera hora de la mañana. <code>00:00</code> = desactivado.</small>
                </div>

                <div style="background: white; padding: 1rem; border-radius: 8px; border: 1px solid #a7f3d0;">
                    <label style="display: block; font-weight: 700; color: #065f46; margin-bottom: 0.4rem;">
                        <i class="far fa-bell"></i> Horario 2 de Búsqueda Diaria
                    </label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="time" name="auto_news_time_2" id="auto_news_time_2" value="<?php echo htmlspecialchars($currentAutoNewsTime2); ?>" style="flex: 1; padding: 0.7rem; border: 1px solid #6ee7b7; border-radius: 6px; font-size: 1rem; font-weight: 700; color: #064e3b;">
                        <button type="button" onclick="document.getElementById('auto_news_time_2').value='00:00'" class="btn btn-sm" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 0.65rem 0.8rem; font-size: 0.8rem;" title="Poner en 00:00 (Desactivar Horario 2)">
                            00:00 (Off)
                        </button>
                    </div>
                    <small style="color: #047857; display: block; margin-top: 0.4rem;">Ej.: <code>20:00</code> para la tarde/noche. <code>00:00</code> = desactivado.</small>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; background: white; padding: 0.8rem 1rem; border-radius: 8px; border: 1px dashed #6ee7b7;">
                <div style="font-size: 0.84rem; color: #065f46;">
                    <strong><i class="fas fa-info-circle"></i> Estado del proceso automático:</strong>
                    <span><?php echo htmlspecialchars($currentAutoNewsStatus); ?></span>
                </div>
                <button type="submit" name="test_auto_news_digest" value="1" class="btn btn-sm" style="background: #059669; color: white; font-weight: 700; padding: 0.55rem 1rem; border-radius: 6px;">
                    <i class="fas fa-paper-plane"></i> Buscar y Enviar Resumen al Correo Ahora (Prueba)
                </button>
            </div>
        </div>

        <div style="margin-bottom: 2.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Texto Móvil (Cabecera)</label>
            <input type="text" name="ticker_text" value="<?php echo htmlspecialchars($currentTicker); ?>" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="Escribe aquí el texto que se moverá en la cabecera...">
        </div>

        <div style="margin-bottom: 2.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Velocidad del Texto (Segundos)</label>
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <input type="range" name="ticker_speed" min="5" max="100" step="1" value="<?php echo htmlspecialchars($currentSpeed); ?>" style="flex: 1; accent-color: var(--primary);">
                <span style="font-weight: 800; color: var(--primary); font-size: 1.2rem; min-width: 50px;"><?php echo $currentSpeed; ?>s</span>
            </div>
            <small style="color: #666; display: block; margin-top: 0.5rem;">Menos segundos = más rápido. Más segundos = más lento.</small>
        </div>
        
        <div style="margin-bottom: 2.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Días antes para Acontecimientos Recurrentes</label>
            <input type="number" name="celebration_days_before" min="0" value="<?php echo htmlspecialchars($currentDaysBefore); ?>" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
            <small style="color: #666; display: block; margin-top: 0.5rem;">Cuántos días antes de la fecha principal aparecerá el banner automáticamente (por defecto: 5).</small>
        </div>

        <div style="margin-bottom: 2.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Días después para Acontecimientos Recurrentes</label>
            <input type="number" name="celebration_days_after" min="0" value="<?php echo htmlspecialchars($currentDaysAfter); ?>" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
            <small style="color: #666; display: block; margin-top: 0.5rem;">Cuántos días después de la fecha principal se ocultará el banner automáticamente (por defecto: 2).</small>
        </div>
        
        <div style="margin-bottom: 2.5rem; border-top: 1px solid #eee; padding-top: 1.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Tiempo entre Acontecimientos (Segundos)</label>
            <input type="number" name="celebration_cycle_time" min="1" value="<?php echo htmlspecialchars($currentCycleTime); ?>" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
            <small style="color: #666; display: block; margin-top: 0.5rem;">Si coinciden varios eventos a la vez, cada cuántos segundos rotará el carrusel para mostrar el siguiente (por defecto: 5).</small>
        </div>
        
        <div style="margin-bottom: 2.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Límite de Ciclos del Carrusel (Por sesión)</label>
            <input type="number" name="celebration_cycle_limit" min="0" value="<?php echo htmlspecialchars($currentCycleLimit); ?>" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
            <small style="color: #666; display: block; margin-top: 0.5rem;">Cuántas veces se repetirán los efectos visuales antes de desactivarse para no molestar a la lectura. Si marcas 0, el carrusel será infinito (por defecto: 0).</small>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Guardar Configuración
        </button>
    </form>
</div>

<script>
    const range = document.querySelector('input[type="range"]');
    const span = document.querySelector('span[style*="min-width: 50px"]');
    range.addEventListener('input', () => {
        span.textContent = range.value + 's';
    });
</script>

<?php adminFooter(); ?>
