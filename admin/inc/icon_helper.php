<?php
// admin/inc/icon_helper.php

/**
 * Retorna todos los iconos personalizados (.svg, .png, etc.) de uploads/icons/
 */
function getUploadedCustomIcons() {
    $dir = dirname(__DIR__, 2) . '/uploads/icons';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    
    $icons = [];
    $allowed = ['svg', 'png', 'webp', 'jpg', 'jpeg', 'gif'];
    $files = @scandir($dir);
    if ($files) {
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                // Auto-actualizar si algún SVG previo fue generado con iniciales (<text...) para convertirlo en gráfico real
                if ($ext === 'svg') {
                    $fullSvgPath = $dir . '/' . $f;
                    $svgContent = @file_get_contents($fullSvgPath);
                    if ($svgContent && stripos($svgContent, '<text ') !== false && function_exists('findGraphicIconsForPrompt')) {
                        $conceptFromName = str_replace(['_', '-'], ' ', pathinfo($f, PATHINFO_FILENAME));
                        $cands = findGraphicIconsForPrompt($conceptFromName);
                        if (!empty($cands[0])) {
                            $newGraphicSvg = fetchAndBuildGraphicSvgFromIconify($cands[0]);
                            if ($newGraphicSvg !== '') {
                                @file_put_contents($fullSvgPath, $newGraphicSvg);
                            }
                        }
                    }
                }
                $icons[] = $f;
            }
        }
    }
    return $icons;
}

/**
 * Retorna lista consolidada de opciones para selectores de iconos
 */
function getConsolidatedIconOptions(PDO $pdo, array $extraUsed = []) {
    $defaultIcons = [
        '📄' => '📄 Página genérica',
        'ℹ️' => 'ℹ️ Información',
        '🏛️' => '🏛️ Patrimonio / Historia',
        '🌳' => '🌳 Naturaleza',
        '📷' => '📷 Fotografía',
        '🎵' => '🎵 Música',
        '⚽' => '⚽ Deportes',
        '⛪' => '⛪ Iglesia',
        '✝️' => '✝️ Religión',
        '📰' => '📰 Noticias',
        '👥' => '👥 Asociaciones',
        '📖' => '📖 Cultura / Lectura',
        '🕰️' => '🕰️ Historia (Reloj)',
        '🎥' => '🎥 Vídeo',
        '🖼️' => '🖼️ Galería',
        '🗺️' => '🗺️ Mapa / Rutas',
        '⭐' => '⭐ Destacado',
        '❤️' => '❤️ Favorito',
        '🍽️' => '🍽️ Gastronomía',
        '🛏️' => '🛏️ Alojamiento',
        '🏀' => '🏀 Baloncesto',
        '🏺' => '🏺 Artesanía',
        '🧺' => '🧺 Esparto',
        '🎨' => '🎨 Pintura',
        '🚴' => '🚴 Ciclismo',
        '🚗' => '🚗 Automóvil',
        '🏫' => '🏫 Escuelas',
        '🎒' => '🎒 Colegios',
        '🎓' => '🎓 Institutos',
        '🏢' => '🏢 Servicios Municipales',
        '✉️' => '✉️ Contacto',
        '🧳' => '🧳 Turismo',
        '🍻' => '🍻 Bares y Restaurantes',
        '🏰' => '🏰 Castillo',
        '🪨' => '🪨 Arte Rupestre',
        '⛰️' => '⛰️ Montes y Montañas',
        '🛤️' => '🛤️ Rutas',
        '🥁' => '🥁 Tambor',
        '🐂' => '🐂 Tauromaquia',
        '🎉' => '🎉 Fiestas / Celebraciones'
    ];

    $all = $defaultIcons;

    // Agregar iconos subidos (.svg, .png, etc.)
    $uploadedIcons = getUploadedCustomIcons();
    foreach ($uploadedIcons as $uFile) {
        $cleanName = pathinfo($uFile, PATHINFO_FILENAME);
        $cleanName = str_replace(['_', '-'], ' ', $cleanName);
        $all[$uFile] = "🖼️ Archivo: {$cleanName} (" . pathinfo($uFile, PATHINFO_EXTENSION) . ")";
    }

    // Agregar iconos extra usados en base de datos
    foreach ($extraUsed as $uIcon) {
        if (!empty($uIcon) && !isset($all[$uIcon])) {
            $all[$uIcon] = $uIcon;
        }
    }

    // Ordenar alfabéticamente
    uasort($all, function($a, $b) {
        $isArchA = (strpos($a, '🖼️ Archivo:') === 0);
        $isArchB = (strpos($b, '🖼️ Archivo:') === 0);
        if ($isArchA && !$isArchB) return -1;
        if (!$isArchA && $isArchB) return 1;

        $textA = trim(mb_substr($a, mb_strpos($a, ' ') !== false ? mb_strpos($a, ' ') : 0));
        $textB = trim(mb_substr($b, mb_strpos($b, ' ') !== false ? mb_strpos($b, ' ') : 0));
        $search  = ['Á','É','Í','Ó','Ú','á','é','í','ó','ú'];
        $replace = ['A','E','I','O','U','a','e','i','o','u'];
        $textA = str_replace($search, $replace, $textA);
        $textB = str_replace($search, $replace, $textB);
        return strcasecmp($textA, $textB);
    });

    return $all;
}

/**
 * Procesa la subida de un archivo de icono (.svg o .png)
 * @return string|false Nombre del archivo subido o false si no hubo subida
 */
function handleIconUpload($fileKey = 'icon_file', &$error = null) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] === UPLOAD_ERR_NO_FILE) {
        return false;
    }

    $file = $_FILES[$fileKey];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Error al subir el archivo de icono (código " . $file['error'] . ").";
        return false;
    }

    $filename = basename($file['name']);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = ['svg', 'png', 'webp', 'jpg', 'jpeg', 'gif'];

    if (!in_array($ext, $allowed)) {
        $error = "Formato de icono no admitido. Solo se admiten archivos .svg, .png, .webp o .jpg.";
        return false;
    }

    // Limpieza de nombre
    $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
    if (empty($cleanBase)) $cleanBase = 'icon';
    $finalName = $cleanBase . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;

    $targetDir = dirname(__DIR__, 2) . '/uploads/icons';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }
    $targetPath = $targetDir . '/' . $finalName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $finalName;
    } else {
        $error = "No se pudo mover el archivo de icono subido a la carpeta uploads/icons/.";
        return false;
    }
}

