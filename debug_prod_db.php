<?php
require_once 'config.php';
$pdo = getDB();

echo "<h1>Mapa de Categorías en Producción</h1>";
$stmt = $pdo->query("SELECT id, parent_id, name, slug FROM categories ORDER BY parent_id ASC, name ASC");
$cats = $stmt->fetchAll();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Parent ID</th><th>Nombre</th><th>Slug</th></tr>";
foreach ($cats as $c) {
    echo "<tr>";
    echo "<td>{$c['id']}</td>";
    echo "<td>" . ($c['parent_id'] ?: 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($c['name']) . "</td>";
    echo "<td>" . htmlspecialchars($c['slug']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h1>Páginas en Producción</h1>";
$stmtP = $pdo->query("SELECT id, category_id, title FROM pages");
$pages = $stmtP->fetchAll();
echo "<ul>";
foreach ($pages as $p) {
    echo "<li>ID: {$p['id']} - Cat: {$p['category_id']} - Tit: " . htmlspecialchars($p['title']) . "</li>";
}
echo "</ul>";
