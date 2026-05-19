<?php
// page.php
require_once 'config.php';
$pdo = getDB();

$id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id) { header("Location: index.php"); exit; }

$stmt = $pdo->prepare("SELECT p.*, c.name as cat_name, c.id as cat_id, c.parent_id as cat_parent_id, c.is_visible as cat_is_visible FROM pages p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) { header("Location: index.php"); exit; }

// Si la página pertenece a una categoría y esa categoría está oculta/invisible, no permitir el acceso
if ($page['cat_id'] && $page['cat_is_visible'] == 0) {
    header("Location: index.php");
    exit;
}

// SEO dinámico
$pageTitle = $page['title'];
$cleanText = trim(strip_tags($page['content']));
// Limitar a 150 caracteres para la meta descripción
$pageDescription = mb_substr(preg_replace('/\s+/', ' ', $cleanText), 0, 150);
if (strlen($cleanText) > 150) $pageDescription .= "...";

require_once 'inc/header.php';

// Evitar bucle en botón "Volver": si la categoría actual es de 1 sola página, volver al padre
$backLink = "category.php?id=" . $page['cat_id'];
$backName = $page['cat_name'];

if ($page['cat_parent_id']) {
    $stmtSub = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ? AND is_visible = 1");
    $stmtSub->execute([$page['cat_id']]);
    $subCount = $stmtSub->fetchColumn();
    
    $stmtPg = $pdo->prepare("SELECT COUNT(*) FROM pages WHERE category_id = ?");
    $stmtPg->execute([$page['cat_id']]);
    $pgCount = $stmtPg->fetchColumn();
    
    if ($pgCount == 1 && $subCount == 0) {
        $stmtParent = $pdo->prepare("SELECT id, name FROM categories WHERE id = ? AND is_visible = 1");
        $stmtParent->execute([$page['cat_parent_id']]);
        $parentCat = $stmtParent->fetch();
        if ($parentCat) {
            $backLink = "category.php?id=" . $parentCat['id'];
            $backName = $parentCat['name'];
        }
    }
}
?>

<section class="hero-page" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('uploads/theme/moratalla.jpg'); background-size: cover; background-position: center; padding: 5rem 0; text-align: center; color: white;">
    <div class="container">
        <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 2rem; border-radius: 20px; display: inline-block; border: 1px solid rgba(255,255,255,0.2);">
            <p style="opacity: 0.9; margin-bottom: 0.5rem; color: white; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-folder-open" style="color: var(--accent);"></i> <?php echo htmlspecialchars($page['cat_name']); ?></p>
            <h2 style="color: white; font-size: 2.5rem; text-shadow: 0 2px 10px rgba(0,0,0,0.3);"><?php echo htmlspecialchars($page['title']); ?></h2>
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
        
        <?php
        // Galería de imágenes
        $iStmt = $pdo->prepare("SELECT * FROM page_images WHERE page_id = ? AND is_visible = 1 ORDER BY sort_order ASC, id ASC");
        $iStmt->execute([$id]);
        $images = $iStmt->fetchAll();
        
        if (count($images) > 0): ?>
            <!-- DEBUG: ID PAGINA <?php echo $id; ?> | IMAGENES ENCONTRADAS: <?php echo count($images); ?> -->
            <div class="gallery-wrapper" style="margin-bottom: 3rem;">
                <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; border-left: 3px solid var(--accent); padding-left: 1rem;">Obras y Galería de Imágenes</h3>
                <!-- Carrusel Animado (Swiper) -->
                <div class="swiper page-gallery-swiper" style="padding-bottom: 3rem;">
                    <div class="swiper-wrapper">
                        <?php 
                        foreach ($images as $img): 
                            $fullPath = $img['image_path'];
                            $isBartolome = (strpos($fullPath, 'bartolome') !== false);
                            $displaySrc = $fullPath;
                            
                            if ($isBartolome && file_exists($fullPath)) {
                                $imageData = base64_encode(file_get_contents($fullPath));
                                $displaySrc = 'data:image/jpeg;base64,' . $imageData;
                            }
                        ?>
                            <a href="<?php echo $fullPath; ?>" class="swiper-slide lightbox-link" data-caption="<?php echo htmlspecialchars(isset($img['caption']) ? $img['caption'] : ''); ?>" style="height: 350px; border-radius: 15px; overflow: hidden; display: block; border: 1px solid var(--gray-200); position: relative; box-shadow: 0 4px 10px rgba(0,0,0,0.1); transition: transform 0.3s ease; background: #f0f0f0; text-align: center;">
                                <img src="<?php echo $displaySrc; ?>" style="width: 100%; height: 100%; object-fit: contain; padding: 10px; display: block;">
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 1rem; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white; text-align: center; font-size: 0.9rem; opacity: 0; transition: opacity 0.3s ease;" class="hover-view">
                                    <?php if (!empty($img['caption'])): ?>
                                        <strong style="font-size: 1.1rem; display: block; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($img['caption']); ?></strong>
                                    <?php endif; ?>
                                    <i class="fas fa-search-plus"></i> Ampliar obra
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        <?php endif; ?>

        <article class="html-content" style="font-size: 1.1rem; line-height: 1.8; color: var(--text);">
            <?php echo $page['content']; ?>
        </article>
        
        <div class="nav-buttons-container" style="justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-100); padding-top: 3rem;">
            <a href="<?php echo htmlspecialchars($backLink); ?>" class="btn-nav btn-nav-back btn-nav-sm">
                <i class="fas fa-arrow-left"></i> Volver a <?php echo htmlspecialchars($backName); ?>
            </a>
            <a href="index.php" class="btn-nav btn-nav-home">
                <i class="fas fa-home"></i> Inicio
            </a>
            <div style="text-align: right; opacity: 0.4; font-size: 0.75rem;" class="hide-mobile">
                Archivo original: <?php echo htmlspecialchars($page['original_file']); ?>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Modal (Carrusel Pantalla Completa) -->
<div id="image-lightbox" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(0,0,0,0.95); flex-direction: column; align-items: center; justify-content: center;">
    
    <!-- Botón Cerrar Mejorado -->
    <div style="width: 100%; padding: 1.5rem; display: flex; justify-content: flex-end; position: absolute; top: 0; z-index: 10000;">
        <button id="close-lightbox" class="btn-modern" style="background: #e53935; color: white; border-radius: 50px; font-weight: bold; border: none; cursor: pointer; font-size: 1.1rem; padding: 0.8rem 1.5rem; box-shadow: 0 4px 15px rgba(229, 57, 53, 0.4);">
            <i class="fas fa-times"></i> Volver a <?php echo htmlspecialchars($page['title']); ?>
        </button>
    </div>
    
    <!-- Botones de Navegación -->
    <button id="prev-img" class="nav-btn" style="left: 2rem;"><i class="fas fa-chevron-left"></i></button>
    <button id="next-img" class="nav-btn" style="right: 2rem;"><i class="fas fa-chevron-right"></i></button>

    <!-- Imagen Principal -->
    <div style="max-width: 95%; max-height: 90vh; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative;">
        <img id="lightbox-img" src="" style="max-width: 100%; max-height: 80vh; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.7); transition: opacity 0.3s ease;">
        <div id="lightbox-caption" style="color: white; margin-top: 1.5rem; font-size: 1.2rem; text-align: center; max-width: 800px; text-shadow: 0 2px 4px rgba(0,0,0,0.8); min-height: 2rem;"></div>
    </div>
    
    <!-- Indicador de Tiempo Visual (Opcional pero elegante) -->
    <div style="position: absolute; bottom: 2rem; display: flex; gap: 0.5rem; color: white; opacity: 0.5; font-size: 0.9rem;">
        <i class="fas fa-play" style="font-size: 0.7rem; margin-top: 3px;"></i> Reproducción Automática (5s)
    </div>
</div>

<style>
    /* CSS para el Carrusel y Lightbox */
    .gallery-scroll::-webkit-scrollbar {
        height: 10px;
    }
    .gallery-scroll::-webkit-scrollbar-track {
        background: #f1f1f1; 
        border-radius: 5px;
    }
    .gallery-scroll::-webkit-scrollbar-thumb {
        background: var(--primary); 
        opacity: 0.5;
        border-radius: 5px;
    }
    .gallery-scroll::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark); 
    }
    .lightbox-link:hover {
        transform: translateY(-5px);
    }
    .lightbox-link:hover .hover-view {
        opacity: 1 !important;
    }
    
    /* Botones de navegación del Lightbox */
    .nav-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: none;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        font-size: 1.5rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 10001;
    }
    .nav-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-50%) scale(1.1);
    }
    
    /* Cleanup for legacy HTML */
    .html-content p { margin-bottom: 1.5rem; }
    .html-content img { max-width: 100%; height: auto; margin: 1rem 0; border-radius: 10px; }
    .html-content center { text-align: center; display: block; }
    .html-content font { font-family: inherit !important; color: inherit !important; }
    .html-content b, .html-content strong { font-weight: 600; color: var(--primary); }