/**
 * Genera un nombre de archivo limpio para el icono en uploads/icons/ basado en el texto descriptivo
 */
function buildCleanIconFilename($text, $ext = 'svg') {
    $t = mb_strtolower(trim($text), 'UTF-8');
    $replacements = [
        'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n',
        'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u', 'Ñ'=>'n'
    ];
    $t = strtr($t, $replacements);
    $slug = preg_replace('/[^a-z0-9]+/', '_', $t);
    $slug = trim($slug, '_');
    if ($slug === '') $slug = 'icono_ia';
    $slug = substr($slug, 0, 36);

    $dir = dirname(__DIR__, 2) . '/uploads/icons';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $candidate = $slug . '.' . $ext;
    $counter = 2;
    while (file_exists($dir . '/' . $candidate)) {
        $candidate = $slug . '_' . $counter . '.' . $ext;
        $counter++;
    }
    return $candidate;
}

/**
 * Traduce un concepto en español a términos visuales en inglés priorizando el sustantivo principal
 */
function translateSpanishConceptToQueries($spanishPrompt) {
    require_once __DIR__ . '/ai_news_helper.php';

    $normEs = strtr(mb_strtolower(trim($spanishPrompt), 'UTF-8'), [
        'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u', 'ñ'=>'n',
        'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u', 'Ñ'=>'n'
    ]);

    $directMap = [
        'bicicleta'   => ['mountain-bicyclist', 'bicycle', 'biking'],
        'bici'        => ['bicycle', 'mountain-bicyclist'],
        'ciclismo'    => ['mountain-bicyclist', 'bicycle'],
        'castillo'    => ['castle', 'european-castle', 'fortress'],
        'fortaleza'   => ['castle', 'shield'],
        'torre'       => ['castle', 'tokyo-tower'],
        'tambor'      => ['drum', 'long-drum'],
        'semana santa'=> ['drum', 'church'],
        'senderismo'  => ['hiking-boot', 'person-walking', 'mountain'],
        'ruta'        => ['compass', 'world-map', 'hiking-boot'],
        'mapa'        => ['world-map', 'compass'],
        'cueva'       => ['mountain', 'rock', 'camping'],
        'rupestre'    => ['artist-palette', 'rock', 'scroll'],
        'arqueolog'   => ['amphora', 'scroll', 'classical-building'],
        'historia'    => ['scroll', 'classical-building', 'hourglass-done'],
        'patrimonio'  => ['classical-building', 'castle'],
        'restaurante' => ['fork-and-knife-with-plate', 'pot-of-food'],
        'gastronomia' => ['shallow-pan-of-food', 'fork-and-knife-with-plate', 'pot-of-food'],
        'comida'      => ['pot-of-food', 'fork-and-knife-with-plate'],
        'bar'         => ['clinking-beer-mugs', ' tropical-drink'],
        'vino'        => ['wine-glass', 'bottle-with-popping-cork'],
        'cafe'        => ['hot-beverage', 'croissant'],
        'helad'       => ['soft-ice-cream', 'ice-cream'],
        'dulce'       => ['cupcake', 'shortcake', 'pie'],
        'mazapan'     => ['pie', 'croissant', 'cupcake'],
        'pastel'      => ['birthday-cake', 'shortcake'],
        'panaderia'   => ['baguette-bread', 'croissant'],
        'ayuntamiento'=> ['classical-building', 'office-building'],
        'iglesia'     => ['church', 'wedding'],
        'ermita'      => ['church', 'place-of-worship'],
        'parroquia'   => ['church', 'bell'],
        'cruz'        => ['latin-cross', 'church'],
        'toro'        => ['ox', 'bull', 'water-buffalo'],
        'encierro'    => ['ox', 'bull', 'party-popper'],
        'fiesta'      => ['party-popper', 'confetti-ball', 'ferris-wheel'],
        'feria'       => ['ferris-wheel', 'carousel-horse', 'party-popper'],
        'musica'      => ['musical-notes', 'guitar', 'violin'],
        'concierto'   => ['microphone', 'musical-notes', 'guitar'],
        'teatro'      => ['performing-arts', 'admission-tickets'],
        'cultura'     => ['performing-arts', 'open-book', 'artist-palette'],
        'museo'       => ['framed-picture', 'classical-building', 'amphora'],
        'arte'        => ['artist-palette', 'framed-picture'],
        'pintura'     => ['artist-palette', 'paintbrush'],
        'libro'       => ['open-book', 'books'],
        'biblioteca'  => ['books', 'open-book'],
        'colegio'     => ['school', 'graduation-cap'],
        'escuela'     => ['school', 'books'],
        'naturaleza'  => ['evergreen-tree', 'deciduous-tree', 'national-park'],
        'arbol'       => ['deciduous-tree', 'evergreen-tree'],
        'pino'        => ['evergreen-tree', 'national-park'],
        'bosque'      => ['evergreen-tree', 'deciduous-tree'],
        'monte'       => ['mountain', 'snow-capped-mountain', 'evergreen-tree'],
        'montana'     => ['snow-capped-mountain', 'mountain'],
        'mirador'     => ['sunrise-over-mountains', 'binoculars', 'mountain'],
        'rio'         => ['water-wave', 'national-park', 'canoe'],
        'agua'        => ['droplet', 'water-wave', 'fountain'],
        'poza'        => ['water-wave', 'swimmer'],
        'piscina'     => ['person-swimming', 'water-wave'],
        'hotel'       => ['hotel', 'bed'],
        'casa'        => ['house-with-garden', 'house'],
        'rural'       => ['house-with-garden', 'evergreen-tree'],
        'alojamiento' => ['bed', 'hotel', 'house-with-garden'],
        'camping'     => ['camping', 'tent'],
        'deporte'     => ['trophy', 'sports-medal', 'soccer-ball'],
        'futbol'      => ['soccer-ball', 'goal-net'],
        'baloncesto'  => ['basketball', 'trophy'],
        'tenis'       => ['tennis', 'racquet'],
        'carrera'     => ['person-running', 'chequered-flag', 'trophy'],
        'trofeo'      => ['trophy', '1st-place-medal'],
        'parking'     => ['automobile', 'bus', 'motorway'],
        'aparcamiento'=> ['automobile', 'bus'],
        'coche'       => ['automobile', 'sport-utility-vehicle'],
        'autobus'     => ['bus', 'minibus'],
        'farmacia'    => ['pill', 'hospital', 'medical-symbol'],
        'salud'       => ['hospital', 'stethoscope', 'ambulance'],
        'hospital'    => ['hospital', 'ambulance'],
        'medico'      => ['stethoscope', 'hospital'],
        'ambulancia'  => ['ambulance', 'hospital'],
        'policia'     => ['police-car', 'shield'],
        'emergencia'  => ['police-car-light', 'fire-engine'],
        'bombero'     => ['fire-engine', 'fire'],
        'foto'        => ['camera-with-flash', 'camera'],
        'camara'      => ['camera-with-flash', 'video-camera'],
        'video'       => ['movie-camera', 'film-frames'],
        'perro'       => ['dog', 'dog-face'],
        'animal'      => ['deer', 'eagle', 'dog'],
        'fauna'       => ['deer', 'eagle', 'butterfly'],
        'flora'       => ['sunflower', 'hibiscus', 'blossom'],
        'flor'        => ['sunflower', 'rose', 'blossom'],
        'sol'         => ['sun', 'sun-behind-small-cloud'],
        'tiempo'      => ['sun-behind-cloud', 'thermometer'],
        'nieve'       => ['snowflake', 'snowman', 'snow-capped-mountain'],
        'lluvia'      => ['umbrella-with-rain-drops', 'cloud-with-rain'],
        'estrella'    => ['glowing-star', 'night-with-stars', 'telescope'],
        'astronomia'  => ['telescope', 'milky-way', 'night-with-stars'],
        'mercado'     => ['shopping-bags', 'basket'],
        'comercio'    => ['shopping-bags', 'convenience-store'],
        'artesania'   => ['amphora', 'yarn', 'basket'],
        'esparto'     => ['basket', 'sheaf-of-rice'],
        'agricultura' => ['tractor', 'sheaf-of-rice', 'olive'],
        'olivo'       => ['olive', 'deciduous-tree'],
        'aceite'      => ['olive', 'amphora'],
        'telefono'    => ['telephone-receiver', 'mobile-phone'],
        'contacto'    => ['envelope', 'telephone-receiver'],
        'noticia'     => ['newspaper', 'rolled-up-newspaper'],
        'calendario'  => ['spiral-calendar', 'calendar'],
        'evento'      => ['spiral-calendar', 'party-popper'],
        'familia'     => ['family', 'people-holding-hands'],
        'asociacion'  => ['handshake', 'busts-in-silhouette']
    ];

    $queries = [];
    $stopEs = ['de', 'del', 'la', 'el', 'los', 'las', 'en', 'con', 'para', 'por', 'un', 'una', 'y', 'o', 'a', 'moratalla', 'municipal', 'tradicional'];
    $esWords = preg_split('/[^a-z0-9]+/', $normEs, -1, PREG_SPLIT_NO_EMPTY);
    $esMainWords = array_values(array_diff($esWords, $stopEs));

    // 1. Evaluar palabra por palabra en el orden en que el usuario las escribió (en español el sustantivo va primero)
    foreach ($esMainWords as $w) {
        foreach ($directMap as $k => $mappedList) {
            if (strpos($w, $k) === 0 || strpos($k, $w) === 0) {
                foreach ($mappedList as $mItem) {
                    if (!in_array($mItem, $queries, true)) {
                        $queries[] = $mItem;
                    }
                }
            }
        }
    }

    // 2. Comprobar también coincidencias parciales en toda la frase
    foreach ($directMap as $k => $mappedList) {
        if (strpos($normEs, $k) !== false) {
            foreach ($mappedList as $mItem) {
                if (!in_array($mItem, $queries, true)) {
                    $queries[] = $mItem;
                }
            }
        }
    }

    // 3. Traducir sustantivos principales y frase completa al inglés con Google Translate para cualquier otro concepto libre
    $translateFn = function ($txt) {
        $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=es&tl=en&dt=t&q=' . urlencode($txt);
        $raw = aiHttpGet($url, 6);
        if (!empty($raw)) {
            $data = @json_decode($raw, true);
            if (!empty($data[0][0][0])) {
                $clean = trim(preg_replace('/[^a-z0-9\s-]/', '', strtolower($data[0][0][0])));
                return $clean;
            }
        }
        return '';
    };

    if (!empty($esMainWords[0])) {
        $enFirstNoun = $translateFn($esMainWords[0]);
        if ($enFirstNoun !== '' && !in_array($enFirstNoun, $queries, true)) {
            array_unshift($queries, $enFirstNoun);
        }
    }

    $enFull = $translateFn($spanishPrompt);
    if ($enFull !== '') {
        if (!in_array($enFull, $queries, true)) {
            $queries[] = $enFull;
        }
        $enParts = array_reverse(preg_split('/\s+/', $enFull, -1, PREG_SPLIT_NO_EMPTY));
        foreach ($enParts as $ep) {
            if (strlen($ep) > 2 && !in_array($ep, ['the', 'and', 'for', 'with', 'from'], true) && !in_array($ep, $queries, true)) {
                $queries[] = $ep;
            }
        }
    }

    if (empty($queries)) {
        $queries[] = 'sparkles';
    }

    return $queries;
}

