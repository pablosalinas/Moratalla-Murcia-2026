<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();

echo "=== DIAGNÓSTICO NOTICIAS ===\n";

try {
    $stmt = $pdo->query("SELECT ne.*, c.name as category_name FROM news_events ne LEFT JOIN categories c ON ne.category_id = c.id ORDER BY ne.sort_order ASC, ne.id DESC");
    $rows = $stmt->fetchAll();
    echo "Query exitosa. Total de registros: " . count($rows) . "\n";
    if (count($rows) > 0) {
        echo "Primer registro:\n";
        print_r(array_slice($rows[0], 0, 5));
    }
} catch (PDOException $e) {
    echo "ERROR al ejecutar query:\n";
    echo $e->getMessage() . "\n";
}

echo "\n=== COLUMNAS EN news_events ===\n";
try {
    $cols = $pdo->query("DESCRIBE news_events")->fetchAll();
    foreach ($cols as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
} catch (PDOException $e) {
    echo "ERROR al describir tabla:\n";
    echo $e->getMessage() . "\n";
}
