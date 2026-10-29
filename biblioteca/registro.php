<?php
session_start();
require 'db.php';
require_once 'includes/mailer.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha  = trim($_POST['captcha'] ?? '');

    if ($username !== '' && $password !== '' && $captcha !== '') {
        if ((int)$captcha !== ($_SESSION['captcha_answer'] ?? -1)) {
            $error = 'Respuesta de seguridad incorrecta. Inténtalo de nuevo.';
        } else {
            // Verificar si el usuario ya existe
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'El nombre de usuario ya está registrado.';
            } else {
                // Crear el usuario con la contraseña encriptada y en estado deshabilitado (activo = 0)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol, activo) VALUES (?, ?, 'consulta', 0)");

                if ($stmt->execute([$username, $hashed_password])) {
                    // Enviar correo a info@moratalla-murcia.com indicando que hay alta pendiente de confirmación
                    $mailError = null;
                    enviarCorreoAltaPendiente($username, $mailError);

                    $success = 'Solicitud de alta registrada correctamente. Tu cuenta ha quedado en estado <strong>deshabilitado</strong> pendiente de confirmación hasta que un supervisor la autorice.';
                } else {
                    $error = 'Ocurrió un error al registrar la solicitud de alta.';
                }
            }
        }
    } else {
        $error = 'Por favor, rellena todos los campos.';
    }
}

if (!$success) {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['captcha_answer'] = $num1 + $num2;
    $_SESSION['captcha_question'] = "¿Cuánto es $num1 + $num2?";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Alta - Biblioteca Dalias y Tortas</title>
    <link rel="shortcut icon" href="assets/img/favicon.ico?v=5" type="image/x-icon">
    <link rel="icon" href="assets/img/favicon.ico?v=5" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-indigo-600">Solicitar Alta de Usuario</h1>
            <p class="text-gray-500 mt-2 text-sm">Regístrate indicando tu usuario y contraseña. Tu cuenta quedará pendiente de autorización por un supervisor.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm text-center">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-lg mb-4 text-sm text-center space-y-2">
                <p><?= $success ?></p>
                <p class="pt-2"><a href="login.php" class="text-indigo-600 hover:underline font-bold">Volver al inicio de sesión</a></p>
            </div>
        <?php else: ?>
            <form method="POST" action="registro.php" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Usuario</label>
                    <input type="text" name="username" id="username" required autofocus
                           class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input type="password" name="password" id="password" required 
                           class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label for="captcha" class="block text-sm font-medium text-gray-700">Verificación de seguridad: <strong class="text-indigo-600"><?= htmlspecialchars($_SESSION['captcha_question']) ?></strong></label>
                    <input type="number" name="captcha" id="captcha" required placeholder="Escribe el resultado..."
                           class="mt-1 w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <button type="submit" class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors">
                    Solicitar Alta
                </button>
            </form>
        <?php endif; ?>

        <p class="mt-6 text-center text-sm text-gray-600">
            ¿Ya tienes cuenta autorizada? <a href="login.php" class="text-indigo-600 hover:underline font-medium">Inicia sesión</a>
        </p>

        <div class="mt-6 pt-4 border-t border-gray-100 text-center">
            <a href="https://www.moratalla-murcia.com" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-indigo-600 font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver a moratalla-murcia.com
            </a>
        </div>
    </div>
</body>
</html>
