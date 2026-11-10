<?php
// guia.php - Controlador de Guías Principales, Guías Temáticas y Enlace a Tarjetas de Patrimonio
require_once 'config.php';
require_once 'inc/seo_90dias_data.php';

$pdo = getDB();
$allGuides = getSeo90Pages();
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

$currentGuide = ($slug !== '' && isset($allGuides[$slug])) ? $allGuides[$slug] : null;
$relatedCards = $currentGuide ? getCardsForGuide($slug, $pdo) : [];

if ($currentGuide) {
    $pageTitle         = $currentGuide['title'];
    $pageDescription   = $currentGuide['meta_description'];
    $seoCustomKeywords = $currentGuide['keywords'];
    $ogImage           = "https://www.moratalla-murcia.com/moratalla/" . ltrim($currentGuide['hero_image'], '/');
    $isArticle         = true;
    $articleDate       = date('Y-m-d');
} else {
    $pageTitle       = "Guías de Moratalla | Qué Ver, Turismo Rural, Historia, Patrimonio y Rutas";
    $pageDescription = "Centro de guías turísticas y culturales de Moratalla (Murcia): qué ver, turismo rural, historia, monumentos, rutas de senderismo, castillo, pinturas rupestres y gastronomía.";
    $isArticle       = false;
}

require_once 'inc/header.php';
?>

