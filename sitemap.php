<?php
// sitemap.php
require_once 'config.php';
require_once 'inc/seo_90dias_data.php';
$pdo = getDB();

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$baseUrl = 'https://www.moratalla-murcia.com/moratalla/';

// Helper function to add URLs
function addUrl($url, $lastmod = null, $changefreq = 'weekly', $priority = '0.5') {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url) . "</loc>\n";
    if ($lastmod) {
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
    }
    echo "    <changefreq>" . $changefreq . "</changefreq>\n";
    echo "    <priority>" . $priority . "</priority>\n";
    echo "  </url>\n";
}

// 1. Homepage
addUrl($baseUrl, date('Y-m-d'), 'daily', '1.0');

// 2. Páginas Pilar y Artículos del Plan SEO 90 Días (si está activo)
if (function_exists('isSeo90Active') && isSeo90Active()) {
    addUrl($baseUrl . 'guia.php', date('Y-m-d'), 'weekly', '0.9');
    $seoGuides = getSeo90Pages();
    foreach ($seoGuides as $slug => $g) {
        $prio = ($g['type'] === 'pilar') ? '0.9' : '0.85';
        addUrl($baseUrl . 'guia.php?slug=' . urlencode($slug), date('Y-m-d'), 'weekly', $prio);
    }
}

// 3. Static and High-Value Local Directories
addUrl($baseUrl . 'restaurantes.php', date('Y-m-d'), 'weekly', '0.85');
addUrl($baseUrl . 'alojamientos.php', date('Y-m-d'), 'weekly', '0.85');
addUrl($baseUrl . 'contacto.php', date('Y-m-d'), 'monthly', '0.6');

// 4. Categories
$stmtCats = $pdo->query("SELECT id FROM categories WHERE is_visible = 1");
while ($cat = $stmtCats->fetch()) {
    addUrl($baseUrl . 'category.php?id=' . $cat['id'], null, 'weekly', '0.8');
}

// 5. Pages (filtrando tanto categoría visible como página visible)
$stmtPages = $pdo->query("SELECT p.id, p.updated_at FROM pages p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.is_visible = 1 OR p.is_visible IS NULL) AND (c.is_visible = 1 OR p.category_id IS NULL)");
while ($page = $stmtPages->fetch()) {
    $lastmod = !empty($page['updated_at']) ? date('Y-m-d', strtotime($page['updated_at'])) : date('Y-m-d');
    addUrl($baseUrl . 'page.php?id=' . $page['id'], $lastmod, 'monthly', '0.7');
}

echo '</urlset>';
