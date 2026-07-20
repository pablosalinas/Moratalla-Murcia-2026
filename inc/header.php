<?php
// inc/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
if (!isset($pdo)) {
    $pdo = getDB();
}

// --- INICIO CONTROL ESTADÍSTICAS ---
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `visit_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45) NOT NULL,
        `user_agent` TEXT,
        `browser` VARCHAR(100),
        `os` VARCHAR(100),
        `page_url` VARCHAR(255),
        `referrer` VARCHAR(255),
        `is_new_session` TINYINT(1) DEFAULT 0,
        `visit_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $stmtCheck = $pdo->query("SHOW COLUMNS FROM `visit_logs` LIKE 'is_new_session'");
    if($stmtCheck->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `visit_logs` ADD COLUMN `is_new_session` TINYINT(1) DEFAULT 0 AFTER `referrer`");
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Evitar registrar la actividad en el admin para que no contamine los datos
    if (strpos($_SERVER['REQUEST_URI'], '/admin/') === false) {
        $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        $os = 'Desconocido';
        if (preg_match('/windows nt/i', $ua)) $os = 'Windows';
        elseif (preg_match('/mac/i', $ua) && preg_match('/os x/i', $ua)) $os = 'Mac OS';
        elseif (preg_match('/linux/i', $ua) && !preg_match('/android/i', $ua)) $os = 'Linux';
        elseif (preg_match('/android/i', $ua)) $os = 'Android';
        elseif (preg_match('/iphone|ipad|ipod/i', $ua)) $os = 'iOS';
        
        $browser = 'Desconocido';
        if (preg_match('/edg/i', $ua)) $browser = 'Edge';
        elseif (preg_match('/opr|opera/i', $ua)) $browser = 'Opera';
        elseif (preg_match('/chrome|crios/i', $ua)) $browser = 'Chrome';
        elseif (preg_match('/firefox|fxios/i', $ua)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $ua)) $browser = 'Safari';

        $is_new_session = 0;
        if (!isset($_SESSION['global_visit_counted'])) {
            $pdo->exec("UPDATE settings SET setting_value = setting_value + 1 WHERE setting_key = 'global_visits'");
            $_SESSION['global_visit_counted'] = true;
            $is_new_session = 1;
        }

        $stmtVisit = $pdo->prepare("INSERT INTO visit_logs (ip_address, user_agent, browser, os, page_url, referrer, is_new_session) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtVisit->execute([$ip, $ua, $browser, $os, $url, $referrer, $is_new_session]);
    }
} catch (Exception $e) {
    // Silencioso para no romper la web
}
// --- FIN CONTROL ESTADÍSTICAS ---

// Variables SEO y Metadatos Dinámicos
$seoKeywords = "moratalla, mOratalla, morAtalla,Moratalla, Murcia, Turismo Moratalla, Trieta Moratalla, Espana Moratalla,cultura moratalla, casas rurales moratalla, rural, alojamientos moratalla,alharabe, benamor, casa cristo moratalla, encantada, san jorge, buitre, sabinar moratalla, otos moratalla, ca ada de la cruz, benizar, mazuza, calar de la santa, campo san juan, campo bejar, rogativa, zaen, murtas, nogueras, bajil,  rupestres, rupestre,pinturas, pintura,mediterraneo,banda, rondalla,Pablo Salinas,casas rurales, naturaleza, monta a, campo, monte, pueblo, castillo, iglesia, calles, fiestas, musica, Maria del Carmen Rodriguez Rodriguez, Pablo Salinas Rodriguez, Francisco Salinas Rodriguez,futbol, automovilismo, tamborada,ciclismo, rocasas, albury, pepe el pintor, villa juana, el olivar, villa zorrilla";
$defaultTitle = "moratalla-murcia.com - Patrimonio Histórico Digital";
$defaultDesc = "Moratalla (Murcia). Archivo histórico digital, turismo, asociaciones, Semana Santa, tambores y cultura local. Proyecto de conservación de memoria histórica.";

