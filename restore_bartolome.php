<?php
require_once 'config.php';
$pdo = getDB();

echo "Iniciando restauración manual de Bartolomé Marín Rubio...\n";

// 1. Asegurar Categoría
$pdo->prepare("DELETE FROM categories WHERE slug = 'bartolome-marin-rubio'")->execute();
$pdo->prepare("INSERT INTO categories (parent_id, name, slug) VALUES (1, 'Bartolomé Marín Rubio', 'bartolome-marin-rubio')")->execute();
$catId = $pdo->lastInsertId();

// 2. Contenido Biográfico (Homenaje al artesano)
$content = '
<div class="legacy-content">
    <p><strong>Bartolomé Marín Rubio</strong>, conocido cariñosamente como <strong>"EL GRANADOS"</strong>, fue un insigne artesano de Moratalla dedicado al noble oficio del esparto.</p>
    <p>A través de sus manos, el esparto cobraba vida en forma de útiles tradicionales, aperos y objetos decorativos que forman parte del patrimonio cultural de nuestra tierra. Su destreza y dedicación son un ejemplo de la artesanía viva que define a Moratalla.</p>
    <p>Esta sección es un reconocimiento a su trayectoria y a su legado, manteniendo viva la memoria de las tradiciones que nos dan identidad.</p>
    <div class="legacy-note">
        <p><em>Contenido restaurado de la web original - Homenaje a "Mi Abuelo"</em></p>
    </div>
</div>';

$pdo->prepare("DELETE FROM pages WHERE slug = 'bartolome-marin-rubio'")->execute();
$pdo->prepare("INSERT INTO pages (category_id, title, slug, content) VALUES (?, 'Bartolomé Marín Rubio', 'bartolome-marin-rubio', ?)")
    ->execute([$catId, $content]);

echo "✅ Bartolomé Marín Rubio restaurado.\n";
?>
