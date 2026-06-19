<?php
// category.php
require_once 'config.php';
$pdo = getDB();

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ? AND is_visible = 1");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) { header("Location: index.php"); exit; }

// Redirección especial: Bares y Restaurantes → página dedicada
if (mb_stripos($category['name'], 'bares') !== false || mb_stripos($category['name'], 'restaurante') !== false) {
    header("Location: restaurantes.php");
    exit;
}

// Redirección especial: Alojamientos → página dedicada
if (mb_stripos($category['name'], 'alojamiento') !== false || mb_stripos($category['name'], 'dormir') !== false) {
    header("Location: alojamientos.php");
    exit;
}


// Buscamos subcategorías
$stmtSub = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? AND is_visible = 1 ORDER BY sort_order ASC, name ASC");
$stmtSub->execute([$id]);
$subcategories = $stmtSub->fetchAll();

// Buscamos páginas directas
$stmtPage = $pdo->prepare("SELECT * FROM pages WHERE category_id = ? AND is_visible = 1 ORDER BY sort_order ASC, title ASC");
$stmtPage->execute([$id]);
$pages = $stmtPage->fetchAll();

// Enlaces externos
$stmtExt = $pdo->prepare("SELECT * FROM external_links WHERE category_id = ? AND show_in_category = 1 AND is_visible = 1 ORDER BY sort_order ASC, title ASC");
$stmtExt->execute([$id]);
$externalLinks = $stmtExt->fetchAll();

// Si solo hay una página y no hay subcategorías ni enlaces externos, redirigimos a la página
if (count($pages) === 1 && count($subcategories) === 0 && count($externalLinks) === 0) {
    header("Location: page.php?id=" . $pages[0]['id']);
    exit;
}

// Lógica de navegación dinámica
$backLink = "index.php";
$backName = "el Inicio";

if (!empty($category['parent_id'])) {
    $stmtParent = $pdo->prepare("SELECT id, name FROM categories WHERE id = ? AND is_visible = 1");
    $stmtParent->execute([$category['parent_id']]);
    $parent = $stmtParent->fetch();
    if ($parent) {
        $backLink = "category.php?id=" . $parent['id'];
        $backName = $parent['name'];
    }
}


// Ahora que hemos pasado todas las posibles redirecciones de cabecera, cargamos el Header
$pageTitle = $category['name'];
require_once 'inc/header.php';

// La función getCategoryIcon ha sido movida a inc/header.php para uso global
?>


<section class="hero-page" style="background: linear-gradient(135deg, rgba(27,67,50,0.85) 0%, rgba(8,28,21,0.95) 100%), url('uploads/theme/moratalla.jpg'); background-size: cover; background-position: center; background-attachment: fixed; padding: 3rem 0; text-align: center; color: white; border-bottom: 4px solid var(--accent);">
    <div class="container">
        <div style="background: rgba(255,255,255,0.05); backdrop-filter: blur(15px); padding: 1.5rem 3rem; border-radius: 15px; display: inline-block; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <h2 style="color: white; margin-bottom: 0.3rem; font-size: 2.2rem; font-weight: 800; text-shadow: 0 2px 10px rgba(0,0,0,0.3); letter-spacing: -0.5px;"><?php echo htmlspecialchars($category['name']); ?></h2>
            <p style="color: var(--accent); font-weight: 600; font-size: 0.9rem; margin-bottom: 0; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-home"></i> Inicio <i class="fas fa-chevron-right" style="font-size: 0.7rem; margin: 0 10px; opacity: 0.5; color: white;"></i> <span style="color: white;"><?php echo htmlspecialchars($category['name']); ?></span></p>
        </div>
    </div>
</section>

<div class="container" style="margin-top: 2rem;">
    <a href="<?php echo htmlspecialchars($backLink); ?>" class="btn-nav btn-nav-back btn-nav-sm" style="box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <i class="fas fa-arrow-left"></i> Volver a <?php echo htmlspecialchars($backName); ?>
    </a>
</div>