$finalTitle = isset($pageTitle) ? $pageTitle . " - moratalla-murcia.com" : $defaultTitle;
$finalDesc = isset($pageDescription) && !empty($pageDescription) ? $pageDescription : $defaultDesc;

// Obtener ajustes
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$tickerText = isset($settings['ticker_text']) ? $settings['ticker_text'] : "Bienvenido a moratalla-murcia.com";

// Obtener una cita aleatoria para concatenarla al ticker
try {
    $quoteStmt = $pdo->query("SELECT phrase, author FROM quotes ORDER BY RAND() LIMIT 1");
    $randomQuote = $quoteStmt->fetch();
    if ($randomQuote) {
        $authorText = !empty($randomQuote['author']) ? " — " . $randomQuote['author'] : "";
        $spacer = str_repeat("&nbsp;", 15);
        $quoteHtml = "<span style=\"color: var(--accent); font-weight: 700; text-shadow: 0 1px 3px rgba(0,0,0,0.5);\">\"" . htmlspecialchars($randomQuote['phrase']) . "\"" . htmlspecialchars($authorText) . "</span>";
        $finalTickerHtml = htmlspecialchars($tickerText) . $spacer . $quoteHtml;
    } else {
        $finalTickerHtml = htmlspecialchars($tickerText);
    }
} catch (Exception $e) {
    // Evitar romper la página si hay algún error
}

function getCategoryIcon($name) {
    $n = mb_strtolower($name, 'UTF-8');
    if (strpos($n, 'artesan') !== false) return 'fas fa-hammer';
    if (strpos($n, 'pintura') !== false || strpos($n, 'almagro') !== false) return 'fas fa-palette';
    if (strpos($n, 'esparto') !== false) return 'fas fa-leaf';
    if (strpos($n, 'música') !== false || strpos($n, 'musica') !== false || strpos($n, 'banda') !== false) return 'fas fa-music';
    if (strpos($n, 'tamboristas') !== false || strpos($n, 'tambor') !== false) return 'fas fa-drum';
    if (strpos($n, 'deporte') !== false || strpos($n, 'fútbol') !== false || strpos($n, 'futbol') !== false) return 'fas fa-futbol';
    if (strpos($n, 'automovilismo') !== false || strpos($n, 'coche') !== false) return 'fas fa-car';
    if (strpos($n, 'ciclista') !== false || strpos($n, 'ciclismo') !== false || strpos($n, 'bici') !== false) return 'fas fa-bicycle';
    if (strpos($n, 'baloncesto') !== false) return 'fas fa-basketball-ball';
    if (strpos($n, 'historia') !== false || strpos($n, 'patrimonio') !== false) return 'fas fa-landmark';
    if (strpos($n, 'naturaleza') !== false || strpos($n, 'lugares') !== false || strpos($n, 'excursiones') !== false) return 'fas fa-tree';
    if (strpos($n, 'gastronomía') !== false || strpos($n, 'recetas') !== false) return 'fas fa-utensils';
    if (strpos($n, 'fiestas') !== false) return 'fas fa-glass-cheers';
    if (strpos($n, 'semana santa') !== false || strpos($n, 'nazareno') !== false || strpos($n, 'tambor') !== false) return 'fas fa-church';
    if (strpos($n, 'cristo') !== false || strpos($n, 'rayo') !== false) return 'fas fa-cross';
    if (strpos($n, 'asociaciones') !== false || strpos($n, 'servicios') !== false) return 'fas fa-users';
    if (strpos($n, 'noticias') !== false || strpos($n, 'actualidad') !== false) return 'fas fa-newspaper';
    if (strpos($n, 'fotografía') !== false || strpos($n, 'galería') !== false) return 'fas fa-camera';
    if (strpos($n, 'biblioteca') !== false) return 'fas fa-book-reader';
    
    return 'fas fa-folder-open';
}
$tickerSpeed = isset($settings['ticker_speed']) ? $settings['ticker_speed'] : "30";
$bannerSpeed = isset($settings['banner_speed']) ? $settings['banner_speed'] : "5000";
if ((int)$bannerSpeed < 3000) $bannerSpeed = 3000; // Seguridad para evitar parpadeos

