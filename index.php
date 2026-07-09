<?php
// index.php - v1.3.0 (Curiosidades / Accesos externos)
require_once 'inc/header.php';

// Contador global de visitas a la web
if (!isset($_SESSION['global_visit_counted'])) {
    $pdo->exec("UPDATE settings SET setting_value = setting_value + 1 WHERE setting_key = 'global_visits'");
    $_SESSION['global_visit_counted'] = true;
}
$stmtGlobal = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'global_visits'");
$globalVisits = $stmtGlobal->fetchColumn() ?: 0;
?>


<div class="container main-content" style="margin-top: 2rem;">
    <div class="content-card">
        <h2 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 2rem; text-align: center;">Noticias y Eventos Destacados</h2>
        
        <div class="news-grid">
            <?php
            $stmt = $pdo->query("SELECT * FROM news_events WHERE is_active_home = 1 ORDER BY sort_order ASC, event_date DESC, id DESC");
            $hasNews = false;
            $isFirstNews = true;
            while ($news = $stmt->fetch()) {
                $hasNews = true;
                $isEvent = !empty($news['event_date']);
                $dateText = $isEvent ? date('d/m/Y', strtotime($news['event_date'])) : date('d/m/Y', strtotime($news['created_at']));
                $excerpt = mb_strimwidth(strip_tags($news['content']), 0, 140, '...');
                
                // Obtener imágenes adicionales de galería
                $stmtG = $pdo->prepare("SELECT id, image_path, caption FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id DESC");
                $stmtG->execute([$news['id']]);
                $gallery = $stmtG->fetchAll(PDO::FETCH_ASSOC);
                
                $mainImagePath = $news['image_path'];
                $mainImageCaption = $news['image_caption'] ?? '';
                
                if (!empty($news['use_latest_gallery_image']) && !empty($gallery)) {
                    $latestImage = null;
                    $maxId = -1;
                    foreach ($gallery as $gImg) {
                        if ($gImg['id'] > $maxId) {
                            $maxId = $gImg['id'];
                            $latestImage = $gImg;
                        }
                    }
                    if ($latestImage) {
                        $mainImagePath = $latestImage['image_path'];
                        $mainImageCaption = $latestImage['caption'];
                    }
                }
                
                $featuredClass = $isFirstNews ? 'news-card-featured' : '';
                $isFirstNews = false;
                ?>
                <div class="news-card <?php echo $featuredClass; ?>" onclick="openNewsModal(<?php echo htmlspecialchars(json_encode([
                    'title' => $news['title'],
                    'date' => $dateText,
                    'isEvent' => $isEvent,
                    'image' => $mainImagePath ? $mainImagePath : '',
                    'image_caption' => $mainImageCaption,
                    'content' => $news['content'],
                    'gallery' => $gallery
                ])); ?>)">
                    <div class="news-card-img-wrapper">
                        <span class="news-badge <?php echo $isEvent ? 'event' : ''; ?>">
                            <?php echo $isEvent ? '<i class="fas fa-calendar-alt"></i> Evento' : '<i class="fas fa-newspaper"></i> Noticia'; ?>
                        </span>
                        <?php if ($mainImagePath): 
                            $newsExt = strtolower(pathinfo($mainImagePath, PATHINFO_EXTENSION));
                            $newsIsVideo = in_array($newsExt, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']);
                            $newsIsPdf = ($newsExt === 'pdf');
                        ?>
                            <?php if ($newsIsPdf): ?>
                                <div style="width:100%; height:100%; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); display:flex; align-items:center; justify-content:center; color:white;">
                                    <i class="fas fa-file-pdf" style="font-size: 4rem;"></i>
                                </div>
                            <?php elseif ($newsIsVideo): ?>
                                <div style="position:relative; width:100%; height:100%; overflow:hidden;">
                                    <video src="<?php echo htmlspecialchars($mainImagePath); ?>" class="news-card-img" style="object-fit: cover; width: 100%; height: 100%; background: #000;" autoplay loop muted playsinline></video>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 2rem; opacity: 0.8; text-shadow: 0 2px 5px rgba(0,0,0,0.5); pointer-events: none;"><i class="fas fa-play-circle"></i></div>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($mainImagePath); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-card-img">
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
                <?php
            }
            if (!$hasNews) {
                ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-light); font-style: italic;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem; display: block;"></i>
                    No hay noticias o eventos destacados en este momento.
                </div>
                <?php
            }
            ?>
        </div>

        <div style="margin-top: 5rem; padding: 3rem; background: var(--bg-alt); border-radius: 20px; text-align: center;">
            <p style="color: var(--text-light); font-style: italic; max-width: 800px; margin: 0 auto;">"Moratalla no es solo un lugar, es un conjunto de historias, gentes y paisajes que perduran en el tiempo."</p>
        </div>
    </div>
