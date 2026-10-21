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

    foreach ($items as &$item) {
        $itemUrlHash = hash('sha256', normalizeUrlForHash($item['url'] ?: $item['title']));
        $itemTitleNorm = normalizeNewsTitleForDedupe($item['title']);

        $item['url_hash'] = $itemUrlHash;
        $item['process_status'] = 'new';
        $item['matched_news_id'] = null;
        $item['matched_title'] = null;
        $item['processed_record_id'] = null;
        $item['processed_date'] = null;

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
                    $item['process_status'] = 'published';
                    $item['matched_news_id'] = $pr['real_news_id'] ?: $pr['news_event_id'];
                    $item['matched_title'] = $pr['published_title'] ?: $pr['title_original'];
                }
                break;
            }
        }

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
 */
function resolveRealArticleUrl($url, $title = '', $source = '') {
    if (stripos($url, 'news.google.com') === false) {
        return $url;
    }

    // 1. Intentar decodificación directa del token base64 si es formato CBMi...
    if (preg_match('#/articles/([A-Za-z0-9_-]+)#', $url, $m)) {
        $b64 = strtr($m[1], '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) $b64 .= str_repeat('=', 4 - $pad);
        $decoded = @base64_decode($b64, true);
        if ($decoded && preg_match('#(https?://[^\x00-\x1f\x7f"\'<>\s]+)#i', $decoded, $uMatch)) {
            $cand = $uMatch[1];
            if (stripos($cand, 'google.com') === false) {
                return $cand;
            }
        }
    }

    // 2. Buscar el titular exacto en Bing News RSS y Bing Web RSS para obtener la URL directa del periódico
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
 * Extrae íntegramente el texto, subtítulos, listas, declaraciones e imágenes originales (Opción A) de una URL de noticia
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

        // 2. JSON-LD (capturar articleBody completo e imágenes)
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
            if (count($images) >= 10) break;
        }

        // 4. Extraer todos los bloques de contenido del DOM (párrafos, subtítulos h2/h3, citas y listas) sin recortar
        foreach ($xpath->query('//script | //style | //nav | //footer | //header | //aside | //form | //noscript') as $rem) {
            if ($rem->parentNode) {
                $rem->parentNode->removeChild($rem);
            }
        }

        $blockNodes = $xpath->query('//article//*[self::p or self::h2 or self::h3 or self::blockquote or self::li] | //main//*[self::p or self::h2 or self::h3 or self::blockquote or self::li]');
        if ($blockNodes->length < 3) {
            $blockNodes = $xpath->query('//p | //h2 | //h3 | //blockquote');
        }

        $seenBlockTexts = [];
        foreach ($blockNodes as $node) {
            $tag = strtolower($node->nodeName);
            $txt = trim(preg_replace('/\s+/u', ' ', $node->textContent));
            if ($txt === '' || isset($seenBlockTexts[$txt])) continue;

            // Ignorar avisos de cookies, suscripciones o pie legal
            if (preg_match('/(cookies|política de privacidad|todos los derechos reservados|suscríbete|iniciar sesión|regístrate gratis|boletín informativo|compartir en facebook)/iu', $txt)) {
                continue;
            }

            if ($tag === 'h2' || $tag === 'h3') {
                if (mb_strlen($txt) >= 8 && mb_strlen($txt) <= 140) {
                    $seenBlockTexts[$txt] = true;
                    $domExtractedBlocks[] = "## " . $txt;
                }
            } elseif ($tag === 'li') {
                if (mb_strlen($txt) >= 25 && mb_strlen($txt) <= 350) {
                    $seenBlockTexts[$txt] = true;
                    $domExtractedBlocks[] = "- " . $txt;
                }
            } elseif ($tag === 'blockquote') {
                if (mb_strlen($txt) >= 30) {
                    $seenBlockTexts[$txt] = true;
                    $domExtractedBlocks[] = "«" . trim($txt, '«»""') . "»";
                }
            } else {
                if (mb_strlen($txt) >= 38) {
                    $seenBlockTexts[$txt] = true;
                    $domExtractedBlocks[] = $txt;
                }
            }
            if (count($domExtractedBlocks) >= 65) break;
        }
    }

    // Combinar o elegir la versión más completa entre DOM estructurado y JSON-LD articleBody
    $domText = implode("\n\n", $domExtractedBlocks);
    if (mb_strlen($domText) >= mb_strlen($jsonLdArticleBody) * 0.75 && mb_strlen($domText) > 250) {
        $extractedText = $domText;
    } elseif (!empty($jsonLdArticleBody)) {
        $extractedText = $jsonLdArticleBody;
    } else {
        $extractedText = $domText ?: $metaDesc;
    }

    if (!empty($metaDesc) && mb_stripos($extractedText, mb_substr($metaDesc, 0, 40)) === false) {
        $extractedText = $metaDesc . "\n\n" . $extractedText;
    }

    // Si el texto extraído es corto (por ejemplo, si el medio original tiene muro o redirección cerrada),
    // recopilar contexto adicional buscando el tema en Bing News y Bing Web RSS
    if ((mb_strlen($extractedText) < 400 || empty($images)) && !empty($extractedTitle)) {
        $bingXml = aiHttpGet('https://www.bing.com/news/search?q=' . urlencode($extractedTitle . ' Moratalla') . '&format=rss&mkt=es-ES', 8);
        if (!empty($bingXml)) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($bingXml);
            if ($xml && isset($xml->channel->item)) {
                $ns = $xml->getNamespaces(true);
                $extraSnippets = [];
                foreach ($xml->channel->item as $item) {
                    $d = trim(strip_tags((string)$item->description));
                    if (mb_strlen($d) > 40 && !in_array($d, $extraSnippets, true)) {
                        $extraSnippets[] = $d;
                    }
                    if (isset($ns['News'])) {
                        $n = $item->children($ns['News']);
                        if (isset($n->Image)) {
                            $addCandidateImg((string)$n->Image, 'Imagen localizada en prensa');
                        }
                    }
                    if (count($extraSnippets) >= 5) break;
                }
                if (mb_strlen($extractedText) < 400 && !empty($extraSnippets)) {
                    $extractedText = trim($extractedText . "\n\n" . implode("\n\n", $extraSnippets));
                }
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

    return [
        'resolved_url'    => $finalUrl,
        'title'           => $extractedTitle,
        'body_text'       => mb_substr(trim($extractedText), 0, 25000),
        'source_name'     => $detectedSource,
        'original_images' => array_slice($images, 0, 10)
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
 * Construye la maquetación HTML con el Estilo Corporativo Oficial de moratalla-murcia.com
 * Soporta extensión dinámica: tantas secciones, subtítulos, párrafos, listas o citas como requiera la noticia original.
 */
function buildMoratallaCorporateNewsHtml(array $parts) {
    $lead = trim($parts['lead'] ?? '');
    $keyPoints = is_array($parts['key_points'] ?? null) ? $parts['key_points'] : [];
    $sections = is_array($parts['sections'] ?? null) ? $parts['sections'] : [];
    $paragraphs = is_array($parts['body_paragraphs'] ?? null) ? $parts['body_paragraphs'] : [];
    $subtitle = trim($parts['subtitle'] ?? 'Desarrollo de la información');
    $highlight = trim($parts['local_highlight'] ?? '');
    $topicTag = trim($parts['topic_tag'] ?? 'Actualidad Moratalla');
    $icon = trim($parts['icon'] ?? '📰');
    $sourceName = trim($parts['source_name'] ?? 'Prensa Regional');
    $dateFormatted = date('d/m/Y');

    // Si solo vinieron `body_paragraphs` sin `sections`, convertirlos en una sección estructurada
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

    $html = '<div class="mm-corp-article" style="font-family: \'Outfit\', \'Inter\', system-ui, sans-serif; color: #2d3436; line-height: 1.78;">';

    // 1. ENTRADILLA EJECUTIVA (Primera frase limpia para el extracto de portada en index.php)
    if ($safeLead !== '') {
        $html .= '<div style="background: linear-gradient(135deg, #f2f9f5 0%, #ffffff 100%); border-left: 5px solid #2d6a4f; padding: 1.2rem 1.4rem; border-radius: 0 12px 12px 0; margin-bottom: 1.5rem; box-shadow: 0 3px 12px rgba(27,67,50,0.06);">'
               . '<p style="margin: 0; font-size: 1.08rem; font-weight: 600; color: #1b4332; line-height: 1.65;">' . $safeLead . '</p>'
               . '</div>';
    }

    // 2. CINTILLO CORPORATIVO DE IDENTIDAD
    $html .= '<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #e2e8e5;">'
           . '<span style="display: inline-flex; align-items: center; gap: 6px; background: #1b4332; color: #ffffff; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; padding: 5px 14px; border-radius: 20px;">'
           . $safeIcon . ' ' . $safeTopic
           . '</span>'
           . '<span style="font-size: 0.82rem; color: #636e72; font-weight: 600;">📍 Moratalla, Región de Murcia · ' . $dateFormatted . '</span>'
           . '</div>';

    // 3. FICHA DE CLAVES DE LA NOTICIA
    if (!empty($keyPoints)) {
        $html .= '<div style="background: #ffffff; border: 1px solid #d8e2dc; border-top: 4px solid #d4af37; border-radius: 12px; padding: 1.2rem 1.4rem; margin-bottom: 1.8rem; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">'
               . '<div style="font-size: 0.88rem; font-weight: 800; color: #1b4332; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 8px;">'
               . '<span style="color: #d4af37; font-size: 1.1rem;">✦</span> Puntos clave de la información'
               . '</div>'
               . '<ul style="margin: 0; padding-left: 1.2rem; color: #2d3436; font-size: 0.96rem;">';
        foreach ($keyPoints as $kp) {
            $kpClean = trim((string)$kp);
            if ($kpClean === '') continue;
            $html .= '<li style="margin-bottom: 0.45rem; line-height: 1.55;">' . htmlspecialchars($kpClean, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $html .= '</ul></div>';
    }

    // 4. SECCIONES DINÁMICAS DE DESARROLLO (Tantas como requiera la extensión de la noticia)
    foreach ($sections as $secIdx => $sec) {
        $secHeading = trim($sec['heading'] ?? '');
        $secParas = is_array($sec['paragraphs'] ?? null) ? $sec['paragraphs'] : [];
        $secItems = is_array($sec['items'] ?? null) ? $sec['items'] : [];
        $secQuote = trim($sec['quote'] ?? '');

        if ($secHeading !== '') {
            $html .= '<h3 style="font-size: 1.2rem; font-weight: 800; color: #1b4332; margin: ' . ($secIdx === 0 ? '1.4rem' : '1.9rem') . ' 0 0.9rem 0; padding-left: 0.7rem; border-left: 4px solid #d4af37; line-height: 1.35;">'
                   . htmlspecialchars($secHeading, ENT_QUOTES, 'UTF-8')
                   . '</h3>';
        }

        foreach ($secParas as $p) {
            $pClean = trim((string)$p);
            if ($pClean === '') continue;
            $html .= '<p style="margin: 0 0 1.1rem 0; font-size: 1rem; color: #2d3436; text-align: justify;">'
                   . htmlspecialchars($pClean, ENT_QUOTES, 'UTF-8')
                   . '</p>';
        }

        if (!empty($secItems)) {
            $html .= '<div style="background: #f8faf9; border: 1px solid #e2e8e5; border-radius: 10px; padding: 1rem 1.3rem; margin: 1rem 0 1.3rem 0;">'
                   . '<ul style="margin: 0; padding-left: 1.2rem; color: #1b4332; font-size: 0.96rem;">';
            foreach ($secItems as $it) {
                $itClean = trim((string)$it);
                if ($itClean === '') continue;
                $html .= '<li style="margin-bottom: 0.45rem; color: #2d3436; line-height: 1.55;">' . htmlspecialchars($itClean, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $html .= '</ul></div>';
        }

        if ($secQuote !== '') {
            $html .= '<blockquote style="margin: 1.3rem 0; padding: 1rem 1.35rem; background: #f4faf6; border-left: 4px solid #2d6a4f; border-radius: 0 10px 10px 0; font-style: italic; color: #1b4332; font-size: 1rem;">'
                   . '«' . htmlspecialchars(trim($secQuote, '«»""'), ENT_QUOTES, 'UTF-8') . '»'
                   . '</blockquote>';
        }
    }

    // 5. BLOQUE DESTACADO EN CLAVE LOCAL ("Interés local · Moratalla")
    if ($highlight !== '') {
        $safeHighlight = htmlspecialchars($highlight, ENT_QUOTES, 'UTF-8');
        $html .= '<div style="background: linear-gradient(135deg, #fffdf5 0%, #fef9e7 100%); border: 1px solid #f1df96; border-radius: 12px; padding: 1.05rem 1.3rem; margin: 1.6rem 0; display: flex; gap: 12px; align-items: flex-start;">'
               . '<div style="font-size: 1.4rem; line-height: 1;">🏰</div>'
               . '<div style="flex: 1;">'
               . '<div style="font-size: 0.78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #9a7b1c; margin-bottom: 0.25rem;">En clave local · Moratalla</div>'
               . '<p style="margin: 0; font-size: 0.95rem; color: #3d3522; font-weight: 500; line-height: 1.55;">' . $safeHighlight . '</p>'
               . '</div>'
               . '</div>';
    }

    // 6. SELLO CORPORATIVO DE PIE DE NOTICIA
    $html .= '<div style="margin-top: 1.7rem; padding: 0.8rem 1.1rem; background: #f1f3f2; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.8rem; color: #636e72;">'
           . '<span><strong style="color: #1b4332;">Redacción Digital · moratalla-murcia.com</strong></span>'
           . '<span><em>Información elaborada a partir de datos de: ' . $safeSource . '</em></span>'
           . '</div>';

    $html .= '</div>';
    return $html;
}

/**
 * Procesador editorial inteligente en PHP cuando no hay clave de Gemini configurada:
 * Conserva toda la extensión e información del artículo original dividida en secciones corporativas
 */
function transformArticleLocallyToCorporateParts($titleOrig, $bodyOrig, $sourceName, $categories = []) {
    $combinedLower = mb_strtolower($titleOrig . ' ' . $bodyOrig, 'UTF-8');

    $suggestedIcon = '📰';
    $topicTag = 'Actualidad Municipal';
    $keywords = 'Moratalla';
    $localHighlight = 'Esta información forma parte de la actualidad de Moratalla y su comarca en el Noroeste de la Región de Murcia.';

    if (strpos($combinedLower, 'tambor') !== false || strpos($combinedLower, 'semana santa') !== false || strpos($combinedLower, 'nazareno') !== false) {
        $suggestedIcon = '🥁';
        $topicTag = 'Tradiciones y Semana Santa';
        $keywords = 'tambor semana santa';
        $localHighlight = 'Las tradiciones del tambor y la Semana Santa constituyen una de las señas de identidad cultural más reconocidas de Moratalla, declaradas de Interés Turístico.';
    } elseif (strpos($combinedLower, 'castillo') !== false || strpos($combinedLower, 'patrimonio') !== false || strpos($combinedLower, 'arqueolog') !== false || strpos($combinedLower, 'historia') !== false) {
        $suggestedIcon = '🏰';
        $topicTag = 'Patrimonio e Historia';
        $keywords = 'castillo fortaleza';
        $localHighlight = 'La puesta en valor del legado histórico y monumental refuerza el atractivo cultural y turístico de Moratalla.';
    } elseif (strpos($combinedLower, 'encierro') !== false || strpos($combinedLower, 'cristo del rayo') !== false || strpos($combinedLower, 'fiesta') !== false || strpos($combinedLower, 'feria') !== false) {
        $suggestedIcon = '🎉';
        $topicTag = 'Fiestas y Eventos';
        $keywords = 'fiestas encierros';
        $localHighlight = 'Las celebraciones y festejos de Moratalla congregan cada año a numerosos vecinos y visitantes en torno a su tradición centenaria.';
    } elseif (strpos($combinedLower, 'alhárabe') !== false || strpos($combinedLower, 'alharabe') !== false || strpos($combinedLower, 'monte') !== false || strpos($combinedLower, 'senderismo') !== false || strpos($combinedLower, 'naturaleza') !== false || strpos($combinedLower, 'benizar') !== false || strpos($combinedLower, 'revolcadores') !== false) {
        $suggestedIcon = '🌲';
        $topicTag = 'Naturaleza y Entorno Rural';
        $keywords = 'naturaleza rio';
        $localHighlight = 'El entorno natural de Moratalla y sus pedanías destaca como uno de los pulmones verdes y turísticos más valiosos de la Región de Murcia.';
    } elseif (strpos($combinedLower, 'restaurante') !== false || strpos($combinedLower, 'gastronom') !== false || strpos($combinedLower, 'mazapán') !== false || strpos($combinedLower, 'hosteler') !== false) {
        $suggestedIcon = '🍽️';
        $topicTag = 'Gastronomía y Comercio Local';
        $keywords = 'gastronomia';
        $localHighlight = 'La gastronomía tradicional y la hostelería local siguen impulsando el dinamismo económico y turístico de Moratalla.';
    } elseif (strpos($combinedLower, 'deporte') !== false || strpos($combinedLower, 'carrera') !== false || strpos($combinedLower, 'fútbol') !== false || strpos($combinedLower, 'trofeo') !== false) {
        $suggestedIcon = '🏆';
        $topicTag = 'Deportes en Moratalla';
        $keywords = 'deportes';
        $localHighlight = 'El fomento de la actividad deportiva en el municipio promueve la participación ciudadana y proyecta el nombre de Moratalla en toda la Región.';
    } elseif (strpos($combinedLower, 'ayuntamiento') !== false || strpos($combinedLower, 'obras') !== false || strpos($combinedLower, 'inversión') !== false || strpos($combinedLower, 'presupuesto') !== false || strpos($combinedLower, 'servicio') !== false) {
        $suggestedIcon = '🏛️';
        $topicTag = 'Gestión y Servicios Municipales';
        $keywords = 'ayuntamiento calle';
        $localHighlight = 'Las actuaciones e iniciativas de ámbito municipal repercuten directamente en la mejora de las infraestructuras y servicios para los vecinos de Moratalla.';
    }

    $cleanTitle = trim($titleOrig);
    $newTitle = $cleanTitle;
    if (mb_stripos($cleanTitle, 'Moratalla') === false) {
        $newTitle = 'Moratalla: ' . mb_strtolower(mb_substr($cleanTitle, 0, 1)) . mb_substr($cleanTitle, 1);
    }

    // Procesar TODOS los bloques capturados para mantener toda la extensión de la noticia
    $rawBlocks = preg_split('/\n\s*\n/', trim($bodyOrig));
    $paragraphs = [];
    $bulletItems = [];
    $quotes = [];
    $customHeadings = [];

    foreach ($rawBlocks as $rb) {
        $rb = trim($rb);
        if ($rb === '') continue;
        if (strpos($rb, '## ') === 0) {
            $customHeadings[] = trim(substr($rb, 3));
        } elseif (strpos($rb, '- ') === 0) {
            $bulletItems[] = trim(substr($rb, 2));
        } elseif (mb_strpos($rb, '«') === 0) {
            $quotes[] = trim($rb, '«»""');
        } else {
            $paragraphs[] = $rb;
        }
    }

    // Construir entradilla a partir del primer párrafo
    if (!empty($paragraphs[0])) {
        $lead = $paragraphs[0];
    } else {
        $lead = "El municipio de Moratalla centra la actualidad informativa en torno a " . mb_strtolower($cleanTitle) . ".";
    }

    // Extraer puntos clave representativos
    $keyPoints = [];
    $keyPoints[] = "Temática: " . $topicTag . " en Moratalla.";
    if (isset($paragraphs[1])) {
        $firstSentence = preg_split('/(?<=[.!?])\s+/u', $paragraphs[1])[0] ?? $paragraphs[1];
        $keyPoints[] = $firstSentence;
    }
    if (isset($paragraphs[2])) {
        $secondSentence = preg_split('/(?<=[.!?])\s+/u', $paragraphs[2])[0] ?? $paragraphs[2];
        $keyPoints[] = $secondSentence;
    }

    // Agrupar todos los párrafos restantes en secciones temáticas proporcionales a la longitud del artículo
    $devParagraphs = count($paragraphs) > 1 ? array_slice($paragraphs, 1) : $paragraphs;
    $sections = [];
    $defaultHeadings = [
        'Desarrollo y detalles de la noticia',
        'Contexto y actuaciones en el municipio',
        'Datos complementarios y alcance local',
        'Información adicional para vecinos y visitantes'
    ];

    $chunks = array_chunk($devParagraphs, 3);
    if (empty($chunks)) {
        $chunks = [["De acuerdo con la información difundida por " . ($sourceName ?: 'medios regionales') . ", este acontecimiento forma parte de la agenda informativa de Moratalla dentro del ámbito de " . mb_strtolower($topicTag) . "."]];
    }

    foreach ($chunks as $cIdx => $chunkParas) {
        $heading = $customHeadings[$cIdx] ?? ($defaultHeadings[$cIdx] ?? ('Ampliación informativa (' . ($cIdx + 1) . ')'));
        $sec = [
            'heading'    => $heading,
            'paragraphs' => $chunkParas,
            'items'      => ($cIdx === 0 && !empty($bulletItems)) ? array_slice($bulletItems, 0, 8) : [],
            'quote'      => $quotes[$cIdx] ?? ''
        ];
        $sections[] = $sec;
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
        'title'            => $newTitle,
        'lead'             => $lead,
        'key_points'       => array_slice($keyPoints, 0, 4),
        'sections'         => $sections,
        'local_highlight'  => $localHighlight,
        'topic_tag'        => $topicTag,
        'event_date'       => '',
        'category_id'      => $suggestedCatId,
        'icon'             => $suggestedIcon,
        'image_caption'    => $newTitle . ' · Moratalla',
        'ai_image_prompt'  => "Photorealistic editorial photography of Moratalla, Region of Murcia, Spain, picturesque historic mountain town with medieval castle and Mediterranean pine forest, related to: " . $titleOrig . ", warm natural daylight, high detail, 8k, no text",
        'archive_keywords' => $keywords,
        'source_name'      => $sourceName
    ];
}

/**
 * Genera el borrador supervisado con estilo corporativo de moratalla-murcia.com usando Google Gemini
 * adaptando la longitud del artículo a toda la extensión e información de la noticia capturada
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
    $charCount = mb_strlen($bodyOrig);

    if (!empty($apiKey)) {
        $prompt = <<<PROMPT
Eres el director editorial del portal web oficial y turístico "moratalla-murcia.com" (Moratalla, Región de Murcia, España).
Fecha actual: {$todayIso}.

Tu objetivo es tomar TODA la información de la siguiente noticia localizada en internet y REESCRIBIRLA COMPLETAMENTE con nuestro estilo corporativo propio: cercano, riguroso, elegante, enfocado en el vecino y el visitante de Moratalla, cambiando la estructura y redacción respecto al original pero manteniendo el 100% de la información, los datos, fechas, horarios, cifras, nombres propios, lugares y declaraciones.

REGLA CRÍTICA DE EXTENSIÓN Y PROFUNDIDAD:
- La noticia generada debe ser TAN EXTENSA Y COMPLETA COMO LO SEA LA INFORMACIÓN ORIGINAL CAPTURADA ({$charCount} caracteres extraídos).
- NO resumas ni omitas detalles relevantes. Si la noticia original es amplia, incluye múltiples secciones (`sections`), cada una con su propio subtítulo (`heading`), varios párrafos extensos y detallados (`paragraphs`), listas de datos/actos/inversiones (`items`) si las hay, y citas o declaraciones destacadas (`quote`) si aparecen en el texto.
- Si la noticia original es breve, adapta la extensión proporcionalmente sin inventar datos falsos, pero contextualizando bien su relevancia en Moratalla.

Noticia de origen:
- Titular original: {$titleOrig}
- Medio original: {$sourceName}
- Enlace: {$sourceUrl}
- Contenido completo extraído:
{$bodyOrig}

Categorías disponibles en nuestra base de datos:
{$catsPromptStr}

Devuelve EXCLUSIVAMENTE un objeto JSON válido (sin bloques markdown ```json) con esta estructura exacta para nuestra plantilla corporativa:
{
  "title": "Nuevo titular atractivo y periodístico con estilo propio de moratalla-murcia.com (diferente al literal pero fiel al hecho, máx 105 caracteres)",
  "topic_tag": "Etiqueta temática corta (ej: Patrimonio e Historia, Actualidad Municipal, Naturaleza y Entorno, Cultura y Tradiciones, Deportes, Turismo)",
  "lead": "Entradilla ejecutiva de 2 a 4 líneas (45 a 75 palabras) que sintetice el hecho principal con redacción propia y atractiva. Debe poder leerse como resumen en la portada.",
  "key_points": [
    "Punto clave 1 con datos concretos",
    "Punto clave 2 con lugares, fechas, cifras o protagonistas",
    "Punto clave 3 con objetivos, programación o alcance (añade 4 o 5 puntos si la noticia es extensa)"
  ],
  "sections": [
    {
      "heading": "Subtítulo atractivo de la primera sección",
      "paragraphs": [
        "Párrafo 1 desarrollado en profundidad con redacción propia...",
        "Párrafo 2 desarrollado en profundidad..."
      ],
      "items": [
        "Elemento de lista opcional si en esta sección hay desglose de actos, horarios, medidas, inversiones o datos (dejar array vacío [] si no aplica)"
      ],
      "quote": "Declaración o frase textual destacada si existe en la noticia original, o cadena vacía"
    }
  ],
  "local_highlight": "Párrafo breve (2 líneas) para nuestro recuadro 'En clave local · Moratalla' destacando el valor o impacto de esta noticia para el municipio y su comarca.",
  "event_date": "Si la noticia trata sobre un evento con fecha concreta pon YYYY-MM-DD, si no deja cadena vacía",
  "category_id": "ID numérico de la categoría más adecuada de la lista proporcionada, o cadena vacía",
  "icon": "Un único emoji representativo (ej. 🏰, 🥁, 🌲, 🎭, 🏛️, 🏆, 🍽️, 🎉, 📰)",
  "image_caption": "Pie de foto corporativo y descriptivo para la imagen principal (máx 115 caracteres)",
  "ai_image_prompt": "Descripción visual detallada en inglés para generar una fotografía realista y luminosa ambientada en Moratalla (Murcia, Spain, historic Mediterranean mountain town, castle, pine forest or local culture) relacionada con esta noticia, sin texto sobreimpreso",
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
                    'temperature' => 0.5,
                    'maxOutputTokens' => 8192,
                    'responseMimeType' => 'application/json'
                ]
            ]);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 35,
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

    // Procesador corporativo local (mantiene toda la extensión capturada estructurada en secciones corporativas)
    $localParts = transformArticleLocallyToCorporateParts($titleOrig, $bodyOrig, $sourceName, $categories);
    $localParts['content_html'] = buildMoratallaCorporateNewsHtml($localParts);
    $localParts['used_gemini'] = false;
    $localParts['gemini_note'] = empty($apiKey)
        ? 'Maquetado completo con diseño corporativo moratalla-murcia.com. Configura tu clave gratuita de Google Gemini para reescritura creativa avanzada con IA.'
        : ('Aviso Gemini: ' . ($lastError ?? 'Se aplicó plantilla corporativa automática.'));

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
