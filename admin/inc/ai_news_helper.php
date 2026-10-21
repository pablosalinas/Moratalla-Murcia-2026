<?php
// admin/inc/ai_news_helper.php
// Motor de búsqueda de noticias de Moratalla, control de duplicados/procesadas, extracción web, redacción con Google Gemini e imágenes (Opciones A, B y C)

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
    } catch (Exception $e) {
        // Ignorar si ya existe
    }

    try {
        $pdo->query("SELECT source_url FROM news_events LIMIT 1");
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE news_events ADD COLUMN source_url VARCHAR(700) NULL AFTER icon");
        } catch (Exception $e2) {}
    }
}

/**
 * Normaliza un titular para comparación anti-duplicados (sin acentos, signos ni stopwords comunes)
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
        // Eliminar parámetros de seguimiento habituales
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
 * Calcula si dos titulares normalizados se refieren a la misma noticia (redundancia semántica/léxica)
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
 * Registra una noticia como procesada (publicada o descartada) para no repetirla
 */
function markNewsAsProcessed($pdo, $sourceUrl, $originalTitle, $sourceName = '', $status = 'published', $newsEventId = null) {
    ensureAiNewsTrackingSchema($pdo);
    $sourceUrl = trim((string)$sourceUrl);
    $originalTitle = trim((string)$originalTitle);
    if ($sourceUrl === '' && $originalTitle === '') return false;

    $normUrl = normalizeUrlForHash($sourceUrl ?: $originalTitle);
    $urlHash = hash('sha256', $normUrl);
    $titleNorm = mb_substr(normalizeNewsTitleForDedupe($originalTitle), 0, 250);

    // Comprobar si ya existe por url_hash o title_norm exacto
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
 * Cruza una lista de noticias encontradas en internet contra:
 * 1) El registro histórico `ai_processed_news` (publicadas o descartadas)
 * 2) Todas las noticias existentes en `news_events` (incluso las creadas manualmente)
 */
