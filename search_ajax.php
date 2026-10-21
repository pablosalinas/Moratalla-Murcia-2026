<?php
// search_ajax.php - Buscador Global de Moratalla (Noticias, Eventos, Asociaciones, Artesanos, Alojamientos, Restauración, Páginas, Citas y Recursos)
ob_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();

$rawQuery = trim($_GET['q'] ?? $_POST['q'] ?? '');
$mode = strtoupper(trim($_GET['mode'] ?? $_POST['mode'] ?? 'OR'));
if ($mode !== 'AND') {
    $mode = 'OR'; // Por defecto OR
}

$scope = strtolower(trim($_GET['scope'] ?? $_POST['scope'] ?? 'web'));
if (!in_array($scope, ['web', 'internet', 'both'], true)) {
    $scope = 'web'; // Por defecto busca en la web moratalla-murcia.com
}

if (mb_strlen($rawQuery, 'UTF-8') < 2) {
    ob_clean();
    echo json_encode([
        'success' => true,
        'query'   => $rawQuery,
        'mode'    => $mode,
        'scope'   => $scope,
        'words'   => [],
        'total'   => 0,
        'counts'  => [],
        'results' => []
    ]);
    exit;
}

// Separar palabras de búsqueda (ignorando palabras vacías de 1 letra salvo números)
$rawWords = preg_split('/\s+/u', $rawQuery, -1, PREG_SPLIT_NO_EMPTY);
$words = [];
foreach ($rawWords as $w) {
    $w = trim($w);
    if (mb_strlen($w, 'UTF-8') >= 2 || is_numeric($w)) {
        $words[] = $w;
    }
}
if (empty($words)) {
    $words = [$rawQuery];
}

/**
 * Construye una cláusula SQL (OR o AND entre cada palabra buscada) sobre una lista de columnas
 */
function buildWordFilterSql(array $columns, array $words, string $mode, array &$params): string {
    $wordClauses = [];
    foreach ($words as $w) {
        $colClauses = [];
        $like = '%' . $w . '%';
        foreach ($columns as $col) {
            $colClauses[] = "$col LIKE ?";
            $params[] = $like;
        }
        $wordClauses[] = '(' . implode(' OR ', $colClauses) . ')';
    }
    $glue = ($mode === 'AND') ? ' AND ' : ' OR ';
    return '(' . implode($glue, $wordClauses) . ')';
}

/**
 * Verifica en PHP si un texto cumple el filtro OR / AND de palabras (para resultados de Internet Global)
 */
function matchesWordsFilter(string $text, array $words, string $mode): bool {
    if (empty($words)) return true;
    $haystack = mb_strtolower($text, 'UTF-8');
    if ($mode === 'AND') {
        foreach ($words as $w) {
            if (mb_strpos($haystack, mb_strtolower($w, 'UTF-8'), 0, 'UTF-8') === false) {
                return false;
            }
        }
        return true;
    } else {
        foreach ($words as $w) {
            if (mb_strpos($haystack, mb_strtolower($w, 'UTF-8'), 0, 'UTF-8') !== false) {
                return true;
            }
        }
        return false;
    }
}

/**
 * Genera un extracto limpio resaltando o centrando alrededor de la primera coincidencia
 */
function buildSearchSnippet($htmlOrText, array $words, $maxLen = 170): string {
    $clean = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$htmlOrText), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($clean === '') return '';

    $firstPos = false;
    foreach ($words as $w) {
        $pos = mb_stripos($clean, $w, 0, 'UTF-8');
        if ($pos !== false && ($firstPos === false || $pos < $firstPos)) {
            $firstPos = $pos;
        }
    }

    if ($firstPos !== false && $firstPos > 60) {
        $start = max(0, $firstPos - 45);
        $sub = '…' . mb_substr($clean, $start, $maxLen, 'UTF-8');
        if ($start + $maxLen < mb_strlen($clean, 'UTF-8')) {
            $sub .= '…';
        }
        return $sub;
    }

    return mb_strimwidth($clean, 0, $maxLen, '…', 'UTF-8');
}

/**
 * Descarga rápida multi-URL con cURL para búsquedas en Internet Global
 */
