<?php
/**
 * includes/auth.php
 * Guard de autenticación y comprobación de rol.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    // Si estamos en un subdirectorio (como controllers/), necesitamos subir un nivel
    $path = (strpos($_SERVER['SCRIPT_NAME'], '/controllers/') !== false) ? '../login.php' : 'login.php';
    header("Location: $path");
    exit;
}

// Rol del usuario en sesión
$rolUsuario = $_SESSION['rol'] ?? 'consulta';
$esAdmin    = ($rolUsuario === 'admin');

/**
 * Redirige si el usuario no es administrador.
 */
function adminOnly() {
    global $esAdmin;
    if (!$esAdmin) {
        $path = (strpos($_SERVER['SCRIPT_NAME'], '/controllers/') !== false) ? '../index.php' : 'index.php';
        header("Location: $path");
        exit;
    }
}
