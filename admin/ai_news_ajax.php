<?php
// admin/ai_news_ajax.php
// Endpoints AJAX para el Buscador y Generador Supervisado de Noticias con IA (Google Gemini + 3 Opciones de Imagen + Control Anti-Duplicados)

ob_start();
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/ai_news_helper.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
ensureAiNewsTrackingSchema($pdo);

$action = $_POST['ajax_action'] ?? $_GET['ajax_action'] ?? '';

try {
    if ($action === 'search_news') {
        $query = trim($_POST['query'] ?? $_GET['query'] ?? 'Moratalla Murcia');
        $items = searchMoratallaNews($pdo, $query, 28);

        $counts = ['new' => 0, 'published' => 0, 'discarded' => 0];
        foreach ($items as $it) {
            $st = $it['process_status'] ?? 'new';
            if (isset($counts[$st])) $counts[$st]++;
        }

        ob_clean();
        echo json_encode([
            'success' => true,
            'query'   => $query,
            'counts'  => $counts,
            'items'   => $items
        ]);
        exit;
    }

    if ($action === 'prepare_draft') {
        $url = trim($_POST['url'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $snippet = trim($_POST['snippet'] ?? '');
        $source = trim($_POST['source'] ?? '');
        $rssImage = trim($_POST['rss_image'] ?? '');

        if ($url === '' && $title === '') {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Debes indicar una noticia o URL válida.']);
            exit;
        }

        // 1. Extraer texto e imágenes originales de la noticia (Opción A)
        $articleInfo = extractArticleContentAndImages($url, $title, $snippet, $rssImage, $source);

        // 2. Obtener categorías de la base de datos para que Gemini sugiera la más adecuada
        $cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

        // 3. Redactar borrador supervisado con Google Gemini
        $draft = generateSupervisedDraftWithGemini($pdo, $articleInfo, $cats);

        // 4. Si hay al menos una imagen original en Opción A, pre-descargar y aplicar marca de agua a la primera para agilizar
        $preDownloadedOriginal = null;
        if (!empty($articleInfo['original_images'][0]['url'])) {
            $dl = downloadAndProcessRemoteImage($articleInfo['original_images'][0]['url'], 'news_ext_');
            if (!empty($dl['success'])) {
                $preDownloadedOriginal = [
                    'local_path' => $dl['path'],
                    'remote_url' => $articleInfo['original_images'][0]['url'],
                    'caption'    => $draft['image_caption'] ?? $articleInfo['title'],
                    'author'     => 'Foto: ' . ($articleInfo['source_name'] ?: 'Prensa')
                ];
            }
        }

        // 5. Buscar fotos sugeridas del archivo propio de Moratalla (Opción C)
        $archiveKeywords = $draft['archive_keywords'] ?? 'Moratalla';
        $archiveImages = searchLocalMoratallaArchive($pdo, $archiveKeywords, 18);

        ob_clean();
        echo json_encode([
            'success'                 => true,
            'article'                 => $articleInfo,
            'draft'                   => $draft,
            'pre_downloaded_original' => $preDownloadedOriginal,
            'archive_images'          => $archiveImages,
            'archive_keywords'        => $archiveKeywords
        ]);
        exit;
    }

    if ($action === 'download_original_image') {
        $imgUrl = trim($_POST['image_url'] ?? '');
        $sourceName = trim($_POST['source_name'] ?? 'Prensa');
        $dl = downloadAndProcessRemoteImage($imgUrl, 'news_ext_');
        if (!empty($dl['success'])) {
            $dl['author'] = 'Foto: ' . ($sourceName ?: 'Archivo de prensa');
        }
        ob_clean();
        echo json_encode($dl);
        exit;
    }

    if ($action === 'generate_ai_image') {
        $prompt = trim($_POST['prompt'] ?? '');
        $res = generateAiImageForNews($pdo, $prompt);
        if (!empty($res['success'])) {
            $res['author'] = 'Imagen ilustrativa generada por IA';
        }
        ob_clean();
        echo json_encode($res);
        exit;
    }

    if ($action === 'search_archive_images') {
        $q = trim($_POST['query'] ?? '');
        $imgs = searchLocalMoratallaArchive($pdo, $q, 24);
        ob_clean();
        echo json_encode([
            'success' => true,
            'images'  => $imgs
        ]);
        exit;
    }

    if ($action === 'mark_discarded') {
        $url = trim($_POST['url'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $source = trim($_POST['source'] ?? '');
        $ok = markNewsAsProcessed($pdo, $url, $title, $source, 'discarded', null);
        ob_clean();
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    if ($action === 'unmark_processed') {
        $urlHash = trim($_POST['url_hash'] ?? '');
        $recordId = $_POST['record_id'] ?? '';
        $url = trim($_POST['url'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $source = trim($_POST['source'] ?? '');
        if ($url !== '' || $title !== '') {
            $ok = markNewsAsProcessed($pdo, $url, $title, $source, 'available', null);
        } else {
            $ok = unmarkProcessedNews($pdo, $recordId ?: $urlHash);
        }
        ob_clean();
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    if ($action === 'change_news_status') {
        $targetStatus = trim($_POST['target_status'] ?? 'available');
        $url = trim($_POST['url'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $source = trim($_POST['source'] ?? '');
        $recordId = $_POST['record_id'] ?? '';
        $urlHash = trim($_POST['url_hash'] ?? '');

        if ($targetStatus === 'new' || $targetStatus === 'available') {
            if ($url !== '' || $title !== '') {
                $ok = markNewsAsProcessed($pdo, $url, $title, $source, 'available', null);
            } else {
                $ok = unmarkProcessedNews($pdo, $recordId ?: $urlHash);
            }
        } elseif ($targetStatus === 'discarded') {
            $ok = markNewsAsProcessed($pdo, $url, $title, $source, 'discarded', null);
        } elseif ($targetStatus === 'published') {
            $ok = markNewsAsProcessed($pdo, $url, $title, $source, 'published', null);
        } else {
            $ok = false;
        }

        ob_clean();
        echo json_encode(['success' => (bool)$ok, 'status' => ($targetStatus === 'available' ? 'new' : $targetStatus)]);
        exit;
    }

    if ($action === 'save_gemini_key') {
        $apiKey = trim($_POST['gemini_api_key'] ?? '');
        if ($apiKey === '') {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'La clave API no puede estar vacía.']);
            exit;
        }
        $ok = saveGeminiApiKey($pdo, $apiKey);
        ob_clean();
        echo json_encode(['success' => (bool)$ok]);
        exit;
    }

    if ($action === 'generate_ai_icon') {
        require_once __DIR__ . '/inc/icon_helper.php';
        $prompt = trim($_POST['prompt'] ?? '');
        $format = trim($_POST['format'] ?? 'svg');
        $res = generateAiIconForWeb($pdo, $prompt, $format);
        ob_clean();
        echo json_encode($res);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Acción AJAX no reconocida.']);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
