<?php
// admin/settings.php
require_once 'inc/auth.php';
require_once 'inc/layout.php';
require_once '../config.php';

$pdo = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tickerText = isset($_POST['ticker_text']) ? $_POST['ticker_text'] : '';
    $tickerSpeed = isset($_POST['ticker_speed']) ? $_POST['ticker_speed'] : '30';
    $adminEmail = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';
    
    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('ticker_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$tickerText, $tickerText]);
    $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('ticker_speed', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$tickerSpeed, $tickerSpeed]);
    
    if (!empty($adminEmail)) {
        $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_email', ?) ON DUPLICATE KEY UPDATE setting_value = ?")->execute([$adminEmail, $adminEmail]);
    }
    
    $message = "Configuración actualizada con éxito.";
}

// Obtener valores actuales
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$currentTicker = isset($settings['ticker_text']) ? $settings['ticker_text'] : '';
$currentSpeed = isset($settings['ticker_speed']) ? $settings['ticker_speed'] : '30';
$currentAdminEmail = isset($settings['admin_email']) && !empty($settings['admin_email']) ? $settings['admin_email'] : 'pablosalinas@moratalla-murcia.com';

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

    <form method="POST" style="margin-top: 2rem;">
        <div style="margin-bottom: 2.5rem;">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Correo Electrónico del Administrador</label>
            <input type="email" name="admin_email" value="<?php echo htmlspecialchars($currentAdminEmail); ?>" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="admin@moratalla-murcia.com">
            <small style="color: #666; display: block; margin-top: 0.5rem;">Este es el correo donde se recibirán los mensajes de contacto y el que aparecerá en la política de privacidad.</small>
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
