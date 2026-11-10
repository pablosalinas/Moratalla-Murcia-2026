<?php
// index.php - v1.3.0 (Curiosidades / Accesos externos)
require_once 'inc/header.php';

// Contador global de visitas a la web (movido a header.php)
$stmtGlobal = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'global_visits'");
$globalVisits = $stmtGlobal->fetchColumn() ?: 0;
?>

<!-- CELEBRACIONES DINÁMICAS -->
<?php
try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('celebration_days_before', 'celebration_days_after', 'celebration_cycle_time', 'celebration_cycle_limit')");
    $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
    $daysBefore = isset($settings['celebration_days_before']) ? (int)$settings['celebration_days_before'] : 5;
    $daysAfter = isset($settings['celebration_days_after']) ? (int)$settings['celebration_days_after'] : 2;
    $cycleTimeMs = isset($settings['celebration_cycle_time']) ? (int)$settings['celebration_cycle_time'] * 1000 : 5000;
    $cycleLimit = isset($settings['celebration_cycle_limit']) ? (int)$settings['celebration_cycle_limit'] : 0;

    $stmtCeleb = $pdo->query("SELECT * FROM celebrations WHERE is_active = 1");
    $now = new DateTime();
    while ($celeb = $stmtCeleb->fetch()) {
        $shouldShow = false;
        $recurrence = $celeb['recurrence'] ?? 'none';
        
        if (empty($recurrence) || $recurrence === 'none') {
            $start = $celeb['start_date'] ? new DateTime($celeb['start_date']) : null;
            $end = $celeb['end_date'] ? new DateTime($celeb['end_date']) : null;
            if ((!$start || $start <= $now) && (!$end || $end >= $now)) {
                $shouldShow = true;
            }
        } else {
            $occurrences = [];
            if ($recurrence === 'easter') {
                $year = $now->format('Y');
                $easterDays = easter_days($year);
                $easterDate = new DateTime($year . '-03-21');
                $easterDate->modify("+{$easterDays} days");
                
                $offset = (int)($celeb['easter_offset'] ?? 0);
                $easterDate->modify(($offset >= 0 ? "+{$offset}" : $offset) . " days");
                $easterDate->setTime(0, 0, 0);
                $occurrences[] = $easterDate;
            } elseif (!empty($celeb['event_date'])) {
                $baseDate = new DateTime($celeb['event_date']);
                if ($recurrence === 'annual') {
                    for ($y = -1; $y <= 1; $y++) {
                        $occ = clone $now; 
                        $occ->setDate($now->format('Y') + $y, $baseDate->format('m'), $baseDate->format('d')); 
                        $occ->setTime(0,0,0);
                        $occurrences[] = $occ;
                    }
                } elseif ($recurrence === 'monthly') {
                    for ($m = -1; $m <= 1; $m++) {
                        $occ = clone $now; 
                        $occ->setDate($now->format('Y'), $now->format('m') + $m, $baseDate->format('d')); 
                        $occ->setTime(0,0,0);
                        $occurrences[] = $occ;
                    }
                }
            }
            
            foreach ($occurrences as $occ) {
                $windowStart = clone $occ;
                $windowStart->modify("-{$daysBefore} days");
                $windowEnd = clone $occ;
                $windowEnd->modify("+{$daysAfter} days");
                $windowEnd->setTime(23, 59, 59);
                
                if ($now >= $windowStart && $now <= $windowEnd) {
                    $shouldShow = true;
                    break;
                }
            }
        }
        
        if ($shouldShow) {
            $activeCelebrations[] = $celeb;
        }
    }
    
    if (!empty($activeCelebrations)) {
        echo "<div id='celebrations-container'>\n";
        foreach ($activeCelebrations as $index => $celeb) {
            $display = $index === 0 ? 'block' : 'none';
            echo "<div class='celebration-item' id='celebration-item-{$index}' style='display: {$display};'>\n";
            if (!empty($celeb['html_content'])) {
                echo $celeb['html_content'] . "\n";
            }
            if (!empty($celeb['css_content'])) {
                echo "<style>\n" . $celeb['css_content'] . "\n</style>\n";
            }
            echo "</div>\n";
        }
        echo "</div>\n";
        
        echo "<script>\n";
        echo "document.addEventListener('DOMContentLoaded', function() {\n";
        echo "    const items = document.querySelectorAll('.celebration-item');\n";
        echo "    let celebIndex = 0;\n";
        echo "    const celebFuncs = [];\n";
        
        foreach ($activeCelebrations as $index => $celeb) {
            echo "    celebFuncs[{$index}] = function() {\n";
            echo "        document.dispatchEvent(new Event('celebrationChange'));\n";
            if (!empty($celeb['js_content'])) {
                echo "        try {\n";
                // We wrap in IIFE in case they have variable declarations to avoid collisions if eval'd
                echo "            (function() {\n";
                echo $celeb['js_content'] . "\n";
                echo "            })();\n";
                echo "        } catch(e) { console.error('Error running celebration JS', e); }\n";
            }
            echo "    };\n";
        }
        
        echo "    const cycleTimeMs = {$cycleTimeMs};\n";
        echo "    const cycleLimit = {$cycleLimit};\n";
        echo "    let cyclesCompleted = parseInt(sessionStorage.getItem('celebCyclesCompleted') || '0');\n";
        echo "    if (cycleLimit > 0 && cyclesCompleted >= cycleLimit) {\n";
        echo "        items.forEach(item => item.style.display = 'none');\n";
        echo "    } else {\n";
        echo "        if (items.length > 0) {\n";
        echo "            celebFuncs[0]();\n";
        echo "        }\n";
        echo "        if (items.length > 1) {\n";
        echo "            const intervalId = setInterval(() => {\n";
        echo "                items[celebIndex].style.display = 'none';\n";
        echo "                celebIndex = (celebIndex + 1) % items.length;\n";
        echo "                items[celebIndex].style.display = 'block';\n";
        echo "                celebFuncs[celebIndex]();\n";
        echo "                if (celebIndex === 0) {\n";
        echo "                    cyclesCompleted++;\n";
        echo "                    sessionStorage.setItem('celebCyclesCompleted', cyclesCompleted);\n";
        echo "                    if (cycleLimit > 0 && cyclesCompleted >= cycleLimit) {\n";
        echo "                        clearInterval(intervalId);\n";
        echo "                        document.dispatchEvent(new Event('celebrationChange'));\n";
        echo "                        items.forEach(item => item.style.display = 'none');\n";
        echo "                    }\n";
        echo "                }\n";
        echo "            }, cycleTimeMs);\n";
        echo "        } else if (items.length === 1 && cycleLimit > 0) {\n";
        echo "            setTimeout(() => {\n";
        echo "                cyclesCompleted++;\n";
        echo "                sessionStorage.setItem('celebCyclesCompleted', cyclesCompleted);\n";
        echo "                document.dispatchEvent(new Event('celebrationChange'));\n";
        echo "                items[0].style.display = 'none';\n";
        echo "            }, cycleTimeMs * cycleLimit);\n";
        echo "        }\n";
        echo "    }\n";
        echo "});\n";
        echo "</script>\n";
    }
} catch (Exception $e) {
    // Log or ignore silently
}
?>
<!-- FIN CELEBRACIONES -->

