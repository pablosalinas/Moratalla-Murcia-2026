<?php
// category.php
require_once 'config.php';
$pdo = getDB();

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) { header("Location: index.php"); exit; }

// Buscamos subcategorías
$stmtSub = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order ASC, name ASC");
$stmtSub->execute([$id]);
$subcategories = $stmtSub->fetchAll();

// Buscamos páginas directas
$stmtPage = $pdo->prepare("SELECT * FROM pages WHERE category_id = ? ORDER BY sort_order ASC, title ASC");
$stmtPage->execute([$id]);
$pages = $stmtPage->fetchAll();

// Si solo hay una página y no hay subcategorías, redirigimos a la página
if (count($pages) === 1 && count($subcategories) === 0) {
    header("Location: page.php?id=" . $pages[0]['id']);
    exit;
}

// Ahora que hemos pasado todas las posibles redirecciones de cabecera, cargamos el Header
require_once 'inc/header.php';

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
    if (strpos($n, 'fiestas') !== false || strpos($n, 'semana santa') !== false || strpos($n, 'nazareno') !== false || strpos($n, 'tambor') !== false) return 'fas fa-church';
    if (strpos($n, 'cristo') !== false || strpos($n, 'rayo') !== false) return 'fas fa-cross';
    if (strpos($n, 'asociaciones') !== false || strpos($n, 'servicios') !== false) return 'fas fa-users';
    if (strpos($n, 'noticias') !== false || strpos($n, 'actualidad') !== false) return 'fas fa-newspaper';
    if (strpos($n, 'fotografía') !== false || strpos($n, 'galería') !== false) return 'fas fa-camera';
    
    return 'fas fa-folder-open';
}
?>

<section class="hero-page">
    <div class="container" style="background: rgba(255,255,255,0.8); padding: 1.5rem; border-radius: 15px; display: inline-block;">
        <h2 style="text-shadow: none;"><?php echo htmlspecialchars($category['name']); ?></h2>
        <p style="color: var(--text);">Inicio > <?php echo htmlspecialchars($category['name']); ?></p>
    </div>
</section>

<div class="container main-content">
    <div class="content-card">
        
        <?php if (count($subcategories) > 0): ?>
            <h3 style="margin-bottom: 2rem; color: var(--primary);">Subsecciones</h3>
            <div class="grid-categories" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 4rem;">
                <?php foreach ($subcategories as $sub): ?>
                    <a href="category.php?id=<?php echo $sub['id']; ?>" class="btn-modern" style="background: white; color: var(--text); border: 1px solid var(--gray-300); justify-content: flex-start;">
                        <i class="<?php echo getCategoryIcon($sub['name']); ?>" style="color: var(--accent);"></i>
                        <?php echo htmlspecialchars($sub['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (count($pages) > 0): ?>
            <h3 style="margin-bottom: 2rem; color: var(--primary);">Páginas en esta sección</h3>
            <div class="grid-categories" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($pages as $p): ?>
                    <a href="page.php?id=<?php echo $p['id']; ?>" class="btn-modern" style="background: white; color: var(--text); border: 1px solid var(--gray-300); justify-content: flex-start; border-left: 4px solid var(--primary);">
                        <i class="far fa-file-alt" style="color: var(--primary);"></i>
                        <?php echo htmlspecialchars($p['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php if (count($subcategories) == 0): ?>
                <div style="text-align: center; padding: 4rem 0;">
                    <i class="fas fa-search" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1.5rem;"></i>
                    <p>No se ha encontrado contenido directo en esta sección todavía.</p>
                    <a href="index.php" class="btn-modern" style="margin-top: 1rem;">Volver al Inicio</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--gray-100); display: flex; justify-content: center;">
            <a href="index.php" class="btn-modern" style="background: var(--gray-200); color: var(--text);">
                <i class="fas fa-home"></i> Volver a la Página Inicial
            </a>
        </div>

    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
