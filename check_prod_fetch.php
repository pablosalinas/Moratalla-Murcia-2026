<?php
// check_prod_fetch.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin/inc/ai_news_helper.php';

$secret = $_GET['secret'] ?? ($argv[1] ?? '');
if (php_sapi_name() !== 'cli' && $secret !== 'moratalla2026_diag') {
    die('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== TEST POLLINATIONS ON IONOS ===\n";
$ch = curl_init('https://text.pollinations.ai/openai');
$payload = json_encode([
    'model' => 'openai',
    'messages' => [
        ['role' => 'user', 'content' => 'Di hola en JSON: {"msg":"hola"}']
    ],
    'response_format' => ['type' => 'json_object']
]);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo "Pollinations HTTP: $code, Err: $err\n";
echo "Pollinations Resp: " . substr($resp, 0, 500) . "\n";
