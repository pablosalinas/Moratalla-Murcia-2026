<?php
require_once 'config.php';
$pdo = getDB();

echo "Iniciando reparación de nombres de Artesanía...\n";

// 1. Reparar Ana María Almagro
$pdo->prepare("UPDATE categories SET name = 'Ana María Almagro', slug = 'ana-maria-almagro' WHERE name = 'Almagroana' OR slug LIKE 'almagroana%'")
    ->execute();
echo "✅ Categoría 'Ana María Almagro' corregida.\n";

// 2. Reparar Bartolo
$pdo->prepare("UPDATE categories SET name = 'Bartolo', slug = 'bartolo' WHERE name = 'Bartolome' OR slug LIKE 'bartolome%'")
    ->execute();
echo "✅ Categoría 'Bartolo' corregida.\n";

// 3. Reparar Páginas vinculadas si es necesario
$pdo->prepare("UPDATE pages SET title = 'Ana María Almagro', slug = 'ana-maria-almagro' WHERE title LIKE '%Almagroana%'")
    ->execute();
$pdo->prepare("UPDATE pages SET title = 'Bartolo', slug = 'bartolo' WHERE title LIKE '%Bartolome%'")
    ->execute();

echo "Reparación completada. Por favor, verifica la web ahora.\n";
?>