<?php
$seoCfgHome = !empty($seo90Active) && function_exists('getSeoConfig') ? getSeoConfig($pdo) : [];
if (!empty($seo90Active) && ($seoCfgHome['seo_show_home_top'] ?? '1') === '1'):
?>
<!-- ═══ PANEL SUPERIOR EQUILIBRADO: VISIBLE, ATRACTIVO Y SIN OCULTAR LAS NOTICIAS ═══ -->
<section class="seo-home-top-banner">
    <div class="container">
        <div class="seo-top-banner-card">
            <div class="seo-top-banner-head">
                <div class="seo-top-banner-title-group">
                    <span class="seo-top-badge"><i class="fas fa-compass"></i> Guía Oficial y Patrimonio Digital</span>
                    <h2>Descubre Moratalla: Historia, Naturaleza, Tambores y Turismo Rural</h2>
                </div>
                <a href="guia.php" class="seo-top-all-btn">
                    <i class="fas fa-book-open"></i> Ver todas las Guías <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="seo-top-nav-grid">
                <a href="guia.php?slug=guia-completa-moratalla" class="seo-top-pill highlight">
                    <i class="fas fa-map-signs"></i>
                    <span>Qué Ver en Moratalla</span>
                </a>
                <a href="guia.php?slug=monumentos-y-patrimonio-moratalla" class="seo-top-pill">
                    <i class="fas fa-chess-rook"></i>
                    <span>Monumentos y Patrimonio</span>
                </a>
                <a href="guia.php?slug=historia-de-moratalla" class="seo-top-pill">
                    <i class="fas fa-landmark"></i>
                    <span>Historia</span>
                </a>
                <a href="guia.php?slug=turismo-rural-moratalla" class="seo-top-pill">
                    <i class="fas fa-tree"></i>
                    <span>Turismo Rural</span>
                </a>
                <a href="guia.php?slug=rutas-de-senderismo-en-moratalla" class="seo-top-pill">
                    <i class="fas fa-hiking"></i>
                    <span>Rutas Senderismo</span>
                </a>
                <a href="guia.php?slug=pinturas-rupestres-de-moratalla" class="seo-top-pill">
                    <i class="fas fa-palette"></i>
                    <span>Arte Rupestre</span>
                </a>
                <a href="guia.php?slug=semana-santa-de-moratalla" class="seo-top-pill">
                    <i class="fas fa-drum"></i>
                    <span>Semana Santa y Tambor</span>
                </a>
                <a href="alojamientos.php" class="seo-top-pill gold">
                    <i class="fas fa-bed"></i>
                    <span>Casas Rurales</span>
                </a>
                <a href="restaurantes.php" class="seo-top-pill gold">
                    <i class="fas fa-utensils"></i>
                    <span>Dónde Comer</span>
                </a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container main-content" style="margin-top: <?php echo !empty($seo90Active) ? '0.75rem' : '2rem'; ?>;">
    <div class="content-card">
        <?php if (!empty($seo90Active)): ?>
            <div class="seo-news-header-row">
                <h2 style="font-size: 2rem; color: var(--primary); margin: 0;">Noticias y Eventos Destacados</h2>
                <div class="seo-news-filters" role="tablist" aria-label="Filtrar noticias y eventos">
                    <button type="button" class="seo-nf-btn active" data-filter="all" onclick="filterHomeNews('all', this)">Todos</button>
                    <button type="button" class="seo-nf-btn" data-filter="news" onclick="filterHomeNews('news', this)"><i class="fas fa-newspaper"></i> Noticias</button>
                    <button type="button" class="seo-nf-btn" data-filter="event" onclick="filterHomeNews('event', this)"><i class="fas fa-calendar-alt"></i> Eventos</button>
                </div>
            </div>
        <?php else: ?>
            <h2 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 2rem; text-align: center;">Noticias y Eventos Destacados</h2>
        <?php endif; ?>
        
        <div class="news-grid" id="homeNewsGrid">
            <?php
            $stmt = $pdo->query("SELECT * FROM news_events WHERE is_active_home = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY sort_order ASC, event_date DESC, id DESC");
            $hasNews = false;
            $isFirstNews = true;
            $newsIndex = 0;
            while ($news = $stmt->fetch()) {
                $hasNews = true;
                $newsIndex++;
                $isEvent = !empty($news['event_date']);
                $dateText = $isEvent ? date('d/m/Y', strtotime($news['event_date'])) : date('d/m/Y', strtotime($news['created_at']));
                $excerpt = mb_strimwidth(strip_tags($news['content']), 0, 140, '...');
                
                // Obtener imágenes adicionales de galería
                $stmtG = $pdo->prepare("SELECT id, image_path, caption, author FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id DESC");
                $stmtG->execute([$news['id']]);
                $gallery = $stmtG->fetchAll(PDO::FETCH_ASSOC);
                
                $mainImagePath = $news['image_path'];
                $mainImageCaption = $news['image_caption'] ?? '';
                $mainImageAuthor = $news['image_author'] ?? '';
                
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
                        $mainImageAuthor = $latestImage['author'] ?? '';
                    }
                }
                
                $featuredClass = $isFirstNews ? 'news-card-featured' : '';
                $isFirstNews = false;
                $itemType = $isEvent ? 'event' : 'news';
                ?>
                <div class="news-card <?php echo $featuredClass; ?>" data-news-type="<?php echo $itemType; ?>" data-news-idx="<?php echo $newsIndex; ?>" onclick="openNewsModal(<?php echo htmlspecialchars(json_encode([
                    'title' => $news['title'],
                    'date' => $dateText,
                    'isEvent' => $isEvent,
                    'image' => $mainImagePath ? $mainImagePath : '',
                    'image_caption' => $mainImageCaption,
                    'image_author' => $mainImageAuthor,
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
                                    <video src="<?php echo htmlspecialchars($mainImagePath); ?>" class="news-card-img" style="object-fit: cover; width: 100%; height: 100%; background: #000;" autoplay loop muted playsinline preload="metadata"></video>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 2rem; opacity: 0.8; text-shadow: 0 2px 5px rgba(0,0,0,0.5); pointer-events: none;"><i class="fas fa-play-circle"></i></div>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($mainImagePath); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-card-img" loading="lazy" decoding="async">
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

        <div style="margin-top: 3.5rem; padding: 2rem; background: var(--bg-alt); border-radius: 18px; text-align: center;">
            <p style="color: var(--text-light); font-style: italic; max-width: 800px; margin: 0 auto;">"Moratalla no es solo un lugar, es un conjunto de historias, gentes y paisajes que perduran en el tiempo."</p>
        </div>
    </div>