function fetchUrlsParallel(array $urls, int $timeout = 4): array {
    $responses = [];
    if (!function_exists('curl_multi_init')) {
        foreach ($urls as $key => $url) {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\nAccept-Language: es-ES,es;q=0.9\r\n"
                ]
            ]);
            $responses[$key] = @file_get_contents($url, false, $ctx) ?: '';
        }
        return $responses;
    }

    $mh = curl_multi_init();
    $handles = [];
    foreach ($urls as $key => $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => ['Accept-Language: es-ES,es;q=0.9']
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($mh, $running);
        if ($running > 0) {
            curl_multi_select($mh, 0.2);
        }
    } while ($running > 0);

    foreach ($handles as $key => $ch) {
        $responses[$key] = (string)curl_multi_getcontent($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $responses;
}

$results = [];
$counts = [
    'all'           => 0,
    'noticias'      => 0,
    'eventos'       => 0,
    'asociaciones'  => 0,
    'artesanos'     => 0,
    'alojamientos'  => 0,
    'restauracion'  => 0,
    'citas'         => 0,
    'paginas'       => 0,
    'internet'      => 0
];

if ($scope === 'web' || $scope === 'both') {

// Mapa rápido de categorías para clasificar páginas (Asociaciones, Artesanos, etc.)
$catMap = [];
try {
    $stmtC = $pdo->query("SELECT id, parent_id, name, icon FROM categories");
    while ($c = $stmtC->fetch(PDO::FETCH_ASSOC)) {
        $catMap[(int)$c['id']] = $c;
    }
} catch (Exception $e) {}

$getCategoryLineageText = function ($catId) use ($catMap): string {
    $names = [];
    $curr = (int)$catId;
    $guard = 0;
    while ($curr > 0 && isset($catMap[$curr]) && $guard < 6) {
        $names[] = $catMap[$curr]['name'];
        $curr = (int)($catMap[$curr]['parent_id'] ?? 0);
        $guard++;
    }
    return implode(' › ', array_reverse($names));
};

$classifyPageGroup = function ($title, $catLineage) {
    $haystack = mb_strtolower($title . ' ' . $catLineage, 'UTF-8');
    if (preg_match('/(asociaci|club|peña|pena|cofrad|hermandad|tamborista|rondalla|banda|futbol|fútbol|baloncesto|ciclista|automovilismo|grupos)/u', $haystack)) {
        return ['group' => 'asociaciones', 'badge' => 'Asociaciones y Colectivos', 'icon' => '🤝'];
    }
    if (preg_match('/(artesan|esparto|pintura|pintor|almagro|cerámic|ceramic|madera|forja|oficio|escultura|arte)/u', $haystack)) {
        return ['group' => 'artesanos', 'badge' => 'Artesanos y Oficios', 'icon' => '🏺'];
    }
    return ['group' => 'paginas', 'badge' => ($catLineage ?: 'Patrimonio y Secciones'), 'icon' => '📄'];
};

// 1. NOTICIAS Y EVENTOS (`news_events`)
try {
    $params = [];
    $whereClause = buildWordFilterSql(['ne.title', 'ne.content', 'ne.image_caption', 'ne.image_author'], $words, $mode, $params);
    $sql = "SELECT ne.*
            FROM news_events ne
            WHERE (ne.start_date IS NULL OR ne.start_date <= NOW())
              AND (ne.end_date IS NULL OR ne.end_date >= NOW())
              AND $whereClause
            ORDER BY ne.event_date DESC, ne.id DESC
            LIMIT 25";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $isEvent = !empty($row['event_date']);
        $dateText = $isEvent ? date('d/m/Y', strtotime($row['event_date'])) : date('d/m/Y', strtotime($row['created_at']));
        $group = $isEvent ? 'eventos' : 'noticias';

        $stmtG = $pdo->prepare("SELECT id, image_path, caption, author FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id DESC");
        $stmtG->execute([$row['id']]);
        $gallery = $stmtG->fetchAll(PDO::FETCH_ASSOC);

        $mainImagePath = $row['image_path'] ?? '';
        $mainImageCaption = $row['image_caption'] ?? '';
        $mainImageAuthor = $row['image_author'] ?? '';

        if (!empty($row['use_latest_gallery_image']) && !empty($gallery)) {
            $mainImagePath = $gallery[0]['image_path'];
            $mainImageCaption = $gallery[0]['caption'] ?? '';
            $mainImageAuthor = $gallery[0]['author'] ?? '';
        }

        $results[] = [
            'group'      => $group,
            'badge'      => $isEvent ? 'Evento' : 'Noticia',
            'icon'       => !empty($row['icon']) ? $row['icon'] : ($isEvent ? '📅' : '📰'),
            'title'      => $row['title'],
            'subtitle'   => $dateText,
            'snippet'    => buildSearchSnippet($row['content'], $words),
            'image'      => $mainImagePath,
            'url'        => 'index.php?action=ver_noticia&id=' . (int)$row['id'],
            'news_modal' => [
                'title'         => $row['title'],
                'date'          => $dateText,
                'isEvent'       => $isEvent,
                'image'         => $mainImagePath ?: '',
                'image_caption' => $mainImageCaption,
                'image_author'  => $mainImageAuthor,
                'content'       => $row['content'],
                'gallery'       => $gallery
            ]
        ];
        $counts[$group]++;
        $counts['all']++;
    }
} catch (Exception $e) {}

// 2. ALOJAMIENTOS (`alojamientos`)
try {
    $params = [];
    $whereClause = buildWordFilterSql(['a.nombre', 'a.descripcion', 'a.calle', 'a.poblacion', 'a.telefono1', 'a.email'], $words, $mode, $params);
    $sql = "SELECT a.*,
                   (SELECT ai.image_path FROM alojamiento_images ai WHERE ai.alojamiento_id = a.id AND ai.is_visible = 1 ORDER BY ai.is_cover DESC, ai.sort_order ASC, ai.id ASC LIMIT 1) as cover_image
            FROM alojamientos a
            WHERE a.is_visible = 1 AND $whereClause
            ORDER BY a.sort_order ASC, a.nombre ASC
            LIMIT 25";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $metaParts = [];
        if (!empty($row['poblacion'])) $metaParts[] = '📍 ' . $row['poblacion'];
        if (!empty($row['calle'])) $metaParts[] = $row['calle'];
        if (!empty($row['telefono1'])) $metaParts[] = '📞 ' . $row['telefono1'];

        $descText = !empty($row['descripcion']) ? buildSearchSnippet($row['descripcion'], $words) : implode(' · ', $metaParts);

        $results[] = [
            'group'    => 'alojamientos',
            'badge'    => 'Alojamiento',
            'icon'     => '🛏️',
            'title'    => $row['nombre'],
            'subtitle' => implode(' · ', $metaParts),
            'snippet'  => $descText,
            'image'    => $row['cover_image'] ?: '',
            'url'      => 'alojamientos.php?buscar=' . urlencode($row['nombre'])
        ];
        $counts['alojamientos']++;
        $counts['all']++;
    }
} catch (Exception $e) {}

