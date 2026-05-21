<?php
// sitemap.php
require_once 'config.php';
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

// 2. Categories
$stmtCats = $pdo->query("SELECT id FROM categories WHERE is_visible = 1");
while ($cat = $stmtCats->fetch()) {
    addUrl($baseUrl . 'category.php?id=' . $cat['id'], null, 'weekly', '0.8');
}

// 3. Pages
// Join with categories to ensure the category is visible, unless it's null (if applicable)
$stmtPages = $pdo->query("SELECT p.id, p.updated_at FROM pages p LEFT JOIN categories c ON p.category_id = c.id WHERE c.is_visible = 1 OR p.category_id IS NULL");
while ($page = $stmtPages->fetch()) {
    $lastmod = !empty($page['updated_at']) ? date('Y-m-d', strtotime($page['updated_at'])) : date('Y-m-d');
    addUrl($baseUrl . 'page.php?id=' . $page['id'], $lastmod, 'monthly', '0.7');
}

// 4. News / Events
$stmtNews = $pdo->query("SELECT id, created_at FROM news ORDER BY created_at DESC");
while ($news = $stmtNews->fetch()) {
    $lastmod = !empty($news['created_at']) ? date('Y-m-d', strtotime($news['created_at'])) : date('Y-m-d');
    addUrl($baseUrl . 'news_detail.php?id=' . $news['id'], $lastmod, 'monthly', '0.6');
}

// 5. Static Pages
addUrl($baseUrl . 'contacto.php', null, 'monthly', '0.5');

echo '</urlset>';
