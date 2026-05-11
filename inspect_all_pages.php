<?php
require_once 'config.php';
$pdo = getDB();

$count = $pdo->query("SELECT count(*) FROM pages")->fetchColumn();
echo "Total páginas: $count\n";

$stmt = $pdo->query("SELECT id, title, category_id, slug FROM pages LIMIT 20");
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | CAT: {$row['category_id']} | TITULO: '{$row['title']}'\n";
}
?>