// 3. RESTAURACIÓN / BARES Y RESTAURANTES (`restaurantes`)
try {
    $params = [];
    $whereClause = buildWordFilterSql(['r.nombre', 'r.descripcion', 'r.calle', 'r.poblacion', 'r.telefono1', 'r.email'], $words, $mode, $params);
    $sql = "SELECT r.*,
                   (SELECT ri.image_path FROM restaurante_images ri WHERE ri.restaurante_id = r.id AND ri.is_visible = 1 ORDER BY ri.is_cover DESC, ri.sort_order ASC, ri.id ASC LIMIT 1) as cover_image
            FROM restaurantes r
            WHERE r.is_visible = 1 AND $whereClause
            ORDER BY r.sort_order ASC, r.nombre ASC
            LIMIT 25";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $metaParts = [];
        if (!empty($row['poblacion'])) $metaParts[] = '📍 ' . $row['poblacion'];
        if (!empty($row['calle'])) $metaParts[] = $row['calle'];
        if (!empty($row['telefono1'])) $metaParts[] = '📞 ' . $row['telefono1'];

        $descText = !empty($row['descripcion']) ? buildSearchSnippet($row['descripcion'], $words) : implode(' · ', $metaParts);

        $results[] = [
            'group'    => 'restauracion',
            'badge'    => 'Restauración y Gastronomía',
            'icon'     => '🍽️',
            'title'    => $row['nombre'],
            'subtitle' => implode(' · ', $metaParts),
            'snippet'  => $descText,
            'image'    => $row['cover_image'] ?: '',
            'url'      => 'restaurantes.php?buscar=' . urlencode($row['nombre'])
        ];
        $counts['restauracion']++;
        $counts['all']++;
    }
} catch (Exception $e) {}