/**
 * Localiza iconos gráficos vectoriales reales (a todo color o siluetas ilustradas) en Iconify referentes al texto
 */
function findGraphicIconsForPrompt($spanishPrompt) {
    require_once __DIR__ . '/ai_news_helper.php';

    $queries = translateSpanishConceptToQueries($spanishPrompt);
    $candidates = [];

    // 1. Buscar primero en colecciones gráficas ilustradas a todo color
    $colorPrefixes = 'fluent-emoji-flat,noto,twemoji,emojione,flat-color-icons,streamline-emojis,fxemoji';
    foreach ($queries as $q) {
        $searchUrl = 'https://api.iconify.design/search?query=' . urlencode($q) . '&prefixes=' . $colorPrefixes . '&limit=12';
        $res = aiHttpGet($searchUrl, 7);
        if (!empty($res)) {
            $json = @json_decode($res, true);
            if (!empty($json['icons']) && is_array($json['icons'])) {
                foreach ($json['icons'] as $ic) {
                    if (strpos($ic, 'flag-') !== false || strpos($ic, 'letter-') !== false || strpos($ic, 'regional-indicator') !== false) {
                        continue;
                    }
                    if (!in_array($ic, $candidates, true)) {
                        $candidates[] = $ic;
                    }
                }
            }
        }
        if (count($candidates) >= 6) break;
    }

    // 2. Si no hubo suficientes en color, buscar en colecciones gráficas temáticas (game-icons, solar, tabler, mdi)
    if (empty($candidates)) {
        $monoPrefixes = 'game-icons,solar,tabler,mdi';
        foreach ($queries as $q) {
            $searchUrl = 'https://api.iconify.design/search?query=' . urlencode($q) . '&prefixes=' . $monoPrefixes . '&limit=10';
            $res = aiHttpGet($searchUrl, 7);
            if (!empty($res)) {
                $json = @json_decode($res, true);
                if (!empty($json['icons']) && is_array($json['icons'])) {
                    foreach ($json['icons'] as $ic) {
                        if (strpos($ic, 'alphabet') !== false || strpos($ic, 'letter') !== false) continue;
                        if (!in_array($ic, $candidates, true)) {
                            $candidates[] = $ic;
                        }
                    }
                }
            }
            if (count($candidates) >= 4) break;
        }
    }

    return $candidates;
}

