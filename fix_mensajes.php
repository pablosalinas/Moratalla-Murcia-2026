<?php
require_once 'config.php';
$pdo = getDB();

try {
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `contact_messages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(150) NOT NULL,
      `phone` varchar(50) DEFAULT NULL,
      `email` varchar(150) NOT NULL,
      `message` text NOT NULL,
      `is_read` tinyint(1) NOT NULL DEFAULT '0',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "<h1>¡Éxito!</h1>";
    echo "<p style='color: green;'>La tabla 'contact_messages' se ha creado correctamente en la base de datos de producción.</p>";
    echo "<p>Ya puedes recibir mensajes del formulario de contacto directamente en tu base de datos.</p>";
    echo "<p><strong>Recuerda borrar este archivo (fix_mensajes.php) de tu servidor por seguridad.</strong></p>";
} catch(Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