</style>

<script>
    // JS para el Lightbox Carrusel Automático
    const links = document.querySelectorAll('.lightbox-link');
    const lightbox = document.getElementById('image-lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const closeBtn = document.getElementById('close-lightbox');
    const prevBtn = document.getElementById('prev-img');
    const nextBtn = document.getElementById('next-img');

    // Extraer todas las rutas de imágenes y descripciones
    const galleryItems = Array.from(links).map(link => ({
        src: link.getAttribute('href'),
        caption: link.getAttribute('data-caption')
    }));
    let currentIndex = 0;
    let autoPlayInterval;

    function showImage(index) {
        if (index < 0) index = galleryItems.length - 1;
        if (index >= galleryItems.length) index = 0;
        
        currentIndex = index;
        lightboxImg.style.opacity = 0; // Efecto de parpadeo suave
        
        setTimeout(() => {
            lightboxImg.src = galleryItems[currentIndex].src;
            document.getElementById('lightbox-caption').textContent = galleryItems[currentIndex].caption;
            lightboxImg.style.opacity = 1;
        }, 150);
    }

    function nextImage() {
        showImage(currentIndex + 1);
        resetAutoPlay();
    }

    function prevImage() {
        showImage(currentIndex - 1);
        resetAutoPlay();
    }

    function startAutoPlay() {
        // Pasar la imagen cada 5000ms (5 segundos)
        autoPlayInterval = setInterval(() => {
            showImage(currentIndex + 1);
        }, 5000);
    }

    function resetAutoPlay() {
        clearInterval(autoPlayInterval);
        startAutoPlay();
    }

    // Inicializar los clics en la galería principal
    links.forEach((link, idx) => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden'; 
            showImage(idx);
            startAutoPlay();
        });
    });

    // Controles manuales
    nextBtn.addEventListener('click', (e) => { e.stopPropagation(); nextImage(); });
    prevBtn.addEventListener('click', (e) => { e.stopPropagation(); prevImage(); });

    // Navegación con teclado
    document.addEventListener('keydown', (e) => {
        if (lightbox.style.display === 'flex') {
            if (e.key === 'ArrowRight') nextImage();
            if (e.key === 'ArrowLeft') prevImage();
            if (e.key === 'Escape') closeLightbox();
        }
    });

    // Cerrar el lightbox
    closeBtn.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function(e) {
        // Cerrar si hace clic fuera de la imagen y de los botones
        if(e.target === lightbox) {
            closeLightbox();
        }
    });

    function closeLightbox() {
        lightbox.style.display = 'none';
        document.body.style.overflow = '';
        clearInterval(autoPlayInterval);
    }
</script>

<script>
    // Inicialización del carrusel animado para la galería de la página
    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('.page-gallery-swiper')) {
            new Swiper('.page-gallery-swiper', {
                loop: false,
                autoplay: {
                    delay: 4000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                breakpoints: {
                    320: { slidesPerView: 1.1, spaceBetween: 15, centeredSlides: true },
                    768: { slidesPerView: 2.2, spaceBetween: 20, centeredSlides: false },
                    1024: { slidesPerView: 3, spaceBetween: 30, centeredSlides: false }
                }
            });
        }
    });
</script>

<?php require_once 'inc/footer.php'; ?>
