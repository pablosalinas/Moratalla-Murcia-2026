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
 * Construye un icono vectorial SVG inteligente y colorido en local según el texto indicado (fallback si no hay clave Gemini)
 */
function buildSmartVectorSvgIcon($text) {
    $lower = mb_strtolower($text, 'UTF-8');

    // Paletas y gráficos vectoriales según temática
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
        // Emblema personalizado con inicial o símbolo estilizado de Moratalla
        $bg1 = '#1b4332'; $bg2 = '#2d6a4f'; $acc = '#d4af37';
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $initials = '';
        foreach ($words as $w) {
            if (mb_strlen($w) > 2 || count($words) === 1) {
                $initials .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
                if (mb_strlen($initials) >= 2) break;
            }
        }
        if ($initials === '') $initials = 'M';
        $safeInit = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
        $inner = '<circle cx="32" cy="32" r="21" fill="none" stroke="' . $acc . '" stroke-width="2" stroke-dasharray="4,2"/>'
               . '<text x="32" y="39" text-anchor="middle" font-family="Outfit, Inter, Arial, sans-serif" font-size="20" font-weight="800" fill="#ffffff">' . $safeInit . '</text>';
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
 * Genera un icono a partir del texto indicado usando IA (Google Gemini para SVG vectorial o IA generativa para PNG)
 * y lo guarda directamente en `uploads/icons/` para incorporarlo a la relación de iconos de la web.
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

    // MODO 1: ICONO VECTORIAL SVG CON GOOGLE GEMINI (o generador vectorial inteligente)
    if ($format === 'svg') {
        $svgCode = '';
        $engineUsed = 'Motor Vectorial Moratalla';

        if (!empty($apiKey)) {
            $svgPrompt = <<<PROMPT
Eres un diseñador experto en iconografía vectorial UI/UX para el portal turístico y municipal "moratalla-murcia.com".
Crea un icono vectorial SVG moderno, colorido, limpio, expresivo y muy legible a tamaño pequeño (24px-64px) que represente exactamente: "{$promptText}".

Requisitos técnicos obligatorios:
1. Devuelve ÚNICAMENTE el código XML `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">...</svg>` sin bloques markdown ni explicaciones.
2. Usa un fondo de tarjeta redondeada (`<rect x="4" y="4" width="56" height="56" rx="14" .../>`) con un degradado atractivo acorde a la temática (o los tonos corporativos verde bosque #1b4332 / dorado #d4af37 si encaja) y figuras centrales en alto contraste con sombras o detalles cuidados.
3. No incluyas etiquetas `<script>` ni imágenes externas; usa exclusivamente `<path>`, `<circle>`, `<rect>`, `<polygon>`, `<ellipse>`, `<line>`, `<g>` y `<linearGradient>`.
PROMPT;

            $models = ['gemini-2.5-flash', 'gemini-2.0-flash', 'gemini-1.5-flash'];
            foreach ($models as $model) {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
                $payload = json_encode([
                    'contents' => [
                        ['parts' => [['text' => $svgPrompt]]]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4
                    ]
                ]);

                $ch = curl_init($endpoint);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $payload,
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT        => 22,
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
                        // Sanitizar por seguridad
                        $candSvg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $candSvg);
                        $candSvg = preg_replace('/\bon\w+\s*=\s*"[^"]*"/i', '', $candSvg);
                        if (stripos($candSvg, 'xmlns=') === false) {
                            $candSvg = preg_replace('/^<svg/i', '<svg xmlns="http://www.w3.org/2000/svg"', $candSvg);
                        }
                        $svgCode = $candSvg;
                        $engineUsed = "Google Gemini SVG ($model)";
                        break;
                    }
                }
            }
        }

        if ($svgCode === '') {
            $svgCode = buildSmartVectorSvgIcon($promptText);
        }

        $filename = buildCleanIconFilename($promptText, 'svg');
        $fullPath = $iconsDir . '/' . $filename;
        if (@file_put_contents($fullPath, $svgCode) !== false) {
            $cleanLabel = str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME));
            return [
                'success'      => true,
                'filename'     => $filename,
                'url'          => '../uploads/icons/' . $filename,
                'option_label' => "🖼️ Archivo: {$cleanLabel} (svg)",
                'engine'       => $engineUsed
            ];
        }

        return ['success' => false, 'error' => 'No se pudo guardar el archivo SVG en uploads/icons/.'];
    }

    // MODO 2: ICONO ILUSTRADO PNG 3D/FLAT CON IA GENERATIVA
    $seed = random_int(100, 999999);
    $iconPrompt = "Flat vector app icon emblem of " . $promptText . ", centered icon badge, vibrant harmonious colors, clean sharp design, minimalist icon illustration, no text, high contrast";
    $pollinationsUrl = 'https://image.pollinations.ai/prompt/' . rawurlencode($iconPrompt) . '?width=256&height=256&seed=' . $seed . '&nologo=true&model=flux';

    $rawBytes = aiHttpGet($pollinationsUrl, 22);
    if (!empty($rawBytes) && strlen($rawBytes) > 512) {
        $srcImg = @imagecreatefromstring($rawBytes);
        if ($srcImg) {
            $w = imagesx($srcImg);
            $h = imagesy($srcImg);
            $iconSize = 128;
            $dstImg = imagecreatetruecolor($iconSize, $iconSize);
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);
            $trans = imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
            imagefilledrectangle($dstImg, 0, 0, $iconSize, $iconSize, $trans);

            // Redimensionar con bordes redondeados limpios para que luzca perfecto como icono en cualquier menú
            $tempResized = imagecreatetruecolor($iconSize, $iconSize);
            imagecopyresampled($tempResized, $srcImg, 0, 0, 0, 0, $iconSize, $iconSize, $w, $h);
            imagedestroy($srcImg);

            $radius = 24;
            for ($x = 0; $x < $iconSize; $x++) {
                for ($y = 0; $y < $iconSize; $y++) {
                    $inCorner = false;
                    $dx = 0; $dy = 0;
                    if ($x < $radius && $y < $radius) { $dx = $radius - $x; $dy = $radius - $y; $inCorner = true; }
                    elseif ($x >= $iconSize - $radius && $y < $radius) { $dx = $x - ($iconSize - $radius - 1); $dy = $radius - $y; $inCorner = true; }
                    elseif ($x < $radius && $y >= $iconSize - $radius) { $dx = $radius - $x; $dy = $y - ($iconSize - $radius - 1); $inCorner = true; }
                    elseif ($x >= $iconSize - $radius && $y >= $iconSize - $radius) { $dx = $x - ($iconSize - $radius - 1); $dy = $y - ($iconSize - $radius - 1); $inCorner = true; }

                    if (!$inCorner || ($dx * $dx + $dy * $dy <= $radius * $radius)) {
                        $rgb = imagecolorat($tempResized, $x, $y);
                        imagesetpixel($dstImg, $x, $y, $rgb);
                    }
                }
            }
            imagedestroy($tempResized);

            $filename = buildCleanIconFilename($promptText, 'png');
            $fullPath = $iconsDir . '/' . $filename;
            if (imagepng($dstImg, $fullPath, 6)) {
                imagedestroy($dstImg);
                $cleanLabel = str_replace(['_', '-'], ' ', pathinfo($filename, PATHINFO_FILENAME));
                return [
                    'success'      => true,
                    'filename'     => $filename,
                    'url'          => '../uploads/icons/' . $filename,
                    'option_label' => "🖼️ Archivo: {$cleanLabel} (png)",
                    'engine'       => 'IA Generativa (PNG 128x128)'
                ];
            }
            imagedestroy($dstImg);
        }
    }

    // Fallback instantáneo a SVG si el servicio externo PNG tardó demasiado
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