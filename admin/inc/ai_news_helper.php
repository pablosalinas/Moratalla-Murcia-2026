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
            snippet_summary TEXT NULL,
            rss_image VARCHAR(700) NULL,
            published_date VARCHAR(60) NULL,
            published_date_iso DATE NULL,
            processed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_url_hash (url_hash),
            INDEX idx_title_norm (title_norm),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}

    try {
        $pdo->query("SELECT snippet_summary FROM ai_processed_news LIMIT 1");
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE ai_processed_news ADD COLUMN snippet_summary TEXT NULL AFTER news_event_id, ADD COLUMN rss_image VARCHAR(700) NULL AFTER snippet_summary");
        } catch (Exception $e2) {}
    }

    try {
        $pdo->query("SELECT published_date FROM ai_processed_news LIMIT 1");
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE ai_processed_news ADD COLUMN published_date VARCHAR(60) NULL AFTER rss_image, ADD COLUMN published_date_iso DATE NULL AFTER published_date");
        } catch (Exception $e2) {}
    }

    try {
        $pdo->query("SELECT source_url FROM news_events LIMIT 1");
    } catch (Exception $e) {
        try {
            $pdo->exec("ALTER TABLE news_events ADD COLUMN source_url VARCHAR(700) NULL AFTER icon");
        } catch (Exception $e2) {}
    }

    // Auto-sanear fechas guardadas previamente para asegurar que siempre respeten DD/MM/YYYY a partir de su valor ISO
    try {
        $pdo->exec("UPDATE ai_processed_news SET published_date = DATE_FORMAT(published_date_iso, '%d/%m/%Y') WHERE published_date_iso IS NOT NULL AND published_date_iso != '0000-00-00'");
    } catch (Exception $e) {}
}

/**
 * Convierte cualquier fecha válida a formato español estrictamente Día/Mes/Año (DD/MM/YYYY o DD/MM/YYYY HH:MM).
 */
function formatDateToSpanish($dateStr, $includeTime = false) {
    $dateStr = trim((string)$dateStr);
    if ($dateStr === '' || $dateStr === 'Fecha no localizada') return $dateStr;

    // Caso 1: Viene en formato ISO YYYY-MM-DD o YYYY-MM-DD HH:MM
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})(?:\s+(\d{1,2}):(\d{2}))?/', $dateStr, $m)) {
        $out = str_pad($m[3], 2, '0', STR_PAD_LEFT) . '/' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '/' . $m[1];
        if ($includeTime && isset($m[4]) && $m[4] !== '00') {
            $out .= ' ' . str_pad($m[4], 2, '0', STR_PAD_LEFT) . ':' . str_pad($m[5] ?? '00', 2, '0', STR_PAD_LEFT);
        }
        return $out;
    }

    // Caso 2: Ya viene en formato español DD/MM/YYYY o DD-MM-YYYY
    if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})(?:\s+(\d{1,2}):(\d{2}))?/', $dateStr, $m)) {
        $out = str_pad($m[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '/' . $m[3];
        if ($includeTime && isset($m[4]) && $m[4] !== '00') {
            $out .= ' ' . str_pad($m[4], 2, '0', STR_PAD_LEFT) . ':' . str_pad($m[5] ?? '00', 2, '0', STR_PAD_LEFT);
        }
        return $out;
    }

    $ts = parseSpanishDateStringToTimestamp($dateStr);
    if ($ts) {
        $hasTime = ($includeTime && date('H:i', $ts) !== '00:00' && date('H:i', $ts) !== '12:00');
        return $hasTime ? date('d/m/Y H:i', $ts) : date('d/m/Y', $ts);
    }

    return $dateStr;
}

/**
 * Normaliza cualquier fecha a formato estándar YYYY-MM-DD (para consultas SQL y comparaciones).
 * Respeta siempre que si viene con barras o guiones, el primer número es el DÍA y el segundo el MES (nomenclatura española).
 */
function normalizeDateToYmd($str) {
    $str = trim((string)$str);
    if ($str === '') return '';

    // Si viene en formato español DD/MM/YYYY o DD-MM-YYYY o DD.MM.YYYY
    if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})/', $str, $m)) {
        return $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
    }

    // Si viene en formato ISO YYYY-MM-DD o YYYY/MM/DD
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})/', $str, $m)) {
        return $m[1] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);
    }

    $ts = parseSpanishDateStringToTimestamp($str);
    return $ts ? date('Y-m-d', $ts) : '';
}

/**
 * Parsea cadenas de fecha en español o estándares (ISO, RFC) a timestamp UNIX.
 * Respeta ESTRICTAMENTE la nomenclatura española (Día/Mes/Año) y NUNCA interpreta
 * barras o guiones como Mes/Día/Año (formato anglosajón).
 * Devuelve null si no es una fecha válida o reconocible (NUNCA devuelve la fecha actual por defecto).
 */
function parseSpanishDateStringToTimestamp($str) {
    $str = trim((string)$str);
    if ($str === '') return null;

    // Descartar frases o etiquetas no temporales
    if (preg_match('/^(reciente|hoy|ayer|hace|desconocid|sin fecha|fecha no)/i', $str)) {
        if (preg_match('/hace\s+(\d+)\s+hora/iu', $str, $hm)) {
            return time() - ((int)$hm[1] * 3600);
        }
        if (preg_match('/hace\s+(\d+)\s+d[ií]a/iu', $str, $dm)) {
            return time() - ((int)$dm[1] * 86400);
        }
        if (preg_match('/^ayer/iu', $str)) {
            return strtotime('-1 day');
        }
        return null;
    }

    // 1. Formato español en texto: '12 de octubre de 2026' o '12 de octubre 2026, 17:30'
    $months = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
        'noviembre' => 11, 'diciembre' => 12
    ];
    if (preg_match('/(\d{1,2})\s+de\s+([a-záéíóúñ]+)\s+(?:de\s+)?(\d{4})(?:\s*,?\s*(\d{1,2}):(\d{2}))?/iu', $str, $m)) {
        $day = (int)$m[1];
        $monthName = mb_strtolower($m[2], 'UTF-8');
        $year = (int)$m[3];
        $hour = isset($m[4]) ? (int)$m[4] : 12;
        $min = isset($m[5]) ? (int)$m[5] : 0;
        if (isset($months[$monthName]) && checkdate($months[$monthName], $day, $year)) {
            return mktime($hour, $min, 0, $months[$monthName], $day, $year);
        }
    }

    // 2. Formato ESPAÑOL numérico obligatorio Día/Mes/Año: DD/MM/YYYY o DD-MM-YYYY o DD.MM.YYYY
    // IMPORTANTE: En España, el primer número es el DÍA y el segundo es el MES. Nunca MM/DD/YYYY.
    if (preg_match('/^(\d{1,2})[\/\.-](\d{1,2})[\/\.-](\d{4})(?:\s*,?\s*(\d{1,2}):(\d{2})(?::(\d{2}))?)?$/', $str, $m)) {
        $day = (int)$m[1];
        $month = (int)$m[2];
        $year = (int)$m[3];
        $hour = isset($m[4]) ? (int)$m[4] : 12;
        $min = isset($m[5]) ? (int)$m[5] : 0;
        $sec = isset($m[6]) ? (int)$m[6] : 0;
        if (checkdate($month, $day, $year)) {
            return mktime($hour, $min, $sec, $month, $day, $year);
        }
    }

    // 3. Formato ISO estándar: Año-Mes-Día (YYYY-MM-DD o YYYY/MM/DD con hora opcional)
    if (preg_match('/^(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})(?:[T\s](\d{1,2}):(\d{2})(?::(\d{2}))?)?/', $str, $m)) {
        $year = (int)$m[1];
        $month = (int)$m[2];
        $day = (int)$m[3];
        $hour = isset($m[4]) ? (int)$m[4] : 12;
        $min = isset($m[5]) ? (int)$m[5] : 0;
        $sec = isset($m[6]) ? (int)$m[6] : 0;
        if (checkdate($month, $day, $year)) {
            return mktime($hour, $min, $sec, $month, $day, $year);
        }
    }

    // 4. Fechas de feeds RSS / RFC (ej: 'Wed, 21 Oct 2026 19:04:00 GMT' o '21 Oct 2026')
    // Reemplazamos barras por guiones para que strtotime nunca intente interpretar m/d/y inglés si quedaran números
    $safeStr = str_replace('/', '-', $str);
    $ts = @strtotime($safeStr);
    if ($ts !== false && $ts > 946684800 && $ts < (time() + 86400 * 365)) {
        return $ts;
    }

    return null;
}

/**
 * Detecta con máxima precisión la fecha REAL de publicación o acontecimiento de una noticia:
 * 1) JSON-LD (datePublished, dateCreated, dateModified)
 * 2) Meta tags (article:published_time, og:published_time, pubdate, etc.)
 * 3) Elementos HTML <time>
 * 4) Patrones textuales de fecha en español en el cuerpo
 * 5) Patrón YYYY/MM/DD o YYYY-MM-DD en la URL
 * 6) pubDate del feed RSS si es válido
 *
 * Si no se encuentra, devuelve found = false y date_fmt = 'Fecha no localizada' (NUNCA el día de hoy).
 */
function detectArticlePublicationDate($html, $url, $fallbackRssPubDate = '') {
    $result = [
        'found'       => false,
        'timestamp'   => null,
        'date_iso'    => '',
        'date_fmt'    => 'Fecha no localizada',
        'date_source' => ''
    ];

    $resolvedTs = null;
    $source = '';

    if (!empty($html)) {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // 1. JSON-LD: datePublished, dateCreated, dateModified
        $scriptNodes = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scriptNodes as $script) {
            $jsonRaw = trim($script->textContent);
            $data = @json_decode($jsonRaw, true);
            if (is_array($data)) {
                $findDateInJson = function ($node) use (&$findDateInJson, &$resolvedTs, &$source) {
                    if ($resolvedTs !== null || !is_array($node)) return;
                    if (!empty($node['datePublished']) && is_string($node['datePublished'])) {
                        $ts = parseSpanishDateStringToTimestamp($node['datePublished']);
                        if ($ts) {
                            $resolvedTs = $ts;
                            $source = 'JSON-LD datePublished';
                            return;
                        }
                    }
                    if (!empty($node['dateCreated']) && is_string($node['dateCreated'])) {
                        $ts = parseSpanishDateStringToTimestamp($node['dateCreated']);
                        if ($ts) {
                            $resolvedTs = $ts;
                            $source = 'JSON-LD dateCreated';
                            return;
                        }
                    }
                    if (!empty($node['dateModified']) && is_string($node['dateModified'])) {
                        $ts = parseSpanishDateStringToTimestamp($node['dateModified']);
                        if ($ts) {
                            $resolvedTs = $ts;
                            $source = 'JSON-LD dateModified';
                            return;
                        }
                    }
                    foreach ($node as $k => $v) {
                        if (is_array($v)) $findDateInJson($v);
                    }
                };
                $findDateInJson($data);
                if ($resolvedTs !== null) break;
            }
        }

        // 2. Meta tags
        if ($resolvedTs === null) {
            $metaProps = [
                'article:published_time', 'og:published_time', 'publication_date',
                'publishdate', 'publish_date', 'pubdate', 'date', 'dc.date',
                'dc.date.issued', 'parsely-pub-date', 'sailthru.date', 'article:modified_time', 'og:updated_time'
            ];
            foreach ($xpath->query('//meta') as $meta) {
                $prop = strtolower(trim($meta->getAttribute('property') ?: $meta->getAttribute('name')));
                $content = trim($meta->getAttribute('content'));
                if ($content === '') continue;
                if (in_array($prop, $metaProps, true)) {
                    $ts = parseSpanishDateStringToTimestamp($content);
                    if ($ts) {
                        $resolvedTs = $ts;
                        $source = "Meta $prop";
                        break;
                    }
                }
            }
        }

        // 3. Time tags
        if ($resolvedTs === null) {
            $timeNodes = $xpath->query('//time');
            foreach ($timeNodes as $tn) {
                $dt = trim($tn->getAttribute('datetime'));
                if ($dt !== '') {
                    $ts = parseSpanishDateStringToTimestamp($dt);
                    if ($ts) {
                        $resolvedTs = $ts;
                        $source = '<time datetime>';
                        break;
                    }
                }
                $txt = trim($tn->textContent);
                if ($txt !== '') {
                    $ts = parseSpanishDateStringToTimestamp($txt);
                    if ($ts) {
                        $resolvedTs = $ts;
                        $source = '<time> text';
                        break;
                    }
                }
            }
        }

        // 4. Patrón textual en el cuerpo o cabecera de la noticia
        if ($resolvedTs === null) {
            if (preg_match('/(?:publicad[oa]|actualizad[oa]|fecha|cread[oa])[:\s]+(\d{1,2}\s+de\s+[a-záéíóúñ]+\s+(?:de\s+)?\d{4}(?:\s*,?\s*\d{1,2}:\d{2})?)/iu', $html, $m)) {
                $ts = parseSpanishDateStringToTimestamp($m[1]);
                if ($ts) {
                    $resolvedTs = $ts;
                    $source = 'Texto: ' . $m[1];
                }
            }
        }
    }

    // 5. Patrón de fecha en la URL (ej: /2026/10/12/ o /2026-10-12-)
    if ($resolvedTs === null && !empty($url)) {
        if (preg_match('#/(20\d{2})/(0[1-9]|1[0-2])/(0[1-9]|[12]\d|3[01])#', $url, $um)) {
            $ts = @strtotime("{$um[1]}-{$um[2]}-{$um[3]} 12:00:00");
            if ($ts) {
                $resolvedTs = $ts;
                $source = 'Ruta URL (YYYY/MM/DD)';
            }
        } elseif (preg_match('#/(20\d{2})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])#', $url, $um)) {
            $ts = @strtotime("{$um[1]}-{$um[2]}-{$um[3]} 12:00:00");
            if ($ts) {
                $resolvedTs = $ts;
                $source = 'Ruta URL (YYYY-MM-DD)';
            }
        }
    }

    // 6. Respaldo: pubDate del feed RSS si es válido
    if ($resolvedTs === null && !empty($fallbackRssPubDate)) {
        $ts = parseSpanishDateStringToTimestamp($fallbackRssPubDate);
        if ($ts) {
            $resolvedTs = $ts;
            $source = 'Feed RSS pubDate';
        }
    }

    if ($resolvedTs !== null) {
        $result['found']       = true;
        $result['timestamp']   = $resolvedTs;
        $result['date_iso']    = date('Y-m-d', $resolvedTs);
        $hasTime               = (date('H:i', $resolvedTs) !== '00:00' && date('H:i', $resolvedTs) !== '12:00');
        $result['date_fmt']    = $hasTime ? date('d/m/Y H:i', $resolvedTs) : date('d/m/Y', $resolvedTs);
        $result['date_source'] = $source;
    }

    return $result;
}

