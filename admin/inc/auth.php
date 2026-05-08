<?php
// admin/inc/auth.php
session_start();

function checkAuth() {
    // Evitar que el navegador guarde en caché las páginas de admin
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
