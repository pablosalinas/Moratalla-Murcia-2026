<?php
// inc/header.php
require_once 'config.php';
$pdo = getDB();

// Obtener ajustes
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

$tickerText = $settings['ticker_text'] ?? "Bienvenido a moratalla-murcia.com";
$tickerSpeed = $settings['ticker_speed'] ?? "30";
$bannerSpeed = $settings['banner_speed'] ?? "5000";

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
    <title>moratalla-murcia.com - Patrimonio Histórico Digital</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
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
                        echo '<img src="' . htmlspecialchars($banner['image_path']) . '" alt="' . htmlspecialchars($banner['title']) . '">';
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