/**
 * Comprueba si una noticia cumple con el rango de fechas delimitado [dateFrom, dateTo].
 * Si dateFrom o dateTo están vacíos, no se aplica límite en ese extremo.
 * Si ambos están vacíos, devuelve true (sin restricción temporal).
 * Si la noticia no tiene fecha localizada y hay un rango activo:
 * - Si $allowUndated es true, se permite.
 * - Si $allowUndated es false, se descarta (para búsquedas estrictas automáticas y filtrado por rango).
 */
function isNewsItemInDateRange($item, $dateFrom = '', $dateTo = '', $allowUndated = false) {
    $dateFromYmd = normalizeDateToYmd($dateFrom);
    $dateToYmd   = normalizeDateToYmd($dateTo);

    // Sin delimitación temporal activa
    if ($dateFromYmd === '' && $dateToYmd === '') {
        return true;
    }

    $hasDate = !empty($item['date_found']);
    $dateIso = !empty($item['event_date_iso']) ? trim((string)$item['event_date_iso']) : '';
    $timestamp = !empty($item['timestamp']) ? (int)$item['timestamp'] : 0;
    $pubDate = !empty($item['pub_date']) ? trim((string)$item['pub_date']) : '';

    // Si explícitamente es 'Fecha no localizada' y no tiene timestamp ni ISO
    if (!$hasDate && empty($dateIso) && $timestamp <= 0 && ($pubDate === '' || $pubDate === 'Fecha no localizada')) {
        return (bool)$allowUndated;
    }

    // Extraer año-mes-día YYYY-MM-DD respetando siempre día/mes/año
    $itemYmd = '';
    if (!empty($dateIso)) {
        $itemYmd = normalizeDateToYmd($dateIso);
    }
    if ($itemYmd === '' && $timestamp > 0) {
        $itemYmd = date('Y-m-d', $timestamp);
    }
    if ($itemYmd === '' && !empty($pubDate) && $pubDate !== 'Fecha no localizada') {
        $itemYmd = normalizeDateToYmd($pubDate);
    }

    if ($itemYmd === '') {
        return (bool)$allowUndated;
    }

    if ($dateFromYmd !== '' && $itemYmd < $dateFromYmd) {
        return false;
    }
    if ($dateToYmd !== '' && $itemYmd > $dateToYmd) {
        return false;
    }

    return true;
}

/**
 * Normaliza un titular para comparación anti-duplicados eliminando sufijos de medios, stopwords y caracteres especiales.
 */
function normalizeNewsTitleForDedupe($title) {
    $t = trim((string)$title);
    if ($t === '') return '';

    // Eliminar sufijos habituales de fuentes y periódicos como " - El Noroeste Digital", " | La Verdad", " - Europa Press"
    $t = preg_replace('/(\s*[-|–—]\s*[^-|–—]+)$/u', '', $t);
    // Eliminar prefijos/sufijos entre corchetes o paréntesis como "[Vídeo]", "[Fotos]"
    $t = preg_replace('/^\[.*?\]\s*|\s*\[.*?\]$/u', '', $t);

    $t = mb_strtolower($t, 'UTF-8');
    $replacements = [
        'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n',
        'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u', 'Ñ'=>'n'
    ];
    $t = strtr($t, $replacements);
    $t = preg_replace('/[^a-z0-9\s]/', ' ', $t);
    $words = preg_split('/\s+/', $t, -1, PREG_SPLIT_NO_EMPTY);
    $stopwords = [
        'el','la','los','las','un','una','unos','unas','de','del','al','en','con','por','para',
        'sobre','se','su','sus','que','y','o','a','ante','bajo','como','entre','hacia','hasta',
        'sin','tras','moratalla','murcia','region','noticias','digital','periodico','diario',
        'redaccion','prensa','noticia','ultima','hora'
    ];
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
 * Calcula si dos titulares normalizados se refieren a la misma noticia (contraste inteligente contra base de datos)
 */
function areNewsTitlesRedundant($normA, $normB) {
    if ($normA === '' || $normB === '') return false;
    if ($normA === $normB) return true;
    if (strpos($normA, $normB) !== false || strpos($normB, $normA) !== false) {
        if (min(strlen($normA), strlen($normB)) >= 14) return true;
    }

    $wordsA = array_unique(explode(' ', $normA));
    $wordsB = array_unique(explode(' ', $normB));
    if (empty($wordsA) || empty($wordsB)) return false;

    $intersection = array_intersect($wordsA, $wordsB);
    $matchCount = count($intersection);
    $minWords = min(count($wordsA), count($wordsB));
    $overlapRatio = $minWords > 0 ? ($matchCount / $minWords) : 0;

    // 1. Si comparten 3 o más palabras clave y representan el 50% o más de la menor
    if ($matchCount >= 3 && $overlapRatio >= 0.50) {
        return true;
    }
    // 2. Si la noticia menor tiene solo 2 palabras clave y ambas coinciden al 100%
    if ($minWords === 2 && $matchCount === 2) {
        return true;
    }
    // 3. Si comparten 4 o más palabras clave de alto valor semántico
    if ($matchCount >= 4) {
        return true;
    }

    similar_text($normA, $normB, $percent);
    return ($percent >= 66.0);
}

/**
 * Registra una noticia en `ai_processed_news` (disponible, publicada o descartada)
 */
function markNewsAsProcessed($pdo, $sourceUrl, $originalTitle, $sourceName = '', $status = 'published', $newsEventId = null, $snippetSummary = null, $rssImage = null, $publishedDate = null, $publishedDateIso = null) {
    ensureAiNewsTrackingSchema($pdo);
    $sourceUrl = trim((string)$sourceUrl);
    $originalTitle = trim((string)$originalTitle);
    if ($sourceUrl === '' && $originalTitle === '') return false;

    $normUrl = normalizeUrlForHash($sourceUrl ?: $originalTitle);
    $urlHash = hash('sha256', $normUrl);
    $titleNorm = mb_substr(normalizeNewsTitleForDedupe($originalTitle), 0, 250);

    $stmt = $pdo->prepare("SELECT id, status FROM ai_processed_news WHERE url_hash = ? OR (title_norm != '' AND title_norm = ?) LIMIT 1");
    $stmt->execute([$urlHash, $titleNorm]);
    $existingRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingRow) {
        $existingId = (int)$existingRow['id'];
        // Si se está pre-registrando desde el buscador automático ('available'), no pisar un estado 'published' o 'discarded' previo
        $finalStatus = $status;
        if ($status === 'available' && in_array($existingRow['status'], ['published', 'discarded'], true)) {
            $finalStatus = $existingRow['status'];
        }
        $upd = $pdo->prepare("UPDATE ai_processed_news SET
            status = ?,
            news_event_id = COALESCE(?, news_event_id),
            snippet_summary = COALESCE(?, snippet_summary),
            rss_image = COALESCE(?, rss_image),
            source_name = COALESCE(NULLIF(?, ''), source_name),
            published_date = COALESCE(NULLIF(?, ''), published_date),
            published_date_iso = COALESCE(?, published_date_iso),
            processed_at = NOW()
            WHERE id = ?");
        return $upd->execute([
            $finalStatus,
            $newsEventId ?: null,
            $snippetSummary ?: null,
            $rssImage ?: null,
            $sourceName,
            $publishedDate ?: null,
            $publishedDateIso ?: null,
            $existingId
        ]);
    } else {
        $ins = $pdo->prepare("INSERT INTO ai_processed_news (source_url, url_hash, title_original, title_norm, source_name, status, news_event_id, snippet_summary, rss_image, published_date, published_date_iso) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $ins->execute([
            $sourceUrl,
            $urlHash,
            $originalTitle,
            $titleNorm,
            $sourceName,
            $status,
            $newsEventId ?: null,
            $snippetSummary ?: null,
            $rssImage ?: null,
            $publishedDate ?: null,
            $publishedDateIso ?: null
        ]);
    }
}

/**
 * Permite restaurar una noticia previamente descartada/procesada a estado disponible (sin borrarla de la base de datos)
 */
function unmarkProcessedNews($pdo, $urlHashOrId) {
    ensureAiNewsTrackingSchema($pdo);
    if (is_numeric($urlHashOrId)) {
        $stmt = $pdo->prepare("UPDATE ai_processed_news SET status = 'available', processed_at = NOW() WHERE id = ?");
        return $stmt->execute([(int)$urlHashOrId]);
    }
    $stmt = $pdo->prepare("UPDATE ai_processed_news SET status = 'available', processed_at = NOW() WHERE url_hash = ?");
    return $stmt->execute([$urlHashOrId]);
}

/**
 * Cruza una lista de noticias encontradas en internet contra `news_events` (publicadas en la web) y `ai_processed_news`
 */
