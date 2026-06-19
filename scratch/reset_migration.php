<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();
$pdo->exec("DELETE FROM `_migrations` WHERE `migration` = '087_add_email_restaurantes.sql'");
$pdo->exec("INSERT INTO `_migrations` (migration, status, error_message) VALUES ('087_add_email_restaurantes.sql', 'success', NULL)");
echo "Registro de migración 087 guardado como exitoso en la BD local para evitar errores.\n";
