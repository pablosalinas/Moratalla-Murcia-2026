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
        <style>
            .nav-link-btn {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 0.8rem 1.2rem;
                border-radius: 10px;
                text-decoration: none;
                font-size: 0.9rem;
                font-weight: 600;
                transition: all 0.3s ease;
                margin-bottom: 0.5rem;
            }
            .nav-link-btn:hover {
                transform: translateY(-2px);
                filter: brightness(1.1);
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            }
            .logout-btn:hover {
                background: #c1121f !important;
            }
        </style>
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
                <a href="news.php" class="nav-link"><i class="fas fa-newspaper"></i> Noticias / Eventos</a>
                <a href="messages.php" class="nav-link"><i class="fas fa-inbox"></i> Bandeja de Mensajes</a>
                <a href="quotes.php" class="nav-link"><i class="fas fa-quote-left"></i> Citas Ilustres</a>
                <a href="external_links.php" class="nav-link"><i class="fas fa-external-link-alt"></i> Accesos Externos</a>
                <a href="users.php" class="nav-link"><i class="fas fa-users-cog"></i> Usuarios</a>
                <div class="nav-divider"></div>
                <a href="settings.php" class="nav-link"><i class="fas fa-cogs"></i> Configuración Gral.</a>
                <a href="banners.php" class="nav-link"><i class="fas fa-image"></i> Banner Interactivo</a>
                <a href="backup.php" class="nav-link"><i class="fas fa-database"></i> Copia de Seguridad</a>
                <div class="nav-divider"></div>
                <div style="padding: 0 1.5rem; display: flex; flex-direction: column; gap: 0.8rem; margin-top: 1rem;">
                    <a href="logout.php?redirect=../index.php" class="nav-link-btn" style="background: var(--primary); color: white;"><i class="fas fa-external-link-alt"></i> Ver Web</a>
                    <a href="logout.php" class="nav-link-btn logout-btn" style="background: #e63946; color: white;"><i class="fas fa-sign-out-alt"></i> Salir</a>
                </div>
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
