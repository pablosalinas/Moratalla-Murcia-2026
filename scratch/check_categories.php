<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();
$cats = $pdo->query("SELECT * FROM categories ORDER BY parent_id, id")->fetchAll();
foreach ($cats as $cat) {
    echo "ID: {$cat['id']} | Name: {$cat['name']} | Slug: {$cat['slug']} | Parent ID: {$cat['parent_id']}\n";
}
