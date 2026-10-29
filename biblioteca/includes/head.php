<?php
/**
 * includes/head.php
 * Fragmento <head> HTML común.
 * Variable esperada del padre: $pageTitle (string)
 */
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Biblioteca Dalias y Tortas') ?></title>
<link rel="shortcut icon" href="assets/img/favicon.ico?v=5" type="image/x-icon">
<link rel="icon" href="assets/img/favicon.ico?v=5" type="image/x-icon">
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- Estilos propios -->
<link rel="stylesheet" href="assets/css/app.css">
