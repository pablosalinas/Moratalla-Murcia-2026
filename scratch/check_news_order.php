<?php
require 'c:/xampp_2023/htdocs/Moratalla-Murcia-2026/config.php';
$pdo = getDB();
$stmt = $pdo->query('SELECT id, sort_order, title FROM news_events ORDER BY id DESC LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
