<?php
require '../includes/auth.php';
adminOnly();
require '../db.php';

// Verificamos que viene una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // ACCIÓN: Crear nueva editorial
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre']);
        
        if ($nombre) {
            // Se usa IGNORE por si se intenta duplicar una ya existente
            $stmt = $pdo->prepare('INSERT IGNORE INTO editoriales (nombre) VALUES (?)');
            $stmt->execute([$nombre]);
        }
    }

    // ACCIÓN: Eliminar editorial
    elseif ($accion === 'eliminar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        $stmt = $pdo->prepare('DELETE FROM editoriales WHERE id = ?');
        $stmt->execute([$id]);
    }

    // ACCIÓN: Editar editorial
    elseif ($accion === 'editar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $nombre = trim($_POST['nombre']);

        if ($nombre) {
            // Comprobamos que el nuevo nombre no lo tenga otra editorial
            $stmtCheck = $pdo->prepare('SELECT id FROM editoriales WHERE nombre = ? AND id != ?');
            $stmtCheck->execute([$nombre, $id]);
            
            if (!$stmtCheck->fetch()) {
                $stmt = $pdo->prepare('UPDATE editoriales SET nombre = ? WHERE id = ?');
                $stmt->execute([$nombre, $id]);
            }
        }
    }

}

// Redirigimos de vuelta a la página de editoriales
$id = (int)($_POST['id'] ?? 0);
$page = isset($_POST['redirect_page']) ? (int)$_POST['redirect_page'] : 1;
header("Location: ../editoriales.php?page=" . $page . ($id ? "#editorial-" . $id : ""));
exit;
