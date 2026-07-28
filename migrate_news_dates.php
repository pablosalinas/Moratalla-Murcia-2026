<?php
require_once 'c:/xampp_2023/htdocs/Moratalla-Murcia-2026/config.php';
$pdo = getDB();
try {
    $pdo->exec("ALTER TABLE news_events ADD COLUMN start_date DATETIME NULL AFTER event_date");
    echo "start_date added.\n";
} catch (Exception $e) {
    echo "Error adding start_date: " . $e->getMessage() . "\n";
}
try {
    $pdo->exec("ALTER TABLE news_events ADD COLUMN end_date DATETIME NULL AFTER start_date");
    echo "end_date added.\n";
} catch (Exception $e) {
    echo "Error adding end_date: " . $e->getMessage() . "\n";
}
