<?php
// admin/login.php
require_once '../config.php';
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Moratalla 2026</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { display: flex; align-items: center; justify-content: center; height: 100vh; background: var(--dark); }
        .login-card { background: white; padding: 3rem; border-radius: 20px; width: 100%; max-width: 400px; box-shadow: var(--shadow); }
        .login-card h2 { margin-bottom: 2rem; color: var(--primary); text-align: center; font-weight: 800; letter-spacing: -1px; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; }
        .form-group input { width: 100%; padding: 0.8rem; border: 1px solid var(--gray-300); border-radius: 8px; font-family: inherit; }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.85rem; text-align: center; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>ADMIN LOGIN</h2>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label>Usuario</label>
                <input type="text" name="username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Contraseña</label>
                <input type="password" name="password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 1rem;">Entrar al Panel</button>
        </form>
        <p style="text-align: center; margin-top: 2rem; font-size: 0.8rem; color: #888;">
            <a href="../index.php" style="color: inherit; text-decoration: none;"><i class="fas fa-arrow-left"></i> Volver a la web</a>
        </p>
    </div>
</body>
</html>
