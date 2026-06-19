<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();
echo "=== COLUMNAS PROD ===\n";
try {
    $cols = $pdo->query("DESCRIBE news_events")->fetchAll();
    foreach ($cols as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
