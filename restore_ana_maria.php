<?php
require_once 'config.php';
$pdo = getDB();

echo "Iniciando restauración manual de Ana María Almagro...\n";

// 1. Asegurar Categoría
$pdo->prepare("DELETE FROM categories WHERE slug = 'ana-maria-almagro'")->execute();
$pdo->prepare("INSERT INTO categories (parent_id, name, slug) VALUES (1, 'Ana María Almagro', 'ana-maria-almagro')")->execute();
$catId = $pdo->lastInsertId();

// 2. Contenido Biográfico (Rescatado del index.htm original)
$content = '
<div class="legacy-content">
    <p><strong>Ana Mª Almagro</strong>, pintora artística, viene dedicando su creatividad a trabajos sobre seda y a pastel, fundamentalmente, creación que queda reflejada en cuadros de muy diversos y variados temas, algunos de ellos, de grandes dimensiones.</p>
    <p>Pero también esa creación e inquietud la transmite a pañuelos y corbatas, en los que el blanco inmaculado de la seda, ha dado paso a una explosión de color siempre unida íntimamente al diseño único y exclusivo de cada una de sus prendas.</p>
    <p>Es la autora de la pintura mural en el arco central del presbiterio de la Ermita de Santa Ana, así como de otros murales a tamaño natural para particulares. Igualmente, es autora del estandarte de Jesucristo Aparecido y de la Santa Faz que lleva La Verónica en la escenificación del Vía Crucis por las calles de Moratalla.</p>
</div>';

$pdo->prepare("DELETE FROM pages WHERE slug = 'ana-maria-almagro'")->execute();
$pdo->prepare("INSERT INTO pages (category_id, title, slug, content) VALUES (?, 'Ana María Almagro', 'ana-maria-almagro', ?)")
    ->execute([$catId, $content]);
$pageId = $pdo->lastInsertId();

// 3. Vincular Imágenes con Pies de Foto Reales
$images = [
    ['path' => 'Panuelos y corbatas.jpg', 'caption' => 'Pañuelos y corbatas en seda'],
    ['path' => 'Desnudo-1.jpg', 'caption' => 'Desnudo - Seda'],
    ['path' => 'Mariposa.jpg', 'caption' => 'Mariposa - Seda'],
    ['path' => 'Suenos.jpg', 'caption' => 'Sueños - Seda'],
    ['path' => 'Santa Ana.jpg', 'caption' => 'Pintura mural - Ermita de Santa Ana'],
    ['path' => 'Estandarte.jpg', 'caption' => 'Estandarte procesional'],
    ['path' => 'Granadas-1.jpg', 'caption' => 'Granadas - Pintura al pastel']
];

$sourceBase = 'E:/00 PARTICULAR/moratalla-murcia/moratalla/artesania/pintura/almagroana/images/images_pq/';
$destBase = 'uploads/artesania/ana-maria/';
if (!is_dir($destBase)) mkdir($destBase, 0777, true);

foreach ($images as $img) {
    $src = $sourceBase . $img['path'];
    $dest = $destBase . str_replace(' ', '_', $img['path']);
    
    if (file_exists($src)) {
        copy($src, $dest);
        $pdo->prepare("INSERT INTO page_images (page_id, image_path, caption) VALUES (?, ?, ?)")
            ->execute([$pageId, $dest, $img['caption']]);
        echo " - Imagen '{$img['path']}' vinculada.\n";
    }
}

echo "✅ Ana María Almagro restaurada.\n";
?>
