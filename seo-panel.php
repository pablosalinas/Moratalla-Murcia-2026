<?php
// seo-panel.php - Centro de Control Local del Plan SEO 90 Días y Reversibilidad Total
require_once 'config.php';
require_once 'inc/seo_90dias_data.php';

$pdo = getDB();
if (!canManageSeo90()) {
    http_response_code(404);
    $_GET['code'] = 404;
    require __DIR__ . '/error.php';
    exit;
}
$statusMsg = '';

// Acción de cambio de modo en vivo (Activo vs Original)
if (isset($_GET['toggle_seo_90'])) {
    $enabled = ($_GET['toggle_seo_90'] === '1');
    setSeo90ActiveState($enabled);
    $statusMsg = $enabled
        ? '✅ Modo Plan SEO 90 Días + Rediseño Visual ACTIVADO (guardado en Base de Datos y servidor).'
        : '⏪ Modo Original restaurado: la web se comporta y muestra exactamente como estaba antes.';
}

$isSeoActive = isSeo90Active();
$allGuides   = getSeo90Pages();
$top10       = getSeoTop10PriorityLinks();
$seasons     = getSeoSeasonalPlans();

$pageTitle = "Centro de Control · Plan SEO 90 Días y Diseño (Modo Local)";
$pageDescription = "Panel de control local para revisar, probar o revertir el Plan SEO de 90 días y las mejoras visuales de moratalla-murcia.com.";

require_once 'inc/header.php';
?>

<section class="seo-guide-hero" style="background: linear-gradient(135deg, #081c15 0%, #1b4332 100%); padding: 3rem 0;">
    <div class="container">
        <div class="seo-guide-hero-box" style="max-width: 980px;">
            <div class="seo-guide-badges">
                <span class="seo-pill-badge"><i class="fas fa-sliders-h"></i> Exclusivo en Entorno Local</span>
                <span class="seo-pill-reading" style="background: <?php echo $isSeoActive ? '#059669' : '#d97706'; ?>; color: white;">
                    Estado actual: <?php echo $isSeoActive ? 'NUEVO DISEÑO + SEO 90 DÍAS ACTIVO' : 'VERSIÓN ORIGINAL ACTIVA'; ?>
                </span>
            </div>
            <h1>Panel de Control Local: Plan SEO 90 Días y Renovación Visual</h1>
            <p class="seo-guide-lead">
                Desde aquí puedes comparar en tiempo real la nueva versión frente a la versión original con un solo clic, explorar las 5 páginas pilar, los 8 artículos, las 10 páginas prioritarias y el kit de autoridad (Fase 3).
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem; justify-content: center;">
                <?php if ($isSeoActive): ?>
                    <a href="seo-panel.php?toggle_seo_90=0" class="btn-nav" style="background: #f59e0b; color: #111827; padding: 0.9rem 1.8rem;">
                        <i class="fas fa-undo"></i> Ver Web como estaba Antes (Desactivar Mejoras)
                    </a>
                <?php else: ?>
                    <a href="seo-panel.php?toggle_seo_90=1" class="btn-nav" style="background: #10b981; color: white; padding: 0.9rem 1.8rem;">
                        <i class="fas fa-magic"></i> Activar Plan SEO 90 Días + Rediseño Visual
                    </a>
                <?php endif; ?>
                <a href="index.php" class="btn-nav btn-nav-back" style="padding: 0.9rem 1.8rem;">
                    <i class="fas fa-eye"></i> Ir a la Portada para Ver el Resultado
                </a>
                <a href="sitemap.php" target="_blank" class="btn-nav btn-nav-back" style="padding: 0.9rem 1.8rem;">
                    <i class="fas fa-sitemap"></i> Ver Sitemap.xml
                </a>
            </div>
        </div>
    </div>
</section>