</div>

<?php
// ─── Sección Curiosidades (accesos externos visibles) ───────────────────────
$stmtEL = $pdo->query("SELECT * FROM external_links WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC");
$externalLinks = $stmtEL ? $stmtEL->fetchAll() : [];

if (count($externalLinks) > 0):
?>
<section class="curiosidades-section">
    <div class="container">
        <div class="curiosidades-header">
            <span class="curiosidades-badge"><i class="fas fa-compass"></i> Descubre más</span>
            <h2 class="curiosidades-title">Curiosidades y Recursos</h2>
            <p class="curiosidades-subtitle">Explora enlaces de interés sobre Moratalla y su entorno</p>
        </div>
        <div class="curiosidades-grid">
            <?php
            $palettes = [
                ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => 'fa-globe'],
                ['bg' => '#e3f2fd', 'color' => '#1565c0', 'icon' => 'fa-link'],
                ['bg' => '#fff8e1', 'color' => '#e65100', 'icon' => 'fa-map-marker-alt'],
                ['bg' => '#fce4ec', 'color' => '#880e4f', 'icon' => 'fa-landmark'],
                ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'icon' => 'fa-star'],
                ['bg' => '#e0f7fa', 'color' => '#006064', 'icon' => 'fa-book-open'],
            ];
            foreach ($externalLinks as $idx => $el):
                $p = $palettes[$idx % count($palettes)];
            ?>
            <a href="<?php echo htmlspecialchars($el['url']); ?>" target="_blank" rel="noopener noreferrer" class="curiosidad-card">
                <div class="curiosidad-icon" style="background:<?php echo $p['bg']; ?>; color:<?php echo $p['color']; ?>;">
                    <i class="fas <?php echo $p['icon']; ?>"></i>
                </div>
                <div class="curiosidad-body">
                    <h3 class="curiosidad-title"><?php echo htmlspecialchars($el['title']); ?></h3>
                    <?php if (!empty($el['description'])): ?>
                        <p class="curiosidad-desc"><?php echo htmlspecialchars($el['description']); ?></p>
                    <?php endif; ?>
                    <span class="curiosidad-link-label">
                        Visitar enlace <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
    .curiosidades-section {
        background: linear-gradient(135deg, #f0faf4 0%, #e8f5e9 100%);
        padding: 5rem 0 4rem;
        margin-top: 3rem;
        border-top: 3px solid var(--accent, #c8a85a);
    }
    .curiosidades-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    .curiosidades-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--primary, #1b4332);
        color: white;
        padding: 6px 18px;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }
    .curiosidades-title {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary, #1b4332);
        margin: 0.5rem 0;
        letter-spacing: -0.5px;
    }
    .curiosidades-subtitle {
        color: #555;
        font-size: 1rem;
        margin: 0;
    }
    .curiosidades-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    .curiosidad-card {
        background: white;
        border-radius: 16px;
        padding: 1.8rem;
        display: flex;
        gap: 1.2rem;
        align-items: flex-start;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    .curiosidad-card::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(27,67,50,0.04) 0%, transparent 60%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .curiosidad-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.13);
    }
    .curiosidad-card:hover::after { opacity: 1; }
    .curiosidad-icon {
        flex-shrink: 0;
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .curiosidad-body { flex: 1; min-width: 0; }
    .curiosidad-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1a1a1a;
        margin: 0 0 0.5rem;
        line-height: 1.3;
    }
    .curiosidad-desc {
        font-size: 0.88rem;
        color: #666;
        line-height: 1.5;
        margin: 0 0 0.8rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .curiosidad-link-label {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--primary, #1b4332);
        display: inline-flex;
        align-items: center;
        gap: 5px;
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    .curiosidad-card:hover .curiosidad-link-label { opacity: 1; }
    @media (max-width: 600px) {
        .curiosidades-grid { grid-template-columns: 1fr; }
        .curiosidades-title { font-size: 1.5rem; }
    }
</style>
<?php endif; ?>

<!-- Contador discreto de accesos globales -->
<div class="container" style="text-align: right; padding: 1rem 0; margin-top: 2rem; border-top: 1px solid var(--gray-200);">
    <span style="font-size: 0.85rem; color: var(--text-light); font-family: monospace; opacity: 0.7;">
        <i class="fas fa-chart-line"></i> Accesos totales: <?php echo number_format($globalVisits, 0, ',', '.'); ?>
    </span>
</div>

<?php require_once 'inc/footer.php'; ?>
