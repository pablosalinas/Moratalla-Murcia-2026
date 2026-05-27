<?php
// update_db_external_links.php
require_once 'config.php';

$pdo = getDB();
$results = [];

try {
    $pdo->exec("ALTER TABLE `external_links` ADD COLUMN `category_id` INT DEFAULT NULL;");
    $results[] = "Columna category_id añadida correctamente.";
} catch (Exception $e) {
    $results[] = "Nota category_id: " . $e->getMessage();
}

try {
    $pdo->exec("ALTER TABLE `external_links` ADD COLUMN `show_in_category` TINYINT(1) DEFAULT 0;");
    $results[] = "Columna show_in_category añadida correctamente.";
} catch (Exception $e) {
    $results[] = "Nota show_in_category: " . $e->getMessage();
}

try {
    $pdo->exec("ALTER TABLE `pages` ADD COLUMN `sort_order` INT DEFAULT 0;");
    $results[] = "Columna sort_order añadida a páginas correctamente.";
} catch (Exception $e) {
    $results[] = "Nota sort_order en pages: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar DB</title>
</head>
<body style="font-family: sans-serif; padding: 2rem;">
    <h2>Actualización de Base de Datos</h2>
    <ul>
        <?php foreach ($results as $res): ?>
            <li><?php echo htmlspecialchars($res); ?></li>
        <?php endforeach; ?>
    </ul>
    <p><a href="admin/external_links.php">Volver a Enlaces Externos</a></p>
</body>
</html>
