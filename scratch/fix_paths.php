<?php
require __DIR__ . '/../config.php';
$pdo = getDB();
$id = 410;
$stmt = $pdo->prepare("SELECT content FROM pages WHERE id = ?");
$stmt->execute([$id]);
$content = $stmt->fetchColumn();

// Replace /assets/images/p126/ with assets/images/p126/
$content = str_replace('/assets/images/p126/', 'assets/images/p126/', $content);

$stmtUpdate = $pdo->prepare("UPDATE pages SET content = ? WHERE id = ?");
$stmtUpdate->execute([$content, $id]);
echo "Rutas corregidas (quitada la barra inicial).\n";
