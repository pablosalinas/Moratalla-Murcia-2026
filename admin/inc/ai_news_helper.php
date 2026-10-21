<?php
// admin/inc/ai_news_helper.php
// Motor de búsqueda de noticias de Moratalla, control de duplicados/procesadas, extracción web completa,
// redacción extensa y proporcional con diseño corporativo moratalla-murcia.com (Google Gemini + procesador inteligente) y 3 opciones de imagen

require_once __DIR__ . '/../../inc/mailer.php';
require_once __DIR__ . '/image_helper.php';

/**
 * Asegura que existan la tabla de control `ai_processed_news` y la columna `source_url` en `news_events`
 */
function ensureAiNewsTrackingSchema($pdo) {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ai_processed_news (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source_url VARCHAR(700) NOT NULL,
            url_hash CHAR(64) NOT NULL,
            title_original VARCHAR(500) NOT NULL,
            title_norm VARCHAR(255) NOT NULL,
            source_name VARCHAR(150) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'published',
            news_event_id INT NULL,
            processed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_url_hash (url_hash),
            INDEX idx_title_norm (title_norm),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}

    try {
        $pdo->query("SELECT source_url FROM news_events LIMIT 1");
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE news_events ADD COLUMN source_url VARCHAR(700) NULL AFTER icon");
        } catch (Exception $e2) {}
    }
}

/**
 * Normaliza un titular para comparación anti-duplicados
 */
function normalizeNewsTitleForDedupe($title) {
    $t = mb_strtolower(trim((string)$title), 'UTF-8');
    $replacements = [
        'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n',
        'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u', 'Ñ'=>'n'
    ];
    $t = strtr($t, $replacements);
    $t = preg_replace('/[^a-z0-9\s]/', ' ', $t);
    $words = preg_split('/\s+/', $t, -1, PREG_SPLIT_NO_EMPTY);
    $stopwords = ['el','la','los','las','un','una','unos','unas','de','del','al','en','con','por','para','sobre','se','su','sus','que','y','o','a','ante','bajo','como','entre','hacia','hasta','sin','tras','moratalla','murcia','region'];
    $filtered = [];
    foreach ($words as $w) {
        if (strlen($w) > 2 && !in_array($w, $stopwords, true)) {
            $filtered[] = $w;
        }
    }
    return implode(' ', $filtered);
}

/**
 * Normaliza una URL para calcular su hash único sin parámetros de tracking
 */
function normalizeUrlForHash($url) {
    $url = trim((string)$url);
    if ($url === '') return '';
    $parts = @parse_url($url);
    if (!$parts || empty($parts['host'])) return mb_strtolower($url);
    $host = preg_replace('/^www\./i', '', strtolower($parts['host']));
    $path = rtrim($parts['path'] ?? '/', '/');
    $query = '';
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $qArr);
        foreach (['utm_source','utm_medium','utm_campaign','utm_term','utm_content','fbclid','gclid','ref'] as $rem) {
            unset($qArr[$rem]);
        }
        if (!empty($qArr)) {
            ksort($qArr);
            $query = '?' . http_build_query($qArr);
        }
    }
    return $host . $path . $query;
}

/**
 * Calcula si dos titulares normalizados se refieren a la misma noticia
 */
function areNewsTitlesRedundant($normA, $normB) {
    if ($normA === '' || $normB === '') return false;
    if ($normA === $normB) return true;
    if (strpos($normA, $normB) !== false || strpos($normB, $normA) !== false) {
        if (min(strlen($normA), strlen($normB)) >= 18) return true;
    }

    $wordsA = array_unique(explode(' ', $normA));
    $wordsB = array_unique(explode(' ', $normB));
    if (count($wordsA) < 3 || count($wordsB) < 3) return false;

    $intersection = array_intersect($wordsA, $wordsB);
    $overlapRatio = count($intersection) / min(count($wordsA), count($wordsB));
    if ($overlapRatio >= 0.65) {
        return true;
    }

    similar_text($normA, $normB, $percent);
    return ($percent >= 72.0);
}

/**
 * Registra una noticia como procesada (publicada o descartada)
 */
