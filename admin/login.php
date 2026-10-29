<?php
// admin/login.php
require_once '../config.php';
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$username = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $captcha  = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';
    $honeypot = isset($_POST['website_hp']) ? trim($_POST['website_hp']) : '';
    
    if (!empty($honeypot)) {
        $error = "Intento rechazado por el filtro de seguridad anti-robots.";
    } elseif ($captcha === '' || !isset($_SESSION['admin_captcha_sum']) || (int)$captcha !== (int)$_SESSION['admin_captcha_sum']) {
        $error = "La respuesta del CAPTCHA de seguridad es incorrecta. Inténtalo de nuevo.";
    } else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Verificar si la cuenta está bloqueada
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $error = "Cuenta bloqueada temporalmente por seguridad. Inténtalo más tarde.";
            } else {
                if (password_verify($password, $user['password'])) {
                    // Éxito: Reiniciar intentos y redirigir
                    $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?")->execute([$user['id']]);
                    unset($_SESSION['admin_captcha_n1'], $_SESSION['admin_captcha_n2'], $_SESSION['admin_captcha_sum']);
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_user'] = $user['username'];
                    header("Location: index.php");
                    exit;
                } else {
                    // Fallo: Incrementar intentos
                    $attempts = $user['failed_attempts'] + 1;
                    $locked_until = null;
                    if ($attempts >= 5) {
                        $locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $error = "Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.";
                    } else {
                        $error = "Contraseña incorrecta. Intentos restantes: " . (5 - $attempts);
                    }
                    $pdo->prepare("UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?")->execute([$attempts, $locked_until, $user['id']]);
                }
            }
        } else {
            $error = "Usuario no encontrado.";
        }
    }
}

// Generar nuevo CAPTCHA para cada carga/intento
$_SESSION['admin_captcha_n1'] = rand(1, 10);
$_SESSION['admin_captcha_n2'] = rand(1, 10);
$_SESSION['admin_captcha_sum'] = $_SESSION['admin_captcha_n1'] + $_SESSION['admin_captcha_n2'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Moratalla 2026</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; background: var(--dark); }
        .login-card { background: white; padding: 2.5rem 3rem; border-radius: 20px; width: 100%; max-width: 420px; box-shadow: var(--shadow); }
        .login-card h2 { margin-bottom: 2rem; color: var(--primary); text-align: center; font-weight: 800; letter-spacing: -1px; }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 0.8rem; border: 1px solid var(--gray-300); border-radius: 8px; font-family: inherit; }
        .captcha-box { background: var(--gray-100); padding: 1rem; border-radius: 10px; border: 1px dashed var(--gray-300); margin-bottom: 1.25rem; }
        .captcha-box label { display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 0.85rem; color: var(--primary); }
        .captcha-row { display: flex; align-items: center; gap: 0.75rem; }
        .captcha-badge { background: white; border: 1px solid var(--gray-300); border-radius: 8px; padding: 0.65rem 1rem; font-weight: 700; font-size: 1.05rem; color: var(--primary); white-space: nowrap; user-select: none; display: flex; align-items: center; gap: 0.4rem; }
        .captcha-row input { flex: 1; padding: 0.65rem; border: 2px solid var(--primary); border-radius: 8px; font-size: 1rem; font-weight: 700; text-align: center; outline: none; }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.85rem; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <div style="text-align: center; margin-bottom: 1.25rem;">
            <img id="login-logo" src="../uploads/theme/logo.jpg" alt="Logo Moratalla" style="max-height: 90px; width: auto; border-radius: 8px; transition: opacity 0.5s;">
            <h1 style="font-size: 1.4rem; color: var(--dark); margin-top: 0.8rem; margin-bottom: 0; font-weight: 700;">moratalla-murcia.com</h1>
        </div>
        <h2 style="font-size: 1.2rem; margin-bottom: 1.5rem; color: #666;">ADMIN LOGIN</h2>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required autocomplete="new-password">
            </div>
            <!-- Honeypot anti-bots oculto -->
            <div style="position:absolute; left:-9999px; opacity:0; pointer-events:none;" aria-hidden="true">
                <label for="website_hp">No rellenar</label>
                <input type="text" name="website_hp" id="website_hp" tabindex="-1" autocomplete="off">
            </div>
            <div class="captcha-box">
                <label for="captcha"><i class="fas fa-shield-alt"></i> Verificación de Seguridad (CAPTCHA) *</label>
                <div class="captcha-row">
                    <div class="captcha-badge">
                        <?php echo (int)$_SESSION['admin_captcha_n1']; ?> + <?php echo (int)$_SESSION['admin_captcha_n2']; ?> = ?
                    </div>
                    <input type="number" id="captcha" name="captcha" required placeholder="Resultado" autocomplete="off">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 0.5rem;">Entrar al Panel</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem; font-size: 0.8rem; color: #888;">
            <a href="../index.php" style="color: inherit; text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver a la web</a>
        </p>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logo = document.getElementById('login-logo');
            const logos = ['../uploads/theme/logo.jpg', '../uploads/theme/logo2.jpg'];
            let currentLogo = 0;
            setInterval(() => {
                logo.style.opacity = '0';
                setTimeout(() => {
                    currentLogo = (currentLogo + 1) % logos.length;
                    logo.src = logos[currentLogo];
                    logo.style.opacity = '1';
                }, 500); // Wait for fade out
            }, 4000); // Alternate every 4 seconds
        });
    </script>
</body>
</html>