function renderHorizontalMenu($parentId = null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE is_visible = 1 AND " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?") . " ORDER BY sort_order ASC, name ASC");
    if ($parentId === null) $stmt->execute();
    else $stmt->execute([$parentId]);
    
    $categories = $stmt->fetchAll();
    
    $pages = [];
    $extLinks = [];
    if ($parentId !== null) {
        $stmtPages = $pdo->prepare("SELECT id, title FROM pages WHERE category_id = ? AND is_visible = 1 ORDER BY sort_order ASC, title ASC");
        $stmtPages->execute([$parentId]);
        $pages = $stmtPages->fetchAll();
        
        $stmtExt = $pdo->prepare("SELECT title, url FROM external_links WHERE category_id = ? AND show_in_category = 1 AND is_visible = 1 ORDER BY sort_order ASC, title ASC");
        $stmtExt->execute([$parentId]);
        $extLinks = $stmtExt->fetchAll();
    }
    
    $totalItems = count($categories) + count($pages) + count($extLinks);
    
    if ($totalItems > 0) {
        echo $parentId === null ? '<ul class="nav-menu container" id="main-nav">' : '<ul class="dropdown">';
        
        // Render Subcategorías
        foreach ($categories as $cat) {
            $stmtChild = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ? AND is_visible = 1");
            $stmtChild->execute([$cat['id']]);
            $numCat = $stmtChild->fetchColumn();
            
            $stmtP = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE category_id = ? AND is_visible = 1");
            $stmtP->execute([$cat['id']]);
            $numP = $stmtP->fetchColumn();
            
            $stmtE = $pdo->prepare("SELECT COUNT(*) FROM external_links WHERE category_id = ? AND show_in_category = 1 AND is_visible = 1");
            $stmtE->execute([$cat['id']]);
            $numE = $stmtE->fetchColumn();
            
            $hasChildren = ($numCat + $numP + $numE) > 0;
            
            $url = "category.php?id={$cat['id']}";
            if (mb_strtolower(trim($cat['name']), 'UTF-8') === 'contacto') {
                $url = "contacto.php";
            }
            
            $hintAttr = "";
            if (!empty($cat['show_hint']) && !empty($cat['hint_text'])) {
                $hintAttr = " data-hint='" . htmlspecialchars($cat['hint_text'], ENT_QUOTES) . "'";
            }
            
            echo "<li>";
            echo "<a href='{$url}'{$hintAttr}>";
            echo htmlspecialchars($cat['name']);
            if ($hasChildren) echo " <i class='fas fa-angle-" . ($parentId === null ? 'down' : 'right') . "'></i>";
            echo "</a>";
            renderHorizontalMenu($cat['id']);
            echo "</li>";
        }
        
        // Separador si hay tanto categorías como páginas/enlaces
        if (count($categories) > 0 && (count($pages) > 0 || count($extLinks) > 0)) {
            echo '<li style="height: 1px; background: rgba(0,0,0,0.1); margin: 4px 0; padding: 0;"></li>';
        }
        
        // Render Páginas
        foreach ($pages as $p) {
            echo "<li><a href='page.php?id={$p['id']}'><i class='far fa-file-alt' style='margin-right:8px; opacity:0.6;'></i>" . htmlspecialchars($p['title']) . "</a></li>";
        }
        
        // Render Enlaces Externos
        foreach ($extLinks as $e) {
            echo "<li><a href='" . htmlspecialchars($e['url']) . "' target='_blank' rel='noopener'><i class='fas fa-external-link-alt' style='margin-right:8px; opacity:0.6; color:#d4af37;'></i>" . htmlspecialchars($e['title']) . "</a></li>";
        }
        
        echo "</ul>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($finalTitle); ?></title>
    
    <!-- Meta Etiquetas SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($finalDesc); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoKeywords); ?>">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph (WhatsApp, Redes Sociales) -->
    <meta property="og:title" content="<?php echo htmlspecialchars($finalTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($finalDesc); ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://www.moratalla-murcia.com/moratalla/uploads/theme/logo.jpg">
    <?php $currentUrl = "https://www.moratalla-murcia.com" . $_SERVER['REQUEST_URI']; ?>
    <meta property="og:url" content="<?php echo $currentUrl; ?>">
    <link rel="canonical" href="<?php echo $currentUrl; ?>" />
    
    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Moratalla Murcia - Patrimonio Histórico Digital",
      "url": "https://www.moratalla-murcia.com/moratalla/",
      "description": "<?php echo htmlspecialchars($defaultDesc); ?>",
      "keywords": "Moratalla, Turismo Moratalla, Patrimonio Moratalla, Historia Moratalla",
      "publisher": {
        "@type": "Organization",
        "name": "moratalla-murcia.com",
        "logo": {
          "@type": "ImageObject",
          "url": "https://www.moratalla-murcia.com/moratalla/uploads/theme/logo.jpg"
        }
      }
    }
    </script>

    <!-- Preload critical assets -->
    <link rel="preload" href="style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <!-- Font is preloaded above, fallback for non-JS -->
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap"></noscript>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Swiper.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <style>
        /* CRITICAL INLINE CSS TO PREVENT FOUC / PARPADEO ON MOBILE */
        body { margin: 0; padding: 0; background-color: #f1f3f2; }
        .ticker-wrapper { height: 40px; background: #2d6a4f; overflow: hidden; display: flex; align-items: center; }
        .main-header { height: 90px; background: rgba(255, 255, 255, 0.95); border-bottom: 2px solid #2d6a4f; box-sizing: border-box; }
        .header-top { height: 100%; display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; padding: 0 4rem; box-sizing: border-box; }
        .logo img.main-site-logo { height: 70px; width: auto; display: block; box-sizing: border-box; border: 3px solid #2d6a4f; padding: 3px; border-radius: 8px; }
        
        .banner-slider { width: 100%; overflow: hidden; background: white; }
        .main-banner-swiper { width: 100%; height: 280px; min-height: 200px; overflow: hidden; display: block; }
        .main-banner-swiper .swiper-wrapper { display: flex; }
        .main-banner-swiper .swiper-slide { flex-shrink: 0; width: 100%; height: 100%; overflow: hidden; }
        .main-banner-swiper img, .main-banner-swiper video { width: 100%; height: 100%; object-fit: cover; }
        
        @media (max-width: 1024px) {
            .nav-container { display: none; }
        }
        @media (max-width: 768px) {
            .header-top { padding: 0 1.5rem; }
            .main-banner-swiper { aspect-ratio: 16 / 9; height: auto; min-height: 200px; }
        }
        @media (max-width: 480px) {
            .main-banner-swiper { aspect-ratio: 4 / 3; height: auto; min-height: 250px; }
        }

        .ticker-content {
            animation: ticker-animation <?php echo (int)$tickerSpeed; ?>s linear infinite !important;
        }
        /* Prevenir parpadeo del Swiper antes de inicializar */
        .swiper:not(.swiper-initialized) {
            opacity: 0;
            visibility: hidden;
        }
        .swiper {
            transition: opacity 0.3s ease-in, visibility 0.3s ease-in;
        }
    </style>
    <script>
    function toggleVideoMute(buttonEl, videoEl) {
        if (!videoEl) return;
        videoEl.muted = !videoEl.muted;
        const icon = buttonEl.querySelector('i');
        if (icon) {
            if (videoEl.muted) {
                icon.className = 'fas fa-volume-mute';
                buttonEl.setAttribute('title', 'Activar sonido');
            } else {
                icon.className = 'fas fa-volume-up';
                buttonEl.setAttribute('title', 'Silenciar');
            }
        }
    }
    </script>
</head>
<body>
    <div class="ticker-wrapper">
        <div class="ticker-content">
            <span><?php echo isset($finalTickerHtml) ? $finalTickerHtml : htmlspecialchars($tickerText); ?></span>
            <span><?php echo isset($finalTickerHtml) ? $finalTickerHtml : htmlspecialchars($tickerText); ?></span>
            <span><?php echo isset($finalTickerHtml) ? $finalTickerHtml : htmlspecialchars($tickerText); ?></span>
        </div>
    </div>

    <header class="main-header">
        <div class="header-top container">
            <a href="index.php" class="logo">
                <img src="uploads/theme/logo.jpg" alt="Logo" class="main-site-logo" onerror="this.onerror=null; this.src='uploads/theme/logo.gif'">
                <div class="logo-text">
                    moratalla-murcia.com
                </div>
            </a>
            <div class="header-right">
                <button class="mobile-toggle" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <a href="admin/" class="btn-admin-top hide-mobile">
                    <i class="fas fa-lock"></i> Acceso
                </a>
            </div>
        </div>
        <nav class="nav-container">
            <?php renderHorizontalMenu(); ?>
        </nav>
    </header>

    <?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
    <section class="banner-slider">
        <div class="swiper main-banner-swiper">
            <div class="swiper-wrapper">
                <?php
                $bannerStmt = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
                $banners = $bannerStmt->fetchAll();
                if (count($banners) > 0) {
                    foreach ($banners as $banner) {
                        $ext = strtolower(pathinfo($banner['image_path'], PATHINFO_EXTENSION));
                        $isVideo = in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']);

                        if ($isVideo) {
                            echo '<div class="swiper-slide" style="position: relative; width: 100%; height: 100%;">';
                            echo '<video class="banner-video-el" src="' . htmlspecialchars($banner['image_path']) . '" style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; display: block;" muted playsinline onclick="openBannerModal(this.src, true)"></video>';
                            echo '<div class="video-mute-btn" style="position: absolute; bottom: 15px; right: 15px; z-index: 10; background: rgba(0,0,0,0.6); border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: white;" onclick="event.stopPropagation(); toggleVideoMute(this, this.previousElementSibling)">';
                            echo '<i class="fas fa-volume-mute"></i>';
                            echo '</div>';
                            if ($banner['title']) {
                                echo '<div class="slide-caption">' . htmlspecialchars($banner['title']) . '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="swiper-slide">';
                            $baseExt = pathinfo($banner['image_path'], PATHINFO_EXTENSION);
                            $baseName = pathinfo($banner['image_path'], PATHINFO_FILENAME);
                            $dirName = pathinfo($banner['image_path'], PATHINFO_DIRNAME);
                            
                            $desktopPath = $dirName . '/' . $baseName . '_desktop.' . $baseExt;
                            $mobilePath = $dirName . '/' . $baseName . '_mobile.' . $baseExt;
                            
                            if (!file_exists(__DIR__ . '/../' . $desktopPath)) {
                                $desktopPath = $banner['image_path'];
                                $mobilePath = $banner['image_path'];
                            }

                            echo '<picture>';
                            echo '<source media="(max-width: 768px)" srcset="' . htmlspecialchars($mobilePath) . '">';
                            echo '<source media="(min-width: 769px)" srcset="' . htmlspecialchars($desktopPath) . '">';
                            echo '<img src="' . htmlspecialchars($desktopPath) . '" alt="' . htmlspecialchars($banner['title']) . '" style="cursor: pointer;" onclick="openBannerModal(this.currentSrc || this.src)">';
                            echo '</picture>';
                            if ($banner['title']) {
                                echo '<div class="slide-caption">' . htmlspecialchars($banner['title']) . '</div>';
                            }
                            echo '</div>';
                        }
                    }
                } else {
                    // Fallback if no banners are active
                    echo '<div class="swiper-slide"><img src="uploads/theme/moratalla.jpg" alt="Moratalla"></div>';
                }
                ?>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </section>
    <?php endif; ?>