// 4. PÁGINAS DE CONTENIDO: ASOCIACIONES, ARTESANOS, CULTURA, HISTORIA, LUGARES (`pages` y pies de foto de `page_images`)
try {
    $params = [];
    $whereClause = buildWordFilterSql(['p.title', 'p.content', 'c.name', 'pi_search.caption'], $words, $mode, $params);
    $sql = "SELECT DISTINCT p.id, p.title, p.content, p.category_id, p.icon,
                   (SELECT pi.image_path FROM page_images pi WHERE pi.page_id = p.id AND (pi.is_video = 0 OR pi.is_video IS NULL) ORDER BY pi.is_cover DESC, pi.sort_order ASC, pi.id ASC LIMIT 1) as cover_image
            FROM pages p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN page_images pi_search ON pi_search.page_id = p.id
            WHERE (p.is_visible = 1 OR p.is_visible IS NULL)
              AND $whereClause
            ORDER BY p.sort_order ASC, p.id DESC
            LIMIT 40";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $catLineage = $getCategoryLineageText($row['category_id'] ?? 0);
        $classInfo = $classifyPageGroup($row['title'], $catLineage);
        $group = $classInfo['group'];

        $results[] = [
            'group'    => $group,
            'badge'    => $classInfo['badge'],
            'icon'     => !empty($row['icon']) && $row['icon'] !== 'far fa-file-alt' ? $row['icon'] : $classInfo['icon'],
            'title'    => $row['title'],
            'subtitle' => $catLineage ?: 'Sección de Moratalla',
            'snippet'  => buildSearchSnippet($row['content'], $words),
            'image'    => $row['cover_image'] ?: '',
            'url'      => 'page.php?id=' . (int)$row['id']
        ];
        $counts[$group]++;
        $counts['all']++;
    }
} catch (Exception $e) {}

// 5. CATEGORÍAS Y SECCIONES (`categories`)
try {
    $params = [];
    $whereClause = buildWordFilterSql(['c.name', 'c.hint_text'], $words, $mode, $params);
    $sql = "SELECT c.id, c.parent_id, c.name, c.icon, c.hint_text
            FROM categories c
            WHERE c.is_visible = 1 AND $whereClause
            ORDER BY c.sort_order ASC, c.name ASC
            LIMIT 15";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $catLineage = $getCategoryLineageText($row['id']);
        $classInfo = $classifyPageGroup($row['name'], $catLineage);
        $group = $classInfo['group'];

        $results[] = [
            'group'    => $group,
            'badge'    => 'Categoría · ' . $classInfo['badge'],
            'icon'     => !empty($row['icon']) ? $row['icon'] : '📁',
            'title'    => $row['name'],
            'subtitle' => $catLineage,
            'snippet'  => !empty($row['hint_text']) ? $row['hint_text'] : ('Explorar contenidos de la sección ' . $row['name']),
            'image'    => '',
            'url'      => 'category.php?id=' . (int)$row['id']
        ];
        $counts[$group]++;
        $counts['all']++;
    }
} catch (Exception $e) {}

// 6. CITAS CÉLEBRES Y FRASES (`quotes`)
try {
    $params = [];
    $whereClause = buildWordFilterSql(['q.phrase', 'q.author'], $words, $mode, $params);
    $sql = "SELECT q.id, q.phrase, q.author
            FROM quotes q
            WHERE $whereClause
            ORDER BY q.id DESC
            LIMIT 25";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $author = !empty($row['author']) ? $row['author'] : 'Tradición / Autor local';
        $results[] = [
            'group'    => 'citas',
            'badge'    => 'Cita / Frase',
            'icon'     => '💬',
            'title'    => '«' . mb_strimwidth($row['phrase'], 0, 95, '…', 'UTF-8') . '»',
            'subtitle' => '✍️ Autor: ' . $author,
            'snippet'  => '«' . $row['phrase'] . '» — ' . $author,
            'image'    => '',
            'url'      => ''
        ];
        $counts['citas']++;
        $counts['all']++;
    }
} catch (Exception $e) {}

