<?php
// contacto.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
$pdo = getDB();

$pageTitle = "Contacto";
$pageDescription = "Formulario de contacto para enviar sugerencias, dudas o aportar material al archivo histórico.";

$mensajeExito = "";
$mensajeError = "";

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
    $telefono = trim(isset($_POST['telefono']) ? $_POST['telefono'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $mensaje = trim(isset($_POST['mensaje']) ? $_POST['mensaje'] : '');
    $captcha = trim(isset($_POST['captcha']) ? $_POST['captcha'] : '');
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($mensaje) || empty($captcha)) {
        $mensajeError = "Por favor, rellena todos los campos obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensajeError = "El correo electrónico introducido no es válido.";
    } elseif (!isset($_SESSION['captcha_sum']) || (int)$captcha !== $_SESSION['captcha_sum']) {
        $mensajeError = "La respuesta a la suma de seguridad es incorrecta. Eres humano, ¿verdad?";
    } else {
        // Enviar el correo
        $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $adminEmail = isset($settings['admin_email']) && !empty($settings['admin_email']) ? $settings['admin_email'] : 'pablosalinas@moratalla-murcia.com';
        
        $to = $adminEmail;
        $subject = "Nuevo mensaje de contacto desde moratalla-murcia.com";
        
        $body = "Has recibido un nuevo mensaje desde el formulario de contacto de la web.\n\n";
        $body .= "DATOS DEL REMITENTE:\n";
        $body .= "Nombre: $nombre\n";
        $body .= "Teléfono: " . ($telefono ?: 'No facilitado') . "\n";
        $body .= "Correo: $email\n\n";
        $body .= "MENSAJE:\n";
        $body .= "$mensaje\n";
        
        $headers = "From: " . $adminEmail . "\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Determinar si estamos en localhost
        $isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost') !== false;
        
        if (mail($to, $subject, $body, $headers, "-f" . $adminEmail)) {
            $mensajeExito = "¡Gracias por contactar con nosotros, $nombre! Tu mensaje se ha enviado correctamente. Te responderemos lo antes posible a $email.";
            // Limpiar datos para evitar doble envío
            $nombre = $telefono = $email = $mensaje = "";
        } else {
            if ($isLocalhost) {
                // Simulación en local guardando en archivo
                $logFile = __DIR__ . '/scratch/local_mail_' . time() . '.txt';
                if (!is_dir(__DIR__ . '/scratch')) mkdir(__DIR__ . '/scratch', 0777, true);
                file_put_contents($logFile, "TO: $to\nSUBJECT: $subject\nHEADERS:\n$headers\nBODY:\n$body");
                
                $mensajeExito = "[MODO LOCAL] El correo se ha 'enviado' simuladamente y guardado en $logFile.";
                $nombre = $telefono = $email = $mensaje = "";
            } else {
                $error = error_get_last();
                $errorMsg = isset($error['message']) ? $error['message'] : 'Desconocido';
                $mensajeError = "Ha ocurrido un error interno del servidor al intentar enviar el correo. Por favor, inténtalo más tarde. (Detalle: " . $errorMsg . ")";
            }
        }
    }
}

// Generar nuevo CAPTCHA
$_SESSION['captcha_num1'] = rand(1, 10);
$_SESSION['captcha_num2'] = rand(1, 10);
$_SESSION['captcha_sum'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];

require_once 'inc/header.php';
?>

<section class="hero-page" style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('uploads/theme/moratalla.jpg'); background-size: cover; background-position: center; padding: 6rem 0; text-align: center; color: white;">
    <div class="container">
        <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 2rem; border-radius: 20px; display: inline-block; border: 1px solid rgba(255,255,255,0.2);">
            <h2 style="color: white; margin-bottom: 0.5rem; font-size: 3rem; text-shadow: 0 2px 10px rgba(0,0,0,0.3);"><i class="fas fa-envelope"></i> Contacto</h2>
            <p style="color: rgba(255,255,255,0.9); font-weight: 600;"><i class="fas fa-home"></i> Inicio <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin: 0 10px; opacity: 0.5;"></i> Contacto</p>
        </div>
    </div>
</section>

