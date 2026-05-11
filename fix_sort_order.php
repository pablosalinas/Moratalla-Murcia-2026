<?php
require_once 'config.php';
$pdo = getDB();

echo "Configurando orden de categorías principales...\n";

$orders = [
    'Artesania' => 1,
    'Asociaciones' => 2,
    'Cultura' => 3,
    'Fiestas' => 4,
    'Noticias' => 5,
    'Lugares' => 6,
    'Gastronomia' => 7,
    'Servicios' => 8,
    'Publicidad' => 9,
    'Corporacion' => 10
];

foreach ($orders as $name => $order) {
    $stmt = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE name = ? AND parent_id IS NULL");
    $stmt->execute([$order, $name]);
    if ($stmt->rowCount() > 0) {
        echo "✅ '$name' actualizado a orden $order.\n";
    } else {
        echo "⚠️ '$name' no encontrado como categoría principal.\n";
    }
}

// Poner el resto en un orden alto para que no estorben al principio
$pdo->query("UPDATE categories SET sort_order = 99 WHERE parent_id IS NULL AND sort_order = 0");
echo "✅ Resto de categorías movidas al final (Orden 99).\n";

echo "Proceso completado.\n";
?>