<div class="container main-content">
    <div class="content-card">
        
        <?php if (mb_strtolower($category['name'], 'UTF-8') === 'biblioteca'): ?>
            <div style="text-align: center; padding: 6rem 2rem; background: white; border-radius: 30px; box-shadow: var(--shadow); border: 1px solid var(--gray-200); margin-bottom: 3rem;">
                <i class="fas fa-lock" style="font-size: 5rem; color: var(--accent); margin-bottom: 2rem; display: block;"></i>
                <h3 style="font-size: 2rem; color: var(--primary); margin-bottom: 1.5rem;">Acceso Privado a Biblioteca</h3>
                <p style="font-size: 1.2rem; color: var(--text-light); max-width: 600px; margin: 0 auto 3rem;">Esta sección es de acceso restringido. Por favor, utiliza tus credenciales para acceder al catálogo y recursos digitales.</p>
                <a href="http://www.moratalla-murcia.com/biblioteca" target="_blank" class="btn-nav btn-nav-home" style="padding: 1.5rem 3rem; font-size: 1.3rem; border-radius: 20px; background: var(--primary-dark);">
                    <i class="fas fa-key"></i> Acceso Privado a Biblioteca
                </a>
            </div>
        <?php endif; ?>

        <?php if (count($subcategories) > 0): ?>
            <h3 style="margin-bottom: 2rem; color: var(--primary); font-size: 1.5rem; border-left: 5px solid var(--accent); padding-left: 1.2rem;">Subsecciones</h3>
            <div class="grid-categories" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 4rem;">
                <?php foreach ($subcategories as $sub): ?>
                    <a href="category.php?id=<?php echo $sub['id']; ?>" class="btn-creative">
                        <i class="<?php echo getCategoryIcon($sub['name']); ?>"></i>
                        <span><?php echo htmlspecialchars($sub['name']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Noticias y Eventos de esta Categoría -->
        <?php
        $stmtNews = $pdo->prepare("SELECT * FROM news_events WHERE category_id = ? AND is_active_category = 1 ORDER BY CASE WHEN sort_order = 0 THEN 9999 ELSE sort_order END ASC, event_date DESC, id DESC");
        $stmtNews->execute([$id]);
        $categoryNews = $stmtNews->fetchAll();
        
        if (count($categoryNews) > 0):
        ?>
            <h3 style="margin-bottom: 2rem; margin-top: 3rem; color: var(--primary); font-size: 1.5rem; border-left: 5px solid var(--accent); padding-left: 1.2rem;">Noticias y Eventos de la Sección</h3>
            <div class="news-grid" style="margin-bottom: 4rem;">
                <?php foreach ($categoryNews as $news): 
                    $isEvent = !empty($news['event_date']);
                    $dateText = $isEvent ? date('d/m/Y', strtotime($news['event_date'])) : date('d/m/Y', strtotime($news['created_at']));
                    $excerpt = mb_strimwidth(strip_tags($news['content']), 0, 140, '...');
                    
                    // Obtener imágenes adicionales de galería
                    $stmtG = $pdo->prepare("SELECT image_path, caption FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id ASC");
                    $stmtG->execute([$news['id']]);
                    $gallery = $stmtG->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <div class="news-card" onclick="openNewsModal(<?php echo htmlspecialchars(json_encode([
                        'title' => $news['title'],
                        'date' => $dateText,
                        'isEvent' => $isEvent,
                        'image' => $news['image_path'] ? $news['image_path'] : '',
                        'image_caption' => $news['image_caption'] ?? '',
                        'content' => nl2br($news['content']),
                        'gallery' => $gallery
                    ])); ?>)">
                        <div class="news-card-img-wrapper">
                            <span class="news-badge <?php echo $isEvent ? 'event' : ''; ?>">
                                <?php echo $isEvent ? '<i class="fas fa-calendar-alt"></i> Evento' : '<i class="fas fa-newspaper"></i> Noticia'; ?>
                            </span>
                        <?php if ($news['image_path']): 
                            $newsExt = strtolower(pathinfo($news['image_path'], PATHINFO_EXTENSION));
                            $newsIsVideo = in_array($newsExt, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']);
                        ?>
                            <?php if ($newsIsVideo): ?>
                                <div style="position:relative; width:100%; height:100%; overflow:hidden;">
                                    <video src="<?php echo htmlspecialchars($news['image_path']); ?>" class="news-card-img" style="object-fit: cover; width: 100%; height: 100%; background: #000;" autoplay loop muted playsinline></video>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 2rem; opacity: 0.8; text-shadow: 0 2px 5px rgba(0,0,0,0.5); pointer-events: none;"><i class="fas fa-play-circle"></i></div>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($news['image_path']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-card-img">
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="width:100%; height:100%; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.2);">
                                <i class="<?php echo $isEvent ? 'fas fa-calendar-alt' : 'fas fa-newspaper'; ?>" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                        </div>
                        <div class="news-card-body">
                            <div class="news-card-date"><?php echo $dateText; ?></div>
                            <h3 class="news-card-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                            <p class="news-card-excerpt"><?php echo htmlspecialchars($excerpt); ?></p>
                            <div class="news-card-more">Leer más <i class="fas fa-arrow-right"></i></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($pages) > 0 || count($externalLinks) > 0): ?>
            <h3 style="margin-bottom: 2rem; color: var(--primary); font-size: 1.5rem; border-left: 5px solid var(--primary); padding-left: 1.2rem;">Páginas en esta sección</h3>
            <div class="grid-categories" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                <?php foreach ($pages as $p): ?>
                    <a href="page.php?id=<?php echo $p['id']; ?>" class="btn-creative" style="border-left: 6px solid var(--primary);">
                        <i class="far fa-file-alt" style="color: var(--primary);"></i>
                        <span><?php echo htmlspecialchars($p['title']); ?></span>
                    </a>
                <?php endforeach; ?>
                
                <?php foreach ($externalLinks as $el): ?>
                    <a href="<?php echo htmlspecialchars($el['url']); ?>" target="_blank" rel="noopener" class="btn-creative" style="border-left: 6px solid #d4af37; background: #fffcf2;">
                        <i class="fas fa-external-link-alt" style="color: #d4af37;"></i>
                        <span><?php echo htmlspecialchars($el['title']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php if (count($subcategories) == 0 && count($categoryNews) == 0): ?>
                <div style="text-align: center; padding: 5rem 0; background: var(--bg-alt); border-radius: 20px; border: 2px dashed var(--gray-300);">
                    <i class="fas fa-search" style="font-size: 4rem; color: var(--gray-300); margin-bottom: 1.5rem; display: block;"></i>
                    <p style="font-size: 1.2rem; color: var(--text-light);">No se ha encontrado contenido directo en esta sección todavía.</p>
                    <a href="index.php" class="btn-nav btn-nav-home" style="margin-top: 2rem;">
                        <i class="fas fa-home"></i> Volver al Inicio
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="nav-buttons-container" style="justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-100); padding-top: 3rem;">
            <a href="<?php echo htmlspecialchars($backLink); ?>" class="btn-nav btn-nav-back btn-nav-sm">
                <i class="fas fa-arrow-left"></i> Volver a <?php echo htmlspecialchars($backName); ?>
            </a>
            <a href="index.php" class="btn-nav btn-nav-home">
                <i class="fas fa-home"></i> Inicio
            </a>
        </div>

    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
