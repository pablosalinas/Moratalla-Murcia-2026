<?php
/**
 * admin/combustible.php
 * Redirección directa a la versión actual de Control de Combustible (/combustible/index.php),
 * compartiendo la sesión PHP autenticada de moratalla-murcia.com.
 */

require_once 'inc/auth.php';
checkAuth();

header("Location: ../combustible/index.php");
exit;
