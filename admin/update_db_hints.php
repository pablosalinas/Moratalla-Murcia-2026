<?php
require __DIR__ . '/../config.php';
$pdo = getDB();

try {
    // Add columns if they don't exist
    $pdo->exec("ALTER TABLE categories ADD COLUMN hint_text TEXT NULL DEFAULT NULL");
    echo "Added hint_text column.\n";
} catch (Exception $e) {
    echo "Column hint_text might already exist: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN show_hint TINYINT(1) NOT NULL DEFAULT 0");
    echo "Added show_hint column.\n";
} catch (Exception $e) {
    echo "Column show_hint might already exist: " . $e->getMessage() . "\n";
}

echo "Database updated successfully.\n";
