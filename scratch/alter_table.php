<?php
require_once 'config.php';
$pdo = getDB();
try {
    $pdo->exec("ALTER TABLE `external_links` ADD COLUMN `category_id` INT DEFAULT NULL;");
    $pdo->exec("ALTER TABLE `external_links` ADD COLUMN `show_in_category` TINYINT(1) DEFAULT 0;");
    echo "Success";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