function annotateNewsWithProcessedStatus($pdo, array $items) {
    ensureAiNewsTrackingSchema($pdo);

    $processedRows = [];
    try {
        $stmt = $pdo->query("SELECT p.*, ne.title as published_title, ne.id as real_news_id, ne.event_date as real_event_date
                             FROM ai_processed_news p
                             LEFT JOIN news_events ne ON p.news_event_id = ne.id
                             ORDER BY p.processed_at DESC");
        $processedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    $existingNews = [];
    try {
        $stmt2 = $pdo->query("SELECT id, title, source_url, event_date FROM news_events ORDER BY id DESC");
        while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            $r['title_norm'] = normalizeNewsTitleForDedupe($r['title']);
            $r['clean_url'] = !empty($r['source_url']) ? normalizeUrlForHash($r['source_url']) : '';
            $r['url_hash'] = !empty($r['clean_url']) ? hash('sha256', $r['clean_url']) : '';
            $existingNews[] = $r;
        }
    } catch (Exception $e) {}

    $matchedRecordIds = [];
    foreach ($items as &$item) {
        $itemCleanUrl = normalizeUrlForHash($item['url'] ?: $item['title']);
        $itemUrlHash = hash('sha256', $itemCleanUrl);
        $itemTitleNorm = normalizeNewsTitleForDedupe($item['title']);

        $item['url_hash'] = $itemUrlHash;
        $item['process_status'] = 'new';
        $item['matched_news_id'] = null;
        $item['matched_title'] = null;
        $item['processed_record_id'] = null;
        $item['processed_date'] = null;

        // 1. Contrastar de forma prioritaria contra `news_events` (noticias reales ya publicadas en la web)
        $isPublishedOnWeb = false;
        foreach ($existingNews as $en) {
            $urlMatch = false;
            if (!empty($en['clean_url']) && !empty($itemCleanUrl)) {
                if ($en['url_hash'] === $itemUrlHash || $en['clean_url'] === $itemCleanUrl) {
                    $urlMatch = true;
                } elseif (strpos($itemCleanUrl, $en['clean_url']) !== false || strpos($en['clean_url'], $itemCleanUrl) !== false) {
                    $urlMatch = true;
                }
            }
            $titleMatch = areNewsTitlesRedundant($itemTitleNorm, $en['title_norm']);

            if ($urlMatch || $titleMatch) {
                $item['process_status'] = 'published';
                $item['matched_news_id'] = (int)$en['id'];
                $item['matched_title'] = $en['title'];
                if (empty($item['date_found']) && !empty($en['event_date'])) {
                    $item['event_date_iso'] = date('Y-m-d', strtotime($en['event_date']));
                    $item['pub_date'] = formatDateToSpanish($en['event_date']);
                    $item['date_found'] = true;
                }
                $isPublishedOnWeb = true;
                break;
            }
        }

        // 2. Contrastar contra el historial de `ai_processed_news`
        foreach ($processedRows as $pr) {
            $sameHash = ($pr['url_hash'] === $itemUrlHash);
            $sameTitle = areNewsTitlesRedundant($itemTitleNorm, $pr['title_norm']);
            if ($sameHash || $sameTitle) {
                $matchedRecordIds[(int)$pr['id']] = true;
                $item['processed_record_id'] = (int)$pr['id'];
                $item['processed_date'] = $pr['processed_at'] ? date('d/m/Y H:i', strtotime($pr['processed_at'])) : '';

                if (empty($item['date_found'])) {
                    if (!empty($pr['published_date_iso']) && $pr['published_date_iso'] !== '0000-00-00') {
                        $item['event_date_iso'] = $pr['published_date_iso'];
                        $item['pub_date'] = formatDateToSpanish($pr['published_date_iso']);
                        $item['date_found'] = true;
                        $item['timestamp'] = strtotime($pr['published_date_iso']) ?: 0;
                    } elseif (!empty($pr['published_date']) && $pr['published_date'] !== 'Fecha no localizada') {
                        $parsedDt = detectArticlePublicationDate('', '', $pr['published_date']);
                        if ($parsedDt['found']) {
                            $item['event_date_iso'] = $parsedDt['date_iso'];
                            $item['pub_date'] = $parsedDt['date_fmt'];
                            $item['date_found'] = true;
                            $item['timestamp'] = $parsedDt['timestamp'];
                        }
                    }
                }

                if ($isPublishedOnWeb) {
                    // Sincronizar ai_processed_news si en base de datos ya está publicada en la web
                    if ($pr['status'] !== 'published') {
                        try {
                            $pdo->prepare("UPDATE ai_processed_news SET status = 'published', news_event_id = ?, processed_at = NOW() WHERE id = ?")
                                ->execute([$item['matched_news_id'], (int)$pr['id']]);
                        } catch (Exception $e) {}
                    }
                } else {
                    if ($pr['status'] === 'discarded') {
                        $item['process_status'] = 'discarded';
                        $item['matched_title'] = $pr['title_original'];
                    } elseif ($pr['status'] === 'published') {
                        $item['process_status'] = 'published';
                        $item['matched_news_id'] = $pr['real_news_id'] ?: $pr['news_event_id'];
                        $item['matched_title'] = $pr['published_title'] ?: $pr['title_original'];
                    } else {
                        $item['process_status'] = 'new';
                    }
                }

                if (empty($item['snippet']) && !empty($pr['snippet_summary'])) {
                    $item['snippet'] = $pr['snippet_summary'];
                }
                if (empty($item['rss_image']) && !empty($pr['rss_image'])) {
                    $item['rss_image'] = $pr['rss_image'];
                }
                break;
            }
        }
    }
    unset($item);

    // Incorporar también las noticias guardadas en `ai_processed_news`
    // que no estén en el feed actual, contrastándolas siempre contra `news_events`
    foreach ($processedRows as $pr) {
        $prId = (int)$pr['id'];
        if (isset($matchedRecordIds[$prId])) continue;

        $st = 'published';
        $matchedNewsId = $pr['real_news_id'] ?: $pr['news_event_id'];
        $matchedNewsTitle = $pr['published_title'] ?: $pr['title_original'];

        if ($pr['status'] === 'discarded') {
            $st = 'discarded';
        } elseif ($pr['status'] === 'available' || $pr['status'] === 'new') {
            // Contrastar contra news_events para comprobar si ya ha sido publicada en la web
            $alreadyPubId = null;
            $alreadyPubTitle = null;
            $prCleanUrl = !empty($pr['source_url']) ? normalizeUrlForHash($pr['source_url']) : '';
            $prNormTitle = !empty($pr['title_norm']) ? $pr['title_norm'] : normalizeNewsTitleForDedupe($pr['title_original']);

            foreach ($existingNews as $en) {
                $uMatch = (!empty($en['clean_url']) && !empty($prCleanUrl) && ($en['clean_url'] === $prCleanUrl || $en['url_hash'] === $pr['url_hash']));
                $tMatch = areNewsTitlesRedundant($prNormTitle, $en['title_norm']);
                if ($uMatch || $tMatch) {
                    $alreadyPubId = (int)$en['id'];
                    $alreadyPubTitle = $en['title'];
                    break;
                }
            }

            if ($alreadyPubId !== null) {
                $st = 'published';
                $matchedNewsId = $alreadyPubId;
                $matchedNewsTitle = $alreadyPubTitle;
                try {
                    $pdo->prepare("UPDATE ai_processed_news SET status = 'published', news_event_id = ?, processed_at = NOW() WHERE id = ?")
                        ->execute([$alreadyPubId, $prId]);
                } catch (Exception $e) {}
            } else {
                $st = 'new';
            }
        }

        $dateFound = false;
        $eventDateIso = '';
        $pubDateStr = 'Fecha no localizada';
        $ts = 0;

        if (!empty($pr['published_date_iso']) && $pr['published_date_iso'] !== '0000-00-00') {
            $dateFound = true;
            $eventDateIso = $pr['published_date_iso'];
            $pubDateStr = formatDateToSpanish($pr['published_date_iso']);
            $ts = strtotime($pr['published_date_iso']) ?: 0;
        } elseif (!empty($pr['published_date']) && $pr['published_date'] !== 'Fecha no localizada') {
            $parsed = detectArticlePublicationDate('', '', $pr['published_date']);
            if ($parsed['found']) {
                $dateFound = true;
                $eventDateIso = $parsed['date_iso'];
                $pubDateStr = $parsed['date_fmt'];
                $ts = $parsed['timestamp'] ?: 0;
            }
        } elseif (!empty($pr['real_event_date'])) {
            $dateFound = true;
            $eventDateIso = date('Y-m-d', strtotime($pr['real_event_date']));
            $pubDateStr = formatDateToSpanish($pr['real_event_date']);
            $ts = strtotime($pr['real_event_date']) ?: 0;
        }

        $items[] = [
            'title'               => $pr['title_original'] ?: ($pr['published_title'] ?: 'Noticia registrada'),
            'url'                 => $pr['source_url'] ?: '',
            'snippet'             => $pr['snippet_summary'] ?: '',
            'source'              => $pr['source_name'] ?: 'Historial IA',
            'pub_date'            => $pubDateStr,
            'event_date_iso'      => $eventDateIso,
            'date_found'          => $dateFound,
            'timestamp'           => $ts,
            'rss_image'           => $pr['rss_image'] ?: '',
            'url_hash'            => $pr['url_hash'],
            'process_status'      => $st,
            'matched_news_id'     => $matchedNewsId,
            'matched_title'       => $matchedNewsTitle,
            'processed_record_id' => $prId,
            'processed_date'      => !empty($pr['processed_at']) ? date('d/m/Y H:i', strtotime($pr['processed_at'])) : ''
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

    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 6,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => '',
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8'
        ]
    ];
    if (stripos($url, 'google.com') !== false) {
        $opts[CURLOPT_COOKIE] = 'SOCS=CAESHAgBEhJnd3NfMjAyNDA5MDQtMF9SQzIaAmVuIAEaBgiA_L22Bg; CONSENT=PENDING+999';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
    curl_close($ch);
    return $body;
}

/**
 * Busca noticias recientes en internet sobre Moratalla combinando Bing News RSS y Google News RSS.
 * Permite delimitar el rango de fechas con $dateFrom y $dateTo (por defecto lee la configuración en settings).
 */
function searchMoratallaNews($pdo, $query = 'Moratalla Murcia y Banda de Música de Moratalla', $maxResults = 30, $dateFrom = null, $dateTo = null) {
    $query = trim($query);
    if ($query === '') {
        $query = 'Moratalla Murcia y Banda de Música de Moratalla';
    }

    if ($dateFrom === null || $dateTo === null) {
        $cfgStmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('news_search_date_from', 'news_search_date_to')");
        $cfgRows = $cfgStmt ? $cfgStmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
        if ($dateFrom === null) $dateFrom = $cfgRows['news_search_date_from'] ?? '';
        if ($dateTo === null) $dateTo = $cfgRows['news_search_date_to'] ?? '';
    }

    // Comprobar si es una búsqueda combinada de Moratalla + Banda de Música
    $isCombined = (
        $query === 'Moratalla Murcia y Banda de Música de Moratalla' ||
        (
            stripos($query, 'banda') !== false &&
            stripos($query, 'moratalla') !== false &&
            (stripos($query, ' y ') !== false || stripos($query, '+') !== false || stripos($query, 'combinad') !== false)
        )
    );

    $fetchBatchSize = max(40, $maxResults * 2);

    if ($isCombined) {
        $subA = searchMoratallaNewsSingleQuery($pdo, 'Moratalla Murcia', $fetchBatchSize, false);
        $subB = searchMoratallaNewsSingleQuery($pdo, 'Banda de Música de Moratalla', $fetchBatchSize, false);
        $combined = [];
        $seen = [];
        $seenNormTitles = [];

        foreach ($subA as $it) {
            $h = $it['url_hash'] ?? hash('sha256', normalizeUrlForHash($it['url'] ?: $it['title']));
            $norm = normalizeNewsTitleForDedupe($it['title']);
            if (!isset($seen[$h])) {
                $seen[$h] = true;
                $seenNormTitles[] = $norm;
                $it['topic_badge'] = (stripos($it['title'], 'banda') !== false || stripos($it['snippet'] ?? '', 'banda') !== false)
                    ? '🎵 Banda de Música de Moratalla'
                    : '📰 Actualidad Moratalla';
                $combined[] = $it;
            }
        }

        foreach ($subB as $it) {
            $h = $it['url_hash'] ?? hash('sha256', normalizeUrlForHash($it['url'] ?: $it['title']));
            $norm = normalizeNewsTitleForDedupe($it['title']);
            if (!isset($seen[$h])) {
                $isDup = false;
                foreach ($seenNormTitles as $prevNorm) {
                    if (areNewsTitlesRedundant($norm, $prevNorm)) {
                        $isDup = true;
                        break;
                    }
                }
                if (!$isDup) {
                    $seen[$h] = true;
                    $seenNormTitles[] = $norm;
                    $it['topic_badge'] = '🎵 Banda de Música de Moratalla';
                    $combined[] = $it;
                }
            }
        }

        $annotated = annotateNewsWithProcessedStatus($pdo, $combined);

        // Si hay rango de fechas configurado, filtrar estrictamente
        if (!empty($dateFrom) || !empty($dateTo)) {
            $filtered = [];
            foreach ($annotated as $it) {
                if (isNewsItemInDateRange($it, $dateFrom, $dateTo, false)) {
                    $filtered[] = $it;
                }
            }
            $annotated = $filtered;
        }

        usort($annotated, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });
        return array_slice($annotated, 0, $maxResults);
    }

    $items = searchMoratallaNewsSingleQuery($pdo, $query, $fetchBatchSize, false);
    foreach ($items as &$it) {
        $it['topic_badge'] = (stripos($it['title'], 'banda') !== false || stripos($it['snippet'] ?? '', 'banda') !== false)
            ? '🎵 Banda de Música de Moratalla'
            : '📰 Actualidad Moratalla';
    }
    unset($it);

    $annotated = annotateNewsWithProcessedStatus($pdo, $items);

    if (!empty($dateFrom) || !empty($dateTo)) {
        $filtered = [];
        foreach ($annotated as $it) {
            if (isNewsItemInDateRange($it, $dateFrom, $dateTo, false)) {
                $filtered[] = $it;
            }
        }
        $annotated = $filtered;
    }

    usort($annotated, function ($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });

    return array_slice($annotated, 0, $maxResults);
}

function searchMoratallaNewsSingleQuery($pdo, $query, $maxResults = 25, $doAnnotate = true) {
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
                    $dateInfo = detectArticlePublicationDate('', $realUrl, $pubDate);
                    $items[] = [
                        'title'          => $title,
                        'url'            => $realUrl,
                        'snippet'        => $desc,
                        'source'         => $sourceName ?: 'Prensa Digital',
                        'pub_date'       => $dateInfo['found'] ? $dateInfo['date_fmt'] : 'Fecha no localizada',
                        'event_date_iso' => $dateInfo['found'] ? $dateInfo['date_iso'] : '',
                        'date_found'     => $dateInfo['found'],
                        'date_source'    => $dateInfo['date_source'],
                        'timestamp'      => $dateInfo['found'] ? $dateInfo['timestamp'] : 0,
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
                    $dateInfo = detectArticlePublicationDate('', $link, $pubDate);
                    $items[] = [
                        'title'          => $cleanTitle,
                        'url'            => $link,
                        'source_domain'  => $sourceUrl,
                        'snippet'        => $cleanDesc ?: $cleanTitle,
                        'source'         => $sourceName ?: 'Google News',
                        'pub_date'       => $dateInfo['found'] ? $dateInfo['date_fmt'] : 'Fecha no localizada',
                        'event_date_iso' => $dateInfo['found'] ? $dateInfo['date_iso'] : '',
                        'date_found'     => $dateInfo['found'],
                        'date_source'    => $dateInfo['date_source'],
                        'timestamp'      => $dateInfo['found'] ? $dateInfo['timestamp'] : 0,
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
    return $doAnnotate ? annotateNewsWithProcessedStatus($pdo, $sliced) : $sliced;
}

/**
 * Busca en DuckDuckGo HTML el enlace real del artículo publicado por un medio digital a partir de su titular.
 */
function searchArticleUrlViaDuckDuckGo($title, $source = '') {
    if (empty($title)) return '';
    $cleanTitle = preg_replace('/^\[.*?\]\s*|\s*-\s*[^-]+$/u', '', $title);
    $cleanTitle = trim(preg_replace('/[«»"“”\(\)]/u', ' ', $cleanTitle));
    if (empty($cleanTitle)) return '';

    $queries = [
        '"' . $cleanTitle . '"',
        $cleanTitle . ' ' . ($source ?: 'Moratalla')
    ];

    foreach ($queries as $q) {
        $ddgUrl = 'https://html.duckduckgo.com/html/?q=' . urlencode($q);
        $html = aiHttpGet($ddgUrl, 7);
        if (!empty($html)) {
            if (preg_match_all('/<a[^>]+class="result__url"[^>]+href="([^"]+)"/i', $html, $matches)) {
                foreach ($matches[1] as $rawHref) {
                    $target = $rawHref;
                    if (preg_match('/uddg=([^&]+)/', $rawHref, $udm)) {
                        $target = urldecode($udm[1]);
                    }
                    if (stripos($target, 'google.com') === false && stripos($target, 'duckduckgo.com') === false && preg_match('#^https?://#i', $target)) {
                        return $target;
                    }
                }
            }
        }
    }
    return '';
}

/**
 * Si una URL es un enlace intermedio de Google News, decodifica o localiza la URL directa del artículo original
 * verificando siempre que corresponda exactamente a la noticia seleccionada.
 */
function resolveRealArticleUrl($url, $title = '', $source = '') {
    // Si viene con parámetro de redirección de Bing u otro servicio (?url=...)
    if (preg_match('/[?&]url=([^&]+)/i', $url, $m)) {
        $candidateDecoded = urldecode($m[1]);
        if (stripos($candidateDecoded, 'google.com') === false && stripos($candidateDecoded, 'bing.com') === false && preg_match('#^https?://#i', $candidateDecoded)) {
            return $candidateDecoded;
        }
    }

    if (stripos($url, 'news.google.com') === false) {
        return $url;
    }

    // 1. Intentar decodificación directa del token base64 si contiene URL en claro
    if (preg_match('#/(?:rss/)?articles/([A-Za-z0-9_-]+)#', $url, $m)) {
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

        // 2. Decodificación oficial de Google News mediante batchexecute RPC Fbv4je
        $ch = curl_init('https://news.google.com/articles/' . $artId);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 6,
            CURLOPT_TIMEOUT        => 7,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        $gnPageHtml = curl_exec($ch);
        $effUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        if (!empty($effUrl) && stripos($effUrl, 'google.com') === false) {
            return $effUrl;
        }

        if (!empty($gnPageHtml)) {
            preg_match('/data-n-a-ts="([^"]+)"/', $gnPageHtml, $tsM);
            preg_match('/data-n-a-sg="([^"]+)"/', $gnPageHtml, $sgM);

            $ts = isset($tsM[1]) ? (int)$tsM[1] : 0;
            $sg = $sgM[1] ?? '';

            if ($ts > 0 && $sg !== '') {
                $innerReq = '["garturlreq",[["X","X",["X","X"],null,null,1,1,"US:en",null,1,null,null,null,null,null,0,1],"X","X",1,[1,1,1],1,1,null,0,0,null,0],"' . $artId . '",' . $ts . ',"' . $sg . '"]';
                $fReq = json_encode([[['Fbv4je', $innerReq, null, 'generic']]]);
                $ch = curl_init('https://news.google.com/_/DotsSplashUi/data/batchexecute');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => 'f.req=' . urlencode($fReq),
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/x-www-form-urlencoded;charset=UTF-8',
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ],
                    CURLOPT_TIMEOUT        => 7,
                    CURLOPT_CONNECTTIMEOUT => 4,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]);
                $batchResp = curl_exec($ch);
                curl_close($ch);

                if (!empty($batchResp)) {
                    $lines = explode("\n\n", $batchResp);
                    if (isset($lines[1])) {
                        $p = @json_decode($lines[1], true);
                        if (isset($p[0][2])) {
                            $inner = @json_decode($p[0][2], true);
                            if (!empty($inner[1]) && stripos($inner[1], 'google.com') === false && preg_match('#^https?://#i', $inner[1])) {
                                return $inner[1];
                            }
                        }
                    }
                    if (preg_match('/garturlres\\\\?",\\\\?"(https?:[^\\\\"]+)/i', $batchResp, $resM) ||
                        preg_match('/\["garturlres","(https?:[^"]+)"/', $batchResp, $resM)) {
                        $candUrl = stripslashes($resM[1]);
                        if (stripos($candUrl, 'google.com') === false) {
                            return $candUrl;
                        }
                    }
                }
            }

            if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $gnPageHtml, $canM) && stripos($canM[1], 'google.com') === false) {
                return $canM[1];
            }
        }
    }

    // 3. Fallback DuckDuckGo: localizar el artículo directamente en el medio original a partir de su titular
    if (!empty($title)) {
        $ddgCandidate = searchArticleUrlViaDuckDuckGo($title, $source);
        if (!empty($ddgCandidate)) {
            return $ddgCandidate;
        }

        // 4. Fallback Bing News
        $normTarget = normalizeNewsTitleForDedupe($title);
        $cleanSearchTitle = preg_replace('/^\[.*?\]\s*|\s*-\s*[^-]+$/u', '', $title);
        $searchQuery = '"' . trim($cleanSearchTitle) . '" ' . ($source ?: 'Moratalla');
        $bingUrl = 'https://www.bing.com/news/search?q=' . urlencode($searchQuery) . '&format=rss&mkt=es-ES';
        $bingXml = aiHttpGet($bingUrl, 7);
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

        $bingWebUrl = 'https://www.bing.com/search?q=' . urlencode(trim($cleanSearchTitle) . ' ' . $source) . '&format=rss';
        $bingWebXml = aiHttpGet($bingWebUrl, 7);
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
function extractArticleContentAndImages($url, $fallbackTitle = '', $fallbackSnippet = '', $rssImage = '', $sourceName = '', $fallbackPubDate = '') {
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

        // 3. Localizar el contenedor principal del cuerpo de la noticia
        // Priorizamos selectores semánticos de WordPress (Elementor, Gutenberg, clásicos) y portales de noticias
        $containerCandidates = [
            '//*[contains(@class, "theme-post-content")]',
            '//*[contains(@class, "entry-content")]',
            '//*[contains(@class, "post-content")]',
            '//*[contains(@class, "article-content")]',
            '//*[contains(@class, "article__body")]',
            '//*[contains(@class, "article-body")]',
            '//*[contains(@class, "c-detail__body")]',
            '//*[contains(@class, "story-body")]',
            '//*[contains(@class, "story__content")]',
            '//*[contains(@class, "cuerpo-noticia")]',
            '//*[contains(@class, "cuerpo")]',
            '//*[contains(@class, "news-text")]',
            '//article[not(contains(@class, "grid") or contains(@class, "related") or contains(@class, "card") or contains(@class, "teaser"))]',
            '//main',
            '//*[@id="content" or @id="main-content" or @id="article-body"]'
        ];

        $mainContainer = null;
        foreach ($containerCandidates as $cq) {
            $matchedNodes = $xpath->query($cq);
            foreach ($matchedNodes as $node) {
                $pCount = $xpath->query('.//p', $node)->length;
                if ($pCount >= 2) {
                    $mainContainer = $node;
                    break 2;
                }
            }
        }

        // Si no se localizó ningún contenedor por clase estándar, buscar el elemento con mayor volumen de texto en párrafos
        if (!$mainContainer) {
            $bestLen = 0;
            $allDivs = $xpath->query('//div | //article | //section');
            foreach ($allDivs as $div) {
                $cl = strtolower($div->getAttribute('class') . ' ' . $div->getAttribute('id'));
                if (preg_match('/(footer|header|menu|nav|sidebar|widget-area|comments)/', $cl)) continue;
                $pDirect = $xpath->query('.//p', $div);
                if ($pDirect->length >= 2) {
                    $txtLen = 0;
                    foreach ($pDirect as $p) $txtLen += mb_strlen(trim($p->textContent));
                    if ($txtLen > $bestLen && $txtLen > 300) {
                        $bestLen = $txtLen;
                        $mainContainer = $div;
                    }
                }
            }
        }

        $targetContext = $mainContainer ?: $dom;

        // 4. Capturar imágenes del artículo desde el contenedor principal y figure
        $imgQueries = [
            './/figure//img',
            './/img[not(contains(@class,"logo") or contains(@class,"avatar") or contains(@class,"icon"))]'
        ];
        foreach ($imgQueries as $iq) {
            $imgNodes = $xpath->query($iq, $targetContext);
            foreach ($imgNodes as $img) {
                $src = $img->getAttribute('data-src') ?: $img->getAttribute('data-lazy-src') ?: $img->getAttribute('src');
                $alt = trim($img->getAttribute('alt') ?: 'Imagen del artículo');
                $w = (int)$img->getAttribute('width');
                $h = (int)$img->getAttribute('height');
                if (($w > 0 && $w < 180) || ($h > 0 && $h < 120)) continue;
                if (!empty($src)) {
                    $addCandidateImg($src, mb_substr($alt, 0, 80));
                }
                if (count($images) >= 12) break 2;
            }
        }

        // 5. Extraer párrafos, subtítulos (h2, h3) y citas (blockquote) del artículo
        // Filtrar elementos de ruido sin borrar el contenedor de noticias
        $blockNodes = $xpath->query('.//*[self::p or self::blockquote or self::h2 or self::h3 or self::li]', $targetContext);
        $seenBlockTexts = [];

        foreach ($blockNodes as $node) {
            // Comprobar si el nodo o sus ancestros directos son de compartir, anuncios o cookies
            $parent = $node->parentNode;
            $parentCl = $parent ? strtolower($parent->getAttribute('class') . ' ' . $parent->getAttribute('id')) : '';
            if (preg_match('/(share|rrss|redes|social|tags|sidebar|comment|banner|ad-|newsletter|relac|related)/i', $parentCl)) {
                continue;
            }

            $tag = strtolower($node->nodeName);
            $txt = trim(preg_replace('/\s+/u', ' ', $node->textContent));
            if ($txt === '' || isset($seenBlockTexts[$txt])) continue;

            // Ignorar avisos de cookies, suscripciones, pies legales o enlaces de compartir
            if (preg_match('/(cookies|política de privacidad|todos los derechos reservados|suscríbete|iniciar sesión|regístrate gratis|boletín informativo|compartir en|comparte este artículo|lee también|te puede interesar|más información:|noticias relacionadas|recibe cada viernes)/iu', $txt)) {
                continue;
            }

            if ($tag === 'blockquote') {
                if (mb_strlen($txt) >= 20) {
                    $seenBlockTexts[$txt] = true;
                    $domExtractedBlocks[] = "«" . trim($txt, '«»""') . "»";
                }
            } elseif ($tag === 'h2' || $tag === 'h3') {
                if (mb_strlen($txt) >= 10 && mb_strlen($txt) <= 120) {
                    $seenBlockTexts[$txt] = true;
                    $domExtractedBlocks[] = "### " . $txt;
                }
            } elseif ($tag === 'li') {
                if (mb_strlen($txt) >= 20) {
                    $seenBlockTexts[$txt] = true;
                    $domExtractedBlocks[] = "• " . $txt;
                }
            } else {
                // Ignorar firmas en mayúsculas de autores o agencias (ej: "JESÚS RODRÍGUEZ/ASOCIACIÓN DESCUBRIENDO MORATALLA", "REDACCIÓN/EFE")
                if (preg_match('/^[A-ZÁÉÍÓÚÑ0-9\s\/\-\.]{4,75}$/u', $txt) && !preg_match('/[a-záéíóúñ]/u', $txt)) {
                    continue;
                }
                if (preg_match('/^(por|texto|foto|redacción|fuente|autor|agencias):?\s+/iu', $txt) && mb_strlen($txt) < 80) {
                    continue;
                }
                if (mb_strlen($txt) >= 30) {
                    $seenBlockTexts[$txt] = true;
                    $domExtractedBlocks[] = $txt;
                }
            }

            if (count($domExtractedBlocks) >= 45) break;
        }

        $domText = implode("\n\n", $domExtractedBlocks);

        // Si el texto extraído del DOM tiene buena longitud, usarlo; de lo contrario recurrir a JSON-LD o metaDesc
        if (mb_strlen($domText) >= mb_strlen($jsonLdArticleBody) && mb_strlen($domText) >= 200) {
            $extractedText = $domText;
        } elseif (mb_strlen($jsonLdArticleBody) >= 200) {
            $extractedText = $jsonLdArticleBody;
        } else {
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

    // Si tras el primer intento no tenemos un texto sustancial (menos de 200 caracteres),
    // intentar localizar la URL directa del medio en DuckDuckGo a partir del titular
    if (mb_strlen(trim($extractedText)) < 200 && !empty($fallbackTitle)) {
        static $ddgRecursionGuard = false;
        if (!$ddgRecursionGuard) {
            $ddgRecursionGuard = true;
            $ddgDirectUrl = searchArticleUrlViaDuckDuckGo($fallbackTitle, $sourceName);
            if (!empty($ddgDirectUrl) && $ddgDirectUrl !== $finalUrl && stripos($ddgDirectUrl, 'google.com') === false) {
                $subInfo = extractArticleContentAndImages($ddgDirectUrl, $fallbackTitle, $fallbackSnippet, $rssImage, $sourceName, $fallbackPubDate);
                $ddgRecursionGuard = false;
                if (!empty($subInfo['body_text']) && mb_strlen(trim($subInfo['body_text'])) >= 200) {
                    return $subInfo;
                }
            }
            $ddgRecursionGuard = false;
        }
    }

    if (empty($detectedSource)) {
        $host = parse_url($finalUrl, PHP_URL_HOST) ?: '';
        $detectedSource = preg_replace('/^www\./i', '', $host) ?: 'Prensa Digital';
    }

    // Localizar la fecha REAL de la noticia en el artículo original
    $dateInfo = detectArticlePublicationDate($html, $finalUrl, $fallbackPubDate);

    return [
        'resolved_url'       => $finalUrl,
        'title'              => $extractedTitle,
        'body_text'          => mb_substr(trim($extractedText), 0, 16000),
        'source_name'        => $detectedSource,
        'original_images'    => array_slice($images, 0, 10),
        'published_date'     => $dateInfo['found'] ? $dateInfo['date_fmt'] : 'Fecha no localizada',
        'published_date_iso' => $dateInfo['found'] ? $dateInfo['date_iso'] : '',
        'date_found'         => $dateInfo['found'],
        'date_source'        => $dateInfo['date_source']
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
 * garantizando que la redacción sea siempre propia (IA), profunda y ceñida exclusivamente a la noticia.
 */
function generateConciseAiDraftViaFreeLlm($prompt) {
    $ch = curl_init('https://text.pollinations.ai/openai');
    $payload = json_encode([
        'model' => 'openai',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Eres un redactor periodístico profesional y riguroso. Tu cometido es leer y analizar minuciosamente todo el texto capturado del artículo web para redactar un borrador periodístico completo, profundo y bien contextualizado con palabras propias de alta calidad, desglosando hechos, antecedentes, participantes, datos y consecuencias sin limitarte al titular. Respondes únicamente en JSON válido.'
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
 * Procesador local limpio y contextualizado (solo en caso de fallo total de red con los motores IA):
 * Ciñe el contenido estrictamente a la noticia seleccionada sin añadir rellenos sobre fiestas ni otros temas.
 */
function transformArticleLocallyToCorporateParts($titleOrig, $bodyOrig, $sourceName, $sourceUrl = '', $categories = [], $articleInfo = []) {
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

    $lead = !empty($paragraphs[0]) ? $paragraphs[0] : $cleanTitle;
    $remaining = count($paragraphs) > 1 ? array_slice($paragraphs, 1, 10) : [];

    $keyPoints = [];
    foreach ($remaining as $dp) {
        $firstSentence = trim(preg_split('/(?<=[.!?])\s+/u', $dp)[0] ?? '');
        if ($firstSentence !== '' && mb_strlen($firstSentence) >= 20 && mb_strlen($firstSentence) <= 180 && count($keyPoints) < 4) {
            $keyPoints[] = $firstSentence;
        }
    }

    $sections = [];
    if (!empty($remaining)) {
        if (count($remaining) <= 4) {
            $sections[] = [
                'heading'    => 'Desarrollo de los hechos',
                'paragraphs' => $remaining,
                'items'      => [],
                'quote'      => ''
            ];
        } else {
            $part1 = array_slice($remaining, 0, 3);
            $part2 = array_slice($remaining, 3);
            $sections[] = [
                'heading'    => 'Desarrollo de los hechos',
                'paragraphs' => $part1,
                'items'      => [],
                'quote'      => ''
            ];
            $sections[] = [
                'heading'    => 'Detalles complementarios y contexto',
                'paragraphs' => $part2,
                'items'      => [],
                'quote'      => ''
            ];
        }
    }

    $suggestedCatId = '';
    foreach ($categories as $c) {
        $cNameLower = mb_strtolower($c['name']);
        if (strpos($titleOnlyLower, $cNameLower) !== false) {
            $suggestedCatId = $c['id'];
            break;
        }
    }

    $resolvedEventDate = !empty($articleInfo['published_date_iso']) ? $articleInfo['published_date_iso'] : '';
    $resolvedDateFound = !empty($articleInfo['date_found']);
    $resolvedDateMsg = $resolvedDateFound
        ? ('Fecha de la noticia localizada: ' . ($articleInfo['published_date'] ?? $resolvedEventDate))
        : 'Fecha no localizada en la fuente de origen';

    return [
        'title'               => $cleanTitle,
        'lead'                => $lead,
        'key_points'          => $keyPoints,
        'sections'            => $sections,
        'topic_tag'           => $topicTag,
        'event_date'          => $resolvedEventDate,
        'date_found'          => $resolvedDateFound,
        'date_status_message' => $resolvedDateMsg,
        'category_id'         => $suggestedCatId,
        'icon'                => $suggestedIcon,
        'image_caption'       => $cleanTitle,
        'ai_image_prompt'     => "Photorealistic editorial news photography in Moratalla, Region of Murcia, Spain, strictly representing: " . $titleOrig . ", natural daylight, realistic journalism photo, no text",
        'archive_keywords'    => $keywords,
        'source_name'         => $sourceName,
        'source_url'          => $sourceUrl
    ];
}

/**
 * Genera el borrador supervisado con el diseño visual corporativo,
 * redactado por IA con redacción propia, de forma profunda, contextualizada y ceñida exclusivamente a la noticia seleccionada.
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

    $articleDateIso = $articleInfo['published_date_iso'] ?? '';
    $articleDateFound = !empty($articleInfo['date_found']);
    $articleDateFmt = $articleInfo['published_date'] ?? 'Fecha no localizada';

    $dateDetectionPromptText = $articleDateFound
        ? "Fecha de publicación REAL localizada en el artículo original: {$articleDateFmt} ({$articleDateIso}). Debes colocar esta fecha concreta en \"event_date\" y poner \"date_found\": true salvo que el texto detalle expresamente otra fecha de celebración o acontecimiento."
        : "Fecha de publicación original: NO LOCALIZADA en la fuente. Si en el texto tampoco se menciona una fecha concreta de suceso, devuelve estrictamente null o \"\" en \"event_date\" y pon \"date_found\": false. NUNCA inventes la fecha actual.";

    $prompt = <<<PROMPT
Eres un redactor periodístico profesional de dilatada experiencia. Fecha actual de referencia: {$todayIso}.

Tu misión es leer y analizar a fondo TODO el texto capturado de la noticia original para redactar un borrador periodístico completo, profundo y contextualizado con PALABRAS PROPIAS (redacción periodística original de alta calidad, sin copiar frases literales del texto de origen).

DIRECTRICES FUNDAMENTALES DE REDACCIÓN:
1. PROFUNDIDAD Y CONTEXTUALIZACIÓN:
   - Prohibido quedarse solo en el titular o hacer un resumen telegráfico superficial.
   - Lee todo el texto aportado y desarrolla la noticia en profundidad (entre 2 y 4 párrafos sustanciosos y bien hilados en las secciones).
   - Explica con claridad: los antecedentes del asunto, los hechos ocurridos o la iniciativa presentada, quiénes intervienen (organismos, colectivos, personas o autoridades), las declaraciones o citas textuales si las hubiere, las fechas y lugares exactos, y el impacto o repercusiones para los vecinos de Moratalla y su comarca según el texto.
2. RIGUROSIDAD Y FIDELIDAD A LOS HECHOS:
   - Cíñete 100% al asunto real de la noticia. Conserva con exactitud los datos reales (fechas, nombres, lugares, cifras) sin inventar hechos no mencionados.
   - PROHIBIDO poner referencias a "Redacción de moratalla-murcia.com", "moratalla-murcia.com" o autorreferencias a nuestra web.
   - PROHIBIDO desviar la noticia hacia fiestas, turismo, encierros o el castillo si el texto original trata de otro tema (medio ambiente, obras, economía, sucesos, deportes, etc.).
3. ESTRUCTURA PERIODÍSTICA DEL BORRADOR:
   - Titular propio, informativo, riguroso y atractivo (máx 100 caracteres).
   - Entradilla (lead) sólida de 2 a 3 frases (40 a 70 palabras) con redacción propia resumiendo el hecho central y su alcance.
   - Claves de la noticia (key_points): entre 2 y 4 puntos esenciales con datos, cifras o conclusiones concretas del artículo.
   - Secciones (sections): estructuradas con subtítulos periodísticos claros (heading) y párrafos desarrollados que agrupen la información de forma lógica.
   - Cita textual (quote): si el artículo original contiene declaraciones relevantes entrecomilladas o directas de algún protagonista, reprodúcela fielmente en el campo "quote" de la sección correspondiente.
4. CRITERIO ESTRICTO DE FECHA:
   - NUNCA asignes la fecha de hoy ({$todayIso}) por defecto, a menos que el texto afirme de forma indiscutible que ocurrió hoy.
   - Si la fecha de la noticia fue localizada ("{$articleDateIso}"), colócala en "event_date" y "date_found": true.
   - Si la noticia describe un suceso con una fecha concreta en el texto, usa esa fecha en "event_date" en formato YYYY-MM-DD y "date_found": true.
   - Si no se localiza ninguna fecha en los datos ni en el texto, debes devolver estrictamente null o "" en "event_date", y "date_found": false. NUNCA inventes la fecha actual.

Noticia seleccionada:
- Titular original: {$titleOrig}
- Obtenido de (Fuente): {$sourceName}
- {$dateDetectionPromptText}
- Texto completo capturado de la noticia original:
{$bodyOrig}

Categorías disponibles en la base de datos:
{$catsPromptStr}

Devuelve EXCLUSIVAMENTE un objeto JSON válido (sin bloques markdown ```json) con esta estructura exacta:
{
  "title": "Titular periodístico con redacción propia, claro y conciso, fiel a la noticia seleccionada (máx 100 caracteres)",
  "topic_tag": "Etiqueta corta del tema real de esta noticia (ej: Actualidad Municipal, Medio Ambiente, Infraestructuras, Deportes, Cultura, Economía, Sucesos)",
  "lead": "Entradilla periodística sólida de 2 a 3 frases con redacción propia resumiendo el hecho central y su alcance.",
  "key_points": [
    "Dato clave 1 extraído de la noticia",
    "Dato clave 2 con detalles concretos",
    "Dato clave 3..."
  ],
  "sections": [
    {
      "heading": "Subtítulo periodístico sobre el desarrollo de los hechos",
      "paragraphs": [
        "Párrafo 1 profundizando en los hechos principales con redacción propia...",
        "Párrafo 2 aportando contexto, antecedentes o detalles complementarios expuestos en el texto original..."
      ],
      "items": [],
      "quote": "Cita textual si aparece en la noticia original, o cadena vacía"
    },
    {
      "heading": "Subtítulo sobre el impacto, acuerdos o siguientes pasos",
      "paragraphs": [
        "Párrafo que detalle las repercusiones, acuerdos o participantes..."
      ],
      "items": [],
      "quote": ""
    }
  ],
  "event_date": "Fecha real de la noticia o acontecimiento en formato español día/mes/año DD/MM/YYYY (ej: 21/10/2026), o null si no se localiza ninguna fecha",
  "date_found": true,
  "date_status_message": "Explicación breve de la fecha localizada (ej: 'Fecha de la noticia localizada: DD/MM/YYYY' o 'Fecha de la noticia no localizada en el texto original')",
  "category_id": "ID numérico de la categoría más adecuada de la lista proporcionada, o cadena vacía",
  "icon": "Un único emoji acorde estrictamente al tema de la noticia (ej. 🌲, 🏛️, 📰, ⚠️, 🏆, 🎭, 🚧)",
  "image_caption": "Pie de foto descriptivo y conciso sobre la noticia (máx 100 caracteres)",
  "ai_image_prompt": "Detailed photorealistic editorial news photography strictly illustrating: {$titleOrig}, in Moratalla, Murcia, Spain, natural lighting, no text",
  "archive_keywords": "1 o 2 palabras clave en español estrictamente relacionadas con el tema de la noticia"
}
PROMPT;

    $lastError = '';

    $finalizeDraftDate = function(&$draft) use ($articleInfo) {
        $evDate = trim((string)($draft['event_date'] ?? ''));
        if ($evDate !== '' && strtolower($evDate) !== 'null') {
            $parsed = detectArticlePublicationDate('', '', $evDate);
            if ($parsed['found']) {
                $draft['event_date'] = $parsed['date_iso'];
                $draft['event_date_formatted'] = $parsed['date_fmt'];
                $draft['date_found'] = true;
                $draft['date_status_message'] = 'Fecha de la noticia localizada: ' . $parsed['date_fmt'];
            } elseif (!empty($articleInfo['date_found'])) {
                $draft['event_date'] = $articleInfo['published_date_iso'];
                $draft['event_date_formatted'] = formatDateToSpanish($articleInfo['published_date']);
                $draft['date_found'] = true;
                $draft['date_status_message'] = 'Fecha de la noticia localizada: ' . $draft['event_date_formatted'];
            } else {
                $draft['event_date'] = '';
                $draft['event_date_formatted'] = '';
                $draft['date_found'] = false;
                $draft['date_status_message'] = 'Fecha no localizada en la fuente de origen';
            }
        } elseif (!empty($articleInfo['date_found'])) {
            $draft['event_date'] = $articleInfo['published_date_iso'];
            $draft['event_date_formatted'] = formatDateToSpanish($articleInfo['published_date']);
            $draft['date_found'] = true;
            $draft['date_status_message'] = 'Fecha de la noticia localizada: ' . $draft['event_date_formatted'];
        } else {
            $draft['event_date'] = '';
            $draft['event_date_formatted'] = '';
            $draft['date_found'] = false;
            $draft['date_status_message'] = 'Fecha no localizada en la fuente de origen';
        }
    };

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
                        $finalizeDraftDate($jsonDraft);
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
        $finalizeDraftDate($freeAiDraft);
        $freeAiDraft['source_name'] = $sourceName;
        $freeAiDraft['source_url'] = $sourceUrl;
        $freeAiDraft['content_html'] = buildMoratallaCorporateNewsHtml($freeAiDraft);
        $freeAiDraft['used_gemini'] = true;
        $freeAiDraft['model'] = 'IA Redacción Propia';
        return $freeAiDraft;
    }

    // 3. Respaldo local escueto sin rellenos ajenos
    $localParts = transformArticleLocallyToCorporateParts($titleOrig, $bodyOrig, $sourceName, $sourceUrl, $categories, $articleInfo);
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

/**
 * Obtiene la configuración actual de los horarios de búsqueda automática diaria de noticias
 */
function getAutoNewsDigestConfig($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('auto_news_time_1', 'auto_news_time_2', 'auto_news_last_run_1', 'auto_news_last_run_2', 'auto_news_last_status', 'admin_email', 'news_search_date_from', 'news_search_date_to')");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];

    $time1 = isset($rows['auto_news_time_1']) && preg_match('/^\d{2}:\d{2}$/', trim($rows['auto_news_time_1']))
        ? trim($rows['auto_news_time_1'])
        : '00:00';
    $time2 = isset($rows['auto_news_time_2']) && preg_match('/^\d{2}:\d{2}$/', trim($rows['auto_news_time_2']))
        ? trim($rows['auto_news_time_2'])
        : '00:00';

    $mailCfg = getMailConfig($pdo);
    $recipient = !empty($rows['admin_email']) ? trim($rows['admin_email']) : $mailCfg['admin_email'];

    $dateFrom = isset($rows['news_search_date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($rows['news_search_date_from']))
        ? trim($rows['news_search_date_from'])
        : '';
    $dateTo = isset($rows['news_search_date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($rows['news_search_date_to']))
        ? trim($rows['news_search_date_to'])
        : '';

    return [
        'time_1'        => $time1,
        'time_2'        => $time2,
        'last_run_1'    => $rows['auto_news_last_run_1'] ?? '',
        'last_run_2'    => $rows['auto_news_last_run_2'] ?? '',
        'last_status'   => $rows['auto_news_last_status'] ?? '',
        'recipient'     => $recipient,
        'date_from'     => $dateFrom,
        'date_to'       => $dateTo
    ];
}

/**
 * Busca noticias combinadas de Moratalla y de la Banda de Música de Moratalla,
 * las registra en `ai_processed_news` para que queden disponibles en el proceso de gestión del panel,
 * enriquece las noticias con su resumen e imagen y devuelve el listado completo más las conteos por estado
 * (Disponibles, Ya Publicadas y Descartadas).
 */
function collectAndSummarizeMoratallaDigestNews($pdo, $maxItemsToEnrich = 30) {
    ensureAiNewsTrackingSchema($pdo);

    $cfg = getAutoNewsDigestConfig($pdo);
    $dateFrom = $cfg['date_from'] ?? '';
    $dateTo   = $cfg['date_to'] ?? '';

    // Ejecutar la misma búsqueda unificada que se realiza por defecto en Administración
    // utilizando idénticos criterios, filtros de fecha delimitada y descarte de duplicados
    $combined = searchMoratallaNews($pdo, 'Moratalla Murcia y Banda de Música de Moratalla', $maxItemsToEnrich, $dateFrom, $dateTo);

    // Separar por estado para mantener sincronizado el flujo de Nuevas Disponibles, Publicadas y Descartadas
    $newAvailable = [];
    $publishedList = [];
    $discardedList = [];

    foreach ($combined as $item) {
        $st = $item['process_status'] ?? 'new';
        if ($st === 'published') {
            $publishedList[] = $item;
        } elseif ($st === 'discarded') {
            $discardedList[] = $item;
        } else {
            // Nuevas disponibles con URL o título identificable
            if (!empty($item['url']) || !empty($item['title'])) {
                $newAvailable[] = $item;
            }
        }
    }

    // Ordenar las nuevas por más recientes
    usort($newAvailable, function ($a, $b) {
        return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
    });

    // Enviar EXCLUSIVAMENTE las noticias nuevas o no procesadas (NUNCA incluir noticias ya publicadas)
    $selected = $newAvailable;

    // Construir la estructura completa de cada noticia respetando exactamente los datos de la búsqueda
    $enrichedItems = [];
    foreach ($selected as $idx => $item) {
        $imageUrl = !empty($item['rss_image'])
            ? $item['rss_image']
            : 'https://www.moratalla-murcia.com/moratalla/uploads/theme/moratalla.jpg';

        $summary = trim($item['snippet'] ?? '');
        if ($summary === '') {
            $summary = $item['title'];
        }
        if (mb_strlen($summary) > 420) {
            $summary = mb_substr($summary, 0, 417) . '...';
        }

        $topicBadge = !empty($item['topic_badge'])
            ? $item['topic_badge']
            : ((stripos($item['title'], 'banda') !== false || stripos($item['snippet'] ?? '', 'banda') !== false)
                ? '🎵 Banda de Música de Moratalla'
                : '📰 Actualidad Moratalla');

        $enrichedItems[] = [
            'title'           => $item['title'],
            'source'          => $item['source'] ?: 'Prensa Digital',
            'pub_date'        => $item['pub_date'] ?: 'Fecha no localizada',
            'event_date_iso'  => $item['event_date_iso'] ?? '',
            'date_found'      => !empty($item['date_found']),
            'url'             => $item['url'] ?? '',
            'original_url'    => $item['url'] ?? '',
            'image_url'       => $imageUrl,
            'summary'         => $summary,
            'topic_badge'     => $topicBadge,
            'process_status'  => $item['process_status'] ?? 'new',
            'matched_news_id' => $item['matched_news_id'] ?? null,
            'matched_title'   => $item['matched_title'] ?? null
        ];
    }

    // Si hay clave Gemini configurada, mejorar los resúmenes en una sola llamada rápida por lote
    $apiKey = getGeminiApiKey($pdo);
    if (!empty($apiKey) && !empty($enrichedItems)) {
        $batchInput = [];
        foreach ($enrichedItems as $i => $ei) {
            $batchInput[] = [
                'index'   => $i,
                'title'   => $ei['title'],
                'source'  => $ei['source'],
                'content' => $ei['summary']
            ];
        }
        $batchPrompt = "Eres un asistente periodístico de Moratalla (Murcia). Para cada una de las siguientes noticias en JSON, redacta un resumen claro, atractivo y directo en español de 2 a 3 frases (máximo 55 palabras por noticia) ceñido estrictamente a los hechos de esa noticia. Devuelve EXCLUSIVAMENTE un array JSON de objetos con el formato [{\"index\": 0, \"summary\": \"...\"}].\n\nNoticias:\n" . json_encode($batchInput, JSON_UNESCAPED_UNICODE);

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($apiKey);
        $payload = json_encode([
            'contents' => [['parts' => [['text' => $batchPrompt]]]],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 2000,
                'responseMimeType' => 'application/json'
            ]
        ]);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && !empty($resp)) {
            $dec = @json_decode($resp, true);
            $txt = $dec['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!empty($txt)) {
                $txt = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($txt));
                $arr = @json_decode($txt, true);
                if (is_array($arr)) {
                    foreach ($arr as $row) {
                        $idx = isset($row['index']) ? (int)$row['index'] : -1;
                        if ($idx >= 0 && isset($enrichedItems[$idx]) && !empty($row['summary'])) {
                            $enrichedItems[$idx]['summary'] = trim($row['summary']);
                        }
                    }
                }
            }
        }
    }

    // Registrar todas las noticias nuevas en `ai_processed_news` con estado 'available'
    // junto con su resumen e imagen, para que queden incorporadas de forma idéntica en el panel Admin
    foreach ($enrichedItems as $ei) {
        if (($ei['process_status'] ?? 'new') === 'new') {
            markNewsAsProcessed(
                $pdo,
                $ei['url'] ?: $ei['original_url'],
                $ei['title'],
                $ei['source'],
                'available',
                null,
                $ei['summary'],
                $ei['image_url'],
                $ei['pub_date'],
                $ei['event_date_iso']
            );
        }
    }

    return [
        'enriched'  => $enrichedItems,
        'published' => array_slice($publishedList, 0, 10),
        'discarded' => array_slice($discardedList, 0, 10),
        'counts'    => [
            'new'       => count($newAvailable),
            'published' => count($publishedList),
            'discarded' => count($discardedList)
        ],
        'date_from' => $dateFrom,
        'date_to'   => $dateTo
    ];
}

/**
 * Envía el boletín diario con resumen e imágenes al correo electrónico configurado en administración
 */
function sendMoratallaNewsDigestEmail($pdo, $slotLabel = 'Automático', &$errorDetail = null) {
    $cfg = getMailConfig($pdo);
    $digestCfg = getAutoNewsDigestConfig($pdo);

    $recipientEmail = $digestCfg['recipient'] ?: $cfg['admin_email'];
    if (empty($recipientEmail)) {
        $errorDetail = "No hay correo electrónico de administrador configurado.";
        return false;
    }

    $digestData = collectAndSummarizeMoratallaDigestNews($pdo, 30);
    $items = $digestData['enriched'] ?? [];
    $publishedItems = $digestData['published'] ?? [];
    $discardedItems = $digestData['discarded'] ?? [];
    $counts = $digestData['counts'] ?? ['new' => count($items), 'published' => count($publishedItems), 'discarded' => count($discardedItems)];

    $dateStr = date('d/m/Y H:i');
    $totalItems = count($items);

    $dateFrom = $digestData['date_from'] ?? ($digestCfg['date_from'] ?? '');
    $dateTo   = $digestData['date_to'] ?? ($digestCfg['date_to'] ?? '');
    $dateFromFmt = formatDateToSpanish($dateFrom);
    $dateToFmt   = formatDateToSpanish($dateTo);
    $dateRangeInfo = '';
    if ($dateFrom !== '' && $dateTo !== '') {
        $dateRangeInfo = "Delimitación temporal: Desde {$dateFromFmt} hasta {$dateToFmt}";
    } elseif ($dateFrom !== '') {
        $dateRangeInfo = "Delimitación temporal: Desde {$dateFromFmt}";
    } elseif ($dateTo !== '') {
        $dateRangeInfo = "Delimitación temporal: Hasta {$dateToFmt}";
    }

    $siteBaseUrl = 'https://www.moratalla-murcia.com/moratalla';
    if (!empty($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $siteBaseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(BASE_URL, '/');
    }
    $adminPanelUrl = $siteBaseUrl . '/admin/news.php?action=add&open_ai=1';

    $subject = ($totalItems === 0)
        ? "📰 Boletín de Noticias de Moratalla: No hay noticias nuevas que procesar - " . date('d/m/Y')
        : "📰 Noticias de Moratalla y Banda de Música ({$totalItems} nuevas pendientes de procesar) - " . date('d/m/Y');

    // Construir versión texto plano
    $plainBody  = "RESUMEN AUTOMÁTICO DE NOTICIAS - MORATALLA Y BANDA DE MÚSICA\r\n";
    $plainBody .= "Fecha y hora: {$dateStr} ({$slotLabel})\r\n";
    if ($dateRangeInfo !== '') {
        $plainBody .= "{$dateRangeInfo}\r\n";
    }
    $plainBody .= "Estado actual: {$counts['new']} Nuevas Disponibles | {$counts['published']} Ya Publicadas en Web | {$counts['discarded']} Descartadas\r\n";
    $plainBody .= "=====================================================================\r\n\r\n";

    if (empty($items)) {
        $plainBody .= "ESTADO: NO HAY NOTICIAS NUEVAS QUE PROCESAR\r\n\r\n";
        $plainBody .= "Se ha realizado la extracción automática de noticias sobre Moratalla y la Banda de Música de Moratalla y se han contrastado contra la web oficial (moratalla-murcia.com).\r\n\r\n";
        $plainBody .= "En este momento no se han localizado titulares nuevos pendientes de procesar. Todas las noticias detectadas en la red ya han sido contrastadas y publicadas en la web o descartadas anteriormente.\r\n\r\n";
    } else {
        foreach ($items as $i => $it) {
            $num = $i + 1;
            $datePlain = !empty($it['date_found'])
                ? formatDateToSpanish($it['pub_date'], true)
                : "[⚠️ Fecha no localizada en la fuente]";
            $draftPrefillParams = "&prefill_url=" . urlencode($it['url'])
                . "&prefill_title=" . urlencode($it['title'])
                . "&prefill_source=" . urlencode($it['source'])
                . "&prefill_img=" . urlencode($it['image_url'])
                . "&prefill_date=" . urlencode($it['pub_date'])
                . "&prefill_date_iso=" . urlencode($it['event_date_iso'] ?? '')
                . "&date_found=" . (!empty($it['date_found']) ? '1' : '0');

            $plainBody .= "{$num}. [{$it['topic_badge']}] {$it['title']}\r\n";
            $plainBody .= "   Fuente: {$it['source']} | Fecha: {$datePlain}\r\n";
            $plainBody .= "   Resumen: {$it['summary']}\r\n";
            $plainBody .= "   Enlace original: {$it['url']}\r\n";
            $plainBody .= "   Redactar con IA: {$adminPanelUrl}{$draftPrefillParams}\r\n\r\n";
        }
    }

    if (!empty($publishedItems)) {
        $plainBody .= "NOTICIAS YA CONTRASTADAS Y PUBLICADAS EN LA WEB (Filtradas automáticamente):\r\n";
        foreach (array_slice($publishedItems, 0, 7) as $pubIt) {
            $plainBody .= " - [PUBLICADA] " . ($pubIt['title'] ?? '') . " (" . ($pubIt['source'] ?? '') . ")\r\n";
        }
        $plainBody .= "\r\n";
    }

    if (!empty($discardedItems)) {
        $plainBody .= "DESCARTADAS ANTERIORMENTE (Omitidas del bloque principal, recuperables en el panel):\r\n";
        foreach (array_slice($discardedItems, 0, 5) as $discIt) {
            $plainBody .= " - [DESCARTADA] " . ($discIt['title'] ?? '') . " (" . ($discIt['source'] ?? '') . ")\r\n";
        }
        $plainBody .= "\r\n";
    }

    $plainBody .= "---------------------------------------------------------------------\r\n";
    $plainBody .= "Gestionar, publicar o descartar noticias en el Panel de Administración:\r\n{$adminPanelUrl}\r\n";

    // Barra de estadísticas sincronizada con el proceso de noticias del panel
    $dateFilterHtml = ($dateRangeInfo !== '')
        ? "<div style='margin-top:8px; font-size:12px; color:#475569;'><span style='background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:2px 8px; border-radius:10px; font-weight:700;'>📅 {$dateRangeInfo}</span></div>"
        : "";

    $statsHeaderHtml = "
        <div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px; margin-bottom:20px; font-size:13px; color:#334155;'>
            <div style='font-weight:bold; color:#1e293b; margin-bottom:6px;'>📊 Estado sincronizado con el Gestor de Noticias IA:</div>
            <span style='display:inline-block; background:#eef2ff; color:#3730a3; border:1px solid #c7d2fe; padding:4px 10px; border-radius:14px; font-weight:bold; margin-right:6px; margin-bottom:4px;'>🟢 {$counts['new']} Nuevas disponibles</span>
            <span style='display:inline-block; background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; padding:4px 10px; border-radius:14px; font-weight:bold; margin-right:6px; margin-bottom:4px;'>🔵 {$counts['published']} Ya publicadas en web</span>
            <span style='display:inline-block; background:#e2e8f0; color:#475569; border:1px solid #cbd5e1; padding:4px 10px; border-radius:14px; font-weight:bold; margin-bottom:4px;'>⚪ {$counts['discarded']} Descartadas</span>
            {$dateFilterHtml}
        </div>";

    // Construir versión HTML con imágenes y tarjetas
    $cardsHtml = $statsHeaderHtml;
    if (empty($items)) {
        $cardsHtml .= "
            <div style='background:#f0fdf4; border:2px solid #86efac; border-radius:12px; padding:28px 24px; text-align:center; color:#166534; margin-bottom:24px;'>
                <div style='font-size:36px; line-height:1; margin-bottom:12px;'>✅</div>
                <h3 style='margin:0 0 10px 0; font-size:20px; color:#14532d; font-weight:700;'>No hay noticias nuevas que procesar</h3>
                <p style='margin:0 0 10px 0; font-size:14px; line-height:1.6; color:#166534;'>
                    Se ha completado la extracción y el contraste automático con la base de datos de noticias de <strong>moratalla-murcia.com</strong>.
                </p>
                <p style='margin:0; font-size:13px; line-height:1.5; color:#15803d;'>
                    Todas las noticias localizadas en los medios digitales y redes sobre <strong>Moratalla</strong> y la <strong>Banda de Música de Moratalla</strong> ya han sido <strong>contrastadas y publicadas en la web</strong> o <strong>descartadas</strong> previamente. No hay novedades pendientes en este turno.
                </p>
            </div>";
    } else {
        foreach ($items as $it) {
            $safeTitle = htmlspecialchars($it['title'], ENT_QUOTES, 'UTF-8');
            $safeSource = htmlspecialchars($it['source'], ENT_QUOTES, 'UTF-8');
            $safeDate = htmlspecialchars(formatDateToSpanish($it['pub_date'], true), ENT_QUOTES, 'UTF-8');
            $safeSummary = nl2br(htmlspecialchars($it['summary'], ENT_QUOTES, 'UTF-8'));
            $safeUrl = htmlspecialchars($it['url'], ENT_QUOTES, 'UTF-8');
            $safeImg = htmlspecialchars($it['image_url'], ENT_QUOTES, 'UTF-8');
            $safeTopic = htmlspecialchars($it['topic_badge'], ENT_QUOTES, 'UTF-8');

            $directDraftUrl = $adminPanelUrl
                . '&prefill_url=' . urlencode($it['url'])
                . '&prefill_title=' . urlencode($it['title'])
                . '&prefill_source=' . urlencode($it['source'])
                . '&prefill_img=' . urlencode($it['image_url'])
                . '&prefill_date=' . urlencode($it['pub_date'])
                . '&prefill_date_iso=' . urlencode($it['event_date_iso'] ?? '')
                . '&date_found=' . (!empty($it['date_found']) ? '1' : '0');
            $safeDirectDraftUrl = htmlspecialchars($directDraftUrl, ENT_QUOTES, 'UTF-8');

            $directDiscardUrl = $adminPanelUrl
                . '&quick_action=discard'
                . '&prefill_url=' . urlencode($it['url'])
                . '&prefill_title=' . urlencode($it['title'])
                . '&prefill_source=' . urlencode($it['source']);
            $safeDirectDiscardUrl = htmlspecialchars($directDiscardUrl, ENT_QUOTES, 'UTF-8');

            $statusPill = "<span style='display:inline-block; background:#ede9fe; color:#5b21b6; font-size:11px; font-weight:bold; padding:3px 9px; border-radius:12px;'>NUEVA PENDIENTE DE PROCESAR</span>";

            $dateBadgeHtml = !empty($it['date_found'])
                ? "<span style='color:#059669; font-weight:700;'>📅 {$safeDate}</span>"
                : "<span style='display:inline-block; background:#fef3c7; color:#92400e; font-size:11px; font-weight:bold; padding:2px 7px; border-radius:6px; border:1px solid #fde68a;'>⚠️ Fecha no localizada</span>";

            $dateBadgeHtml = !empty($it['date_found'])
                ? "<span style='color:#059669; font-weight:700;'>📅 {$safeDate}</span>"
                : "<span style='display:inline-block; background:#fef3c7; color:#92400e; font-size:11px; font-weight:bold; padding:2px 7px; border-radius:6px; border:1px solid #fde68a;'>⚠️ Fecha no localizada</span>";

            $imgBlock = '';
            if (!empty($safeImg)) {
                $imgBlock = "
                    <div style='width:100%; max-height:240px; overflow:hidden; background:#e2e8f0;'>
                        <a href='{$safeUrl}' target='_blank' style='text-decoration:none; display:block;'>
                            <img src='{$safeImg}' alt='{$safeTitle}' style='width:100%; height:210px; object-fit:cover; display:block; border:0;'>
                        </a>
                    </div>";
            }

            $cardsHtml .= "
            <div style='background:#ffffff; border:1px solid #d8e2dc; border-radius:12px; overflow:hidden; margin-bottom:22px; box-shadow:0 3px 10px rgba(0,0,0,0.05);'>
                {$imgBlock}
                <div style='padding:18px 20px;'>
                    <div style='margin-bottom:8px;'>
                        <span style='display:inline-block; background:#1b4332; color:#ffffff; font-size:11px; font-weight:bold; padding:3px 10px; border-radius:12px; margin-right:6px;'>{$safeTopic}</span>
                        {$statusPill}
                        <span style='font-size:12px; color:#64748b; margin-left:6px;'><strong>{$safeSource}</strong> &bull; {$dateBadgeHtml}</span>
                    </div>
                    <h3 style='margin:0 0 10px 0; font-size:18px; line-height:1.35; color:#1b4332;'>
                        <a href='{$safeUrl}' target='_blank' style='color:#1b4332; text-decoration:none;'>{$safeTitle}</a>
                    </h3>
                    <p style='margin:0 0 16px 0; font-size:14px; line-height:1.6; color:#334155;'>
                        {$safeSummary}
                    </p>
                    <div>
                        <a href='{$safeDirectDraftUrl}' target='_blank' style='display:inline-block; background:#6d28d9; color:#ffffff; text-decoration:none; font-size:13px; font-weight:bold; padding:8px 14px; border-radius:6px; margin-right:6px; margin-bottom:6px;'>✨ Redactar y Publicar en Admin</a>
                        <a href='{$safeUrl}' target='_blank' style='display:inline-block; background:#2d6a4f; color:#ffffff; text-decoration:none; font-size:13px; font-weight:bold; padding:8px 14px; border-radius:6px; margin-right:6px; margin-bottom:6px;'>Leer original &rarr;</a>
                        <a href='{$safeDirectDiscardUrl}' target='_blank' style='display:inline-block; background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; text-decoration:none; font-size:12px; font-weight:bold; padding:7px 12px; border-radius:6px; margin-bottom:6px;'>🚫 Descartar</a>
                    </div>
                </div>
            </div>";
        }
    }

    // Bloque resumen de noticias ya publicadas o descartadas para facilitar el control sin duplicar
    if (!empty($publishedItems) || !empty($discardedItems)) {
        $cardsHtml .= "<div style='background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; margin-top:10px;'>";
        $cardsHtml .= "<h4 style='margin:0 0 10px 0; font-size:14px; color:#1e293b;'>🗂️ Control de Noticias Ya Publicadas o Descartadas (Incorporadas en el Buscador IA)</h4>";

        if (!empty($publishedItems)) {
            $cardsHtml .= "<div style='margin-bottom:10px;'><div style='font-size:12px; font-weight:bold; color:#065f46; margin-bottom:4px;'>🔵 Ya publicadas en la web (filtradas automáticamente):</div><ul style='margin:0; padding-left:18px; font-size:12px; color:#334155;'>";
            foreach (array_slice($publishedItems, 0, 5) as $pubIt) {
                $pTitle = htmlspecialchars($pubIt['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $pSource = htmlspecialchars($pubIt['source'] ?? '', ENT_QUOTES, 'UTF-8');
                $editLink = !empty($pubIt['matched_news_id'])
                    ? " — <a href='{$siteBaseUrl}/admin/news.php?action=edit&id=" . (int)$pubIt['matched_news_id'] . "' target='_blank' style='color:#059669; font-weight:bold;'>Editar en web</a>"
                    : "";
                $cardsHtml .= "<li style='margin-bottom:3px;'>{$pTitle} <span style='color:#64748b;'>({$pSource})</span>{$editLink}</li>";
            }
            $cardsHtml .= "</ul></div>";
        }

        if (!empty($discardedItems)) {
            $cardsHtml .= "<div><div style='font-size:12px; font-weight:bold; color:#475569; margin-bottom:4px;'>⚪ Descartadas anteriormente (puedes recuperarlas desde la pestaña «Descartadas» del panel):</div><ul style='margin:0; padding-left:18px; font-size:12px; color:#64748b;'>";
            foreach (array_slice($discardedItems, 0, 5) as $discIt) {
                $dTitle = htmlspecialchars($discIt['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $dSource = htmlspecialchars($discIt['source'] ?? '', ENT_QUOTES, 'UTF-8');
                $cardsHtml .= "<li style='margin-bottom:3px;'>{$dTitle} <span>({$dSource})</span></li>";
            }
            $cardsHtml .= "</ul></div>";
        }

        $cardsHtml .= "</div>";
    }

    $safeAdminUrl = htmlspecialchars($adminPanelUrl, ENT_QUOTES, 'UTF-8');
    $safeSlot = htmlspecialchars($slotLabel, ENT_QUOTES, 'UTF-8');

    $htmlBody = "<!DOCTYPE html><html lang='es'><body style='margin:0; padding:20px; font-family: Arial, Helvetica, sans-serif; background:#f1f5f3; color:#1e293b;'>
        <div style='max-width:660px; margin:0 auto; background:#ffffff; border-radius:16px; overflow:hidden; border:2px solid #2d6a4f; box-shadow:0 8px 24px rgba(0,0,0,0.08);'>
            <div style='background:linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%); color:#ffffff; padding:22px 26px;'>
                <div style='font-size:12px; text-transform:uppercase; letter-spacing:1px; color:#d4af37; font-weight:bold; margin-bottom:4px;'>Boletín Automático Desatendido &bull; {$safeSlot}</div>
                <h2 style='margin:0; font-size:22px; line-height:1.3;'>Noticias de Moratalla y Banda de Música</h2>
                <p style='margin:6px 0 0 0; font-size:13px; opacity:0.9;'>Resumen diario generado el {$dateStr} para <strong>moratalla-murcia.com</strong></p>
            </div>
            <div style='padding:24px;'>
                {$cardsHtml}
                <div style='text-align:center; margin-top:26px; padding-top:20px; border-top:1px solid #e2e8f0;'>
                    <a href='{$safeAdminUrl}' target='_blank' style='display:inline-block; background:#6d28d9; color:#ffffff; text-decoration:none; font-size:14px; font-weight:bold; padding:12px 24px; border-radius:8px;'>Ir al Panel de Noticias / Eventos (Admin)</a>
                    <p style='font-size:12px; color:#64748b; margin-top:14px; margin-bottom:0;'>
                        Puedes modificar o desactivar los 2 horarios diarios de este envío automático (poniendo 00:00) desde <strong>Configuración General</strong> en el panel de administración.
                    </p>
                </div>
            </div>
        </div>
    </body></html>";

    $attempts = [
        ['port' => $cfg['smtp_port'], 'enc' => $cfg['smtp_encryption']],
    ];
    if ((int)$cfg['smtp_port'] === 587) {
        $attempts[] = ['port' => 465, 'enc' => 'ssl'];
    } else {
        $attempts[] = ['port' => 587, 'enc' => 'tls'];
    }

    $errors = [];
    foreach ($attempts as $attempt) {
        try {
            smtpSendRaw(
                $cfg['smtp_host'],
                $attempt['port'],
                $attempt['enc'],
                $cfg['smtp_user'],
                $cfg['smtp_pass'],
                $cfg['smtp_user'],
                "Buscador IA Moratalla-Murcia",
                $recipientEmail,
                $cfg['smtp_user'],
                "Moratalla-Murcia Admin",
                $subject,
                $plainBody,
                $htmlBody
            );
            return true;
        } catch (Exception $e) {
            $errors[] = "[{$cfg['smtp_host']}:{$attempt['port']}/{$attempt['enc']}] " . $e->getMessage();
        }
    }

    $errorDetail = implode(" | ", $errors);
    @file_put_contents(dirname(__DIR__, 2) . '/error_mail.log', date('Y-m-d H:i:s') . " [AUTO-NEWS] - " . $errorDetail . PHP_EOL, FILE_APPEND);
    return false;
}

/**
 * Evalúa si alguno de los 2 horarios configurados (distintos de 00:00) ya ha llegado en el día de hoy
 * y aún no se ha ejecutado hoy. Si corresponde, ejecuta la búsqueda y envía el correo.
 */
function checkAndRunScheduledNewsDigest($pdo, $forceRun = false) {
    date_default_timezone_set('Europe/Madrid');
    $cfg = getAutoNewsDigestConfig($pdo);
    $today = date('Y-m-d');
    $nowHm = date('H:i');

    $upsertSetting = function ($key, $val) use ($pdo) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $val, $val]);
    };

    if ($forceRun) {
        $err = null;
        $ok = sendMoratallaNewsDigestEmail($pdo, "Envío Manual / Prueba ({$nowHm})", $err);
        $statusText = $ok
            ? ("Último envío manual con éxito el " . date('d/m/Y H:i') . " a " . $cfg['recipient'])
            : ("Error en envío (" . date('d/m/Y H:i') . "): " . ($err ?: 'Fallo SMTP'));
        $upsertSetting('auto_news_last_status', $statusText);
        return [
            'executed' => true,
            'success'  => $ok,
            'message'  => $statusText
        ];
    }

    $slotsToCheck = [
        1 => ['time' => $cfg['time_1'], 'last_run' => $cfg['last_run_1'], 'key_run' => 'auto_news_last_run_1'],
        2 => ['time' => $cfg['time_2'], 'last_run' => $cfg['last_run_2'], 'key_run' => 'auto_news_last_run_2']
    ];

    $results = [];
    foreach ($slotsToCheck as $slotNum => $slot) {
        $schedTime = trim($slot['time']);
        if ($schedTime === '' || $schedTime === '00:00') {
            continue;
        }
        // Si ya se ejecutó hoy en este horario, omitir
        if ($slot['last_run'] === $today) {
            continue;
        }
        // Si la hora actual en España ya alcanzó o superó la hora programada
        if ($nowHm >= $schedTime) {
            // Marcar inmediatamente como ejecutado hoy para evitar envíos duplicados concurrentes
            $upsertSetting($slot['key_run'], $today);

            $err = null;
            $ok = sendMoratallaNewsDigestEmail($pdo, "Horario {$slotNum} ({$schedTime})", $err);
            $statusText = $ok
                ? ("Último envío automático (Horario {$slotNum} - {$schedTime}) completado el " . date('d/m/Y H:i') . " a " . $cfg['recipient'])
                : ("Error en Horario {$slotNum} ({$schedTime}) el " . date('d/m/Y H:i') . ": " . ($err ?: 'Fallo SMTP'));
            $upsertSetting('auto_news_last_status', $statusText);

            if (!$ok) {
                // Si hubo un fallo de envío, liberar el slot para permitir reintento posterior
                $upsertSetting($slot['key_run'], '');
            }

            $results[] = [
                'slot'    => $slotNum,
                'time'    => $schedTime,
                'success' => $ok,
                'message' => $statusText
            ];

            // Si se completó el envío con éxito, no ejecutar el segundo slot en el mismo segundo
            if ($ok) {
                break;
            }
        }
    }

    return [
        'executed' => !empty($results),
        'runs'     => $results,
        'now_hm'   => $nowHm,
        'config'   => $cfg
    ];
}
