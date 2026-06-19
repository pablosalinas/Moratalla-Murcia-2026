<?php
require 'c:/xampp_2023/htdocs/Moratalla-Murcia-2026/config.php';
$pdo = getDB();
$stmt = $pdo->query('SELECT id, news_id, image_path FROM news_images LIMIT 5');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
