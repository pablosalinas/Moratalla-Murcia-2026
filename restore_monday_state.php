<?php
require_once 'config.php';
$pdo = getDB();

echo "Restaurando refinamientos del Lunes (Ana María Almagro y Bartolo)...\n";

// 1. ANA MARÍA ALMAGRO
$anaContent = '
<div class="legacy-page">
    <p><strong>Ana Mª Almagro</strong>, pintora artística, viene dedicando su creatividad a trabajos sobre seda y a pastel, fundamentalmente, creación que queda reflejada en cuadros de muy diversos y variados temas, algunos de ellos, de grandes dimensiones.</p>
    <p>Pero también esa creación e inquietud la transmite a pañuelos y corbatas, en los que el blanco inmaculado de la seda, ha dado paso a una explosión de color siempre unida íntimamente al diseño único y exclusivo de cada una de sus prendas.</p>
    
    <div class="gallery-info">
        <p><strong>Ana Mª Almagro</strong> es la autora de la pintura mural en el arco central del presbiterio de la Ermita de Santa Ana, así como de otros murales a tamaño natural para particulares. Igualmente, es autora del estandarte de Jesucristo Aparecido y de la Santa Faz que lleva La Verónica en la escenificación del Vía Crucis por las calles de Moratalla.</p>
    </div>
</div>';

$pdo->prepare("UPDATE pages SET title = 'Ana María Almagro', content = ?, slug = 'ana-maria-almagro' WHERE slug LIKE 'almagroana%' OR title LIKE '%Almagroana%'")
    ->execute([$anaContent]);

// Actualizar pies de foto de Ana María (según lo que vimos el lunes)
// Buscamos las imágenes vinculadas a esta página
$stmt = $pdo->query("SELECT id FROM pages WHERE slug = 'ana-maria-almagro'");
$anaPage = $stmt->fetch();
if ($anaPage) {
    $stmtImg = $pdo->prepare("SELECT id, image_path FROM page_images WHERE page_id = ?");
    $stmtImg->execute([$anaPage['id']]);
    $images = $stmtImg->fetchAll();
    
    foreach ($images as $img) {
        $caption = "";
        if (strpos($img['image_path'], 'Panuelos') !== false) $caption = "Pañuelos y corbatas en seda";
        if (strpos($img['image_path'], 'Desnudo') !== false) $caption = "Desnudo - Seda";
        if (strpos($img['image_path'], 'Mariposa') !== false) $caption = "Mariposa - Seda";
        if (strpos($img['image_path'], 'Suenos') !== false) $caption = "Sueños - Seda";
        
        if ($caption) {
            $pdo->prepare("UPDATE page_images SET caption = ? WHERE id = ?")->execute([$caption, $img['id']]);
        }
    }
}

// 2. BARTOLO (Bartolomé Medina)
$bartoloContent = '
<div class="legacy-page">
    <p><strong>Bartolomé Medina (Bartolo)</strong> es uno de los últimos artesanos del esparto en Moratalla. Su destreza con la pleita y el punto de media convierte la fibra natural en auténticas obras de arte utilitario.</p>
    <p>En su taller podemos encontrar desde las tradicionales "sopladeras" hasta cestos, alfombras y piezas decorativas que mantienen viva una tradición milenaria de nuestra tierra.</p>
</div>';

$pdo->prepare("UPDATE pages SET title = 'Bartolo - Artesanía del Esparto', content = ?, slug = 'bartolo' WHERE slug LIKE 'bartolome%' OR title LIKE '%Bartolome%'")
    ->execute([$bartoloContent]);

echo "✅ Refinamientos aplicados localmente.\n";
?>
