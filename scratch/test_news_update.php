<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();

$title = 'Test News';
$content = 'Content';
$sort_order_news = 5;

try {
    $stmt = $pdo->prepare("INSERT INTO news_events (title, content, sort_order) VALUES (?, ?, ?)");
    $stmt->execute([$title, $content, $sort_order_news]);
    $id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT sort_order FROM news_events WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetchColumn();
    echo "Inserted news with order 5. In DB: $res\n";
    
    $sort_order_news = 10;
    $stmt = $pdo->prepare("UPDATE news_events SET sort_order = ? WHERE id = ?");
    $stmt->execute([$sort_order_news, $id]);
    
    $stmt = $pdo->prepare("SELECT sort_order FROM news_events WHERE id = ?");
    $stmt->execute([$id]);
    $res = $stmt->fetchColumn();
    echo "Updated news with order 10. In DB: $res\n";
    
    $pdo->prepare("DELETE FROM news_events WHERE id = ?")->execute([$id]);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
