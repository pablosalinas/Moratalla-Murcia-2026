<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require '../db.php';

$tipo = $_GET['tipo'] ?? '';
$q = trim($_GET['q'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 40;
if ($page < 1)
    $page = 1;
if ($perPage < 1)
    $perPage = 40;
$offset = ($page - 1) * $perPage;

$estado = $_GET['estado'] ?? 'todos'; // todos, leidos, no_leidos
$likeQuery = '%' . $q . '%';
$resultados = [];
$total = 0;

if ($tipo === 'libros') {
    // 1. Contar total
    $sqlCount = 'SELECT COUNT(*) FROM libros l LEFT JOIN editoriales e ON l.editorial_id = e.id';
    
    $whereConditions = [];
    $params = [];

    if ($q !== '') {
        if (preg_match('/^BIBLIO-(\d+)$/i', $q, $matches)) {
            $whereConditions[] = 'l.id = ?';
            $params[] = $matches[1];
        } else {
            $whereConditions[] = '(l.titulo LIKE ? OR l.autor LIKE ? OR e.nombre LIKE ? OR l.isbn LIKE ?)';
            array_push($params, $likeQuery, $likeQuery, $likeQuery, $likeQuery);
        }
    }
    
    if ($estado === 'leidos') {
        $whereConditions[] = 'EXISTS (SELECT 1 FROM libros_leidos ll WHERE ll.libro_id = l.id AND ll.usuario_id = ?)';
        $params[] = $_SESSION['user_id'];
    } elseif ($estado === 'no_leidos') {
        $whereConditions[] = 'NOT EXISTS (SELECT 1 FROM libros_leidos ll WHERE ll.libro_id = l.id AND ll.usuario_id = ?)';
        $params[] = $_SESSION['user_id'];
    }

    if (!empty($whereConditions)) {
        $sqlCount .= ' WHERE ' . implode(' AND ', $whereConditions);
    }

    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();

    // 2. Obtener datos con paginación y JOIN CDU
    $sql = '
        SELECT l.*, e.nombre as editorial_nombre, c.nombre as cdu_nombre,
               (SELECT COUNT(*) FROM libros_leidos ll WHERE ll.libro_id = l.id AND ll.usuario_id = ?) as leido_user
        FROM libros l 
        LEFT JOIN editoriales e ON l.editorial_id = e.id 
        LEFT JOIN cdu c ON l.cdu_id = c.id
    ';
    
    $execParams = [$_SESSION['user_id']];
    if (!empty($whereConditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $whereConditions);
        $execParams = array_merge($execParams, $params);
    }
    
    $sql .= ' ORDER BY l.id DESC LIMIT ? OFFSET ?';
    
    $execParams[] = $perPage;
    $execParams[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($execParams);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
elseif ($tipo === 'editoriales') {
    // 1. Contar total
    $sqlCount = 'SELECT COUNT(*) FROM editoriales';
    if ($q !== '')
        $sqlCount .= ' WHERE nombre LIKE ?';
    $stmtCount = $pdo->prepare($sqlCount);
    if ($q !== '')
        $stmtCount->execute([$likeQuery]);
    else
        $stmtCount->execute();
    $total = $stmtCount->fetchColumn();

    // 2. Obtener datos
    $sql = '
        SELECT e.id, e.nombre, COUNT(l.id) AS cantidad_libros 

        FROM editoriales e 
        LEFT JOIN libros l ON e.id = l.editorial_id 
    ';
    if ($q !== '')
        $sql .= ' WHERE e.nombre LIKE ? ';
    $sql .= ' GROUP BY e.id ORDER BY e.nombre ASC LIMIT ? OFFSET ?';

    $stmt = $pdo->prepare($sql);
    $execParams = $q !== '' ? [$likeQuery] : [];
    $execParams[] = $perPage;
    $execParams[] = $offset;
    $stmt->execute($execParams);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
elseif ($tipo === 'usuarios') {
    // 1. Contar total
    $sqlCount = 'SELECT COUNT(*) FROM usuarios';
    if ($q !== '')
        $sqlCount .= ' WHERE username LIKE ?';
    $stmtCount = $pdo->prepare($sqlCount);
    if ($q !== '')
        $stmtCount->execute([$likeQuery]);
    else
        $stmtCount->execute();
    $total = $stmtCount->fetchColumn();

    // 2. Obtener datos
    $sql = 'SELECT id, username, fecha_registro, rol, activo FROM usuarios ';
    if ($q !== '')
        $sql .= ' WHERE username LIKE ? ';
    $sql .= ' ORDER BY activo ASC, id ASC LIMIT ? OFFSET ?';

    $stmt = $pdo->prepare($sql);
    $execParams = $q !== '' ? [$likeQuery] : [];
    $execParams[] = $perPage;
    $execParams[] = $offset;
    $stmt->execute($execParams);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
elseif ($tipo === 'cdu') {
    // 1. Contar total
    $sqlCount = 'SELECT COUNT(*) FROM cdu';
    if ($q !== '')
        $sqlCount .= ' WHERE codigo LIKE ? OR nombre LIKE ?';
    $stmtCount = $pdo->prepare($sqlCount);
    if ($q !== '')
        $stmtCount->execute([$likeQuery, $likeQuery]);
    else
        $stmtCount->execute();
    $total = $stmtCount->fetchColumn();

    // 2. Obtener datos
    $sql = '
        SELECT c.*, (SELECT COUNT(*) FROM libros l WHERE l.cdu_id = c.id) as cantidad_libros 
        FROM cdu c
    ';
    if ($q !== '')
        $sql .= ' WHERE c.codigo LIKE ? OR c.nombre LIKE ? ';
    $sql .= ' ORDER BY c.codigo ASC LIMIT ? OFFSET ?';

    $stmt = $pdo->prepare($sql);
    $execParams = $q !== '' ? [$likeQuery, $likeQuery] : [];
    $execParams[] = $perPage;
    $execParams[] = $offset;
    $stmt->execute($execParams);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
else {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo inválido']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'total' => (int)$total,
    'page' => (int)$page,
    'per_page' => (int)$perPage,
    'data' => $resultados
]);
