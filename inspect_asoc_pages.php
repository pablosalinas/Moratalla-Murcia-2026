<?php
require_once 'config.php';
$pdo = getDB();

echo "=== PÁGINAS BAJO ASOCIACIONES (RECURSIVO) ===\n";

// Obtener todos los IDs descendientes de Asociaciones (ID: 1)
function getDescendants($pdo, $parentId) {
    $ids = [$parentId];
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
    $stmt->execute([$parentId]);
    while ($row = $stmt->fetch()) {
        $ids = array_merge($ids, getDescendants($pdo, $row['id']));
    }
    return $ids;
}

$allAsocIds = getDescendants($pdo, 1);
$inQuery = implode(',', $allAsocIds);

$stmt = $pdo->query("
    SELECT p.id, p.title, p.category_id, c.name as category_name, p.slug 
    FROM pages p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id IN ($inQuery)
    ORDER BY c.name, p.title
");

while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | CAT: {$row['category_name']} (ID: {$row['category_id']}) | TITULO: '{$row['title']}'\n";
}
?>