<div class="container main-content">
    <?php if ($statusMsg): ?>
        <div style="background: #ecfdf5; border: 2px solid #10b981; color: #065f46; padding: 1.2rem 1.5rem; border-radius: 14px; margin-bottom: 2rem; font-weight: 700; text-align: center; font-size: 1.05rem;">
            <?php echo htmlspecialchars($statusMsg); ?>
        </div>
    <?php endif; ?>

    <!-- GARANTÍA DE REVERSIBILIDAD -->
    <div class="content-card" style="margin-bottom: 2.5rem; border-left: 6px solid #d4af37;">
        <h2 style="color: var(--primary); font-size: 1.5rem; margin-bottom: 0.8rem;">
            <i class="fas fa-shield-alt" style="color: #d4af37;"></i> Garantía Total: ¿Cómo volver exactamente al estado original si no quieres subirlo?
        </h2>
        <p style="color: var(--text-light); margin-bottom: 1rem;">
            Todo se ha implementado <strong>exclusivamente en local</strong> (no se ha hecho <code>git push</code> a producción ni se ha borrado ningún dato de la base de datos). Además, cuentas con <strong>dos formas inmediatas</strong> de dejarlo como estaba:
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem;">
            <div style="background: var(--bg-alt); padding: 1.2rem; border-radius: 12px;">
                <strong style="color: var(--primary); display: block; margin-bottom: 0.4rem;">Opción 1: Conmutador instantáneo en el navegador</strong>
                <p style="font-size: 0.9rem; color: var(--text-light);">Usa el botón flotante inferior izquierdo (o el botón de arriba) para alternar entre <em>Modo SEO 90 Días</em> y <em>Versión Original</em> cuantas veces quieras para comparar.</p>
            </div>
            <div style="background: var(--bg-alt); padding: 1.2rem; border-radius: 12px;">
                <strong style="color: var(--primary); display: block; margin-bottom: 0.4rem;">Opción 2: Restauración física 100% de archivos</strong>
                <p style="font-size: 0.9rem; color: var(--text-light);">Se ha guardado una copia íntegra en <code>.backup_pre_seo_90dias/</code>. Ejecutando <code>restaurar_estado_original.bat</code> (o <code>php restaurar_estado_original.php</code>) todos los archivos vuelven al estado exacto previo en 1 segundo.</p>
            </div>
        </div>
    </div>

    <!-- FASE 1 -->
    <div class="content-card" style="margin-bottom: 2.5rem;">
        <div class="seo-section-heading" style="text-align: left; margin-bottom: 1.8rem;">
            <span class="seo-kicker">FASE 1 · DÍAS 1 AL 30</span>
            <h2>Auditoría Técnica, Palabras Clave y las 5 Páginas Pilar</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.2rem; margin-bottom: 2rem;">
            <div class="seo-audit-check">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>1. Títulos SEO y Metadescripciones Únicas</strong>
                    <p>Optimizados dinámicamente en Portada, Categorías, Páginas, Alojamientos, Restaurantes y Guías.</p>
                </div>
            </div>
            <div class="seo-audit-check">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>2. Indexación (Sitemap.xml + Robots.txt)</strong>
                    <p><code>sitemap.php</code> actualizado con todas las páginas visibles, páginas pilar, prioritarias y artículos.</p>
                </div>
            </div>
            <div class="seo-audit-check">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>3. Velocidad y Core Web Vitals (LCP &lt; 2,5s)</strong>
                    <p><code>fetchpriority="high"</code> en banner principal, <code>loading="lazy"</code> en tarjetas/galerías y caché en <code>.htaccess</code>.</p>
                </div>
            </div>
            <div class="seo-audit-check">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>4. Datos Estructurados Schema.org</strong>
                    <p>JSON-LD enriquecido con <code>TouristDestination</code>, <code>Article</code>, <code>FAQPage</code> y <code>BreadcrumbList</code>.</p>
                </div>
            </div>
        </div>

        <h3 style="color: var(--primary); margin-bottom: 1rem;">Las 5 Páginas Pilar Creadas (con mapa integrado, FAQs y enlaces internos)</h3>
        <div class="seo-pillars-grid">
            <?php foreach ($allGuides as $gSlug => $gItem): ?>
                <?php if ($gItem['type'] === 'pilar'): ?>
                    <a href="guia.php?slug=<?php echo htmlspecialchars($gSlug); ?>" class="seo-pillar-card">
                        <div class="seo-pillar-img-wrap">
                            <img src="<?php echo htmlspecialchars($gItem['hero_image']); ?>" alt="<?php echo htmlspecialchars($gItem['short_title']); ?>" loading="lazy">
                            <span class="seo-pillar-tag"><?php echo htmlspecialchars($gItem['phase']); ?></span>
                        </div>
                        <div class="seo-pillar-body">
                            <h3><i class="<?php echo htmlspecialchars($gItem['icon']); ?>"></i> <?php echo htmlspecialchars($gItem['short_title']); ?></h3>
                            <p><?php echo htmlspecialchars($gItem['meta_description']); ?></p>
                            <span class="seo-pillar-cta">Abrir Página Pilar <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- FASE 2 -->
    <div class="content-card" style="margin-bottom: 2.5rem;">
        <div class="seo-section-heading" style="text-align: left; margin-bottom: 1.8rem;">
            <span class="seo-kicker">FASE 2 · DÍAS 31 AL 60</span>
            <h2>Marketing de Contenidos (Artículos SEO + Red de Enlazado Interno)</h2>
            <p>Todos los artículos propuestos para el Mes 2 ya están redactados, maquetados e interconectados con Guía de Turismo, Historia, Patrimonio y Eventos.</p>
        </div>

        <div class="seo-articles-grid">
            <?php foreach ($allGuides as $gSlug => $gItem): ?>
                <?php if ($gItem['type'] === 'articulo'): ?>
                    <a href="guia.php?slug=<?php echo htmlspecialchars($gSlug); ?>" class="seo-article-card">
                        <div class="seo-article-icon">
                            <i class="<?php echo htmlspecialchars($gItem['icon']); ?>"></i>
                        </div>
                        <div class="seo-article-info">
                            <span class="seo-article-phase"><?php echo htmlspecialchars($gItem['phase']); ?></span>
                            <h3><?php echo htmlspecialchars($gItem['title']); ?></h3>
                            <p><?php echo htmlspecialchars($gItem['meta_description']); ?></p>
                            <span class="seo-article-link">Ver artículo publicado <i class="fas fa-chevron-right"></i></span>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- FASE 3 -->
    <div class="content-card">
        <div class="seo-section-heading" style="text-align: left; margin-bottom: 1.8rem;">
            <span class="seo-kicker">FASE 3 · DÍAS 61 AL 90</span>
            <h2>Las 10 Páginas SEO Prioritarias, Google Business Profile y Autoridad (Backlinks)</h2>
        </div>

        <h3 style="color: var(--primary); margin-bottom: 1rem;">1. Acceso Directo a las 10 Páginas con Mayor Potencial de Tráfico</h3>
        <div class="seo-top10-grid" style="margin-bottom: 2.5rem;">
            <?php foreach ($top10 as $item): ?>
                <a href="<?php echo htmlspecialchars($item['url']); ?>" class="seo-top10-card">
                    <span class="seo-top10-num">#<?php echo (int)$item['rank']; ?></span>
                    <div class="seo-top10-info">
                        <strong><i class="<?php echo htmlspecialchars($item['icon']); ?>"></i> <?php echo htmlspecialchars($item['title']); ?></strong>
                        <span><?php echo htmlspecialchars($item['desc']); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <h3 style="color: var(--primary); margin-bottom: 1rem;">2. Ficha Lista para Google Business Profile</h3>
        <div style="background: var(--bg-alt); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--gray-200); margin-bottom: 2.5rem;">
            <p style="margin-bottom: 0.8rem; color: var(--text-light);">Datos optimizados listos para copiar y dar de alta en <strong>Google Business Profile</strong>:</p>
            <ul style="list-style: none; display: grid; gap: 0.6rem; font-size: 0.95rem;">
                <li><strong>Nombre de la ficha:</strong> <code>Moratalla Murcia - Patrimonio Histórico Digital</code></li>
                <li><strong>Categoría principal:</strong> Centro de información turística / Organización cultural / Archivo histórico</li>
                <li><strong>Sitio web:</strong> <code>https://www.moratalla-murcia.com/moratalla/</code></li>
                <li><strong>Descripción SEO (750 caracteres):</strong> <em>"Proyecto de patrimonio histórico digital, promoción cultural y guía turística de Moratalla (Región de Murcia). Consulta noticias locales, calendario de fiestas (Semana Santa, Tamboradas Patrimonio UNESCO y Santísimo Cristo del Rayo), rutas de senderismo por el río Alhárabe y Revolcadores, pinturas rupestres, historia del Castillo-Fortaleza, directorio de casas rurales y dónde comer en Moratalla."</em></li>
            </ul>
        </div>

        <h3 style="color: var(--primary); margin-bottom: 1rem;">3. Estrategia de Autoridad y Backlinks Locales</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.2rem;">
            <div style="background: white; border: 1px solid var(--gray-200); padding: 1.2rem; border-radius: 12px;">
                <strong style="color: var(--primary);"><i class="fas fa-university"></i> Ayuntamiento y Turismo Oficial</strong>
                <p style="font-size: 0.88rem; color: var(--text-light); margin-top: 0.4rem;">Solicitar enlace como recurso de Archivo Histórico Digital y directorio de alojamientos/restauración local.</p>
            </div>
            <div style="background: white; border: 1px solid var(--gray-200); padding: 1.2rem; border-radius: 12px;">
                <strong style="color: var(--primary);"><i class="fas fa-users"></i> Asociaciones y Cofradías</strong>
                <p style="font-size: 0.88rem; color: var(--text-light); margin-top: 0.4rem;">Enlazar las secciones históricas de Tamboristas, Cofradías, Banda de Música y Clubes Deportivos desde sus perfiles y webs.</p>
            </div>
            <div style="background: white; border: 1px solid var(--gray-200); padding: 1.2rem; border-radius: 12px;">
                <strong style="color: var(--primary);"><i class="fas fa-hiking"></i> Blogs de Senderismo y Turismo Rural</strong>
                <p style="font-size: 0.88rem; color: var(--text-light); margin-top: 0.4rem;">Compartir las guías de Bolvonegro, Alhárabe, Pinturas Rupestres y Revolcadores como referencia local documentada.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
