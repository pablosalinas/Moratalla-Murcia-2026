<?php
// admin/inc/layout.php
function adminHeader($title = "Admin Panel") {
    $currentUser = function_exists('getCurrentAdminUser') ? getCurrentAdminUser() : null;
    $esSuper = $currentUser ? !empty($currentUser['es_superusuario']) : true;
    $accesoSistema = ($currentUser && !empty($currentUser['acceso_sistema'])) ? $currentUser['acceso_sistema'] : 'ambos';
    if ($esSuper) {
        $accesoSistema = 'ambos';
    }
    $canWeb = ($accesoSistema === 'ambos' || $accesoSistema === 'web');
    $canCombustible = ($accesoSistema === 'ambos' || $accesoSistema === 'combustible');
    $displayUser = ($currentUser && !empty($currentUser['username'])) ? htmlspecialchars($currentUser['username']) : 'Admin';
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
            /* Evitar que el buscador sticky tape los anclajes al volver */
            tr[id], div.tree-item[id], div.image-item[id] {
                scroll-margin-top: 120px;
            }
        </style>
    </head>
    <body class="admin-body">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2 style="font-size: 1.1rem; letter-spacing: 0;">moratalla-murcia.com<?php echo $esSuper ? '.' : ''; ?></h2>
            </div>
            <nav class="sidebar-nav">
                <?php if ($canWeb): ?>
                    <a href="index.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                    <a href="categories.php" class="nav-link"><i class="fas fa-folder-tree"></i> Categorías</a>
                    <a href="pages.php" class="nav-link"><i class="fas fa-file-alt"></i> Páginas</a>
                    <a href="images.php" class="nav-link"><i class="fas fa-images"></i> Galería</a>
                    <a href="news.php" class="nav-link"><i class="fas fa-newspaper"></i> Noticias / Eventos</a>
                    <a href="messages.php" class="nav-link"><i class="fas fa-inbox"></i> Bandeja de Mensajes</a>
                    <a href="quotes.php" class="nav-link"><i class="fas fa-quote-left"></i> Citas Ilustres</a>
                    <a href="restaurantes.php" class="nav-link"><i class="fas fa-utensils"></i> Bares y Restaurantes</a>
                    <a href="alojamientos.php" class="nav-link"><i class="fas fa-hotel"></i> Alojamientos</a>
                    <a href="external_links.php" class="nav-link"><i class="fas fa-external-link-alt"></i> Accesos Externos</a>
                    <a href="icons.php" class="nav-link"><i class="fas fa-icons"></i> Gestor de Iconos</a>
                    <a href="users.php" class="nav-link"><i class="fas fa-users-cog"></i> Usuarios</a>
                <?php endif; ?>

                <?php if ($canCombustible): ?>
                    <a href="../combustible/index.php" class="nav-link" style="background: rgba(15, 118, 110, 0.18); border-left: 3px solid #14b8a6;"><i class="fas fa-gas-pump" style="color: #2dd4bf;"></i> Control Combustible</a>
                <?php endif; ?>

                <?php if ($canWeb): ?>
                    <div class="nav-divider"></div>
                    <a href="manual.php" class="nav-link"><i class="fas fa-book"></i> Manual de Usuario</a>
                    <a href="settings.php" class="nav-link"><i class="fas fa-cogs"></i> Configuración Gral.</a>
                    <a href="seo.php" class="nav-link"><i class="fas fa-compass"></i> SEO y Guías</a>
                    <a href="banners.php" class="nav-link"><i class="fas fa-image"></i> Banner Interactivo</a>
                    <a href="celebrations.php" class="nav-link"><i class="fas fa-glass-cheers"></i> Acontecimientos</a>
                    <a href="stats.php" class="nav-link"><i class="fas fa-chart-pie"></i> Estadísticas</a>
                    <a href="backup.php" class="nav-link"><i class="fas fa-database"></i> Copia de Seguridad</a>
                <?php endif; ?>

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
                    <span>Bienvenido, <strong><?php echo $displayUser; ?></strong></span>
                </div>
            </header>
            <div class="content-wrapper">
    <?php
}

function adminFooter() {
    ?>
            </div>
        </main>
        <script>
        // Verificacion desatendida de tareas programadas (resumen diario de noticias Moratalla)
        (function() {
            if (typeof window !== 'undefined' && window.fetch) {
                setTimeout(function() {
                    try {
                        fetch('../cron_daily_news.php', { method: 'GET', cache: 'no-store', keepalive: true }).catch(function(){});
                    } catch(e) {}
                }, 1500);
            }
        })();
        </script>
    </body>
    </html>
    <?php
}
?>