</div>

<?php if (!empty($seo90Active) && ($seoCfgHome['seo_show_home_bottom'] ?? '1') === '1'):
    $homeGuides = getSeo90Pages(false, $pdo);
?>
<!-- ═══ SECCIÓN INFERIOR EQUILIBRADA DE GUÍAS Y PATRIMONIO ═══ -->
<section class="seo-home-discreet-section">
    <div class="container">
        <div class="seo-discreet-box">
            <div class="seo-discreet-header">
                <div>
                    <span class="seo-discreet-kicker"><i class="fas fa-compass"></i> Guías y Patrimonio de Moratalla</span>
                    <h3>Explora Nuestras Guías de Turismo, Historia, Naturaleza y Gastronomía</h3>
                </div>
                <a href="guia.php" class="seo-discreet-all-link">Ver todas las guías y fichas <i class="fas fa-arrow-right"></i></a>
            </div>

            <div class="seo-balanced-pillars-grid">
                <?php foreach ($homeGuides as $gSlug => $gItem): ?>
                    <?php if ($gItem['type'] === 'pilar'): ?>
                        <a href="guia.php?slug=<?php echo htmlspecialchars($gSlug); ?>" class="seo-balanced-pillar-card">
                            <div class="seo-bp-img">
                                <img src="<?php echo htmlspecialchars($gItem['logo_image'] ?? $gItem['hero_image']); ?>" alt="<?php echo htmlspecialchars($gItem['short_title']); ?>" loading="lazy" decoding="async">
                                <span class="seo-bp-tag"><i class="<?php echo htmlspecialchars($gItem['icon']); ?>"></i> <?php echo htmlspecialchars($gItem['phase']); ?></span>
                            </div>
                            <div class="seo-bp-body">
                                <h4><?php echo htmlspecialchars($gItem['short_title']); ?></h4>
                                <p><?php echo htmlspecialchars($gItem['subtitle']); ?></p>
                                <span class="seo-bp-cta">Abrir guía <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="seo-mini-chips-row">
                <span class="seo-chips-title"><i class="fas fa-star"></i> Accesos directos:</span>
                <a href="guia.php?slug=castillo-de-moratalla" class="seo-mini-chip"><i class="fas fa-chess-rook"></i> Castillo de Moratalla</a>
                <a href="page.php?id=440" class="seo-mini-chip"><i class="fas fa-church"></i> Parroquia de la Asunción</a>
                <a href="page.php?id=441" class="seo-mini-chip"><i class="fas fa-place-of-worship"></i> Casa de Cristo</a>
                <a href="guia.php?slug=pinturas-rupestres-de-moratalla" class="seo-mini-chip"><i class="fas fa-palette"></i> Pinturas Rupestres UNESCO</a>
                <a href="guia.php?slug=semana-santa-de-moratalla" class="seo-mini-chip"><i class="fas fa-cross"></i> Semana Santa</a>
                <a href="guia.php?slug=tamboradas-de-moratalla" class="seo-mini-chip"><i class="fas fa-drum"></i> Tamboradas y Museo</a>
                <a href="guia.php?slug=rutas-de-senderismo-en-moratalla" class="seo-mini-chip"><i class="fas fa-hiking"></i> Rutas de Senderismo</a>
                <a href="guia.php?slug=fiestas-populares-de-moratalla" class="seo-mini-chip"><i class="fas fa-glass-cheers"></i> Fiestas y Cristo del Rayo</a>
                <a href="guia.php?slug=que-hacer-en-moratalla-fin-de-semana" class="seo-mini-chip"><i class="fas fa-calendar-check"></i> Escapada Fin de Semana</a>
            </div>
        </div>
    </div>