/**
 * Descarga un gráfico SVG desde Iconify y lo envuelve como un icono vectorial rico y vistoso
 */
function fetchAndBuildGraphicSvgFromIconify($iconId) {
    require_once __DIR__ . '/ai_news_helper.php';

    $parts = explode(':', $iconId, 2);
    if (count($parts) !== 2) return '';
    list($prefix, $name) = $parts;

    $isMono = in_array($prefix, ['game-icons', 'solar', 'tabler', 'mdi'], true);
    $svgUrl = 'https://api.iconify.design/' . rawurlencode($prefix) . '/' . rawurlencode($name) . '.svg?width=64&height=64' . ($isMono ? '&color=%23ffffff' : '');
    $rawSvg = aiHttpGet($svgUrl, 8);

    if (empty($rawSvg) || stripos($rawSvg, '<svg') === false) {
        return '';
    }

    // Si es un icono monocromo, presentarlo sobre un escudo/tarjeta redondeada con degradado colorido
    if ($isMono && preg_match('/<svg[^>]*>(.*)<\/svg>/is', $rawSvg, $m)) {
        $innerPaths = $m[1];
        $viewBox = '0 0 24 24';
        if (preg_match('/viewBox="([^"]+)"/i', $rawSvg, $vbM)) {
            $viewBox = $vbM[1];
        }
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#1b4332"/>
      <stop offset="100%" stop-color="#2d6a4f"/>
    </linearGradient>
  </defs>
  <rect x="4" y="4" width="56" height="56" rx="14" fill="url(#bgGrad)" stroke="#d4af37" stroke-width="2"/>
  <svg x="12" y="12" width="40" height="40" viewBox="{$viewBox}">
    {$innerPaths}
  </svg>
</svg>
SVG;
    }

    return $rawSvg;
}

/**
 * Construye un icono vectorial SVG gráfico en local según el texto indicado (NUNCA usa iniciales ni letras)
 */
