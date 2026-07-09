<?php
// api_app.php
// Endpoint seguro para la app Android

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

$pdo = getDB();

// Autenticación básica (HTTP Basic Auth) con Fallback para servidores CGI/FastCGI
$username = $_SERVER['PHP_AUTH_USER'] ?? '';
$password = $_SERVER['PHP_AUTH_PW'] ?? '';

// Fallback si el servidor (Apache/IONOS) oculta las variables de PHP_AUTH
if (!$username || !$password) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($authHeader && preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
        $decoded = base64_decode($matches[1]);
        if (strpos($decoded, ':') !== false) {
            list($username, $password) = explode(':', $decoded, 2);
        }
    }
}

if (!$username || !$password) {
    http_response_code(401);
    echo json_encode(['error' => 'No se proporcionaron credenciales']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Credenciales inválidas']);
    exit;
}

// Usuario autenticado correctamente.
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        // Solo para verificar que el login es correcto desde la app
        echo json_encode(['success' => true, 'message' => 'Autenticado correctamente', 'user' => $user['username']]);
        break;

    case 'news':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Obtener noticias (solo las activas por defecto, pero dejemos que la app las vea todas para poder gestionar)
            $stmt = $pdo->query("SELECT id, title, image_path, event_date, is_active_home, is_active_category FROM news_events ORDER BY id DESC LIMIT 100");
            $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'news' => $news]);
        }
        break;

    case 'toggle_news':
        // Cambiar la visibilidad de la noticia en la web (is_active_home e is_active_category)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            // Fallback para form-data
            $news_id = $data['id'] ?? $_POST['id'] ?? 0;
            $is_active = $data['is_active'] ?? $_POST['is_active'] ?? 0;
            
            if ($news_id) {
                $stmt = $pdo->prepare("UPDATE news_events SET is_active_home = ?, is_active_category = ? WHERE id = ?");
                $stmt->execute([$is_active, $is_active, $news_id]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID de noticia requerido']);
            }
        }
        break;

    case 'media':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Obtener medios de una noticia
            $news_id = $_GET['news_id'] ?? 0;
            $stmt = $pdo->prepare("SELECT id, image_path, is_video, caption, sort_order FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id DESC");
            $stmt->execute([$news_id]);
            $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'media' => $media]);
        } 
        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Subir nuevo medio (soporta múltiples archivos pero la app puede mandarlos de uno en uno)
            $news_id = $_POST['news_id'] ?? 0;
            if (!$news_id || !isset($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta el ID de la noticia o el archivo']);
                exit;
            }

            require_once 'admin/inc/image_helper.php';

            $file = $_FILES['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['error' => 'Error al subir el archivo al servidor']);
                exit;
            }

            $uploadDir = 'uploads/news/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $filename = uniqid('newsg_api_') . '_' . basename($file['name']);
            $targetFile = $uploadDir . $filename;
            
            $fileExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $isVid = in_array($fileExt, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']) ? 1 : 0;
            
            $success = false;
            if ($fileExt === 'pdf') {
                $success = move_uploaded_file($file['tmp_name'], $targetFile);
            } elseif ($isVid) {
                $success = processUploadedVideo($file['tmp_name'], $targetFile, true);
            } else {
                $success = processUploadedImage($file['tmp_name'], $targetFile, true, 1200, 85);
            }

            if ($success) {
                $dbPath = 'uploads/news/' . $filename;
                $stmtImg = $pdo->prepare("INSERT INTO news_images (news_id, image_path, sort_order, is_video) VALUES (?, ?, ?, ?)");
                $stmtImg->execute([$news_id, $dbPath, 0, $isVid]);
                $newId = $pdo->lastInsertId();
                
                echo json_encode(['success' => true, 'media' => [
                    'id' => $newId,
                    'image_path' => $dbPath,
                    'is_video' => $isVid,
                    'caption' => null,
                    'sort_order' => 0
                ]]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al procesar el archivo']);
            }
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            // Actualizar descripción (caption)
            $data = json_decode(file_get_contents("php://input"), true);
            $media_id = $data['id'] ?? 0;
            $caption = $data['caption'] ?? '';
            
            if ($media_id) {
                $stmt = $pdo->prepare("UPDATE news_images SET caption = ? WHERE id = ?");
                $stmt->execute([$caption, $media_id]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID de medio requerido']);
            }
        }
        elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            // Borrar un medio
            $media_id = $_GET['id'] ?? 0;
            if ($media_id) {
                $stmt = $pdo->prepare("SELECT image_path FROM news_images WHERE id = ?");
                $stmt->execute([$media_id]);
                $path = $stmt->fetchColumn();
                
                if (!empty($path) && is_file($path)) {
                    @unlink($path);
                }
                
                $stmt = $pdo->prepare("DELETE FROM news_images WHERE id = ?");
                $stmt->execute([$media_id]);
                echo json_encode(['success' => true]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'ID de medio requerido']);
            }
        }
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Acción no encontrada']);
        break;
}