</section>

<script>
function filterHomeNews(type, btnEl) {
    document.querySelectorAll('.seo-nf-btn').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');

    const cards = document.querySelectorAll('#homeNewsGrid .news-card');
    cards.forEach(card => {
        const cardType = card.getAttribute('data-news-type');
        card.style.display = (type === 'all' || cardType === type) ? '' : 'none';
    });
}
</script>
<?php endif; ?>

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
                    <?php
                    $cardIcon = !empty($el['icon']) ? htmlspecialchars($el['icon']) : $p['icon'];
                    $lowerCard = strtolower($cardIcon);
                    if (preg_match('/\.(svg|png|jpg|jpeg|webp|gif)$/i', $lowerCard)) {
                        $src = (strpos($cardIcon, 'uploads/') === 0 || strpos($cardIcon, 'http') === 0) ? $cardIcon : 'uploads/icons/' . $cardIcon;
                        echo '<img src="' . htmlspecialchars($src) . '" alt="Icon" style="width: 24px; height: 24px; object-fit: contain;">';
                    } elseif (strpos($cardIcon, 'fa-') !== false) {
                        echo '<i class="' . (strpos($cardIcon, 'fas ') === false && strpos($cardIcon, 'far ') === false && strpos($cardIcon, 'fab ') === false ? 'fas ' : '') . $cardIcon . '"></i>';
                    } else {
                        echo '<span style="font-size: 1.2em;">' . $cardIcon . '</span>';
                    }
                    ?>
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