function buildSmartVectorSvgIcon($text) {
    $lower = mb_strtolower($text, 'UTF-8');

    if (strpos($lower, 'castillo') !== false || strpos($lower, 'fortaleza') !== false || strpos($lower, 'torre') !== false || strpos($lower, 'patrimonio') !== false) {
        $bg1 = '#1b4332'; $bg2 = '#2d6a4f'; $acc = '#d4af37';
        $inner = '<path d="M16 48V24h6v4h4v-4h6v4h4v-4h6v4h4v-4h6v24H16z" fill="#f8f9fa" stroke="' . $acc . '" stroke-width="2"/>'
               . '<path d="M22 24V14h4v3h4v-3h4v3h4v-3h4v10H22z" fill="#e9ecef"/>'
               . '<path d="M28 48V37a4 4 0 0 1 8 0v11h-8z" fill="#7f5539"/>'
               . '<rect x="12" y="48" width="40" height="4" rx="2" fill="' . $acc . '"/>';
    } elseif (strpos($lower, 'tambor') !== false || strpos($lower, 'semana santa') !== false || strpos($lower, 'tambores') !== false || strpos($lower, 'musica') !== false || strpos($lower, 'música') !== false) {
        $bg1 = '#5b21b6'; $bg2 = '#7c3aed'; $acc = '#fbbf24';
        $inner = '<ellipse cx="32" cy="24" rx="16" ry="6" fill="#fef3c7" stroke="' . $acc . '" stroke-width="2.5"/>'
               . '<path d="M16 24v18c0 3.3 7.2 6 16 6s16-2.7 16-6V24" fill="#dc2626" stroke="' . $acc . '" stroke-width="2"/>'
               . '<path d="M16 30l8 14 8-14 8 14 8-14" fill="none" stroke="' . $acc . '" stroke-width="2" stroke-linejoin="round"/>'
               . '<line x1="20" y1="12" x2="30" y2="23" stroke="#fde68a" stroke-width="3" stroke-linecap="round"/>'
               . '<line x1="44" y1="12" x2="34" y2="23" stroke="#fde68a" stroke-width="3" stroke-linecap="round"/>';
    } elseif (strpos($lower, 'arbol') !== false || strpos($lower, 'árbol') !== false || strpos($lower, 'pino') !== false || strpos($lower, 'bosque') !== false || strpos($lower, 'monte') !== false || strpos($lower, 'naturaleza') !== false || strpos($lower, 'senderismo') !== false || strpos($lower, 'ruta') !== false) {
        $bg1 = '#065f46'; $bg2 = '#10b981'; $acc = '#fde047';
        $inner = '<circle cx="45" cy="18" r="5" fill="' . $acc . '"/>'
               . '<polygon points="32,12 18,30 25,30 15,44 49,44 39,30 46,30" fill="#dcfce7" stroke="#047857" stroke-width="1.5"/>'
               . '<rect x="29" y="44" width="6" height="8" rx="1" fill="#78350f"/>'
               . '<path d="M12 52c8-3 16-3 24 0s12 3 16 0" fill="none" stroke="#a7f3d0" stroke-width="2.5" stroke-linecap="round"/>';
    } elseif (strpos($lower, 'rio') !== false || strpos($lower, 'río') !== false || strpos($lower, 'agua') !== false || strpos($lower, 'alharabe') !== false || strpos($lower, 'alhárabe') !== false || strpos($lower, 'poza') !== false) {
        $bg1 = '#0369a1'; $bg2 = '#0ea5e9'; $acc = '#bae6fd';
        $inner = '<path d="M12 26c6-5 14-5 20 0s14 5 20 0" fill="none" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round"/>'
               . '<path d="M12 36c6-5 14-5 20 0s14 5 20 0" fill="none" stroke="' . $acc . '" stroke-width="3.5" stroke-linecap="round"/>'
               . '<path d="M16 46c5-4 11-4 16 0s11 4 16 0" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round"/>';
    } elseif (strpos($lower, 'restaurante') !== false || strpos($lower, 'bar') !== false || strpos($lower, 'comida') !== false || strpos($lower, 'gastronomia') !== false || strpos($lower, 'gastronomía') !== false || strpos($lower, 'mazapan') !== false || strpos($lower, 'dulce') !== false) {
        $bg1 = '#9a3412'; $bg2 = '#ea580c'; $acc = '#fed7aa';
        $inner = '<circle cx="32" cy="34" r="14" fill="#fff7ed" stroke="' . $acc . '" stroke-width="2"/>'
               . '<circle cx="32" cy="34" r="9" fill="none" stroke="#fdba74" stroke-width="1.5" stroke-dasharray="3,2"/>'
               . '<path d="M14 20v10c0 2 2 3 2 5v13" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round"/>'
               . '<path d="M50 20v28" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round"/>';
    } elseif (strpos($lower, 'iglesia') !== false || strpos($lower, 'ermita') !== false || strpos($lower, 'cruz') !== false || strpos($lower, 'cristo') !== false || strpos($lower, 'parroquia') !== false) {
        $bg1 = '#1e3a8a'; $bg2 = '#3b82f6'; $acc = '#fde047';
        $inner = '<path d="M32 10v10M28 14h8" stroke="' . $acc . '" stroke-width="3" stroke-linecap="round"/>'
               . '<polygon points="32,20 16,32 48,32" fill="#fef3c7"/>'
               . '<rect x="19" y="32" width="26" height="18" fill="#ffffff"/>'
               . '<path d="M28 50V40a4 4 0 0 1 8 0v10h-8z" fill="#1e40af"/>';
    } elseif (strpos($lower, 'toro') !== false || strpos($lower, 'encierro') !== false || strpos($lower, 'fiesta') !== false || strpos($lower, 'feria') !== false) {
        $bg1 = '#991b1b'; $bg2 = '#ef4444'; $acc = '#fef08a';
        $inner = '<path d="M16 18c-2 8 4 12 8 12h16c4 0 10-4 8-12" fill="none" stroke="' . $acc . '" stroke-width="3.5" stroke-linecap="round"/>'
               . '<path d="M22 28h20l-3 18H25l-3-18z" fill="#ffffff"/>'
               . '<circle cx="27" cy="35" r="2" fill="#991b1b"/><circle cx="37" cy="35" r="2" fill="#991b1b"/>';
    } elseif (strpos($lower, 'hotel') !== false || strpos($lower, 'casa') !== false || strpos($lower, 'alojamiento') !== false || strpos($lower, 'rural') !== false || strpos($lower, 'camping') !== false) {
        $bg1 = '#115e59'; $bg2 = '#14b8a6'; $acc = '#fde68a';
        $inner = '<polygon points="32,14 12,30 52,30" fill="' . $acc . '"/>'
               . '<rect x="17" y="30" width="30" height="20" rx="2" fill="#ffffff"/>'
               . '<rect x="28" y="38" width="8" height="12" fill="#0f766e"/>'
               . '<rect x="21" y="34" width="5" height="5" fill="#99f6e4"/><rect x="38" y="34" width="5" height="5" fill="#99f6e4"/>';
    } else {
        // Gráfico paisajístico y monumental (montañas, sol y torre) sin letras ni iniciales
        $bg1 = '#1b4332'; $bg2 = '#2d6a4f'; $acc = '#d4af37';
        $inner = '<circle cx="44" cy="18" r="5" fill="#fde047"/>'
               . '<polygon points="10,48 26,24 40,48" fill="#a7f3d0" opacity="0.85"/>'
               . '<polygon points="24,48 39,27 54,48" fill="#ffffff"/>'
               . '<rect x="21" y="18" width="8" height="12" rx="1" fill="#fde68a" stroke="' . $acc . '" stroke-width="1.5"/>'
               . '<path d="M12 51h40" stroke="' . $acc . '" stroke-width="3" stroke-linecap="round"/>';
    }

    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$bg1}"/>
      <stop offset="100%" stop-color="{$bg2}"/>
    </linearGradient>
  </defs>
  <rect x="4" y="4" width="56" height="56" rx="14" fill="url(#bgGrad)" stroke="{$acc}" stroke-width="2"/>
  {$inner}
</svg>
SVG;
}

/**
 * Genera un icono gráfico o imagen referente al texto indicado (SVG vectorial ilustrado o PNG de alta resolución)
 * y lo incorpora directamente a `uploads/icons/` para toda la web.
 */
