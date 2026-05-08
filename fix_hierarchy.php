<?php
require_once 'config.php';
$pdo = getDB();

echo "Corrigiendo jerarquía de Artesanía...\n";

// 1. Obtener IDs reales
$artesaniaId = 117;
$pinturaId = 120;
$espartoId = 118;

// 2. Mover Ana María Almagro a Pintura (ID 120)
$pdo->prepare("UPDATE categories SET parent_id = ? WHERE name = 'Ana María Almagro'")->execute([$pinturaId]);
echo "✅ Ana María Almagro movida a Pintura.\n";

// 3. Mover Bartolomé Marín Rubio a Esparto (ID 118)
$pdo->prepare("UPDATE categories SET parent_id = ? WHERE name = 'Bartolomé Marín Rubio'")->execute([$espartoId]);
echo "✅ Bartolomé Marín Rubio movido a Esparto.\n";

// 4. Eliminar categoría 'Bartolo' antigua (ID 119) que está vacía
$pdo->exec("DELETE FROM categories WHERE id = 119 OR name = 'Bartolo'");
echo "✅ Categoría 'Bartolo' antigua eliminada.\n";

echo "--- VERIFICACIÓN ---\n";
$stmt = $pdo->query("SELECT c1.name as sub, c2.name as parent FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id WHERE c1.name LIKE '%Ana%' OR c1.name LIKE '%Bartolomé%'");
while ($row = $stmt->fetch()) {
    echo "Subcategoría: {$row['sub']} -> Cuelga de: {$row['parent']}\n";
}
?>
