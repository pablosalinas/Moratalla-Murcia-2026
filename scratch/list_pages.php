<?php
require_once 'config.php';
$pdo = getDB();

$stmt = $pdo->query("SELECT id, title FROM pages");
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pages as $p) {
    echo $p['id'] . " - " . $p['title'] . "\n";
}
?>