// 7. ENLACES EXTERNOS Y CURIOSIDADES (`external_links`)
try {
    $params = [];
    $whereClause = buildWordFilterSql(['el.title', 'el.description', 'el.url'], $words, $mode, $params);
    $sql = "SELECT el.id, el.title, el.description, el.url, el.icon
            FROM external_links el
            WHERE el.is_visible = 1 AND $whereClause
            ORDER BY el.sort_order ASC, el.id DESC
            LIMIT 15";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'group'    => 'paginas',
            'badge'    => 'Recurso / Curiosidad',
            'icon'     => !empty($row['icon']) ? $row['icon'] : '🔗',
            'title'    => $row['title'],
            'subtitle' => 'Enlace de interés',
            'snippet'  => buildSearchSnippet($row['description'] ?: $row['url'], $words),
            'image'    => '',
            'url'      => $row['url'],
            'external' => true
        ];
        $counts['paginas']++;
        $counts['all']++;
    }
} catch (Exception $e) {}

} // fin if ($scope === 'web' || $scope === 'both')

// 8. BÚSQUEDA EN INTERNET GLOBAL (cuando $scope === 'internet' o $scope === 'both')
if ($scope === 'internet' || $scope === 'both') {
    try {
        $engineQuery = (count($words) > 1)
            ? implode(($mode === 'AND') ? ' ' : ' OR ', $words)
            : $rawQuery;

        $urlsToFetch = [
            'bing_web'  => 'https://www.bing.com/search?q=' . urlencode($engineQuery) . '&format=rss&setlang=es-ES&cc=ES',
            'bing_news' => 'https://www.bing.com/news/search?q=' . urlencode($engineQuery) . '&format=rss&mkt=es-ES',
            'gnews'     => 'https://news.google.com/rss/search?q=' . urlencode($engineQuery) . '&hl=es&gl=ES&ceid=ES:es',
            'wiki_es'   => 'https://es.wikipedia.org/w/api.php?action=query&list=search&srsearch=' . urlencode($engineQuery) . '&utf8=1&format=json&srlimit=8'
        ];

        $rawFeeds = fetchUrlsParallel($urlsToFetch, 4);
        $seenUrls = [];

        // 8.1 Procesar Wikipedia en Español
        if (!empty($rawFeeds['wiki_es'])) {
            $wikiJson = @json_decode($rawFeeds['wiki_es'], true);
            $wikiItems = $wikiJson['query']['search'] ?? [];
            foreach ($wikiItems as $wItem) {
                $wTitle = trim($wItem['title'] ?? '');
                $wSnippet = trim(html_entity_decode(strip_tags($wItem['snippet'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($wTitle === '') continue;
                if (!matchesWordsFilter($wTitle . ' ' . $wSnippet, $words, $mode)) continue;

                $wUrl = 'https://es.wikipedia.org/wiki/' . str_replace(' ', '_', $wTitle);
                if (isset($seenUrls[$wUrl])) continue;
                $seenUrls[$wUrl] = true;

                $results[] = [
                    'group'    => 'internet',
                    'badge'    => 'Internet Global · Wikipedia',
                    'icon'     => '📚',
                    'title'    => $wTitle,
                    'subtitle' => 'es.wikipedia.org',
                    'snippet'  => buildSearchSnippet($wSnippet, $words),
                    'image'    => '',
                    'url'      => $wUrl,
                    'external' => true
                ];
                $counts['internet']++;
                $counts['all']++;
            }
        }

        // 8.2 Procesar Bing Web RSS y Bing News RSS
        foreach (['bing_web' => 'Web Global', 'bing_news' => 'Prensa Digital'] as $feedKey => $feedLabel) {
            if (empty($rawFeeds[$feedKey])) continue;
            $xml = @simplexml_load_string($rawFeeds[$feedKey], 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$xml || !isset($xml->channel->item)) continue;

            foreach ($xml->channel->item as $item) {
                $iTitle = trim(html_entity_decode(strip_tags((string)$item->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $iLink  = trim((string)$item->link);
                $iDesc  = trim(html_entity_decode(strip_tags((string)$item->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($iTitle === '' || $iLink === '') continue;
                if (!matchesWordsFilter($iTitle . ' ' . $iDesc, $words, $mode)) continue;
                if (isset($seenUrls[$iLink])) continue;
                $seenUrls[$iLink] = true;

                $host = parse_url($iLink, PHP_URL_HOST) ?: 'Internet';
                $host = preg_replace('/^www\./i', '', $host);
                $pubDate = !empty($item->pubDate) ? @ strtotime((string)$item->pubDate) : false;
                $sub = $pubDate ? ($host . ' · ' . date('d/m/Y', $pubDate)) : $host;

                $results[] = [
                    'group'    => 'internet',
                    'badge'    => 'Internet Global · ' . $feedLabel,
                    'icon'     => ($feedKey === 'bing_news') ? '🗞️' : '🌍',
                    'title'    => $iTitle,
                    'subtitle' => $sub,
                    'snippet'  => buildSearchSnippet($iDesc, $words),
                    'image'    => '',
                    'url'      => $iLink,
                    'external' => true
                ];
                $counts['internet']++;
                $counts['all']++;
                if ($counts['internet'] >= 30) break;
            }
        }

        // 8.3 Procesar Google News RSS (ES)
        if (!empty($rawFeeds['gnews']) && $counts['internet'] < 35) {
            $xmlG = @simplexml_load_string($rawFeeds['gnews'], 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($xmlG && isset($xmlG->channel->item)) {
                foreach ($xmlG->channel->item as $item) {
                    $gTitle = trim(html_entity_decode(strip_tags((string)$item->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $gLink  = trim((string)$item->link);
                    $gDesc  = trim(html_entity_decode(strip_tags((string)$item->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $gSource = isset($item->source) ? trim((string)$item->source) : 'Google News';
                    if ($gTitle === '' || $gLink === '') continue;
                    if (!matchesWordsFilter($gTitle . ' ' . $gDesc . ' ' . $gSource, $words, $mode)) continue;
                    if (isset($seenUrls[$gLink])) continue;
                    $seenUrls[$gLink] = true;

                    $pubDate = !empty($item->pubDate) ? @strtotime((string)$item->pubDate) : false;
                    $sub = $pubDate ? ($gSource . ' · ' . date('d/m/Y', $pubDate)) : $gSource;

                    $results[] = [
                        'group'    => 'internet',
                        'badge'    => 'Internet Global · ' . $gSource,
                        'icon'     => '🌐',
                        'title'    => $gTitle,
                        'subtitle' => $sub,
                        'snippet'  => buildSearchSnippet($gDesc ?: $gTitle, $words),
                        'image'    => '',
                        'url'      => $gLink,
                        'external' => true
                    ];
                    $counts['internet']++;
                    $counts['all']++;
                    if ($counts['internet'] >= 35) break;
                }
            }
        }

        // 8.4 Añadir siempre accesos directos a buscadores globales con el filtro OR/AND aplicado
        $results[] = [
            'group'    => 'internet',
            'badge'    => 'Buscador Externo · Google',
            'icon'     => '🔍',
            'title'    => 'Ver todos los resultados en Google para «' . $rawQuery . '»',
            'subtitle' => 'google.es · Modo ' . $mode,
            'snippet'  => 'Abrir búsqueda global en Google (' . $engineQuery . ') en una nueva pestaña.',
            'image'    => '',
            'url'      => 'https://www.google.es/search?q=' . urlencode($engineQuery),
            'external' => true
        ];
        $counts['internet']++;
        $counts['all']++;
    } catch (Exception $e) {}
}

ob_clean();
echo json_encode([
    'success' => true,
    'query'   => $rawQuery,
    'mode'    => $mode,
    'scope'   => $scope,
    'words'   => $words,
    'total'   => $counts['all'],
    'counts'  => $counts,
    'results' => $results
]);