function markNewsAsProcessed($pdo, $sourceUrl, $originalTitle, $sourceName = '', $status = 'published', $newsEventId = null) {
    ensureAiNewsTrackingSchema($pdo);
    $sourceUrl = trim((string)$sourceUrl);
    $originalTitle = trim((string)$originalTitle);
    if ($sourceUrl === '' && $originalTitle === '') return false;

    $normUrl = normalizeUrlForHash($sourceUrl ?: $originalTitle);
    $urlHash = hash('sha256', $normUrl);
    $titleNorm = mb_substr(normalizeNewsTitleForDedupe($originalTitle), 0, 250);

    $stmt = $pdo->prepare("SELECT id FROM ai_processed_news WHERE url_hash = ? OR (title_norm != '' AND title_norm = ?) LIMIT 1");
    $stmt->execute([$urlHash, $titleNorm]);
    $existingId = $stmt->fetchColumn();

    if ($existingId) {
        $upd = $pdo->prepare("UPDATE ai_processed_news SET status = ?, news_event_id = COALESCE(?, news_event_id), processed_at = NOW() WHERE id = ?");
        return $upd->execute([$status, $newsEventId ?: null, $existingId]);
    } else {
        $ins = $pdo->prepare("INSERT INTO ai_processed_news (source_url, url_hash, title_original, title_norm, source_name, status, news_event_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $ins->execute([$sourceUrl, $urlHash, $originalTitle, $titleNorm, $sourceName, $status, $newsEventId ?: null]);
    }
}

/**
 * Permite restaurar una noticia previamente descartada/procesada
 */
function unmarkProcessedNews($pdo, $urlHashOrId) {
    ensureAiNewsTrackingSchema($pdo);
    if (is_numeric($urlHashOrId)) {
        $stmt = $pdo->prepare("DELETE FROM ai_processed_news WHERE id = ?");
        return $stmt->execute([(int)$urlHashOrId]);
    }
    $stmt = $pdo->prepare("DELETE FROM ai_processed_news WHERE url_hash = ?");
    return $stmt->execute([$urlHashOrId]);
}

/**
 * Cruza una lista de noticias encontradas en internet contra `ai_processed_news` y `news_events`
 */
function annotateNewsWithProcessedStatus($pdo, array $items) {
    ensureAiNewsTrackingSchema($pdo);

    $processedRows = [];
    try {
        $stmt = $pdo->query("SELECT p.*, ne.title as published_title, ne.id as real_news_id
                             FROM ai_processed_news p
                             LEFT JOIN news_events ne ON p.news_event_id = ne.id
                             ORDER BY p.processed_at DESC");
        $processedRows = $stmt->fetchAll();
    } catch (Exception $e) {}

    $existingNews = [];
    try {
        $stmt2 = $pdo->query("SELECT id, title, source_url, event_date FROM news_events ORDER BY id DESC");
        while ($r = $stmt2->fetch()) {
            $r['title_norm'] = normalizeNewsTitleForDedupe($r['title']);
            $r['url_hash'] = !empty($r['source_url']) ? hash('sha256', normalizeUrlForHash($r['source_url'])) : '';
            $existingNews[] = $r;
        }
    } catch (Exception $e) {}

    $matchedRecordIds = [];
    foreach ($items as &$item) {
        $itemUrlHash = hash('sha256', normalizeUrlForHash($item['url'] ?: $item['title']));
        $itemTitleNorm = normalizeNewsTitleForDedupe($item['title']);

        $item['url_hash'] = $itemUrlHash;
        $item['process_status'] = 'new';
        $item['matched_news_id'] = null;
        $item['matched_title'] = null;
        $item['processed_record_id'] = null;
        $item['processed_date'] = null;

        $explicitlyAvailable = false;
        foreach ($processedRows as $pr) {
            $sameHash = ($pr['url_hash'] === $itemUrlHash);
            $sameTitle = areNewsTitlesRedundant($itemTitleNorm, $pr['title_norm']);
            if ($sameHash || $sameTitle) {
                $matchedRecordIds[(int)$pr['id']] = true;
                $item['processed_record_id'] = (int)$pr['id'];
                $item['processed_date'] = $pr['processed_at'] ? date('d/m/Y H:i', strtotime($pr['processed_at'])) : '';
                if ($pr['status'] === 'discarded') {
                    $item['process_status'] = 'discarded';
                    $item['matched_title'] = $pr['title_original'];
                } elseif ($pr['status'] === 'available' || $pr['status'] === 'new') {
                    $item['process_status'] = 'new';
                    $explicitlyAvailable = true;
                } else {
                    $item['process_status'] = 'published';
                    $item['matched_news_id'] = $pr['real_news_id'] ?: $pr['news_event_id'];
                    $item['matched_title'] = $pr['published_title'] ?: $pr['title_original'];
                }
                break;
            }
        }

        if ($item['process_status'] === 'new' && !$explicitlyAvailable) {
            foreach ($existingNews as $en) {
                if (!empty($en['url_hash']) && $en['url_hash'] === $itemUrlHash) {
                    $item['process_status'] = 'published';
                    $item['matched_news_id'] = (int)$en['id'];
                    $item['matched_title'] = $en['title'];
                    break;
                }
                if (areNewsTitlesRedundant($itemTitleNorm, $en['title_norm'])) {
                    $item['process_status'] = 'published';
                    $item['matched_news_id'] = (int)$en['id'];
                    $item['matched_title'] = $en['title'];
                    break;
                }
            }
        }
    }
    unset($item);

    // Incorporar también las noticias guardadas en `ai_processed_news` (descartadas, publicadas o reconducidas previamente)
    // que no estén en el feed actual, para que siempre puedan verse y reconducirse desde los botones de filtro
    foreach ($processedRows as $pr) {
        $prId = (int)$pr['id'];
        if (isset($matchedRecordIds[$prId])) continue;

        $ts = !empty($pr['processed_at']) ? @strtotime($pr['processed_at']) : time();
        $st = 'published';
        if ($pr['status'] === 'discarded') $st = 'discarded';
        elseif ($pr['status'] === 'available' || $pr['status'] === 'new') $st = 'new';

        $items[] = [
            'title'               => $pr['title_original'] ?: ($pr['published_title'] ?: 'Noticia registrada'),
            'url'                 => $pr['source_url'] ?: '',
            'snippet'             => '',
            'source'              => $pr['source_name'] ?: 'Historial IA',
            'pub_date'            => $ts ? date('d/m/Y H:i', $ts) : 'Registrada',
            'event_date_iso'      => $ts ? date('Y-m-d', $ts) : date('Y-m-d'),
            'timestamp'           => $ts ?: 0,
            'rss_image'           => '',
            'url_hash'            => $pr['url_hash'],
            'process_status'      => $st,
            'matched_news_id'     => $pr['real_news_id'] ?: $pr['news_event_id'],
            'matched_title'       => $pr['published_title'] ?: $pr['title_original'],
            'processed_record_id' => $prId,
            'processed_date'      => $ts ? date('d/m/Y H:i', $ts) : ''
        ];
    }

    return $items;
}

/**
 * Obtiene la clave API de Google Gemini desencriptada desde settings o .env
 */
function getGeminiApiKey($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'gemini_api_key_enc' LIMIT 1");
        $stmt->execute();
        $enc = $stmt->fetchColumn();
        if (!empty($enc)) {
            $dec = decryptSecret($enc);
            if (!empty($dec)) {
                return trim($dec);
            }
        }
    } catch (Exception $e) {}
    if (function_exists('env')) {
        $envKey = env('GEMINI_API_KEY', '');
        if (!empty($envKey)) return trim($envKey);
    }
    return '';
}

/**
 * Guarda la clave API de Google Gemini encriptada con AES-256 en settings
 */
function saveGeminiApiKey($pdo, $plainKey) {
    $plainKey = trim($plainKey);
    if ($plainKey === '') return false;
    $enc = encryptSecret($plainKey);
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gemini_api_key_enc', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    return $stmt->execute([$enc, $enc]);
}

/**
 * Realiza una petición HTTP GET mediante cURL con encabezados de navegador real
 */
function aiHttpGet($url, $timeout = 12, &$finalUrl = null) {
    if (!function_exists('curl_init')) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'follow_location' => 1
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $finalUrl = $url;
        return @file_get_contents($url, false, $ctx);
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 6,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8'
        ]
    ]);
    $body = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
    curl_close($ch);
    return $body;
}

/**
 * Busca noticias recientes en internet sobre Moratalla combinando Bing News RSS y Google News RSS
 */
