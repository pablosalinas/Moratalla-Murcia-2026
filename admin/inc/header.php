<?php
session_start();
require_once __DIR__ . '/../../config.php';

function checkLogin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: index.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Moratalla 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <h2>Panel Moratalla</h2>
        <nav>
            <ul>
                <li><a href="dashboard.php">Inicio</a></li>
                <li><a href="pages.php">Páginas y Textos</a></li>
                <li><a href="categories.php">Menú y Submenús</a></li>
                <li><a href="images.php">Galería de Imágenes</a></li>
                <li><a href="news.php">Noticias y Eventos</a></li>
                <li><a href="users.php">Usuarios</a></li>
                <br>
                <li style="padding-left: 1rem; color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase;">Ajustes</li>
                <li><a href="settings.php">Configuración General</a></li>
                <li><a href="banners.php">Banner Interactivo</a></li>
                <br>
                <li><a href="logout.php?redirect=../index.php">Ver Web</a></li>
                <br>
                <li><a href="logout.php" style="color: #f87171;">Cerrar Sesión</a></li>
            </ul>
        </nav>
    </aside>
    <main class="main-content">