function generateAiIconForWeb(PDO $pdo, $promptText, $format = 'svg') {
    require_once __DIR__ . '/ai_news_helper.php';

    $promptText = trim((string)$promptText);
    if ($promptText === '') {
        return ['success' => false, 'error' => 'Escribe una descripción para el icono que deseas crear.'];
    }

    $format = strtolower(trim($format)) === 'png' ? 'png' : 'svg';
    $iconsDir = dirname(__DIR__, 2) . '/uploads/icons';
    if (!is_dir($iconsDir)) {
        @mkdir($iconsDir, 0777, true);
    }

    $apiKey = getGeminiApiKey($pdo);
    $graphicCandidates = findGraphicIconsForPrompt($promptText);

    // Determinar índice de variación si ya existen iconos previos con el mismo nombre base
    $nextFilename = buildCleanIconFilename($promptText, $format);
    $variationIdx = 0;
    if (preg_match('/_(\d+)\.' . preg_quote($format, '/') . '$/', $nextFilename, $vMatch)) {
        $variationIdx = max(0, ((int)$vMatch[1]) - 1);
    }

    // MODO 1: ICONO VECTORIAL GRÁFICO (.SVG)
    if ($format === 'svg') {
        $svgCode = '';
        $engineUsed = 'Catálogo Gráfico Vectorial';

        // 1A. Si hay candidatos gráficos ilustrados a todo color referentes al texto, usar el correspondiente
        if (!empty($graphicCandidates)) {
            $chosenIconId = $graphicCandidates[$variationIdx % count($graphicCandidates)];
            $fetchedSvg = fetchAndBuildGraphicSvgFromIconify($chosenIconId);
            if ($fetchedSvg !== '') {
                $svgCode = $fetchedSvg;
                $engineUsed = "Gráfico Vectorial ({$chosenIconId})";
            }
        }

        // 1B. Si está configurada la clave de Google Gemini y aún no hay SVG (o el usuario pide variante extra), generar ilustración SVG con Gemini (sin texto ni iniciales)
        if ($svgCode === '' && !empty($apiKey)) {
            $svgPrompt = <<<PROMPT
Eres un ilustrador experto en iconos vectoriales planos (Flat Vector Icon Illustration).
Dibuja un icono vectorial SVG colorido, detallado y muy expresivo que represente VISUALMENTE este concepto: "{$promptText}".

REGLAS ESTRICTAS:
1. PROHIBIDO usar etiquetas `<text>` o poner letras/iniciales. Debe ser 100% un dibujo o representación gráfica reconocible del objeto, lugar, actividad o concepto "{$promptText}".
2. Devuelve ÚNICAMENTE el código XML `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">...</svg>` sin bloques markdown ni explicaciones.
3. Usa múltiples capas de formas (`<path>`, `<circle>`, `<rect>`, `<polygon>`, `<ellipse>`, `<g>`, `<linearGradient>`) con colores vivos y buen contraste para que la figura principal se reconozca claramente.
PROMPT;

            $models = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'];
            foreach ($models as $model) {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
                $payload = json_encode([
                    'contents' => [
                        ['parts' => [['text' => $svgPrompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.45
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
                    $decoded = json_decode($resp, true);
                    $rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    if (preg_match('/<svg\b[^>]*>.*?<\/svg>/is', $rawText, $m)) {
                        $candSvg = $m[0];
                        $candSvg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $candSvg);
                        $candSvg = preg_replace('/<text\b[^>]*>.*?<\/text>/is', '', $candSvg);
                        $candSvg = preg_replace('/\bon\w+\s*=\s*"[^"]*"/i', '', $candSvg);
                        if (stripos($candSvg, 'xmlns=') === false) {
                            $candSvg = preg_replace('/^<svg/i', '<svg xmlns="http://www.w3.org/2000/svg"', $candSvg);
                        }
                        $svgCode = $candSvg;
                        $engineUsed = "Google Gemini Ilustración SVG ($model)";
                        break;
                    }
                }
            }
        }

        if ($svgCode === '') {
            $svgCode = buildSmartVectorSvgIcon($promptText);
        }

        $fullPath = $iconsDir . '/' . $nextFilename;
        if (@file_put_contents($fullPath, $svgCode) !== false) {
            $cleanLabel = str_replace(['_', '-'], ' ', pathinfo($nextFilename, PATHINFO_FILENAME));
            return [
                'success'      => true,
                'filename'     => $nextFilename,
                'url'          => '../uploads/icons/' . $nextFilename,
                'option_label' => "🖼️ Archivo: {$cleanLabel} (svg)",
                'engine'       => $engineUsed
            ];
        }

        return ['success' => false, 'error' => 'No se pudo guardar el archivo SVG en uploads/icons/.'];
    }

    // MODO 2: IMAGEN GRÁFICA ILUSTRADA (.PNG 128x128) REFERENTE AL TEXTO
    $rawPngBytes = '';
    $pngEngine = '';

    // 2A. Obtener imagen PNG de alta resolución a partir del gráfico ilustrado referente al texto
    if (!empty($graphicCandidates)) {
        $chosenIconId = $graphicCandidates[$variationIdx % count($graphicCandidates)];
        $parts = explode(':', $chosenIconId, 2);
        if (count($parts) === 2) {
            $svgSourceUrl = 'https://api.iconify.design/' . rawurlencode($parts[0]) . '/' . rawurlencode($parts[1]) . '.svg?width=128&height=128';
            $wsrvPngUrl = 'https://wsrv.nl/?url=' . urlencode($svgSourceUrl) . '&w=128&h=128&output=png';
            $candBytes = aiHttpGet($wsrvPngUrl, 10);
            if (!empty($candBytes) && strlen($candBytes) > 256 && @getimagesizefromstring($candBytes)) {
                $rawPngBytes = $candBytes;
                $pngEngine = "Imagen Gráfica PNG ({$chosenIconId})";
            }
        }
    }

    // 2B. Si no se obtuvo por vectorizador PNG o se prefiere imagen generativa IA (Pollinations Flux)
    if ($rawPngBytes === '') {
        $queries = translateSpanishConceptToQueries($promptText);
        $enConcept = $queries[0] ?? $promptText;
        $seed = random_int(100, 999999);
        $iconPrompt = "3D flat app icon illustration of " . $enConcept . ", single centered graphic object, vibrant colors, clean icon design, no letters, no text";
        $pollinationsUrl = 'https://image.pollinations.ai/prompt/' . rawurlencode($iconPrompt) . '?width=256&height=256&seed=' . $seed . '&nologo=true&model=flux';
        $candBytes = aiHttpGet($pollinationsUrl, 12);
        if (!empty($candBytes) && strlen($candBytes) > 512 && @getimagesizefromstring($candBytes)) {
            $rawPngBytes = $candBytes;
            $pngEngine = 'IA Generativa (PNG 128x128)';
        }
    }

    if ($rawPngBytes !== '') {
        $srcImg = @imagecreatefromstring($rawPngBytes);
        if ($srcImg) {
            $w = imagesx($srcImg);
            $h = imagesy($srcImg);
            $iconSize = 128;
            $dstImg = imagecreatetruecolor($iconSize, $iconSize);
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $trans = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
            imagefilledrectangle($dstImg, 0, 0, $iconSize, $iconSize, $trans);

            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $iconSize, $iconSize, $w, $h);
            imagedestroy($srcImg);

            $fullPath = $iconsDir . '/' . $nextFilename;
            if (imagepng($dstImg, $fullPath, 6)) {
                imagedestroy($dstImg);
                $cleanLabel = str_replace(['_', '-'], ' ', pathinfo($nextFilename, PATHINFO_FILENAME));
                return [
                    'success'      => true,
                    'filename'     => $nextFilename,
                    'url'          => '../uploads/icons/' . $nextFilename,
                    'option_label' => "🖼️ Archivo: {$cleanLabel} (png)",
                    'engine'       => $pngEngine
                ];
            }
            imagedestroy($dstImg);
        }
    }

    // Fallback a SVG gráfico
    return generateAiIconForWeb($pdo, $promptText, 'svg');
}

/**
 * Renderiza el widget completo de selección, subida y creación con IA de iconos para formularios de administración
 */
function renderIconPickerField($currentIcon, $iconOptions, $inputName = 'icon', $inputId = 'adminIconInput', $helpText = 'Puedes elegir un icono de la lista, crearlo con IA según texto, o subir un archivo .svg / .png') {
    $currentIcon = trim((string)$currentIcon);
    $isImg = preg_match('/\.(svg|png|jpg|jpeg|webp|gif)$/i', $currentIcon);
    $imgSrc = '';
    if ($isImg) {
        $imgSrc = (strpos($currentIcon, 'uploads/') === 0 || strpos($currentIcon, 'http') === 0) ? '../' . $currentIcon : '../uploads/icons/' . $currentIcon;
    }
    
    $uniqueId = preg_replace('/[^a-zA-Z0-9]/', '', $inputId);
    ?>
    <div class="icon-picker-widget" id="widget_<?php echo $uniqueId; ?>" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.2rem;">
        <input type="hidden" name="<?php echo htmlspecialchars($inputName); ?>" id="<?php echo htmlspecialchars($inputId); ?>" value="<?php echo htmlspecialchars($currentIcon); ?>">

        <!-- Fila de Selección y Preview -->
        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 0.8rem;">
            <!-- Caja de Vista Previa -->
            <div id="preview_<?php echo $uniqueId; ?>" style="width: 44px; height: 44px; border-radius: 8px; background: white; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <?php if ($isImg): ?>
                    <img src="<?php echo htmlspecialchars($imgSrc); ?>" style="width: 28px; height: 28px; object-fit: contain;">
                <?php elseif (strpos($currentIcon, 'fa-') !== false): ?>
                    <?php $pfx = (strpos($currentIcon, 'fas ') === false && strpos($currentIcon, 'far ') === false && strpos($currentIcon, 'fab ') === false) ? 'fas ' : ''; ?>
                    <i class="<?php echo $pfx . htmlspecialchars($currentIcon); ?>"></i>
                <?php else: ?>
                    <span><?php echo !empty($currentIcon) ? htmlspecialchars($currentIcon) : '❓'; ?></span>
                <?php endif; ?>
            </div>

            <!-- Desplegable -->
            <div style="flex: 1; min-width: 240px;">
                <select id="select_<?php echo $uniqueId; ?>" style="width: 100%; padding: 0.75rem 0.9rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white; cursor: pointer;">
                    <?php
                    $found = false;
                    foreach ($iconOptions as $val => $lbl) {
                        $sel = ($val === $currentIcon) ? 'selected' : '';
                        if ($sel) $found = true;
                        echo "<option value=\"" . htmlspecialchars($val) . "\" {$sel}>" . htmlspecialchars($lbl) . "</option>";
                    }
                    if (!$found && !empty($currentIcon)) {
                        echo "<option value=\"" . htmlspecialchars($currentIcon) . "\" selected>" . htmlspecialchars($currentIcon) . " (Actual)</option>";
                    }
                    ?>
                    <option value="_custom_">✏️ Escribir Emoji / Clase FontAwesome...</option>
                </select>
            </div>
        </div>

        <!-- Input para modo manual -->
        <div id="customDiv_<?php echo $uniqueId; ?>" style="display: none; margin-bottom: 0.8rem;">
            <div style="display: flex; gap: 8px;">
                <input type="text" id="customInput_<?php echo $uniqueId; ?>" placeholder="Ej: 🍕 o fas fa-star" value="<?php echo htmlspecialchars($currentIcon); ?>" style="flex: 1; padding: 0.7rem 0.9rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                <button type="button" id="applyCustom_<?php echo $uniqueId; ?>" class="btn" style="background: var(--primary); color: white; padding: 0.7rem 1rem; border-radius: 8px;"><i class="fas fa-check"></i> Aplicar</button>
            </div>
        </div>

        <!-- Generador rápido de icono con IA e incorporación automática a la lista -->
        <div style="background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%); border: 1px solid #c4b5fd; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span style="font-size: 0.82rem; font-weight: 700; color: #5b21b6;"><i class="fas fa-wand-magic-sparkles"></i> Crear icono con IA:</span>
            <input type="text" id="aiIconPrompt_<?php echo $uniqueId; ?>" placeholder="Describe el icono (ej: Castillo medieval, Tambor, Senderismo...)" style="flex: 1; min-width: 180px; padding: 0.45rem 0.75rem; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 0.85rem; background: white;">
            <select id="aiIconFormat_<?php echo $uniqueId; ?>" style="padding: 0.45rem 0.6rem; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 0.82rem; background: white; color: #4c1d95; font-weight: 600;">
                <option value="svg">Vectorial (.SVG)</option>
                <option value="png">Ilustrado (.PNG)</option>
            </select>
            <button type="button" id="btnGenAiIcon_<?php echo $uniqueId; ?>" class="btn btn-sm" style="background: #6d28d9; color: white; padding: 0.48rem 0.9rem; font-size: 0.82rem; font-weight: 700; border-radius: 6px;">
                <i class="fas fa-magic"></i> Crear e Incorporar
            </button>
        </div>

        <!-- Fila de subida rápida de archivo .svg o .png -->
        <div style="background: white; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 0.75rem 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #475569;">
                <i class="fas fa-cloud-upload-alt" style="color: var(--primary); font-size: 1.1rem;"></i>
                <span><strong>¿Subir archivo propio?</strong> (<code>.svg</code> o <code>.png</code>):</span>
            </div>
            <div>
                <input type="file" name="icon_file" id="file_<?php echo $uniqueId; ?>" accept=".svg,.png,.webp,.jpg,.jpeg" style="font-size: 0.82rem; color: #64748b;">
            </div>
        </div>

        <small style="color: #64748b; display: block; margin-top: 0.5rem; font-size: 0.8rem;"><?php echo htmlspecialchars($helpText); ?></small>
    </div>

    <script>
    (function() {
        const sel = document.getElementById('select_<?php echo $uniqueId; ?>');
        const mainInput = document.getElementById('<?php echo $inputId; ?>');
        const customDiv = document.getElementById('customDiv_<?php echo $uniqueId; ?>');
        const customInput = document.getElementById('customInput_<?php echo $uniqueId; ?>');
        const applyBtn = document.getElementById('applyCustom_<?php echo $uniqueId; ?>');
        const preview = document.getElementById('preview_<?php echo $uniqueId; ?>');
        const fileInput = document.getElementById('file_<?php echo $uniqueId; ?>');
        const aiPromptInput = document.getElementById('aiIconPrompt_<?php echo $uniqueId; ?>');
        const aiFormatSel = document.getElementById('aiIconFormat_<?php echo $uniqueId; ?>');
        const aiGenBtn = document.getElementById('btnGenAiIcon_<?php echo $uniqueId; ?>');

        function updatePreview(val) {
            val = (val || '').trim();
            if (!val) {
                preview.innerHTML = '❓';
                return;
            }
            if (val.match(/\.(svg|png|jpg|jpeg|webp|gif)$/i)) {
                let src = (val.indexOf('uploads/') === 0 || val.indexOf('http') === 0) ? '../' + val : '../uploads/icons/' + val;
                preview.innerHTML = '<img src="' + src + '?t=' + Date.now() + '" style="width:28px; height:28px; object-fit:contain;">';
            } else if (val.indexOf('fa-') !== -1) {
                let pfx = (val.indexOf('fas ') === -1 && val.indexOf('far ') === -1 && val.indexOf('fab ') === -1) ? 'fas ' : '';
                preview.innerHTML = '<i class="' + pfx + val + '"></i>';
            } else {
                preview.innerHTML = '<span>' + val + '</span>';
            }
        }

        if (mainInput) {
            mainInput.addEventListener('input', function() {
                updatePreview(this.value);
            });
        }

        if (sel) {
            sel.addEventListener('change', function() {
                if (this.value === '_custom_') {
                    customDiv.style.display = 'block';
                    customInput.focus();
                } else {
                    customDiv.style.display = 'none';
                    mainInput.value = this.value;
                    updatePreview(this.value);
                }
            });

            if (sel.value === '_custom_') {
                customDiv.style.display = 'block';
            }
        }

        if (applyBtn && customInput) {
            applyBtn.addEventListener('click', function() {
                const val = customInput.value.trim();
                if (val) {
                    mainInput.value = val;
                    let exists = false;
                    for (let i = 0; i < sel.options.length; i++) {
                        if (sel.options[i].value === val) {
                            sel.selectedIndex = i;
                            exists = true;
                            break;
                        }
                    }
                    if (!exists) {
                        const opt = new Option(val + ' (Personalizado)', val, true, true);
                        sel.insertBefore(opt, sel.options[sel.options.length - 1]);
                    }
                    updatePreview(val);
                    customDiv.style.display = 'none';
                }
            });
        }

        if (aiGenBtn && aiPromptInput) {
            aiGenBtn.addEventListener('click', function() {
                const promptVal = aiPromptInput.value.trim();
                if (!promptVal) {
                    alert('Escribe una descripción para el icono que deseas generar con IA.');
                    aiPromptInput.focus();
                    return;
                }
                const origHtml = aiGenBtn.innerHTML;
                aiGenBtn.disabled = true;
                aiGenBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';

                const fd = new FormData();
                fd.append('ajax_action', 'generate_ai_icon');
                fd.append('prompt', promptVal);
                fd.append('format', aiFormatSel ? aiFormatSel.value : 'svg');

                fetch('ai_news_ajax.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        aiGenBtn.disabled = false;
                        aiGenBtn.innerHTML = origHtml;
                        if (data.success && data.filename) {
                            const opt = new Option(data.option_label || ('🖼️ Archivo: ' + data.filename), data.filename, true, true);
                            sel.insertBefore(opt, sel.firstChild);
                            sel.value = data.filename;
                            mainInput.value = data.filename;
                            updatePreview(data.filename);
                            aiPromptInput.value = '';
                        } else {
                            alert(data.error || 'No se pudo generar el icono.');
                        }
                    })
                    .catch(err => {
                        aiGenBtn.disabled = false;
                        aiGenBtn.innerHTML = origHtml;
                        alert('Error de conexión al generar el icono.');
                    });
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const f = this.files[0];
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = '<img src="' + e.target.result + '" style="width:28px; height:28px; object-fit:contain;">';
                    };
                    reader.readAsDataURL(f);
                }
            });
        }
    })();
    </script>
    <?php
}