function searchMoratallaNews($pdo, $query = 'Moratalla Murcia', $maxResults = 25) {
    $query = trim($query);
    if ($query === '') {
        $query = 'Moratalla Murcia';
    }

    $items = [];
    $seenNormTitles = [];

    // 1. Fuente: Bing News RSS
    $bingUrl = 'https://www.bing.com/news/search?q=' . urlencode($query) . '&format=rss&mkt=es-ES&setlang=es';
    $bingXmlStr = aiHttpGet($bingUrl, 10);
    if (!empty($bingXmlStr)) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($bingXmlStr);
        if ($xml && isset($xml->channel->item)) {
            $namespaces = $xml->getNamespaces(true);
            foreach ($xml->channel->item as $item) {
                $title = trim((string)$item->title);
                $rawLink = trim((string)$item->link);
                $desc = trim(strip_tags((string)$item->description));
                $pubDate = trim((string)$item->pubDate);

                $realUrl = $rawLink;
                if (preg_match('/[?&]url=([^&]+)/', $rawLink, $m)) {
                    $realUrl = urldecode($m[1]);
                }

                $sourceName = '';
                $rssImage = '';
                if (isset($namespaces['News'])) {
                    $newsNs = $item->children($namespaces['News']);
                    if (isset($newsNs->Source)) {
                        $sourceName = trim((string)$newsNs->Source);
                    }
                    if (isset($newsNs->Image)) {
                        $rssImage = trim((string)$newsNs->Image);
                    }
                }
                if (empty($sourceName)) {
                    $host = parse_url($realUrl, PHP_URL_HOST) ?: '';
                    $sourceName = preg_replace('/^www\./i', '', $host);
                }

                $normTitle = normalizeNewsTitleForDedupe($title);
                $isDupInBatch = false;
                foreach ($seenNormTitles as $prevNorm) {
                    if (areNewsTitlesRedundant($normTitle, $prevNorm)) {
                        $isDupInBatch = true;
                        break;
                    }
                }

                if ($title !== '' && !$isDupInBatch) {
                    $seenNormTitles[] = $normTitle;
                    $timestamp = $pubDate ? @strtotime($pubDate) : time();
                    $items[] = [
                        'title'          => $title,
                        'url'            => $realUrl,
                        'snippet'        => $desc,
                        'source'         => $sourceName ?: 'Prensa Digital',
                        'pub_date'       => $timestamp ? date('d/m/Y H:i', $timestamp) : 'Reciente',
                        'event_date_iso' => $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d'),
                        'timestamp'      => $timestamp ?: 0,
                        'rss_image'      => $rssImage
                    ];
                }
            }
        }
    }

    // 2. Fuente: Google News RSS
    $gnewsUrl = 'https://news.google.com/rss/search?q=' . urlencode($query) . '&hl=es&gl=ES&ceid=ES:es';
    $gnewsXmlStr = aiHttpGet($gnewsUrl, 10);
    if (!empty($gnewsXmlStr)) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($gnewsXmlStr);
        if ($xml && isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $fullTitle = trim((string)$item->title);
                $sourceName = isset($item->source) ? trim((string)$item->source) : '';
                $sourceUrl = (isset($item->source['url'])) ? trim((string)$item->source['url']) : '';

                $cleanTitle = $fullTitle;
                if ($sourceName !== '' && mb_substr($fullTitle, -mb_strlen(' - ' . $sourceName)) === ' - ' . $sourceName) {
                    $cleanTitle = trim(mb_substr($fullTitle, 0, mb_strlen($fullTitle) - mb_strlen(' - ' . $sourceName)));
                } elseif (preg_match('/^(.*)\s+-\s+([^-]+)$/u', $fullTitle, $m)) {
                    $cleanTitle = trim($m[1]);
                    if ($sourceName === '') $sourceName = trim($m[2]);
                }

                $rawDesc = (string)$item->description;
                $cleanDesc = trim(html_entity_decode(strip_tags($rawDesc), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if (mb_stripos($cleanDesc, $cleanTitle) === 0) {
                    $cleanDesc = trim(mb_substr($cleanDesc, mb_strlen($cleanTitle)));
                    $cleanDesc = trim($cleanDesc, " -–—\t\n\r");
                }

                $pubDate = trim((string)$item->pubDate);
                $link = trim((string)$item->link);

                $normTitle = normalizeNewsTitleForDedupe($cleanTitle);
                $isDupInBatch = false;
                foreach ($seenNormTitles as $prevNorm) {
                    if (areNewsTitlesRedundant($normTitle, $prevNorm)) {
                        $isDupInBatch = true;
                        break;
                    }
                }

                if ($cleanTitle !== '' && !$isDupInBatch) {
                    $seenNormTitles[] = $normTitle;
                    $timestamp = $pubDate ? @strtotime($pubDate) : time();
                    $items[] = [
                        'title'          => $cleanTitle,
                        'url'            => $link,
                        'source_domain'  => $sourceUrl,
                        'snippet'        => $cleanDesc ?: $cleanTitle,
                        'source'         => $sourceName ?: 'Google News',
                        'pub_date'       => $timestamp ? date('d/m/Y H:i', $timestamp) : 'Reciente',
                        'event_date_iso' => $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d'),
                        'timestamp'      => $timestamp ?: 0,
                        'rss_image'      => ''
                    ];
                }
            }
        }
    }

    usort($items, function ($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });

    $sliced = array_slice($items, 0, $maxResults);
    return annotateNewsWithProcessedStatus($pdo, $sliced);
}

/**
 * Si una URL es un enlace intermedio de Google News, decodifica o localiza la URL directa del artículo original
 * verificando siempre que corresponda exactamente a la noticia seleccionada.
 */
function resolveRealArticleUrl($url, $title = '', $source = '') {
    if (stripos($url, 'news.google.com') === false) {
        return $url;
    }

    // 1. Intentar decodificación directa del token base64 si contiene URL en claro
    if (preg_match('#/articles/([A-Za-z0-9_-]+)#', $url, $m)) {
        $artId = $m[1];
        $b64 = strtr($artId, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) $b64 .= str_repeat('=', 4 - $pad);
        $decoded = @base64_decode($b64, true);
        if ($decoded && preg_match('#(https?://[^\x00-\x1f\x7f"\'<>\s]+)#i', $decoded, $uMatch)) {
            $cand = $uMatch[1];
            if (stripos($cand, 'google.com') === false) {
                return $cand;
            }
        }

        // 2. Decodificación oficial de Google News (tokens AU_yqL...) mediante batchexecute Fbv4je
        $gnPageHtml = aiHttpGet('https://news.google.com/rss/articles/' . $artId, 8);
        if (empty($gnPageHtml) || strpos($gnPageHtml, 'data-n-a-sg') === false) {
            $gnPageHtml = aiHttpGet('https://news.google.com/articles/' . $artId, 8);
        }
        if (!empty($gnPageHtml) &&
            preg_match('/data-n-a-ts="([^"]+)"/', $gnPageHtml, $tsM) &&
            preg_match('/data-n-a-sg="([^"]+)"/', $gnPageHtml, $sgM)) {
            $ts = $tsM[1];
            $sg = $sgM[1];
            $innerReq = '["garturlreq",[["es","ES",["FINANCE_TOP_INDICES","WEB_TEST_1_0_0"],null,null,1,1,"ES:es",null,180,null,null,null,null,null,0,null,null,[1608992183,723341000]],"es","ES",1,[2,3,4,8],1,0,"655000234",0,0,null,0],"' . addslashes($artId) . '",' . addslashes($ts) . ',"' . addslashes($sg) . '"]';
            $fReq = json_encode([[['Fbv4je', $innerReq, null, 'generic']]]);
            $ch = curl_init('https://news.google.com/_/DotsSplashUi/data/batchexecute?rpcids=Fbv4je');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => 'f.req=' . urlencode($fReq),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ],
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $batchResp = curl_exec($ch);
            curl_close($ch);
            if (!empty($batchResp) && preg_match('/\["garturlres","(https?:[^"]+)"/', $batchResp, $resM)) {
                $decodedUrl = json_decode('"' . $resM[1] . '"');
                if (!empty($decodedUrl) && stripos($decodedUrl, 'google.com') === false) {
                    return $decodedUrl;
                }
            }
        }
    }

    // 3. Buscar el titular en Bing News/Web SOLO si el título del resultado coincide con la noticia seleccionada
    if (!empty($title)) {
        $normTarget = normalizeNewsTitleForDedupe($title);
        $searchQuery = '"' . $title . '" ' . ($source ?: 'Moratalla');
        $bingUrl = 'https://www.bing.com/news/search?q=' . urlencode($searchQuery) . '&format=rss&mkt=es-ES';
        $bingXml = aiHttpGet($bingUrl, 8);
        if (!empty($bingXml)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($bingXml);
            if ($xml && isset($xml->channel->item)) {
                foreach ($xml->channel->item as $it) {
                    $itTitle = normalizeNewsTitleForDedupe((string)$it->title);
                    if (areNewsTitlesRedundant($normTarget, $itTitle)) {
                        $rawLink = (string)$it->link;
                        if (preg_match('/[?&]url=([^&]+)/', $rawLink, $m)) {
                            return urldecode($m[1]);
                        }
                        if (!empty($rawLink) && stripos($rawLink, 'news.google.com') === false) {
                            return $rawLink;
                        }
                    }
                }
            }
        }

        $bingWebUrl = 'https://www.bing.com/search?q=' . urlencode($title . ' ' . $source) . '&format=rss';
        $bingWebXml = aiHttpGet($bingWebUrl, 8);
        if (!empty($bingWebXml)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($bingWebXml);
            if ($xml && isset($xml->channel->item)) {
                foreach ($xml->channel->item as $it) {
                    $itTitle = normalizeNewsTitleForDedupe((string)$it->title);
                    if (areNewsTitlesRedundant($normTarget, $itTitle)) {
                        $candidate = trim((string)$it->link);
                        if (!empty($candidate) && stripos($candidate, 'news.google.com') === false) {
                            return $candidate;
                        }
                    }
                }
            }
        }
    }

    return $url;
}

