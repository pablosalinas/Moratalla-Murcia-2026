<?php
// check_prod_fetch.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin/inc/ai_news_helper.php';

$secret = $_GET['secret'] ?? ($argv[1] ?? '');
if (php_sapi_name() !== 'cli' && $secret !== 'moratalla2026_diag') {
    die('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$gnUrl = 'https://news.google.com/rss/articles/CBMikgFBVV95cUxQUVAzWjVTTGhvUk5zcUlEX1dCMGRsUTNtR3lsVDVaeWVaNHdQQmYzVUVHMmdxOUJ2QlJ1dllyWVJfQ2VUWVdMaGl5UkZVTGJmVzg1a2JXeGxadDB2dXBtYk1tczZNRFBNejMzZV9kcDVCVnE4NkdFTHBJdFFaYVlGWkdEdHhvNEUtekhMZk5NMXBFQQ?oc=5';
$title = 'Una reserva de la Biosfera en Murcia: Moratalla y Caravaca - El Noroeste Digital';

echo "1. Testing resolveRealArticleUrl on Ionos...\n";
$start = microtime(true);
$resolved = resolveRealArticleUrl($gnUrl, $title, 'El Noroeste Digital');
$elapsed = round(microtime(true) - $start, 2);
echo "Resolved URL in {$elapsed}s: $resolved\n";

echo "2. Testing extractArticleContentAndImages on Ionos...\n";
$info = extractArticleContentAndImages($gnUrl, $title, '', '', 'El Noroeste Digital');
echo "Extracted Title: " . $info['title'] . "\n";
echo "Extracted Body Length: " . strlen($info['body_text']) . "\n";
echo "Extracted Body Snippet: " . substr($info['body_text'], 0, 150) . "\n";

echo "3. Testing generateSupervisedDraftWithGemini on Ionos...\n";
$pdo = getDB();
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$draft = generateSupervisedDraftWithGemini($pdo, $info, $cats);
echo "Draft Title: " . $draft['title'] . "\n";
echo "Lead: " . $draft['lead'] . "\n";
echo "HTML Length: " . strlen($draft['content_html']) . "\n";
echo "Sections count: " . count($draft['sections'] ?? []) . "\n";
