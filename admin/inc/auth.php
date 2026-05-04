<?php
// admin/inc/auth.php
session_start();

function checkAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
