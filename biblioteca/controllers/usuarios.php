<?php
require '../includes/auth.php';
adminOnly();
require '../db.php';

// Verificamos que viene una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    error_log("CONTROLADOR USUARIOS: Accion = " . $_POST['accion'] . " ID = " . ($_POST['id'] ?? 'N/A'));
    $accion = $_POST['accion'];
    file_put_contents(__DIR__ . '/debug.log', "Accion: $accion para ID: " . ($_POST['id'] ?? 'N/A') . "\n", FILE_APPEND);

    // ACCIÓN: Crear nuevo usuario
    if ($accion === 'crear') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $rol = $_POST['rol'] ?? 'consulta';
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
        
        if ($username && strlen($password) >= 4) {
            // Verificar si el username ya existe
            $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE username = ?');
            $stmtCheck->execute([$username]);
            
            if (!$stmtCheck->fetch()) {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO usuarios (username, password, rol, activo) VALUES (?, ?, ?, ?)');
                    $stmt->execute([$username, $hash, $rol, $activo]);
                } catch (\PDOException $e) {
                    // Log del error para depuración
                    error_log("Error al crear usuario: " . $e->getMessage());
                }
            }
        }
    }

    // ACCIÓN: Autorizar (habilitar) usuario
    elseif ($accion === 'autorizar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('UPDATE usuarios SET activo = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    // ACCIÓN: Deshabilitar usuario
    elseif ($accion === 'deshabilitar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($id !== (int)$_SESSION['user_id']) {
            $stmt = $pdo->prepare('UPDATE usuarios SET activo = 0 WHERE id = ?');
            $stmt->execute([$id]);
        }
    }

    // ACCIÓN: Eliminar usuario
    elseif ($accion === 'eliminar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // Evitarnos borrar a nosotros mismos (seguridad básica)
        if ($id !== (int)$_SESSION['user_id']) {
            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
        }
    }

    // ACCIÓN: Editar usuario
    elseif ($accion === 'editar' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $rol = $_POST['rol'] ?? 'consulta';
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
        if ($id === (int)$_SESSION['user_id']) {
            $activo = 1; // Evitar deshabilitarse a sí mismo
        }

        if ($username) {
            // Verificar si el nuevo username ya existe en OTRO usuario
            $stmtCheck = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? AND id != ?');
            $stmtCheck->execute([$username, $id]);
            
            if (!$stmtCheck->fetch()) {
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE usuarios SET username = ?, password = ?, rol = ?, activo = ? WHERE id = ?');
                    $stmt->execute([$username, $hash, $rol, $activo, $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE usuarios SET username = ?, rol = ?, activo = ? WHERE id = ?');
                    $stmt->execute([$username, $rol, $activo, $id]);
                }
            }
        }
    }

}

// Redirigimos de vuelta a la página correspondiente
$id = (int)($_POST['id'] ?? 0);
$page = isset($_POST['redirect_page']) ? (int)$_POST['redirect_page'] : 1;
header("Location: ../usuarios.php?page=" . $page . ($id ? "#usuario-" . $id : ""));
exit;