/**
 * Convierte una URL relativa de imagen a absoluta respecto a la URL de la página
 */
function makeAbsoluteUrl($relUrl, $baseUrl) {
    $relUrl = trim($relUrl);
    if ($relUrl === '' || stripos($relUrl, 'data:') === 0) return '';
    if (preg_match('#^https?://#i', $relUrl)) return $relUrl;
    if (strpos($relUrl, '//') === 0) {
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';
        return $scheme . ':' . $relUrl;
    }
    $parts = parse_url($baseUrl);
    if (empty($parts['host'])) return $relUrl;
    $scheme = $parts['scheme'] ?? 'https';
    $root = $scheme . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    if (strpos($relUrl, '/') === 0) {
        return $root . $relUrl;
    }
    $path = isset($parts['path']) ? preg_replace('#/[^/]*$#', '/', $parts['path']) : '/';
    return $root . $path . $relUrl;
}

/**
 * Extrae exclusivamente el contenido e imágenes originales (Opción A) de la noticia seleccionada,
 * sin mezclar noticias relacionadas ni resultados externos de otros temas.
 */
function extractArticleContentAndImages($url, $fallbackTitle = '', $fallbackSnippet = '', $rssImage = '', $sourceName = '') {
    $resolvedUrl = resolveRealArticleUrl($url, $fallbackTitle, $sourceName);
    $finalUrl = $resolvedUrl;
    $html = '';

    if (!empty($resolvedUrl) && stripos($resolvedUrl, 'news.google.com') === false) {
        $html = aiHttpGet($resolvedUrl, 14, $finalUrl);
    }

    $images = [];
    $seenImg = [];
    $addCandidateImg = function ($imgUrl, $label = 'Imagen del artículo') use (&$images, &$seenImg, $finalUrl) {
        $abs = makeAbsoluteUrl($imgUrl, $finalUrl);
        if ($abs === '') return;
        if (preg_match('/(logo|avatar|icon|sprite|pixel|1x1|blank|banner-ad|doubleclick|gravatar|\.svg)/i', $abs)) {
            return;
        }
        if (!isset($seenImg[$abs])) {
            $seenImg[$abs] = true;
            $images[] = [
                'url'   => $abs,
                'label' => $label
            ];
        }
    };

    if (!empty($rssImage)) {
        $addCandidateImg($rssImage, 'Imagen de portada (Prensa)');
    }

    $extractedTitle = $fallbackTitle;
    $jsonLdArticleBody = '';
    $domExtractedBlocks = [];
    $detectedSource = $sourceName;
    $metaDesc = '';

    if (!empty($html)) {
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8');
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // 1. Meta tags
        $metaNodes = $xpath->query('//meta');
        foreach ($metaNodes as $meta) {
            $prop = strtolower(trim($meta->getAttribute('property') ?: $meta->getAttribute('name')));
            $content = trim($meta->getAttribute('content'));
            if ($content === '') continue;

            if (in_array($prop, ['og:image', 'og:image:secure_url', 'twitter:image', 'twitter:image:src'])) {
                $addCandidateImg($content, 'Foto principal de la noticia (Portada)');
            } elseif ($prop === 'og:site_name' && empty($detectedSource)) {
                $detectedSource = $content;
            } elseif ($prop === 'og:title' && empty($extractedTitle)) {
                $extractedTitle = $content;
            } elseif (in_array($prop, ['og:description', 'description']) && empty($metaDesc)) {
                $metaDesc = $content;
            }
        }

        // 2. JSON-LD (capturar articleBody e imágenes propias de la noticia)
        $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scriptNodes as $script) {
            $jsonRaw = trim($script->textContent);
            $data = @json_decode($jsonRaw, true);
            if (is_array($data)) {
                $walkJson = function ($node) use (&$walkJson, &$addCandidateImg, &$jsonLdArticleBody) {
                    if (!is_array($node)) return;
                    if (isset($node['image'])) {
                        if (is_string($node['image'])) {
                            $addCandidateImg($node['image'], 'Imagen de cabecera (Artículo)');
                        } elseif (is_array($node['image'])) {
                            if (isset($node['image']['url']) && is_string($node['image']['url'])) {
                                $addCandidateImg($node['image']['url'], 'Imagen de cabecera (Artículo)');
                            } else {
                                foreach ($node['image'] as $subImg) {
                                    if (is_string($subImg)) $addCandidateImg($subImg, 'Imagen del artículo');
                                    elseif (is_array($subImg) && isset($subImg['url'])) $addCandidateImg($subImg['url'], 'Imagen del artículo');
                                }
                            }
                        }
                    }
                    if (!empty($node['articleBody']) && is_string($node['articleBody']) && mb_strlen($node['articleBody']) > mb_strlen($jsonLdArticleBody)) {
                        $jsonLdArticleBody = trim(html_entity_decode($node['articleBody'], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    }
                    foreach ($node as $k => $v) {
                        if (is_array($v)) $walkJson($v);
                    }
                };
                $walkJson($data);
            }
        }

        // 3. Imágenes del artículo
        $imgNodes = $xpath->query('//article//img | //figure//img');
        if ($imgNodes->length === 0) {
            $imgNodes = $xpath->query('//main//img');
        }
        foreach ($imgNodes as $img) {
            $src = $img->getAttribute('data-src') ?: $img->getAttribute('data-lazy-src') ?: $img->getAttribute('src');
            $alt = trim($img->getAttribute('alt') ?: 'Imagen del artículo');
            $w = (int)$img->getAttribute('width');
            $h = (int)$img->getAttribute('height');
            if (($w > 0 && $w < 180) || ($h > 0 && $h < 120)) continue;
            if (!empty($src)) {
                $addCandidateImg($src, mb_substr($alt, 0, 80));
            }
            if (count($images) >= 10) break;
        }

        // 4. Eliminar menús, barras laterales y módulos de "noticias relacionadas" / "más leídas" para no contaminar el texto
        $removeQuery = '//script | //style | //nav | //footer | //header | //aside | //form | //noscript | '
                     . '//*[contains(@class,"related") or contains(@class,"relac") or contains(@class,"sidebar") or contains(@class,"widget") or contains(@class,"mas-leido") or contains(@class,"most-read") or contains(@class,"comments") or contains(@class,"newsletter") or contains(@class,"share") or contains(@class,"tags") or contains(@class,"recomend")]';
        foreach ($xpath->query($removeQuery) as $rem) {
            if ($rem->parentNode) {
                $rem->parentNode->removeChild($rem);
            }
        }

        // Priorizar JSON-LD articleBody si existe, ya que contiene exclusivamente el texto limpio de la noticia seleccionada
        if (mb_strlen($jsonLdArticleBody) >= 180) {
            $extractedText = $jsonLdArticleBody;
        } else {
            $blockNodes = $xpath->query('//article//*[self::p or self::blockquote]');
            if ($blockNodes->length < 2) {
                $blockNodes = $xpath->query('//main//p | //p');
            }

            $seenBlockTexts = [];
            foreach ($blockNodes as $node) {
                $tag = strtolower($node->nodeName);
                $txt = trim(preg_replace('/\s+/u', ' ', $node->textContent));
                if ($txt === '' || isset($seenBlockTexts[$txt])) continue;

                // Ignorar avisos de cookies, suscripciones, pies legales o enlaces a otras noticias
                if (preg_match('/(cookies|política de privacidad|todos los derechos reservados|suscríbete|iniciar sesión|regístrate gratis|boletín informativo|compartir en|lee también|te puede interesar|más información:|noticias relacionadas)/iu', $txt)) {
                    continue;
                }

                if ($tag === 'blockquote') {
                    if (mb_strlen($txt) >= 30) {
                        $seenBlockTexts[$txt] = true;
                        $domExtractedBlocks[] = "«" . trim($txt, '«»""') . "»";
                    }
                } else {
                    if (mb_strlen($txt) >= 40) {
                        $seenBlockTexts[$txt] = true;
                        $domExtractedBlocks[] = $txt;
                    }
                }
                if (count($domExtractedBlocks) >= 30) break;
            }

            $domText = implode("\n\n", $domExtractedBlocks);
            $extractedText = $domText ?: ($jsonLdArticleBody ?: $metaDesc);
        }
    } else {
        $extractedText = $fallbackSnippet ?: $fallbackTitle;
    }

    if (!empty($metaDesc) && empty($extractedText)) {
        $extractedText = $metaDesc;
    }

    if (empty($extractedText)) {
        $extractedText = $fallbackSnippet ?: $fallbackTitle;
    }

    if (empty($detectedSource)) {
        $host = parse_url($finalUrl, PHP_URL_HOST) ?: '';
        $detectedSource = preg_replace('/^www\./i', '', $host) ?: 'Prensa Digital';
    }

    return [
        'resolved_url'    => $finalUrl,
        'title'           => $extractedTitle,
        'body_text'       => mb_substr(trim($extractedText), 0, 12000),
        'source_name'     => $detectedSource,
        'original_images' => array_slice($images, 0, 10)
    ];
}

/**
 * Descarga una imagen remota (Opción A o B), la procesa con marca de agua y la guarda en uploads/news/
 */
function downloadAndProcessRemoteImage($imageUrl, $prefix = 'news_ext_') {
    if (empty($imageUrl)) return ['success' => false, 'error' => 'URL de imagen vacía'];

    $rawBytes = aiHttpGet($imageUrl, 20);
    if (empty($rawBytes) || strlen($rawBytes) < 512) {
        return ['success' => false, 'error' => 'No se pudo descargar la imagen desde el servidor de origen.'];
    }

    $uploadDir = dirname(__DIR__, 2) . '/uploads/news/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $tmpFile = $uploadDir . 'tmp_' . uniqid() . '.img';
    if (@file_put_contents($tmpFile, $rawBytes) === false) {
        return ['success' => false, 'error' => 'No se pudo escribir el archivo temporal en uploads/news/'];
    }

    $imgInfo = @getimagesize($tmpFile);
    if (!$imgInfo) {
        @unlink($tmpFile);
        return ['success' => false, 'error' => 'El archivo descargado no es una imagen válida.'];
    }

    $filename = uniqid($prefix) . '.jpg';
    $targetFile = $uploadDir . $filename;

    $processed = processUploadedImage($tmpFile, $targetFile, true, 1200, 85);
    if (!$processed && file_exists($tmpFile)) {
        $processed = @copy($tmpFile, $targetFile);
    }
    @unlink($tmpFile);

    if ($processed && file_exists($targetFile)) {
        return [
            'success' => true,
            'path'    => 'uploads/news/' . $filename
        ];
    }

    return ['success' => false, 'error' => 'Error al procesar y aplicar marca de agua a la imagen.'];
}

/**
 * Construye la maquetación HTML con el diseño visual corporativo,
 * sin referencias a la redacción de moratalla-murcia.com ni rellenos ajenos,
 * indicando claramente de dónde se ha obtenido la noticia.
 */
function buildMoratallaCorporateNewsHtml(array $parts) {
    $lead = trim($parts['lead'] ?? '');
    $keyPoints = is_array($parts['key_points'] ?? null) ? $parts['key_points'] : [];
    $sections = is_array($parts['sections'] ?? null) ? $parts['sections'] : [];
    $paragraphs = is_array($parts['body_paragraphs'] ?? null) ? $parts['body_paragraphs'] : [];
    $subtitle = trim($parts['subtitle'] ?? '');
    $topicTag = trim($parts['topic_tag'] ?? 'Actualidad');
    $icon = trim($parts['icon'] ?? '📰');
    $sourceName = trim($parts['source_name'] ?? 'Prensa Digital');
    $sourceUrl = trim($parts['source_url'] ?? '');
    $dateFormatted = date('d/m/Y');

    if (empty($sections) && !empty($paragraphs)) {
        $sections[] = [
            'heading'    => $subtitle,
            'paragraphs' => $paragraphs,
            'items'      => [],
            'quote'      => ''
        ];
    }

    $safeLead = htmlspecialchars($lead, ENT_QUOTES, 'UTF-8');
    $safeTopic = htmlspecialchars($topicTag, ENT_QUOTES, 'UTF-8');
    $safeSource = htmlspecialchars($sourceName, ENT_QUOTES, 'UTF-8');
    $safeIcon = htmlspecialchars($icon, ENT_QUOTES, 'UTF-8');

    $html = '<div class="mm-corp-article" style="font-family: \'Outfit\', \'Inter\', system-ui, sans-serif; color: #2d3436; line-height: 1.75;">';

    // 1. ENTRADILLA PRINCIPAL (Síntesis directa de la noticia para vista previa y apertura)
    if ($safeLead !== '') {
        $html .= '<div style="background: linear-gradient(135deg, #f2f9f5 0%, #ffffff 100%); border-left: 5px solid #2d6a4f; padding: 1.15rem 1.35rem; border-radius: 0 12px 12px 0; margin-bottom: 1.35rem; box-shadow: 0 3px 12px rgba(27,67,50,0.06);">'
               . '<p style="margin: 0; font-size: 1.06rem; font-weight: 600; color: #1b4332; line-height: 1.62;">' . $safeLead . '</p>'
               . '</div>';
    }

    // 2. CINTILLO INFORMATIVO CON TEMA Y FUENTE DE OBTENCIÓN
    $html .= '<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 1.35rem; padding-bottom: 0.7rem; border-bottom: 2px solid #e2e8e5;">'
           . '<span style="display: inline-flex; align-items: center; gap: 6px; background: #1b4332; color: #ffffff; font-size: 0.77rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 13px; border-radius: 20px;">'
           . $safeIcon . ' ' . $safeTopic
           . '</span>'
           . '<span style="font-size: 0.82rem; color: #636e72; font-weight: 600;">📰 Obtenido de: <strong style="color: #1b4332;">' . $safeSource . '</strong> · ' . $dateFormatted . '</span>'
           . '</div>';

    // 3. PUNTOS CLAVE (Solo si existen datos concretos de la noticia)
    $validKeyPoints = array_values(array_filter(array_map('trim', $keyPoints), function ($v) {
        return $v !== '';
    }));
    if (!empty($validKeyPoints)) {
        $html .= '<div style="background: #ffffff; border: 1px solid #d8e2dc; border-top: 4px solid #d4af37; border-radius: 12px; padding: 1.1rem 1.35rem; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">'
               . '<div style="font-size: 0.85rem; font-weight: 800; color: #1b4332; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.65rem; display: flex; align-items: center; gap: 8px;">'
               . '<span style="color: #d4af37; font-size: 1.05rem;">✦</span> Claves de la noticia'
               . '</div>'
               . '<ul style="margin: 0; padding-left: 1.2rem; color: #2d3436; font-size: 0.95rem;">';
        foreach ($validKeyPoints as $kpClean) {
            $html .= '<li style="margin-bottom: 0.4rem; line-height: 1.52;">' . htmlspecialchars($kpClean, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '</ul></div>';
    }

    // 4. DESARROLLO ESCUETO Y PROPIO DE LA NOTICIA
    foreach ($sections as $secIdx => $sec) {
        $secHeading = trim($sec['heading'] ?? '');
        $secParas = is_array($sec['paragraphs'] ?? null) ? $sec['paragraphs'] : [];
        $secItems = is_array($sec['items'] ?? null) ? $sec['items'] : [];
        $secQuote = trim($sec['quote'] ?? '');

        if ($secHeading !== '') {
            $html .= '<h3 style="font-size: 1.14rem; font-weight: 800; color: #1b4332; margin: ' . ($secIdx === 0 ? '1.1rem' : '1.5rem') . ' 0 0.75rem 0; padding-left: 0.7rem; border-left: 4px solid #d4af37; line-height: 1.35;">'
                   . htmlspecialchars($secHeading, ENT_QUOTES, 'UTF-8')
                   . '</h3>';
        }

        foreach ($secParas as $p) {
            $pClean = trim((string)$p);
            if ($pClean === '') continue;
            $html .= '<p style="margin: 0 0 1rem 0; font-size: 0.99rem; color: #2d3436; text-align: justify;">'
                   . htmlspecialchars($pClean, ENT_QUOTES, 'UTF-8')
                   . '</p>';
        }

        if (!empty($secItems)) {
            $html .= '<div style="background: #f8faf9; border: 1px solid #e2e8e5; border-radius: 10px; padding: 0.9rem 1.2rem; margin: 0.85rem 0 1.15rem 0;">'
                   . '<ul style="margin: 0; padding-left: 1.2rem; color: #1b4332; font-size: 0.94rem;">';
            foreach ($secItems as $it) {
                $itClean = trim((string)$it);
                if ($itClean === '') continue;
                $html .= '<li style="margin-bottom: 0.38rem; color: #2d3436; line-height: 1.5;">' . htmlspecialchars($itClean, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $html .= '</ul></div>';
        }

        if ($secQuote !== '') {
            $html .= '<blockquote style="margin: 1.1rem 0; padding: 0.9rem 1.25rem; background: #f4faf6; border-left: 4px solid #2d6a4f; border-radius: 0 10px 10px 0; font-style: italic; color: #1b4332; font-size: 0.97rem;">'
                   . '«' . htmlspecialchars(trim($secQuote, '«»""'), ENT_QUOTES, 'UTF-8') . '»'
                   . '</blockquote>';
        }
    }

    // 5. PIE DE ATRIBUCIÓN DE FUENTE (De dónde se ha obtenido la noticia, sin referencias a redacción moratalla-murcia.com)
    $sourceLinkHtml = '';
    if ($sourceUrl !== '' && preg_match('#^https?://#i', $sourceUrl) && stripos($sourceUrl, 'news.google.com') === false) {
        $safeUrl = htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8');
        $sourceLinkHtml = '<a href="' . $safeUrl . '" target="_blank" rel="noopener noreferrer" style="color: #1b4332; font-weight: 700; text-decoration: none;">Ver publicación original ↗</a>';
    }

    $html .= '<div style="margin-top: 1.4rem; padding: 0.75rem 1.1rem; background: #f1f3f2; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.82rem; color: #4b5563;">'
           . '<span>📌 Información obtenida de: <strong style="color: #1b4332;">' . $safeSource . '</strong></span>'
           . ($sourceLinkHtml !== '' ? '<span>' . $sourceLinkHtml . '</span>' : '')
           . '</div>';

    $html .= '</div>';
    return $html;
}

/**
 * Llamada de respaldo a motor IA gratuito (Pollinations OpenAI) cuando no hay clave Gemini configurada,
 * garantizando que la redacción sea siempre propia (IA), escueta y ceñida exclusivamente a la noticia.
 */
function generateConciseAiDraftViaFreeLlm($prompt) {
    $ch = curl_init('https://text.pollinations.ai/openai');
    $payload = json_encode([
        'model' => 'openai',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Eres un redactor periodístico riguroso y conciso. Reescribes noticias con palabras propias de forma escueta, clara y directa, ciñéndote exclusivamente a los hechos del texto proporcionado sin añadir temas no relacionados ni mencionar portales propios. Respondes únicamente en JSON válido.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.35,
        'response_format' => ['type' => 'json_object']
    ]);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300 && !empty($resp)) {
        $decoded = @json_decode($resp, true);
        $textOut = $decoded['choices'][0]['message']['content'] ?? '';
        if (!empty($textOut)) {
            $textOut = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($textOut));
            $jsonDraft = @json_decode($textOut, true);
            if (is_array($jsonDraft) && !empty($jsonDraft['title'])) {
                return $jsonDraft;
            }
        }
    }
    return null;
}

/**
 * Procesador local limpio y escueto (solo en caso de fallo total de red con los motores IA):
 * Ciñe el contenido estrictamente a la noticia seleccionada sin añadir rellenos sobre fiestas ni otros temas.
 */
function transformArticleLocallyToCorporateParts($titleOrig, $bodyOrig, $sourceName, $sourceUrl = '', $categories = []) {
    $titleOnlyLower = mb_strtolower($titleOrig, 'UTF-8');

    $suggestedIcon = '📰';
    $topicTag = 'Actualidad';
    $keywords = 'Moratalla';

    if (strpos($titleOnlyLower, 'ayuntamiento') !== false || strpos($titleOnlyLower, 'pleno') !== false || strpos($titleOnlyLower, 'obras') !== false || strpos($titleOnlyLower, 'presupuesto') !== false) {
        $suggestedIcon = '🏛️';
        $topicTag = 'Actualidad Municipal';
        $keywords = 'ayuntamiento';
    } elseif (strpos($titleOnlyLower, 'deporte') !== false || strpos($titleOnlyLower, 'carrera') !== false || strpos($titleOnlyLower, 'trofeo') !== false) {
        $suggestedIcon = '🏆';
        $topicTag = 'Deportes';
        $keywords = 'deportes';
    } elseif (strpos($titleOnlyLower, 'incendio') !== false || strpos($titleOnlyLower, 'emergencia') !== false || strpos($titleOnlyLower, 'lluvia') !== false || strpos($titleOnlyLower, 'tiempo') !== false || strpos($titleOnlyLower, 'guardia civil') !== false || strpos($titleOnlyLower, 'policía') !== false) {
        $suggestedIcon = '⚠️';
        $topicTag = 'Sucesos y Avisos';
        $keywords = 'moratalla';
    } elseif (strpos($titleOnlyLower, 'monte') !== false || strpos($titleOnlyLower, 'naturaleza') !== false || strpos($titleOnlyLower, 'río') !== false || strpos($titleOnlyLower, 'agua') !== false || strpos($titleOnlyLower, 'agricult') !== false || strpos($titleOnlyLower, 'campo') !== false) {
        $suggestedIcon = '🌲';
        $topicTag = 'Entorno y Medio Ambiente';
        $keywords = 'naturaleza';
    } elseif (strpos($titleOnlyLower, 'cultura') !== false || strpos($titleOnlyLower, 'concierto') !== false || strpos($titleOnlyLower, 'exposición') !== false || strpos($titleOnlyLower, 'teatro') !== false || strpos($titleOnlyLower, 'libro') !== false) {
        $suggestedIcon = '🎭';
        $topicTag = 'Cultura';
        $keywords = 'cultura';
    }

    $cleanTitle = trim($titleOrig);
    $rawBlocks = preg_split('/\n\s*\n/', trim($bodyOrig));
    $paragraphs = [];
    foreach ($rawBlocks as $rb) {
        $rb = trim(preg_replace('/^##\s+|^-\s+/u', '', trim($rb)));
        if ($rb === '') continue;
        $paragraphs[] = $rb;
    }

    // Sintetizar de forma escueta (máximo 3 párrafos principales)
    $lead = !empty($paragraphs[0]) ? $paragraphs[0] : $cleanTitle;
    $devParagraphs = count($paragraphs) > 1 ? array_slice($paragraphs, 1, 3) : [];

    $keyPoints = [];
    foreach ($devParagraphs as $dp) {
        $firstSentence = trim(preg_split('/(?<=[.!?])\s+/u', $dp)[0] ?? '');
        if ($firstSentence !== '' && mb_strlen($firstSentence) <= 180 && count($keyPoints) < 3) {
            $keyPoints[] = $firstSentence;
        }
    }

    $sections = [];
    if (!empty($devParagraphs)) {
        $sections[] = [
            'heading'    => 'Detalles de la noticia',
            'paragraphs' => $devParagraphs,
            'items'      => [],
            'quote'      => ''
        ];
    }

    $suggestedCatId = '';
    foreach ($categories as $c) {
        $cNameLower = mb_strtolower($c['name']);
        if (strpos($titleOnlyLower, $cNameLower) !== false) {
            $suggestedCatId = $c['id'];
            break;
        }
    }

    return [
        'title'            => $cleanTitle,
        'lead'             => $lead,
        'key_points'       => $keyPoints,
        'sections'         => $sections,
        'topic_tag'        => $topicTag,
        'event_date'       => '',
        'category_id'      => $suggestedCatId,
        'icon'             => $suggestedIcon,
        'image_caption'    => $cleanTitle,
        'ai_image_prompt'  => "Photorealistic editorial news photography in Moratalla, Region of Murcia, Spain, strictly representing: " . $titleOrig . ", natural daylight, realistic journalism photo, no text",
        'archive_keywords' => $keywords,
        'source_name'      => $sourceName,
        'source_url'       => $sourceUrl
    ];
}

/**
 * Genera el borrador supervisado con el diseño visual corporativo,
 * redactado por IA con redacción propia, de forma escueta y ceñida exclusivamente a la noticia seleccionada.
 */
function generateSupervisedDraftWithGemini($pdo, $articleInfo, $categories = []) {
    $apiKey = getGeminiApiKey($pdo);
    $catListText = [];
    foreach ($categories as $c) {
        $catListText[] = "ID {$c['id']}: {$c['name']}";
    }
    $catsPromptStr = !empty($catListText) ? implode(" | ", $catListText) : "Sin categorías";

    $todayIso = date('Y-m-d');
    $titleOrig = $articleInfo['title'] ?? '';
    $bodyOrig = $articleInfo['body_text'] ?? '';
    $sourceName = $articleInfo['source_name'] ?? 'Prensa Digital';
    $sourceUrl = $articleInfo['resolved_url'] ?? '';

    $prompt = <<<PROMPT
Eres un redactor periodístico profesional. Fecha actual: {$todayIso}.

Tu tarea es redactar con PALABRAS PROPIAS (redacción propia de IA, sin copiar literalmente frases del original) la siguiente noticia sobre Moratalla, de forma ESCUETA, DIRECTA, CLARA Y RIGUROSA.

REGLAS ESTRICTAS DE CONTENIDO:
1. PROHIBIDO poner referencias a "Redacción de moratalla-murcia.com", "moratalla-murcia.com" o cualquier autorreferencia a nuestra web.
2. PROHIBIDO hablar de fiestas, turismo, encierros, tambores, el castillo o cualquier otro tema que NO esté directamente en la noticia seleccionada. Cíñete 100% al asunto real de la noticia.
3. REDACCIÓN PROPIA Y ESCUETA: Reescribe la noticia con vocabulario y estructura propios, pero manteniendo un estilo escueto, conciso y al grano (entre 1 y 3 párrafos bien redactados con los datos esenciales de la noticia: qué ha pasado, quién, cuándo, dónde y cifras o datos clave). No rellenes con paja ni frases genéricas sobre el municipio.
4. Conserva con exactitud los datos reales (fechas, nombres, lugares, cifras) e indica el medio de donde se ha obtenido.

Noticia seleccionada:
- Titular original: {$titleOrig}
- Obtenido de (Fuente): {$sourceName}
- Texto capturado de la noticia:
{$bodyOrig}

Categorías disponibles en la base de datos:
{$catsPromptStr}

Devuelve EXCLUSIVAMENTE un objeto JSON válido (sin bloques markdown ```json) con esta estructura exacta:
{
  "title": "Titular periodístico con redacción propia, claro y conciso, fiel a la noticia seleccionada (máx 100 caracteres)",
  "topic_tag": "Etiqueta corta del tema real de esta noticia (ej: Actualidad Municipal, Sucesos, Infraestructuras, Medio Ambiente, Deportes, Cultura, Economía)",
  "lead": "Entradilla concisa de 2 a 3 líneas (35 a 60 palabras) con redacción propia que resuma el hecho principal de la noticia.",
  "key_points": [
    "Dato clave 1 extraído exclusivamente de esta noticia",
    "Dato clave 2 concreto de esta noticia"
  ],
  "sections": [
    {
      "heading": "Subtítulo breve y directo sobre el contenido de la noticia",
      "paragraphs": [
        "Párrafo 1 con redacción propia, escueta y directa explicando los hechos principales...",
        "Párrafo 2 conciso con los detalles o datos complementarios de la noticia (solo si el texto original aporta más información)..."
      ],
      "items": [],
      "quote": "Cita textual breve solo si aparece literalmente en la noticia original, o cadena vacía"
    }
  ],
  "event_date": "Si la noticia menciona una fecha concreta de evento pon YYYY-MM-DD, si no deja cadena vacía",
  "category_id": "ID numérico de la categoría más adecuada de la lista proporcionada, o cadena vacía",
  "icon": "Un único emoji acorde estrictamente al tema de la noticia (ej. 🏛️, 📰, ⚠️, 🌲, 🏆, 🎭, 🚧)",
  "image_caption": "Pie de foto descriptivo y conciso sobre la noticia (máx 100 caracteres)",
  "ai_image_prompt": "Detailed photorealistic editorial news photography strictly illustrating: {$titleOrig}, in Moratalla, Murcia, Spain, natural lighting, no text",
  "archive_keywords": "1 o 2 palabras clave en español estrictamente relacionadas con el tema de la noticia"
}
PROMPT;

    $lastError = '';

    // 1. Intentar con Google Gemini si hay clave configurada
    if (!empty($apiKey)) {
        $models = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'];

        foreach ($models as $model) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
            $payload = json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.35,
                    'maxOutputTokens' => 2048,
                    'responseMimeType' => 'application/json'
                ]
            ]);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300 && !empty($resp)) {
                $decoded = json_decode($resp, true);
                $textOut = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($textOut)) {
                    $textOut = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($textOut));
                    $jsonDraft = json_decode($textOut, true);
                    if (is_array($jsonDraft) && !empty($jsonDraft['title'])) {
                        $jsonDraft['source_name'] = $sourceName;
                        $jsonDraft['source_url'] = $sourceUrl;
                        $jsonDraft['content_html'] = buildMoratallaCorporateNewsHtml($jsonDraft);
                        $jsonDraft['used_gemini'] = true;
                        $jsonDraft['model'] = $model;
                        return $jsonDraft;
                    }
                }
            } else {
                $errData = @json_decode($resp, true);
                $lastError = $errData['error']['message'] ?? "HTTP $httpCode";
            }
        }
    }

    // 2. Motor IA gratuito automático (para que SIEMPRE tenga redacción propia por IA aunque no haya clave Gemini)
    $freeAiDraft = generateConciseAiDraftViaFreeLlm($prompt);
    if (is_array($freeAiDraft) && !empty($freeAiDraft['title'])) {
        $freeAiDraft['source_name'] = $sourceName;
        $freeAiDraft['source_url'] = $sourceUrl;
        $freeAiDraft['content_html'] = buildMoratallaCorporateNewsHtml($freeAiDraft);
        $freeAiDraft['used_gemini'] = true;
        $freeAiDraft['model'] = 'IA Redacción Propia';
        return $freeAiDraft;
    }

    // 3. Respaldo local escueto sin rellenos ajenos
    $localParts = transformArticleLocallyToCorporateParts($titleOrig, $bodyOrig, $sourceName, $sourceUrl, $categories);
    $localParts['content_html'] = buildMoratallaCorporateNewsHtml($localParts);
    $localParts['used_gemini'] = false;
    $localParts['gemini_note'] = empty($apiKey)
        ? 'Configura tu clave gratuita de Google Gemini para activar la reescritura con IA.'
        : ('Aviso Gemini: ' . ($lastError ?? 'Se aplicó síntesis directa.'));

    return $localParts;
}

/**
 * Opción B: Genera una imagen ilustrativa mediante IA (intenta Google Imagen/Gemini y tiene fallback gratuito instantáneo de alta calidad)
 */
function generateAiImageForNews($pdo, $prompt) {
    $prompt = trim($prompt);
    if ($prompt === '') {
        $prompt = 'Photorealistic view of Moratalla, Murcia, Spain, medieval castle on the hill, historic Mediterranean village and nature, sunny day, editorial photography';
    }

    $uploadDir = dirname(__DIR__, 2) . '/uploads/news/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $apiKey = getGeminiApiKey($pdo);

    if (!empty($apiKey)) {
        $imagenEndpoint = "https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict?key=" . urlencode($apiKey);
        $payload = json_encode([
            'instances' => [
                ['prompt' => $prompt]
            ],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => '16:9',
                'outputOptions' => ['mimeType' => 'image/jpeg']
            ]
        ]);

        $ch = curl_init($imagenEndpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($resp)) {
            $data = json_decode($resp, true);
            $b64 = $data['predictions'][0]['bytesBase64Encoded'] ?? '';
            if (!empty($b64)) {
                $rawBytes = base64_decode($b64);
                $tmpFile = $uploadDir . 'tmp_ai_' . uniqid() . '.jpg';
                if (@file_put_contents($tmpFile, $rawBytes) !== false) {
                    $filename = uniqid('news_ai_') . '.jpg';
                    $targetFile = $uploadDir . $filename;
                    if (processUploadedImage($tmpFile, $targetFile, true, 1200, 85)) {
                        @unlink($tmpFile);
                        return [
                            'success' => true,
                            'path'    => 'uploads/news/' . $filename,
                            'engine'  => 'Google Imagen 3'
                        ];
                    }
                    @unlink($tmpFile);
                }
            }
        }
    }

    // Motor generativo gratuito instantáneo (Pollinations Flux Realism)
    $seed = random_int(1000, 999999);
    $cleanPrompt = preg_replace('/\s+/', ' ', $prompt);
    $pollinationsUrl = 'https://image.pollinations.ai/prompt/' . rawurlencode($cleanPrompt) . '?width=1200&height=720&seed=' . $seed . '&nologo=true&model=flux';

    $dl = downloadAndProcessRemoteImage($pollinationsUrl, 'news_ai_');
    if (!empty($dl['success'])) {
        $dl['engine'] = 'IA Generativa (Flux)';
        return $dl;
    }

    return [
        'success' => false,
        'error'   => 'No se pudo generar la imagen con IA en este momento. Inténtalo de nuevo o usa la Opción A / C.'
    ];
}

/**
 * Opción C: Busca fotografías del archivo propio de Moratalla (page_images y news_events)
 */
function searchLocalMoratallaArchive($pdo, $searchQuery = '', $limit = 24) {
    $searchQuery = trim($searchQuery);
    $results = [];
    $seenPaths = [];

    $addArchiveItem = function ($path, $caption, $author, $origin) use (&$results, &$seenPaths) {
        $path = trim($path);
        if ($path === '' || isset($seenPaths[$path])) return;
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) return;
        $fullPath = dirname(__DIR__, 2) . '/' . ltrim($path, '/');
        if (!is_file($fullPath)) return;

        $seenPaths[$path] = true;
        $results[] = [
            'path'    => $path,
            'caption' => $caption ?: $origin,
            'author'  => $author ?: '',
            'origin'  => $origin
        ];
    };

    try {
        if ($searchQuery !== '') {
            $words = preg_split('/\s+/', $searchQuery);
            $conditions = [];
            $params = [];
            foreach ($words as $w) {
                $w = trim($w);
                if (mb_strlen($w) < 2) continue;
                $conditions[] = "(pi.caption LIKE ? OR p.title LIKE ? OR pi.image_path LIKE ?)";
                $like = '%' . $w . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            if (!empty($conditions)) {
                $sql = "SELECT pi.image_path, pi.caption, pi.author, p.title as page_title
                        FROM page_images pi
                        LEFT JOIN pages p ON pi.page_id = p.id
                        WHERE (pi.is_video = 0 OR pi.is_video IS NULL)
                          AND (" . implode(' OR ', $conditions) . ")
                        ORDER BY pi.is_cover DESC, pi.id DESC
                        LIMIT " . (int)$limit;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                while ($row = $stmt->fetch()) {
                    $addArchiveItem($row['image_path'], $row['caption'], $row['author'] ?? '', $row['page_title'] ?: 'Galería Moratalla');
                }
            }
        }

        if (count($results) < $limit) {
            $rem = $limit - count($results);
            $stmt = $pdo->query("SELECT pi.image_path, pi.caption, pi.author, p.title as page_title
                                 FROM page_images pi
                                 LEFT JOIN pages p ON pi.page_id = p.id
                                 WHERE (pi.is_video = 0 OR pi.is_video IS NULL)
                                 ORDER BY pi.is_cover DESC, pi.id DESC
                                 LIMIT " . (int)($rem + 15));
            while ($row = $stmt->fetch()) {
                $addArchiveItem($row['image_path'], $row['caption'], $row['author'] ?? '', $row['page_title'] ?: 'Galería Moratalla');
                if (count($results) >= $limit) break;
            }
        }
    } catch (Exception $e) {}

    return $results;
}