function annotateNewsWithProcessedStatus($pdo, array $items) {
    ensureAiNewsTrackingSchema($pdo);

    // Cargar registro de noticias procesadas por IA
    $processedRows = [];
    try {
        $stmt = $pdo->query("SELECT p.*, ne.title as published_title, ne.id as real_news_id
                             FROM ai_processed_news p
                             LEFT JOIN news_events ne ON p.news_event_id = ne.id
                             ORDER BY p.processed_at DESC");
        $processedRows = $stmt->fetchAll();
    } catch (Exception $e) {}

    // Cargar todas las noticias ya existentes en la web (news_events)
    $existingNews = [];
    try {
        $stmt2 = $pdo->query("SELECT id, title, source_url, event_date FROM news_events ORDER BY id DESC");
        while ($r = $stmt2->fetch()) {
            $r['title_norm'] = normalizeNewsTitleForDedupe($r['title']);
            $r['url_hash'] = !empty($r['source_url']) ? hash('sha256', normalizeUrlForHash($r['source_url'])) : '';
            $existingNews[] = $r;
        }
    } catch (Exception $e) {}

    foreach ($items as &$item) {
        $itemUrlHash = hash('sha256', normalizeUrlForHash($item['url'] ?: $item['title']));
        $itemTitleNorm = normalizeNewsTitleForDedupe($item['title']);

        $item['url_hash'] = $itemUrlHash;
        $item['process_status'] = 'new'; // 'new', 'published', 'discarded', 'similar'
        $item['matched_news_id'] = null;
        $item['matched_title'] = null;
        $item['processed_record_id'] = null;
        $item['processed_date'] = null;

        // 1. Verificar coincidencia en tabla `ai_processed_news`
        foreach ($processedRows as $pr) {
            $sameHash = ($pr['url_hash'] === $itemUrlHash);
            $sameTitle = areNewsTitlesRedundant($itemTitleNorm, $pr['title_norm']);
            if ($sameHash || $sameTitle) {
                $item['processed_record_id'] = (int)$pr['id'];
                $item['processed_date'] = $pr['processed_at'] ? date('d/m/Y H:i', strtotime($pr['processed_at'])) : '';
                if ($pr['status'] === 'discarded') {
                    $item['process_status'] = 'discarded';
                    $item['matched_title'] = $pr['title_original'];
                } else {
                    // Si estaba marcada como publicada, verificar si la noticia sigue existiendo o enlazarla
                    $item['process_status'] = 'published';
                    $item['matched_news_id'] = $pr['real_news_id'] ?: $pr['news_event_id'];
                    $item['matched_title'] = $pr['published_title'] ?: $pr['title_original'];
                }
                break;
            }
        }

        // 2. Si no está en `ai_processed_news`, comprobar contra `news_events` existentes en la web
        if ($item['process_status'] === 'new') {
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
    } catch (Exception $e) {
        // Ignorar si no existe
    }
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
 * y las cruza con la base de datos para marcar cuáles ya fueron publicadas o descartadas
 */
function searchMoratallaNews($pdo, $query = 'Moratalla Murcia', $maxResults = 25) {
    $query = trim($query);
    if ($query === '') {
        $query = 'Moratalla Murcia';
    }

    $items = [];
    $seenNormTitles = [];

    // 1. Fuente: Bing News RSS (incluye enlaces directos e imágenes miniatura directas)
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

                // Extraer URL real del redirect de Bing si viene como ?url=...
                $realUrl = $rawLink;
                if (preg_match('/[?&]url=([^&]+)/', $rawLink, $m)) {
                    $realUrl = urldecode($m[1]);
                }

                // Extraer fuente e imagen del namespace News de Bing
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

    // 2. Fuente: Google News RSS (gran cobertura local en medios regionales murcianos)
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

                // Limpiar " - Nombre del Medio" al final del titular de Google News
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

    // Ordenar por fecha más reciente primero
    usort($items, function ($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });

    $sliced = array_slice($items, 0, $maxResults);
    return annotateNewsWithProcessedStatus($pdo, $sliced);
}

/**
 * Si una URL es un enlace intermedio de Google News, busca la URL directa del artículo por su titular
 */
function resolveRealArticleUrl($url, $title = '', $source = '') {
    if (stripos($url, 'news.google.com') === false) {
        return $url;
    }

    if (!empty($title)) {
        $searchQuery = $title . ($source ? ' ' . $source : ' Moratalla');
        $bingUrl = 'https://www.bing.com/news/search?q=' . urlencode($searchQuery) . '&format=rss&mkt=es-ES';
        $bingXml = aiHttpGet($bingUrl, 8);
        if (!empty($bingXml)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($bingXml);
            if ($xml && isset($xml->channel->item[0])) {
                $rawLink = (string)$xml->channel->item[0]->link;
                if (preg_match('/[?&]url=([^&]+)/', $rawLink, $m)) {
                    return urldecode($m[1]);
                }
                if (!empty($rawLink) && stripos($rawLink, 'news.google.com') === false) {
                    return $rawLink;
                }
            }
        }

        $bingWebUrl = 'https://www.bing.com/search?q=' . urlencode($searchQuery) . '&format=rss';
        $bingWebXml = aiHttpGet($bingWebUrl, 8);
        if (!empty($bingWebXml)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($bingWebXml);
            if ($xml && isset($xml->channel->item[0]->link)) {
                $candidate = trim((string)$xml->channel->item[0]->link);
                if (!empty($candidate) && stripos($candidate, 'news.google.com') === false) {
                    return $candidate;
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
 * Extrae el texto principal y las imágenes originales (Opción A) de una URL de noticia
 */
function extractArticleContentAndImages($url, $fallbackTitle = '', $fallbackSnippet = '', $rssImage = '', $sourceName = '') {
    $resolvedUrl = resolveRealArticleUrl($url, $fallbackTitle, $sourceName);
    $finalUrl = $resolvedUrl;
    $html = '';

    if (!empty($resolvedUrl) && stripos($resolvedUrl, 'news.google.com') === false) {
        $html = aiHttpGet($resolvedUrl, 12, $finalUrl);
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
    $extractedText = '';
    $detectedSource = $sourceName;

    if (!empty($html)) {
        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1,Windows-1252,UTF-8');
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        $metaNodes = $xpath->query('//meta');
        $metaDesc = '';
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

        $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scriptNodes as $script) {
            $jsonRaw = trim($script->textContent);
            $data = @json_decode($jsonRaw, true);
            if (is_array($data)) {
                $walkJson = function ($node) use (&$walkJson, &$addCandidateImg, &$extractedText) {
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
                    if (!empty($node['articleBody']) && is_string($node['articleBody']) && mb_strlen($node['articleBody']) > mb_strlen($extractedText)) {
                        $extractedText = trim($node['articleBody']);
                    }
                    foreach ($node as $k => $v) {
                        if (is_array($v)) $walkJson($v);
                    }
                };
                $walkJson($data);
            }
        }

        $imgNodes = $xpath->query('//article//img | //main//img | //figure//img');
        if ($imgNodes->length === 0) {
            $imgNodes = $xpath->query('//img');
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
            if (count($images) >= 8) break;
        }

        if (mb_strlen($extractedText) < 200) {
            foreach ($xpath->query('//script | //style | //nav | //footer | //header | //aside | //form') as $rem) {
                $rem->parentNode->removeChild($rem);
            }
            $pNodes = $xpath->query('//article//p | //main//p');
            if ($pNodes->length < 2) {
                $pNodes = $xpath->query('//p');
            }
            $paragraphs = [];
            foreach ($pNodes as $p) {
                $txt = trim(preg_replace('/\s+/u', ' ', $p->textContent));
                if (mb_strlen($txt) >= 45 && !preg_match('/(cookies|política de privacidad|todos los derechos reservados|suscríbete|iniciar sesión)/iu', $txt)) {
                    $paragraphs[] = $txt;
                }
                if (count($paragraphs) >= 18) break;
            }
            if (!empty($paragraphs)) {
                $extractedText = implode("\n\n", $paragraphs);
            } elseif (!empty($metaDesc)) {
                $extractedText = $metaDesc;
            }
        }
    }

    if (empty($extractedText)) {
        $extractedText = $fallbackSnippet ?: $fallbackTitle;
    }

    if (empty($detectedSource)) {
        $host = parse_url($finalUrl, PHP_URL_HOST) ?: '';
        $detectedSource = preg_replace('/^www\./i', '', $host) ?: 'Prensa Regional';
    }

    if (empty($images) && !empty($extractedTitle)) {
        $bingXml = aiHttpGet('https://www.bing.com/news/search?q=' . urlencode($extractedTitle . ' Moratalla') . '&format=rss&mkt=es-ES', 8);
        if (!empty($bingXml)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($bingXml);
            if ($xml && isset($xml->channel->item)) {
                $ns = $xml->getNamespaces(true);
                foreach ($xml->channel->item as $item) {
                    if (isset($ns['News'])) {
                        $n = $item->children($ns['News']);
                        if (isset($n->Image)) {
                            $addCandidateImg((string)$n->Image, 'Imagen localizada en prensa');
                        }
                    }
                }
            }
        }
    }

    return [
        'resolved_url'    => $finalUrl,
        'title'           => $extractedTitle,
        'body_text'       => mb_substr($extractedText, 0, 7000),
        'source_name'     => $detectedSource,
        'original_images' => array_slice($images, 0, 8)
    ];
}

/**
 * Descarga una imagen remota (Opción A o B), la procesa con marca de agua de moratalla-murcia.com y la guarda en uploads/news/
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
 * Genera el borrador de la noticia utilizando Google Gemini API (con fallback heurístico inteligente si no hay clave configurada)
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
    $sourceName = $articleInfo['source_name'] ?? 'Prensa Regional';
    $sourceUrl = $articleInfo['resolved_url'] ?? '';

    if (!empty($apiKey)) {
        $prompt = <<<PROMPT
Eres el redactor jefe del portal web turístico y cultural "moratalla-murcia.com" (Moratalla, Región de Murcia, España).
Fecha actual: {$todayIso}.

A partir de la siguiente información de una noticia localizada en internet sobre Moratalla:
- Titular original: {$titleOrig}
- Medio / Fuente: {$sourceName}
- Enlace original: {$sourceUrl}
- Contenido extraído:
{$bodyOrig}

Categorías disponibles en nuestra web:
{$catsPromptStr}

Redacta una noticia propia, veraz, atractiva y bien estructurada en español para los vecinos y visitantes de Moratalla, sin copiar literalmente frase por frase, manteniendo todos los datos reales (fechas, lugares, protagonistas, cifras).

Devuelve EXCLUSIVAMENTE un objeto JSON válido (sin bloques markdown ```json) con las siguientes claves exactas:
{
  "title": "Titular atractivo, claro y periodístico sobre Moratalla (máximo 110 caracteres)",
  "content_html": "Contenido completo en HTML limpio usando <p>, <h3>, <ul>, <li> y <strong>. Incluye un párrafo inicial de entradilla en negrita, desarrollo en 2 o 3 párrafos bien redactados y al final una línea discreta: <p><small><em>Fuente informativa: {$sourceName}</em></small></p>",
  "event_date": "Si la noticia anuncia un evento con fecha concreta pon YYYY-MM-DD, si es una noticia general deja cadena vacía",
  "category_id": "ID numérico de la categoría más adecuada de la lista proporcionada, o cadena vacía si ninguna encaja",
  "icon": "Un único emoji representativo de la noticia (ej. 🏰, 🥁, 🌲, 🎭, 🏛️, ⚽, 🍽️, 🎉, 📰)",
  "image_caption": "Pie de foto descriptivo para la imagen principal (máximo 120 caracteres)",
  "ai_image_prompt": "Descripción visual detallada en inglés para generar una fotografía realista y luminosa ambientada en Moratalla (Murcia, Spain, Mediterranean mountain town, castle, nature or culture) relacionada con esta noticia, sin texto sobreimpreso",
  "archive_keywords": "2 o 3 palabras clave en español separadas por espacio para buscar fotos en nuestro archivo de Moratalla (ej: castillo, tambor, alharabe, ayuntamiento, naturaleza, fiestas)"
}
PROMPT;

        $models = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'];
        $lastError = '';

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
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json'
                ]
            ]);

            $ch = curl_init($endpoint);
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
                $decoded = json_decode($resp, true);
                $textOut = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($textOut)) {
                    $textOut = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($textOut));
                    $jsonDraft = json_decode($textOut, true);
                    if (is_array($jsonDraft) && !empty($jsonDraft['title'])) {
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

    // Fallback inteligente si aún no se ha configurado la API Key de Gemini o si hubo límite de cuota
    $paragraphs = preg_split('/\n\s*\n/', trim($bodyOrig));
    $htmlParts = [];
    foreach ($paragraphs as $idx => $p) {
        $p = trim($p);
        if ($p === '') continue;
        $safeP = htmlspecialchars($p, ENT_QUOTES, 'UTF-8');
        if ($idx === 0) {
            $htmlParts[] = "<p><strong>{$safeP}</strong></p>";
        } else {
            $htmlParts[] = "<p>{$safeP}</p>";
        }
        if (count($htmlParts) >= 6) break;
    }
    if (empty($htmlParts)) {
        $htmlParts[] = "<p>" . htmlspecialchars($titleOrig, ENT_QUOTES, 'UTF-8') . "</p>";
    }
    if (!empty($sourceName)) {
        $htmlParts[] = "<p><small><em>Fuente informativa: " . htmlspecialchars($sourceName, ENT_QUOTES, 'UTF-8') . "</em></small></p>";
    }

    $combinedLower = mb_strtolower($titleOrig . ' ' . $bodyOrig);
    $suggestedIcon = '📰';
    $keywords = 'Moratalla';
    if (strpos($combinedLower, 'tambor') !== false || strpos($combinedLower, 'semana santa') !== false) {
        $suggestedIcon = '🥁';
        $keywords = 'tambor semana santa';
    } elseif (strpos($combinedLower, 'castillo') !== false || strpos($combinedLower, 'patrimonio') !== false || strpos($combinedLower, 'historia') !== false) {
        $suggestedIcon = '🏰';
        $keywords = 'castillo fortaleza';
    } elseif (strpos($combinedLower, 'encierro') !== false || strpos($combinedLower, 'cristo del rayo') !== false || strpos($combinedLower, 'fiesta') !== false) {
        $suggestedIcon = '🎉';
        $keywords = 'fiestas encierros';
    } elseif (strpos($combinedLower, 'alhárabe') !== false || strpos($combinedLower, 'alharabe') !== false || strpos($combinedLower, 'monte') !== false || strpos($combinedLower, 'senderismo') !== false || strpos($combinedLower, 'naturaleza') !== false) {
        $suggestedIcon = '🌲';
        $keywords = 'naturaleza rio';
    } elseif (strpos($combinedLower, 'restaurante') !== false || strpos($combinedLower, 'gastronom') !== false || strpos($combinedLower, 'mazapán') !== false) {
        $suggestedIcon = '🍽️';
        $keywords = 'gastronomia';
    } elseif (strpos($combinedLower, 'deporte') !== false || strpos($combinedLower, 'carrera') !== false || strpos($combinedLower, 'fútbol') !== false) {
        $suggestedIcon = '🏆';
        $keywords = 'deportes';
    }

    $suggestedCatId = '';
    foreach ($categories as $c) {
        $cNameLower = mb_strtolower($c['name']);
        if (strpos($combinedLower, $cNameLower) !== false) {
            $suggestedCatId = $c['id'];
            break;
        }
    }

    return [
        'title'            => $titleOrig,
        'content_html'     => implode("\n", $htmlParts),
        'event_date'       => '',
        'category_id'      => $suggestedCatId,
        'icon'             => $suggestedIcon,
        'image_caption'    => $titleOrig,
        'ai_image_prompt'  => "Photorealistic editorial photography of Moratalla, Region of Murcia, Spain, picturesque historic mountain town with medieval castle and Mediterranean pine forest, related to: " . $titleOrig . ", warm natural daylight, high detail, 8k, no text",
        'archive_keywords' => $keywords,
        'used_gemini'      => false,
        'gemini_note'      => empty($apiKey)
            ? 'Borrador estructurado en modo local. Configura tu clave gratuita de Google Gemini para redacción avanzada con IA.'
            : ('Aviso Gemini: ' . ($lastError ?? 'No disponible temporalmente, se usó extracción estructurada.'))
    ];
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
    } catch (Exception $e) {
        // Ignorar errores de consulta si alguna tabla varía
    }

    return $results;
}
