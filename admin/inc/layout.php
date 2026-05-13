<?php
// admin/inc/layout.php
function adminHeader($title = "Admin Panel") {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> - moratalla-murcia.com</title>
        <link rel="stylesheet" href="admin-style.css">
        <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body class="admin-body">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 style="font-size: 1.1rem; letter-spacing: 0;">moratalla-murcia.com</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="categories.php" class="nav-link"><i class="fas fa-folder-tree"></i> Categorías</a>
                <a href="pages.php" class="nav-link"><i class="fas fa-file-alt"></i> Páginas</a>
                <a href="images.php" class="nav-link"><i class="fas fa-images"></i> Galería</a>
                <a href="users.php" class="nav-link"><i class="fas fa-users-cog"></i> Usuarios</a>
                <div class="nav-divider"></div>
                <a href="settings.php" class="nav-link"><i class="fas fa-cogs"></i> Configuración Gral.</a>
                <a href="banners.php" class="nav-link"><i class="fas fa-image"></i> Banner Interactivo</a>
                <div class="nav-divider"></div>
                <a href="../index.php" target="_blank" class="nav-link"><i class="fas fa-external-link-alt"></i> Ver Web</a>
                <a href="logout.php" class="nav-link logout"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="top-bar">
                <div class="user-info">
                    <span>Bienvenido, <strong>Admin</strong></span>
                </div>
            </header>
            <div class="content-wrapper">
    <?php
}

function adminFooter() {
    ?>
            </div>
        </main>
    </body>
    </html>
    <?php
}
?>
