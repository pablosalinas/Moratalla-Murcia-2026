<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();

$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "TABLAS DE LA BASE DE DATOS:\n";
foreach ($tables as $t) {
    echo "- $t\n";
    $stmtCol = $pdo->query("DESCRIBE `$t`");
    $cols = $stmtCol->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "   * {$c['Field']} ({$c['Type']})\n";
    }
}
