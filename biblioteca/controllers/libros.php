<?php
require '../includes/auth.php';
// adminOnly(); // Quitamos el bloqueo global para permitir marcar como leído a usuarios 'consulta'
require '../db.php';

/**
 * Función que genera el texto para el tejuelo (Signatura Topográfica)
 * Línea 1: CDU
 * Línea 2: 3 primeras letras del AUTOR en MAYÚSCULAS
 * Línea 3: 3 primeras letras del TÍTULO en minúsculas (ignorando los artículos 'El ', 'La ', 'Los ', 'Las ', 'Un ', 'Una ')
 */
function generarSignatura($cdu, $autor, $titulo) {
    // 1. CDU
    $linea1 = trim($cdu);
    if (empty($linea1)) {
        $linea1 = '---';
    }

    // 2. Autor (3 letras en mayúsculas)
    $linea2 = mb_strtoupper(mb_substr(trim($autor), 0, 3, 'UTF-8'), 'UTF-8');

    // 3. Título (3 letras en minúsculas, filtrando artículos)
    $articulos = ['el ', 'la ', 'los ', 'las ', 'un ', 'una '];
    $titulo_limpio = mb_strtolower(trim($titulo), 'UTF-8');
    
    foreach ($articulos as $art) {
        if (strpos($titulo_limpio, $art) === 0) {
            $titulo_limpio = mb_substr($titulo_limpio, mb_strlen($art, 'UTF-8'), null, 'UTF-8');
            break; // Eliminar solo el primer artículo encontrado
        }
    }
    $linea3 = mb_substr(trim($titulo_limpio), 0, 3, 'UTF-8');

    // Unir todo con saltos de línea
    return $linea1 . "\n" . $linea2 . "\n" . $linea3;
}

// Función para obtener el ID de la editorial (y crearla si no existe)
function obtenerOcrearEditorial($pdo, $nombre) {
    $nombre = trim($nombre);
    if (empty($nombre)) return null;

    // Buscar si existe
    $stmt = $pdo->prepare('SELECT id FROM editoriales WHERE nombre = ?');
    $stmt->execute([$nombre]);
    $editorial = $stmt->fetch();

    if ($editorial) {
        return $editorial['id'];
    }

    // Si no existe, crearla
    $stmt = $pdo->prepare('INSERT INTO editoriales (nombre) VALUES (?)');
    $stmt->execute([$nombre]);
    return $pdo->lastInsertId();
}

// Función para obtener el ID de CDU basado en el código
function obtenerCduId($pdo, $codigo) {
    if (empty($codigo)) return null;
    $stmt = $pdo->prepare('SELECT id FROM cdu WHERE codigo = ?');
    $stmt->execute([$codigo]);
    $cdu = $stmt->fetch();
    return $cdu ? $cdu['id'] : null;
}

// Verificamos que viene una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    error_log("CONTROLADOR LIBROS: Accion = " . $_POST['accion'] . " ID = " . ($_POST['id'] ?? 'N/A'));
    file_put_contents(__DIR__ . '/debug_libros.log', "Accion: " . $_POST['accion'] . " para ID: " . ($_POST['id'] ?? 'N/A') . "\n", FILE_APPEND);
    $accion = $_POST['accion'];

    // ACCIÓN: Crear nuevo libro
    if ($accion === 'crear') {
        adminOnly(); // Solo admins pueden crear
        $titulo = trim($_POST['titulo']);
        $autor = trim($_POST['autor']);
        $anio = !empty($_POST['anio']) ? (int)$_POST['anio'] : null;
        $isbn = trim($_POST['isbn'] ?? '');
        $editorial_nombre = trim($_POST['editorial'] ?? '');
        $paginas = !empty($_POST['paginas']) ? (int)$_POST['paginas'] : null;
        $cdu = trim($_POST['cdu'] ?? '');

        if ($titulo && $autor) {
            $signatura = generarSignatura($cdu, $autor, $titulo);
            $editorial_id = obtenerOcrearEditorial($pdo, $editorial_nombre);
            $cdu_id = obtenerCduId($pdo, $cdu);

            $stmt = $pdo->prepare('INSERT INTO libros (titulo, autor, anio, isbn, editorial_id, paginas, cdu, signatura, cdu_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$titulo, $autor, $anio, $isbn, $editorial_id, $paginas, $cdu, $signatura, $cdu_id]);
        }
    }

    // ACCIÓN: Marcar como leído o no leído (PER USER)
    elseif ($accion === 'cambiar_estado' && isset($_POST['id'], $_POST['leido'])) {
        $libro_id = (int)$_POST['id'];
        $leido = (int)$_POST['leido']; // 1 = Marcar como leído, 0 = Marcar como no leído
        $usuario_id = $_SESSION['user_id'];
        
        if ($leido) {
            // Insertar si no existe (con IGNORE para evitar errores si ya estaba)
            $stmt = $pdo->prepare('INSERT IGNORE INTO libros_leidos (usuario_id, libro_id) VALUES (?, ?)');
            $stmt->execute([$usuario_id, $libro_id]);
        } else {
            // Eliminar registro
            $stmt = $pdo->prepare('DELETE FROM libros_leidos WHERE usuario_id = ? AND libro_id = ?');
            $stmt->execute([$usuario_id, $libro_id]);
        }
        
        // Devolvemos JSON si se trata de una consulta AJAX. Mantenemos el bloque normal de redirect en caso de fallback
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'leido' => $leido]);
            exit;
        }
    }

    // ACCIÓN: Eliminar libro
    elseif ($accion === 'eliminar' && isset($_POST['id'])) {
        adminOnly(); // Solo admins pueden borrar
        $id = (int)$_POST['id'];
        
        $stmt = $pdo->prepare('DELETE FROM libros WHERE id = ?');
        $stmt->execute([$id]);
    }

    // ACCIÓN: Editar libro
    elseif ($accion === 'editar' && isset($_POST['id'])) {
        adminOnly(); // Solo admins pueden editar
        $id = (int)$_POST['id'];
        $titulo = trim($_POST['titulo']);
        $autor = trim($_POST['autor']);
        $anio = !empty($_POST['anio']) ? (int)$_POST['anio'] : null;
        $isbn = trim($_POST['isbn'] ?? '');
        $editorial_nombre = trim($_POST['editorial'] ?? '');
        $paginas = !empty($_POST['paginas']) ? (int)$_POST['paginas'] : null;
        $cdu = trim($_POST['cdu'] ?? '');

        if ($titulo && $autor) {
            $signatura = generarSignatura($cdu, $autor, $titulo);
            $editorial_id = obtenerOcrearEditorial($pdo, $editorial_nombre);
            $cdu_id = obtenerCduId($pdo, $cdu);
            $stmt = $pdo->prepare('UPDATE libros SET titulo = ?, autor = ?, anio = ?, isbn = ?, editorial_id = ?, paginas = ?, cdu = ?, signatura = ?, cdu_id = ? WHERE id = ?');
            $stmt->execute([$titulo, $autor, $anio, $isbn, $editorial_id, $paginas, $cdu, $signatura, $cdu_id, $id]);
        }
        $page = isset($_POST['redirect_page']) ? (int)$_POST['redirect_page'] : 1;
        header('Location: ../index.php?page=' . $page . '#libro-' . $id);
        exit;
    }

}
header("Location: ../index.php");
exit;
