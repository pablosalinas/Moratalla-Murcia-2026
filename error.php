<?php
// error.php
require_once __DIR__ . '/config.php';

// Obtener el código de error
$code = isset($_GET['code']) ? (int)$_GET['code'] : 404;
if (isset($_SERVER['REDIRECT_STATUS']) && $_SERVER['REDIRECT_STATUS'] >= 400) {
    $code = (int)$_SERVER['REDIRECT_STATUS'];
}

$errorMessages = [
    400 => ['title' => 'Petición Incorrecta', 'desc' => 'La solicitud no pudo ser entendida por el servidor.'],
    401 => ['title' => 'No Autorizado', 'desc' => 'Se requiere autenticación para acceder a este recurso.'],
    403 => ['title' => 'Acceso Denegado', 'desc' => 'No tienes permisos para ver esta página.'],
    404 => ['title' => 'Página no encontrada', 'desc' => 'Lo sentimos, no hemos podido encontrar la página que buscas. Es posible que el enlace esté roto o la página haya sido eliminada.'],
    500 => ['title' => 'Error Interno del Servidor', 'desc' => 'Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo más tarde.'],
    503 => ['title' => 'Servicio No Disponible', 'desc' => 'El servidor no está disponible en este momento por mantenimiento.']
];

if (!array_key_exists($code, $errorMessages)) {
    $code = 404;
}

http_response_code($code);

$pageTitle = "Error " . $code . " - " . $errorMessages[$code]['title'];

// Incluimos el header nativo de la web para mantener el menú y la navegación
require_once __DIR__ . '/inc/header.php';
?>

<div class="container main-content" style="margin-top: 3rem; margin-bottom: 5rem; min-height: 50vh; display: flex; align-items: center; justify-content: center;">
    <div style="text-align: center; background: white; padding: 4rem 2rem; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.08); max-width: 700px; width: 100%; position: relative; overflow: hidden; border-top: 5px solid var(--accent);">
        
        <!-- Elemento decorativo de fondo -->
        <div style="position: absolute; top: -50px; left: 50%; transform: translateX(-50%); opacity: 0.03; font-size: 300px; font-weight: 900; line-height: 1; pointer-events: none; color: var(--primary);">
            <?= $code ?>
        </div>
        
        <img src="<?php echo rtrim(BASE_URL, '/'); ?>/uploads/theme/logo.jpg" alt="Logo Moratalla" style="max-height: 100px; margin-bottom: 1.5rem; border: 3px solid var(--primary); border-radius: 12px; padding: 5px; position: relative; z-index: 1;">
        
        <h1 style="font-size: 6rem; font-weight: 800; color: var(--primary); margin: 0; line-height: 1; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); position: relative; z-index: 1;">
            <?= $code ?>
        </h1>
        
        <h2 style="font-size: 2rem; color: var(--text); margin: 1rem 0; font-weight: 700; position: relative; z-index: 1;">
            <?= htmlspecialchars($errorMessages[$code]['title']) ?>
        </h2>
        
        <p style="font-size: 1.1rem; color: var(--text-light); max-width: 500px; margin: 0 auto 2.5rem; line-height: 1.6; position: relative; z-index: 1;">
            <?= htmlspecialchars($errorMessages[$code]['desc']) ?>
        </p>
        
        <div style="display: flex; gap: 1rem; justify-content: center; position: relative; z-index: 1; flex-wrap: wrap;">
            <a href="<?php echo rtrim(BASE_URL, '/'); ?>/index.php" class="btn" style="background: var(--primary); color: white; padding: 0.8rem 2rem; text-decoration: none; border-radius: 30px; font-weight: 600; transition: transform 0.2s, box-shadow 0.2s; box-shadow: 0 4px 15px rgba(27, 67, 50, 0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(27, 67, 50, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(27, 67, 50, 0.3)'">
                <i class="fas fa-home" style="margin-right: 8px;"></i> Volver al Inicio
            </a>
            <a href="<?php echo rtrim(BASE_URL, '/'); ?>/contacto.php" class="btn" style="background: white; color: var(--primary); padding: 0.8rem 2rem; text-decoration: none; border-radius: 30px; font-weight: 600; border: 2px solid var(--primary); transition: background 0.2s, color 0.2s;" onmouseover="this.style.background='var(--primary)'; this.style.color='white'" onmouseout="this.style.background='white'; this.style.color='var(--primary)'">
                <i class="fas fa-envelope" style="margin-right: 8px;"></i> Contactar
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/inc/footer.php'; ?>
