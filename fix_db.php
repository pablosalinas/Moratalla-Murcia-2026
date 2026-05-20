<?php
require_once 'config.php';
$pdo = getDB();

echo "<h1>Actualización de Base de Datos de Producción</h1>";

try {
    $pdo->exec("ALTER TABLE news_images ADD COLUMN caption VARCHAR(255) NULL AFTER image_path");
    echo "<p style='color:green;'>Éxito: Columna 'caption' añadida a 'news_images'.</p>";
} catch(Exception $e) {
    echo "<p style='color:orange;'>Nota (news_images): " . $e->getMessage() . "</p>";
}

try {
    $pdo->exec("ALTER TABLE news_events ADD COLUMN image_caption VARCHAR(255) NULL AFTER image_path");
    echo "<p style='color:green;'>Éxito: Columna 'image_caption' añadida a 'news_events'.</p>";
} catch(Exception $e) {
    echo "<p style='color:orange;'>Nota (news_events): " . $e->getMessage() . "</p>";
}

echo "<h2>¡Base de datos actualizada!</h2>";
echo "<p>Por favor, comprueba si la web (el banner y las noticias) ya carga correctamente.</p>";
echo "<p><strong>IMPORTANTE:</strong> Por seguridad, elimina este archivo (fix_db.php) de tu servidor de producción cuando hayas terminado.</p>";
?>
