<?php
// admin/inc/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Asegura de forma estrictamente aditiva que la tabla `users` disponga
 * de las columnas `acceso_sistema` ('ambos', 'web', 'combustible') y `es_superusuario` (0/1).
 */
function ensureUserAccessColumns(PDO $pdo) {
    if (!empty($_SESSION['user_access_cols_checked'])) {
        return;
    }
    try {
        $colAcceso = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'acceso_sistema'")->fetch();
        if (!$colAcceso) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `acceso_sistema` VARCHAR(30) NOT NULL DEFAULT 'ambos'");
        }
        $colSuper = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'es_superusuario'")->fetch();
        if (!$colSuper) {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `es_superusuario` TINYINT(1) NOT NULL DEFAULT 0");
            $pdo->exec("UPDATE `users` SET `es_superusuario` = 1 WHERE `role` = 'admin'");
        }
        $_SESSION['user_access_cols_checked'] = true;
    } catch (Exception $e) {
        // Ignorar silenciosamente si hubiera algún bloqueo puntual
    }
}

/**
 * Devuelve la información actualizada del usuario en sesión.
 */
function getCurrentAdminUser() {
    static $cachedUser = null;
    if ($cachedUser !== null) {
        return $cachedUser;
    }
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    require_once __DIR__ . '/../../config.php';
    $pdo = getDB();
    ensureUserAccessColumns($pdo);

    $stmt = $pdo->prepare("SELECT id, username, role, acceso_sistema, es_superusuario FROM `users` WHERE id = ? LIMIT 1");
    $stmt->execute(array($_SESSION['admin_id']));
    $u = $stmt->fetch();
    if ($u) {
        $_SESSION['admin_role'] = $u['role'];
        $_SESSION['acceso_sistema'] = !empty($u['acceso_sistema']) ? $u['acceso_sistema'] : 'ambos';
        $_SESSION['es_superusuario'] = (int)$u['es_superusuario'];
        $cachedUser = $u;
    }
    return $cachedUser;
}

/**
 * Indica si el usuario actual tiene la marca de Superusuario.
 */
function isSuperUser() {
    $u = getCurrentAdminUser();
    if (!$u) return false;
    return !empty($u['es_superusuario']);
}

function checkAuth() {
    // Evitar que el navegador guarde en caché las páginas de admin
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }

    $u = getCurrentAdminUser();
    if ($u && empty($u['es_superusuario'])) {
        $acceso = !empty($u['acceso_sistema']) ? $u['acceso_sistema'] : 'ambos';
        $currentScript = basename($_SERVER['SCRIPT_NAME']);

        // Si el usuario tiene asignado SOLO 'combustible', redirigir cualquier otra página del admin hacia ../combustible/index.php
        if ($acceso === 'combustible' && $currentScript !== 'combustible.php' && $currentScript !== 'logout.php') {
            header("Location: ../combustible/index.php");
            exit;
        }

        // Si el usuario tiene asignado SOLO 'web', impedir acceso a combustible.php
        if ($acceso === 'web' && $currentScript === 'combustible.php') {
            header("Location: index.php?msg=sin_permiso_combustible");
            exit;
        }
    }
}
?>
