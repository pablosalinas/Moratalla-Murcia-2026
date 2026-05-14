<?php
require_once 'inc/header.php';
checkLogin();

$pdo = getDB();
$countPages = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$countCats = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$countImages = $pdo->query("SELECT COUNT(*) FROM page_images")->fetchColumn();
?>

<div class="header-admin">
    <h1>Dashboard</h1>
</div>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3>Páginas</h3>
        <p style="font-size: 2rem; font-weight: 800; color: #10b981;"><?= $countPages ?></p>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3>Categorías</h3>
        <p style="font-size: 2rem; font-weight: 800; color: #10b981;"><?= $countCats ?></p>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3>Imágenes</h3>
        <p style="font-size: 2rem; font-weight: 800; color: #10b981;"><?= $countImages ?></p>
    </div>
</div>

<div style="margin-top: 3rem; background: #fffbeb; border: 1px solid #fde68a; padding: 2rem; border-radius: 12px;">
    <h2>Mantenimiento y Sincronización</h2>
    <p>Si notas que falta contenido o el menú no está actualizado, pulsa el botón para sincronizar la base de datos.</p>
    <a href="../migrate_runner.php?secret=<?= urlencode(MIGRATE_SECRET) ?>" target="_blank" class="btn" style="background: #d97706; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; display: inline-block; margin-top: 1rem; font-weight: 600;">
        🔄 Sincronizar Base de Datos Ahora
    </a>
</div>

<div style="margin-top: 3rem;">
    <h2>Bienvenido al Panel de Administración 2026</h2>
    <p>Desde aquí podrás gestionar todos los contenidos de la web, incluyendo textos de las fiestas, lugares, asociaciones y galerías de imágenes.</p>
</div>


<?php require_once 'inc/footer.php'; ?>
