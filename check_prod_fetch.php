<?php
// check_prod_fetch.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin/inc/ai_news_helper.php';

$secret = $_GET['secret'] ?? ($argv[1] ?? '');
if (php_sapi_name() !== 'cli' && $secret !== 'moratalla2026_diag') {
    die('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$testUrl = 'https://elnoroestedigital.com/una-reserva-de-la-biosfera-en-murcia-moratalla-y-caravaca/';
$info = extractArticleContentAndImages($testUrl, 'Una reserva de la Biosfera en Murcia: Moratalla y Caravaca', '', '', 'El Noroeste Digital');
$pdo = getDB();
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

echo "Testing generateSupervisedDraftWithGemini...\n";
$start = microtime(true);
$draft = generateSupervisedDraftWithGemini($pdo, $info, $cats);
$elapsed = round(microtime(true) - $start, 2);
echo "Elapsed: {$elapsed}s\n";
echo "Draft Title: " . $draft['title'] . "\n";
echo "Used Gemini: " . (!empty($draft['used_gemini']) ? 'YES' : 'NO') . "\n";
echo "Model: " . ($draft['model'] ?? 'none') . "\n";
echo "Sections count: " . count($draft['sections'] ?? []) . "\n";
echo "HTML Length: " . strlen($draft['content_html'] ?? '') . "\n";
echo "Lead: " . ($draft['lead'] ?? '') . "\n";
if (!empty($draft['gemini_note'])) echo "Note: " . $draft['gemini_note'] . "\n";
