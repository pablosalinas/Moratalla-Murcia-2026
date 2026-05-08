<?php
// admin/logout.php
session_start();

// 1. Limpiar todas las variables de sesión
$_SESSION = array();

// 2. Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_start(), '', time()-42000, '/');
}

// 3. Destruir la sesión físicamente en el servidor
session_destroy();

// 4. Redirigir al login asegurando que no haya caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Location: login.php?msg=logged_out");
exit;
?>
