<?php
// index.php - v1.2.0 (Finalizing deployment)
require_once 'inc/header.php';
?>


<div class="container main-content" style="margin-top: 2rem;">
    <div class="content-card">
        <h2 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 2rem; text-align: center;">Secciones Destacadas</h2>
        
        <div class="sections-grid">
            <?php
            $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL AND sort_order > 0 ORDER BY sort_order ASC");
            while ($cat = $stmt->fetch()) {
                ?>
                <a href="category.php?id=<?php echo $cat['id']; ?>" class="section-link">
                    <i class="fas fa-folder"></i>
                    <div>
                        <div style="font-size: 1.1rem;"><?php echo htmlspecialchars($cat['name']); ?></div>
                        <small style="font-weight: 400; color: var(--text-light); font-size: 0.75rem;">Explorar sección</small>
                    </div>
                </a>
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
