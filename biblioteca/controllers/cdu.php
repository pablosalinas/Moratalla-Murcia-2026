<?php
require '../includes/auth.php';
adminOnly();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    // ACCIÓN: Crear nueva categoría
    if ($accion === 'crear') {
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);
        
        if ($codigo && $nombre) {
            $stmt = $pdo->prepare('INSERT IGNORE INTO cdu (codigo, nombre) VALUES (?, ?)');
            $stmt->execute([$codigo, $nombre]);
        }
    }

    // ACCIÓN: Eliminar categoría
    elseif ($accion === 'eliminar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // Antes de eliminar, verificamos si hay libros asociados
        $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM libros WHERE cdu_id = ?');
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() == 0) {
            $stmt = $pdo->prepare('DELETE FROM cdu WHERE id = ?');
            $stmt->execute([$id]);
        }
    }

    // ACCIÓN: Editar categoría
    elseif ($accion === 'editar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);

        if ($codigo && $nombre) {
            $stmt = $pdo->prepare('UPDATE cdu SET codigo = ?, nombre = ? WHERE id = ?');
            $stmt->execute([$codigo, $nombre, $id]);
        }
    }

}

$id = (int)($_POST['id'] ?? 0);
$page = isset($_POST['redirect_page']) ? (int)$_POST['redirect_page'] : 1;
header("Location: ../cdu.php?page=" . $page . ($id ? "#cdu-" . $id : ""));
exit;
