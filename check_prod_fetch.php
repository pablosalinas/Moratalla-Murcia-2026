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

echo "=== 1. TEST DIRECT FETCH TO EL NOROESTE ===\n";
$finalUrl = '';
$html = aiHttpGet($testUrl, 12, $finalUrl);
echo "Final URL: $finalUrl\n";
echo "HTML Length: " . strlen($html) . "\n";
echo "HTML Prefix (300 chars): " . substr($html, 0, 300) . "\n\n";

echo "=== 2. TEST CONTENT EXTRACTION ===\n";
$info = extractArticleContentAndImages($testUrl, 'Una reserva de la Biosfera en Murcia: Moratalla y Caravaca', '', '', 'El Noroeste Digital');
echo "Extracted Title: " . $info['title'] . "\n";
echo "Extracted Body Length: " . strlen($info['body_text']) . "\n";
echo "Extracted Body Snippet: " . substr($info['body_text'], 0, 300) . "\n\n";

echo "=== 3. TEST DRAFT GENERATION ===\n";
$pdo = getDB();
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$draft = generateSupervisedDraftWithGemini($pdo, $info, $cats);
echo "Draft Title: " . $draft['title'] . "\n";
echo "Used Gemini: " . (!empty($draft['used_gemini']) ? 'YES' : 'NO') . "\n";
echo "Model: " . ($draft['model'] ?? 'none') . "\n";
echo "Sections count: " . count($draft['sections'] ?? []) . "\n";
echo "Content HTML length: " . strlen($draft['content_html'] ?? '') . "\n";