<?php if ($currentGuide): ?>
    <!-- Schema.org FAQPage + BreadcrumbList -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "Inicio",
          "item": "https://www.moratalla-murcia.com/moratalla/index.php"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "Guías de Moratalla",
          "item": "https://www.moratalla-murcia.com/moratalla/guia.php"
        },
        {
          "@type": "ListItem",
          "position": 3,
          "name": <?php echo json_encode($currentGuide['short_title'], JSON_UNESCAPED_UNICODE); ?>,
          "item": <?php echo json_encode("https://www.moratalla-murcia.com/moratalla/guia.php?slug=" . $currentGuide['slug'], JSON_UNESCAPED_UNICODE); ?>
        }
      ]
    }
    </script>
    <?php if (!empty($currentGuide['faqs'])): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
        <?php
        $faqItems = [];
        foreach ($currentGuide['faqs'] as $f) {
            $faqItems[] = json_encode([
                '@type' => 'Question',
                'name' => $f['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $f['a']
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
        echo implode(",\n        ", $faqItems);
        ?>
      ]
    }
    </script>
    <?php endif; ?>

    <!-- HERO DE LA GUÍA CON IMAGEN / LOGO CONTEXTUAL -->
    <section class="seo-guide-hero" style="background: linear-gradient(135deg, rgba(8,28,21,0.92) 0%, rgba(27,67,50,0.88) 100%), url('<?php echo htmlspecialchars($currentGuide['hero_image']); ?>'); background-size: cover; background-position: center;">
        <div class="container">
            <nav aria-label="Breadcrumb" class="seo-breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <span class="sep">/</span>
                <a href="guia.php">Guías y Patrimonio</a>
                <span class="sep">/</span>
                <span class="current"><?php echo htmlspecialchars($currentGuide['short_title']); ?></span>
            </nav>

            <div class="seo-guide-hero-box seo-guide-hero-flex">
                <div class="seo-guide-hero-content">
                    <div class="seo-guide-badges">
                        <span class="seo-pill-badge"><i class="<?php echo htmlspecialchars($currentGuide['icon']); ?>"></i> <?php echo htmlspecialchars($currentGuide['phase']); ?></span>
                        <span class="seo-pill-reading"><i class="far fa-clock"></i> <?php echo htmlspecialchars($currentGuide['reading_time']); ?></span>
                    </div>
                    <h1><?php echo htmlspecialchars($currentGuide['h1']); ?></h1>
                    <p class="seo-guide-lead"><?php echo htmlspecialchars($currentGuide['subtitle']); ?></p>
                </div>
                <div class="seo-guide-hero-emblem">
                    <img src="<?php echo htmlspecialchars($currentGuide['logo_image'] ?? $currentGuide['hero_image']); ?>" alt="<?php echo htmlspecialchars($currentGuide['short_title']); ?>">
                </div>
            </div>
        </div>
    </section>

    <div class="container main-content" style="padding-top: 2rem;">
        <div class="nav-buttons-container" style="justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <a href="guia.php" class="btn-nav btn-nav-back btn-nav-sm">
                <i class="fas fa-arrow-left"></i> Todas las Guías
            </a>
            <a href="index.php" class="btn-nav btn-nav-home btn-nav-sm">
                <i class="fas fa-home"></i> Página Principal
            </a>
        </div>
        <div class="seo-guide-layout">
            <!-- COLUMNA PRINCIPAL -->
            <main class="seo-guide-main">
                <!-- FICHA RÁPIDA DE DATOS CLAVE -->
                <?php if (!empty($currentGuide['quick_facts'])): ?>
                <div class="seo-quick-facts-card">
                    <div class="seo-qf-header">
                        <i class="fas fa-info-circle"></i> Datos Clave de <?php echo htmlspecialchars($currentGuide['short_title']); ?>
                    </div>
                    <div class="seo-qf-grid">
                        <?php foreach ($currentGuide['quick_facts'] as $fact): ?>
                            <div class="seo-qf-item">
                                <span class="seo-qf-label"><?php echo htmlspecialchars($fact['label']); ?></span>
                                <strong class="seo-qf-val"><?php echo htmlspecialchars($fact['value']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- TARJETAS EXISTENTES VINCULADAS EN LA WEB (DESCRIPCIÓN Y FOTOGRAFÍAS) -->
                <?php if (!empty($relatedCards)): ?>
                <section id="tarjetas-patrimonio-web" class="seo-existing-cards-section">
                    <div class="seo-ec-header">
                        <span class="seo-discreet-kicker"><i class="fas fa-camera-retro"></i> Archivo Documental y Fotográfico de la Web</span>
                        <h2>Tarjetas y Galerías Fotográficas Relacionadas</h2>
                        <p>Accede directamente a las fichas ya creadas en la web con su descripción histórica y álbumes de fotografías:</p>
                    </div>
                    <div class="seo-existing-cards-grid">
                        <?php foreach ($relatedCards as $rc): ?>
                            <a href="<?php echo htmlspecialchars($rc['url']); ?>" class="seo-existing-card">
                                <div class="seo-ec-thumb">
                                    <img src="<?php echo htmlspecialchars($rc['image']); ?>"
                                         alt="<?php echo htmlspecialchars($rc['title']); ?>"
                                         loading="lazy"
                                         onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($rc['fallback_img']); ?>';">
                                    <?php if (!empty($rc['photos_count'])): ?>
                                        <span class="seo-ec-photo-badge"><i class="fas fa-images"></i> <?php echo (int)$rc['photos_count']; ?> fotos</span>
                                    <?php else: ?>
                                        <span class="seo-ec-photo-badge"><i class="fas fa-file-alt"></i> Ficha oficial</span>
                                    <?php endif; ?>
                                </div>
                                <div class="seo-ec-body">
                                    <span class="seo-ec-cat"><i class="<?php echo htmlspecialchars($rc['icon']); ?>"></i> <?php echo htmlspecialchars($rc['category_name']); ?></span>
                                    <h3><?php echo htmlspecialchars($rc['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($rc['description']); ?></p>
                                    <span class="seo-ec-cta">Abrir tarjeta y fotografías <i class="fas fa-arrow-right"></i></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- ÍNDICE DE CONTENIDOS -->
                <div class="seo-toc-box">
                    <div class="seo-toc-title"><i class="fas fa-list-ul"></i> Contenido de esta guía:</div>
                    <ol class="seo-toc-list">
                        <?php if (!empty($relatedCards)): ?>
                            <li><a href="#tarjetas-patrimonio-web">Tarjetas y galerías fotográficas relacionadas</a></li>
                        <?php endif; ?>
                        <?php foreach ($currentGuide['sections'] as $sec): ?>
                            <li><a href="#<?php echo htmlspecialchars($sec['id']); ?>"><?php echo htmlspecialchars($sec['title']); ?></a></li>
                        <?php endforeach; ?>
                        <li><a href="#mapa-interactivo">Mapa de ubicación y cómo llegar</a></li>
                        <?php if (!empty($currentGuide['faqs'])): ?>
                            <li><a href="#preguntas-frecuentes">Preguntas frecuentes</a></li>
                        <?php endif; ?>
                    </ol>
                </div>

                <!-- SECCIONES DE CONTENIDO -->
                <?php foreach ($currentGuide['sections'] as $idx => $sec): ?>
                    <section id="<?php echo htmlspecialchars($sec['id']); ?>" class="seo-article-section">
                        <h2><?php echo htmlspecialchars($sec['title']); ?></h2>
                        <div class="seo-article-body">
                            <?php echo $sec['content']; ?>
                        </div>
                    </section>
                <?php endforeach; ?>

                <!-- MAPA INTEGRADO -->
                <section id="mapa-interactivo" class="seo-article-section seo-map-section">
                    <h2><i class="fas fa-map-marked-alt" style="color: var(--accent);"></i> Mapa e Información de Ubicación en Moratalla</h2>
                    <p style="margin-bottom: 1rem; color: var(--text-light);">
                        Explora la ubicación de <strong><?php echo htmlspecialchars($currentGuide['map_query']); ?></strong> en el término municipal de Moratalla (Comarca del Noroeste, Región de Murcia):
                    </p>
                    <div class="seo-map-embed-wrapper">
                        <iframe
                            title="Mapa de <?php echo htmlspecialchars($currentGuide['short_title']); ?>"
                            width="100%"
                            height="320"
                            style="border:0; border-radius: 14px;"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.openstreetmap.org/export/embed.html?bbox=-2.0200%2C38.1200%2C-1.8200%2C38.2800&amp;layer=mapnik&amp;marker=38.1886%2C-1.8911">
                        </iframe>
                        <div class="seo-map-actions">
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($currentGuide['map_query']); ?>" target="_blank" rel="noopener" class="btn-nav btn-nav-home btn-nav-sm">
                                <i class="fas fa-directions"></i> Abrir en Google Maps (Cómo llegar)
                            </a>
                            <a href="alojamientos.php" class="btn-nav btn-nav-back btn-nav-sm">
                                <i class="fas fa-bed"></i> Ver Casas Rurales cercanas
                            </a>
                            <a href="restaurantes.php" class="btn-nav btn-nav-back btn-nav-sm">
                                <i class="fas fa-utensils"></i> Dónde Comer cerca
                            </a>
                        </div>
                    </div>
                </section>

                <!-- PREGUNTAS FRECUENTES (FAQ) -->
                <?php if (!empty($currentGuide['faqs'])): ?>
                <section id="preguntas-frecuentes" class="seo-article-section">
                    <h2><i class="fas fa-question-circle" style="color: var(--accent);"></i> Preguntas Frecuentes sobre <?php echo htmlspecialchars($currentGuide['short_title']); ?></h2>
                    <div class="seo-faq-list">
                        <?php foreach ($currentGuide['faqs'] as $f): ?>
                            <details class="seo-faq-item" open>
                                <summary><?php echo htmlspecialchars($f['q']); ?></summary>
                                <div class="seo-faq-answer">
                                    <p><?php echo htmlspecialchars($f['a']); ?></p>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- BLOQUE DE CONEXIÓN INTERNA -->
                <section id="enlaces-internos-seo" class="seo-internal-linking-box">
                    <div class="seo-il-badge"><i class="fas fa-compass"></i> Guías Principales de Moratalla</div>
                    <h3>Continúa explorando el Patrimonio de Moratalla</h3>
                    <div class="seo-il-pillars-grid">
                        <a href="guia.php?slug=guia-completa-moratalla" class="seo-il-card">
                            <i class="fas fa-compass"></i>
                            <div>
                                <strong>Guía de Turismo</strong>
                                <span>Qué ver y hacer en Moratalla</span>
                            </div>
                        </a>
                        <a href="guia.php?slug=historia-de-moratalla" class="seo-il-card">
                            <i class="fas fa-landmark"></i>
                            <div>
                                <strong>Historia de Moratalla</strong>
                                <span>Del Arte Rupestre a la Edad Moderna</span>
                            </div>
                        </a>
                        <a href="guia.php?slug=monumentos-y-patrimonio-moratalla" class="seo-il-card">
                            <i class="fas fa-chess-rook"></i>
                            <div>
                                <strong>Patrimonio y Monumentos</strong>
                                <span>Castillo, Iglesias y Santuarios</span>
                            </div>
                        </a>
                        <a href="guia.php?slug=fiestas-populares-de-moratalla" class="seo-il-card">
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <strong>Eventos y Fiestas</strong>
                                <span>Semana Santa, Tambores y Cristo del Rayo</span>
                            </div>
                        </a>
                    </div>
                </section>
            </main>

            <!-- BARRA LATERAL -->
            <aside class="seo-guide-sidebar">
                <?php if (!empty($relatedCards)): ?>
                <div class="seo-sidebar-card">
                    <h4><i class="fas fa-camera" style="color: var(--primary);"></i> Fichas y Galerías en la Web</h4>
                    <ul class="seo-sidebar-links">
                        <?php foreach ($relatedCards as $rc): ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($rc['url']); ?>">
                                    <i class="<?php echo htmlspecialchars($rc['icon']); ?>"></i>
                                    <span><?php echo htmlspecialchars($rc['title']); ?><?php echo !empty($rc['photos_count']) ? ' (' . (int)$rc['photos_count'] . ' fotos)' : ''; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="seo-sidebar-card seo-sidebar-cta">
                    <h4><i class="fas fa-suitcase-rolling"></i> Planifica tu Visita</h4>
                    <p>Directorios oficiales de Moratalla y sus pedanías:</p>
                    <a href="alojamientos.php" class="seo-side-btn primary">
                        <i class="fas fa-bed"></i> Casas Rurales y Alojamientos
                    </a>
                    <a href="restaurantes.php" class="seo-side-btn accent">
                        <i class="fas fa-utensils"></i> Bares y Restaurantes
                    </a>
                    <a href="contacto.php" class="seo-side-btn outline">
                        <i class="fas fa-envelope"></i> Consultar / Aportar Material
                    </a>
                </div>

                <div class="seo-sidebar-card">
                    <h4><i class="fas fa-star" style="color: var(--accent);"></i> Guías Principales</h4>
                    <ul class="seo-sidebar-links">
                        <?php foreach ($allGuides as $gSlug => $gItem): ?>
                            <?php if ($gItem['type'] === 'pilar'): ?>
                                <li>
                                    <a href="guia.php?slug=<?php echo htmlspecialchars($gSlug); ?>" class="<?php echo ($gSlug === $slug) ? 'active' : ''; ?>">
                                        <i class="<?php echo htmlspecialchars($gItem['icon']); ?>"></i>
                                        <span><?php echo htmlspecialchars($gItem['short_title']); ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="seo-sidebar-card">
                    <h4><i class="fas fa-book-open" style="color: var(--primary);"></i> Monografías y Rutas</h4>
                    <ul class="seo-sidebar-links">
                        <?php foreach ($allGuides as $gSlug => $gItem): ?>
                            <?php if ($gItem['type'] === 'articulo'): ?>
                                <li>
                                    <a href="guia.php?slug=<?php echo htmlspecialchars($gSlug); ?>" class="<?php echo ($gSlug === $slug) ? 'active' : ''; ?>">
                                        <i class="<?php echo htmlspecialchars($gItem['icon']); ?>"></i>
                                        <span><?php echo htmlspecialchars($gItem['short_title']); ?></span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>

        <div class="nav-buttons-container" style="justify-content: space-between; align-items: center; border-top: 1px solid var(--gray-200); padding-top: 2rem; margin-top: 2.5rem;">
            <a href="guia.php" class="btn-nav btn-nav-back btn-nav-sm">
                <i class="fas fa-arrow-left"></i> Ver Todas las Guías de Moratalla
            </a>
            <a href="index.php" class="btn-nav btn-nav-home btn-nav-sm">
                <i class="fas fa-home"></i> Volver a Portada
            </a>
        </div>
    </div>

<?php else:
    $allProductionCards = getProductionExistingCards($pdo);
?>
    <!-- HUB CENTRAL DE GUÍAS Y TARJETAS DE PATRIMONIO -->
    <section class="seo-guide-hero" style="background: linear-gradient(135deg, rgba(8,28,21,0.92) 0%, rgba(27,67,50,0.86) 100%), url('uploads/seo/pilar-guia-completa.svg'); background-size: cover; background-position: center;">
        <div class="container">
            <nav aria-label="Breadcrumb" class="seo-breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <span class="sep">/</span>
                <span class="current">Guías Turísticas y Patrimonio de Moratalla</span>
            </nav>
            <div class="seo-guide-hero-box">
                <div class="seo-guide-badges">
                    <span class="seo-pill-badge"><i class="fas fa-compass"></i> Patrimonio y Turismo en Moratalla</span>
                </div>
                <h1>Guías de Turismo Rural, Historia y Patrimonio de Moratalla</h1>
                <p class="seo-guide-lead">Explora nuestras guías principales, rutas de senderismo, monumentos y el acceso directo a las tarjetas y galerías fotográficas del archivo de Moratalla.</p>
            </div>
        </div>
    </section>

    <div class="container main-content">
        <div class="nav-buttons-container" style="justify-content: flex-start; align-items: center; margin-bottom: 1.5rem;">
            <a href="index.php" class="btn-nav btn-nav-home btn-nav-sm">
                <i class="fas fa-home"></i> Volver a la Página Principal
            </a>
        </div>
        <div class="content-card">
            <div class="seo-section-heading">
                <span class="seo-discreet-kicker"><i class="fas fa-compass"></i> Guías de Referencia</span>
                <h2>Guías Principales de Moratalla</h2>
                <p>Cinco recorridos temáticos con ilustraciones de referencia, mapas, preguntas frecuentes y enlaces al archivo fotográfico.</p>
            </div>

            <div class="seo-pillars-grid">
                <?php foreach ($allGuides as $gSlug => $gItem): ?>
                    <?php if ($gItem['type'] === 'pilar'): ?>
                        <a href="guia.php?slug=<?php echo htmlspecialchars($gSlug); ?>" class="seo-pillar-card">
                            <div class="seo-pillar-img-wrap">
                                <img src="<?php echo htmlspecialchars($gItem['hero_image']); ?>" alt="<?php echo htmlspecialchars($gItem['short_title']); ?>" loading="lazy" decoding="async">
                                <span class="seo-pillar-tag"><?php echo htmlspecialchars($gItem['phase']); ?></span>
                            </div>
                            <div class="seo-pillar-body">
                                <h3><i class="<?php echo htmlspecialchars($gItem['icon']); ?>"></i> <?php echo htmlspecialchars($gItem['short_title']); ?></h3>
                                <p><?php echo htmlspecialchars($gItem['subtitle']); ?></p>
                                <span class="seo-pillar-cta">Leer Guía <i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 3rem 0;">

            <div class="seo-section-heading">
                <span class="seo-discreet-kicker"><i class="fas fa-camera-retro"></i> Fichas Oficiales con Descripción y Galería</span>
                <h2>Tarjetas de Patrimonio, Tradiciones y Cultura en la Web</h2>
                <p>Acceso directo a las tarjetas ya creadas en moratalla-murcia.com con sus fotografías y textos originales.</p>
            </div>

            <div class="seo-existing-cards-grid">
                <?php foreach ($allProductionCards as $rc): ?>
                    <a href="<?php echo htmlspecialchars($rc['url']); ?>" class="seo-existing-card">
                        <div class="seo-ec-thumb">
                            <img src="<?php echo htmlspecialchars($rc['image']); ?>"
                                 alt="<?php echo htmlspecialchars($rc['title']); ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($rc['fallback_img']); ?>';">
                            <?php if (!empty($rc['photos_count'])): ?>
                                <span class="seo-ec-photo-badge"><i class="fas fa-images"></i> <?php echo (int)$rc['photos_count']; ?> fotos</span>
                            <?php else: ?>
                                <span class="seo-ec-photo-badge"><i class="fas fa-file-alt"></i> Ficha oficial</span>
                            <?php endif; ?>
                        </div>
                        <div class="seo-ec-body">
                            <span class="seo-ec-cat"><i class="<?php echo htmlspecialchars($rc['icon']); ?>"></i> <?php echo htmlspecialchars($rc['category_name']); ?></span>
                            <h3><?php echo htmlspecialchars($rc['title']); ?></h3>
                            <p><?php echo htmlspecialchars($rc['description']); ?></p>
                            <span class="seo-ec-cta">Ver tarjeta y fotos <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 3rem 0;">

            <div class="seo-section-heading">
                <span class="seo-discreet-kicker"><i class="fas fa-feather-alt"></i> Artículos y Monografías</span>
                <h2>Rutas, Monumentos y Tradiciones en Detalle</h2>
                <p>Artículos temáticos sobre lugares imprescindibles, casco antiguo, castillo, arte rupestre, tambores, senderismo y fiestas.</p>
            </div>

            <div class="seo-articles-grid">
                <?php foreach ($allGuides as $gSlug => $gItem): ?>
                    <?php if ($gItem['type'] === 'articulo'): ?>
                        <a href="guia.php?slug=<?php echo htmlspecialchars($gSlug); ?>" class="seo-article-card">
                            <div class="seo-article-icon">
                                <img src="<?php echo htmlspecialchars($gItem['logo_image'] ?? $gItem['hero_image']); ?>" alt="<?php echo htmlspecialchars($gItem['short_title']); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:10px;">
                            </div>
                            <div class="seo-article-info">
                                <span class="seo-article-phase"><?php echo htmlspecialchars($gItem['phase']); ?> · <?php echo htmlspecialchars($gItem['reading_time']); ?></span>
                                <h3><?php echo htmlspecialchars($gItem['short_title']); ?></h3>
                                <p><?php echo htmlspecialchars($gItem['subtitle']); ?></p>
                                <span class="seo-article-link">Ver guía <i class="fas fa-chevron-right"></i></span>
                            </div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="nav-buttons-container" style="justify-content: center; align-items: center; border-top: 1px solid var(--gray-200); padding-top: 2rem; margin-top: 2.5rem;">
                <a href="index.php" class="btn-nav btn-nav-home btn-nav-sm">
                    <i class="fas fa-home"></i> Volver a la Página Principal
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'inc/footer.php'; ?>