<?php
// Manejo automático de apertura de noticia por ID
if (isset($_GET['action']) && $_GET['action'] == 'ver_noticia' && isset($_GET['id'])) {
    $newsId = (int)$_GET['id'];
    $stmtVerNoticia = $pdo->prepare("SELECT * FROM news_events WHERE id = ?");
    $stmtVerNoticia->execute([$newsId]);
    $newsVer = $stmtVerNoticia->fetch();
    
    if ($newsVer) {
        $isEvent = !empty($newsVer['event_date']);
        $dateText = $isEvent ? date('d/m/Y', strtotime($newsVer['event_date'])) : date('d/m/Y', strtotime($newsVer['created_at']));
        
        $stmtG = $pdo->prepare("SELECT id, image_path, caption, author FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id DESC");
        $stmtG->execute([$newsId]);
        $gallery = $stmtG->fetchAll(PDO::FETCH_ASSOC);
        
        $mainImagePath = $newsVer['image_path'];
        $mainImageCaption = $newsVer['image_caption'] ?? '';
        $mainImageAuthor = $newsVer['image_author'] ?? '';
        
        if (!empty($newsVer['use_latest_gallery_image']) && !empty($gallery)) {
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
                $mainImageAuthor = $latestImage['author'] ?? '';
            }
        }
        
        $newsData = [
            'title' => $newsVer['title'],
            'date' => $dateText,
            'isEvent' => $isEvent,
            'image' => $mainImagePath ? $mainImagePath : '',
            'image_caption' => $mainImageCaption,
            'image_author' => $mainImageAuthor,
            'content' => $newsVer['content'],
            'gallery' => $gallery
        ];
        
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof openNewsModal === 'function') {
                openNewsModal(" . json_encode($newsData) . ");
            }
        });
        </script>";
    }
}
?>

<?php require_once 'inc/footer.php'; ?>
