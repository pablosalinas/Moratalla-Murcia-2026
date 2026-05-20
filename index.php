<?php
// index.php - v1.2.0 (Finalizing deployment)
require_once 'inc/header.php';
?>


<div class="container main-content" style="margin-top: 2rem;">
    <div class="content-card">
        <h2 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 2rem; text-align: center;">Noticias y Eventos Destacados</h2>
        
        <div class="news-grid">
            <?php
            $stmt = $pdo->query("SELECT * FROM news_events WHERE is_active_home = 1 ORDER BY event_date DESC, id DESC");
            $hasNews = false;
            while ($news = $stmt->fetch()) {
                $hasNews = true;
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
                        <?php if ($news['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($news['image_path']); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-card-img">
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

<?php require_once 'inc/footer.php'; ?>
