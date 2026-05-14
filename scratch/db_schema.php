<?php
require 'config.php';
$pdo = getDB();
$stmt = $pdo->query('SHOW CREATE TABLE page_images');
print_r($stmt->fetch(PDO::FETCH_ASSOC));