<div class="container main-content" style="max-width: 800px; padding: 3rem 1.5rem;">
    <div class="content-card" style="box-shadow: 0 15px 35px rgba(0,0,0,0.05); border: 1px solid var(--gray-200); border-radius: 24px; padding: 3rem 2rem;">
        
        <div style="text-align: center; margin-bottom: 2.5rem;">
            <h3 style="color: var(--primary); font-size: 1.8rem; margin-bottom: 1rem;">Envíanos tu mensaje</h3>
            <p style="color: var(--text-light); font-size: 1.1rem; line-height: 1.6;">
                Si tienes alguna sugerencia, duda, o deseas aportar material (fotos, documentos) al archivo histórico de Moratalla, no dudes en escribirnos.
            </p>
        </div>

        <?php if ($mensajeExito): ?>
            <div style="background: #d1fae5; color: #065f46; border: 1px solid #34d399; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; text-align: center; font-weight: 600; font-size: 1.1rem;">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                <?php echo htmlspecialchars($mensajeExito); ?>
            </div>
        <?php endif; ?>

        <?php if ($mensajeError): ?>
            <div style="background: #fee2e2; color: #991b1b; border: 1px solid #f87171; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; text-align: center; font-weight: 600;">
                <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                <?php echo htmlspecialchars($mensajeError); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="contacto.php" style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-dark);">Nombre y Apellidos <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="nombre" required value="<?php echo htmlspecialchars(isset($nombre) ? $nombre : ''); ?>" placeholder="Ej: María García" style="width: 100%; padding: 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 1rem; font-family: inherit; transition: border-color 0.3s; outline: none;">
                </div>
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-dark);">Teléfono (Opcional)</label>
                    <input type="tel" name="telefono" value="<?php echo htmlspecialchars(isset($telefono) ? $telefono : ''); ?>" placeholder="Ej: 600 123 456" style="width: 100%; padding: 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 1rem; font-family: inherit; transition: border-color 0.3s; outline: none;">
                </div>
            </div>

            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-dark);">Correo Electrónico <span style="color: #e74c3c;">*</span></label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars(isset($email) ? $email : ''); ?>" placeholder="tu@correo.com" style="width: 100%; padding: 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 1rem; font-family: inherit; transition: border-color 0.3s; outline: none;">
            </div>

            <div>
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-dark);">Mensaje <span style="color: #e74c3c;">*</span></label>
                <textarea name="mensaje" required placeholder="Escribe aquí tu consulta o comentario..." style="width: 100%; height: 180px; padding: 1rem; border: 2px solid var(--gray-200); border-radius: 12px; font-size: 1rem; font-family: inherit; resize: vertical; transition: border-color 0.3s; outline: none;"><?php echo htmlspecialchars(isset($mensaje) ? $mensaje : ''); ?></textarea>
            </div>

            <div style="background: var(--bg-alt); padding: 1.5rem; border-radius: 12px; border: 2px dashed var(--gray-300);">
                <label style="display:block; margin-bottom: 0.5rem; font-weight: 600; color: var(--primary-dark);">Comprobación de Seguridad <span style="color: #e74c3c;">*</span></label>
                <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 1rem;">Para evitar mensajes automáticos (spam), por favor resuelve esta sencilla suma:</p>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); background: white; padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <?php echo $_SESSION['captcha_num1']; ?> + <?php echo $_SESSION['captcha_num2']; ?> =
                    </div>
                    <input type="number" name="captcha" required placeholder="?" style="width: 100px; padding: 0.8rem; border: 2px solid var(--primary); border-radius: 8px; font-size: 1.2rem; font-weight: bold; text-align: center; outline: none;">
                </div>
            </div>

            <button type="submit" style="background: var(--primary); color: white; border: none; padding: 1.2rem; border-radius: 12px; font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: transform 0.2s, background 0.3s; margin-top: 1rem; box-shadow: 0 4px 15px rgba(27, 67, 50, 0.2);">
                <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Enviar Mensaje
            </button>
            
        </form>
        
        <script>
            // Pequeño script para mejorar el UX de los inputs
            document.querySelectorAll('input, textarea').forEach(el => {
                el.addEventListener('focus', () => el.style.borderColor = 'var(--primary)');
                el.addEventListener('blur', () => el.style.borderColor = 'var(--gray-200)');
            });
        </script>
        
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
