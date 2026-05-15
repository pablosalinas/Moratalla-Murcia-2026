<?php
// inc/header.php
require_once 'config.php';
if (!isset($pdo)) {
    $pdo = getDB();
}

// Variables SEO y Metadatos Dinámicos
$seoKeywords = "moratalla, mOratalla, morAtalla,Moratalla, Murcia, Turismo Moratalla, Trieta Moratalla, Espana Moratalla,cultura moratalla, casas rurales moratalla, rural, alojamientos moratalla,alharabe, benamor, casa cristo moratalla, encantada, san jorge, buitre, sabinar moratalla, otos moratalla, ca ada de la cruz, benizar, mazuza, calar de la santa, campo san juan, campo bejar, rogativa, zaen, murtas, nogueras, bajil,  rupestres, rupestre,pinturas, pintura,mediterraneo,banda, rondalla,Pablo Salinas,casas rurales, naturaleza, monta a, campo, monte, pueblo, castillo, iglesia, calles, fiestas, musica, Maria del Carmen Rodriguez Rodriguez, Pablo Salinas Rodriguez, Francisco Salinas Rodriguez,futbol, automovilismo, tamborada,ciclismo, rocasas, albury, pepe el pintor, villa juana, el olivar, villa zorrilla";
$defaultTitle = "moratalla-murcia.com - Patrimonio Histórico Digital";
$defaultDesc = "Moratalla (Murcia). Archivo histórico digital, turismo, asociaciones, Semana Santa, tambores y cultura local. Proyecto de conservación de memoria histórica.";

$finalTitle = isset($pageTitle) ? $pageTitle . " - moratalla-murcia.com" : $defaultTitle;
$finalDesc = isset($pageDescription) && !empty($pageDescription) ? $pageDescription : $defaultDesc;

// Obtener ajustes
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$tickerText = $settings['ticker_text'] ?? "Bienvenido a moratalla-murcia.com";
$tickerSpeed = $settings['ticker_speed'] ?? "30";
$bannerSpeed = $settings['banner_speed'] ?? "5000";
if ((int)$bannerSpeed < 3000) $bannerSpeed = 3000; // Seguridad para evitar parpadeos

function renderHorizontalMenu($parentId = null) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?") . " ORDER BY sort_order ASC, name ASC");
    if ($parentId === null) $stmt->execute();
    else $stmt->execute([$parentId]);
    
    $categories = $stmt->fetchAll();
    if (count($categories) > 0) {
        echo $parentId === null ? '<ul class="nav-menu container" id="main-nav">' : '<ul class="dropdown">';
        foreach ($categories as $cat) {
            $stmtChild = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $stmtChild->execute([$cat['id']]);
            $hasChildren = $stmtChild->fetchColumn() > 0;
            
            $url = "category.php?id={$cat['id']}";
            if (mb_strtolower($cat['name'], 'UTF-8') === 'biblioteca') {
                $url = "http://www.moratalla-murcia.com/biblioiteca";
            }
            
            echo "<li>";
            echo "<a href='{$url}'>";
            echo htmlspecialchars($cat['name']);
            if ($hasChildren) echo " <i class='fas fa-angle-" . ($parentId === null ? 'down' : 'right') . "'></i>";
            echo "</a>";
            renderHorizontalMenu($cat['id']);
            echo "</li>";
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
    <meta property="og:image" content="https://www.moratalla-murcia.com/uploads/theme/logo.jpg">
    <meta property="og:url" content="https://www.moratalla-murcia.com<?php echo $_SERVER['REQUEST_URI']; ?>">
    
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Swiper.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <style>
        .ticker-content {
            animation: ticker-animation <?php echo (int)$tickerSpeed; ?>s linear infinite !important;
        }
    </style>
</head>
<body>
    <div class="ticker-wrapper">
        <div class="ticker-content">
            <span><?php echo htmlspecialchars($tickerText); ?></span>
            <span><?php echo htmlspecialchars($tickerText); ?></span>
            <span><?php echo htmlspecialchars($tickerText); ?></span>
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
