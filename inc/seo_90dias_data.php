<?php
// inc/seo_90dias_data.php
// Repositorio estructurado de Guías Pilar, Guías Temáticas, Tarjetas Existentes de Producción y Contenido Estacional.

function getSeo90StateFile(): string {
    return __DIR__ . '/../.seo_90dias_state.json';
}

function canManageSeo90(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (!empty($_SESSION['admin_id'])) {
        return true;
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'moratalla-murcia.com') === false) {
        return true; // Entorno local
    }
    return false;
}

function setSeo90ActiveState(bool $newState): void {
    global $pdo;
    @file_put_contents(getSeo90StateFile(), json_encode([
        'enabled'    => $newState,
        'updated_at' => date('Y-m-d H:i:s')
    ]));
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['seo_90dias_enabled'] = $newState;
    }
    try {
        if (!isset($pdo) && function_exists('getDB')) {
            $pdo = getDB();
        }
        if ($pdo) {
            $val = $newState ? '1' : '0';
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'seo_90dias_enabled'");
            $stmt->execute();
            if ((int)$stmt->fetchColumn() > 0) {
                $upd = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'seo_90dias_enabled'");
                $upd->execute([$val]);
            } else {
                $ins = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('seo_90dias_enabled', ?)");
                $ins->execute([$val]);
            }
        }
    } catch (\Throwable $e) {
        // Silencioso si no hay conexión en ese instante
    }
}

function isSeo90Active(): bool {
    global $pdo;

    if (isset($_GET['toggle_seo_90']) && canManageSeo90()) {
        $newState = ($_GET['toggle_seo_90'] === '1');
        setSeo90ActiveState($newState);
        return $newState;
    }

    if (isset($_GET['preview_seo_90'])) {
        return $_GET['preview_seo_90'] === '1';
    }

    // 1. Consultar preferencia persistente en base de datos
    try {
        if (!isset($pdo) && function_exists('getDB')) {
            $pdo = getDB();
        }
        if ($pdo) {
            $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'seo_90dias_enabled' LIMIT 1");
            $dbVal = $stmt ? $stmt->fetchColumn() : false;
            if ($dbVal !== false && $dbVal !== null) {
                return ($dbVal === '1' || $dbVal === 1 || $dbVal === true);
            }
        }
    } catch (\Throwable $e) {}

    // 2. Fallback a archivo de estado
    $stateFile = getSeo90StateFile();
    if (file_exists($stateFile)) {
        $data = @json_decode(@file_get_contents($stateFile), true);
        if (is_array($data) && isset($data['enabled'])) {
            return (bool)$data['enabled'];
        }
    }

    return true; // Activo por defecto
}

/**
 * Obtiene la configuración granular del módulo SEO desde la tabla settings
 */
function getSeoConfig(?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) ? $GLOBALS['pdo'] : (function_exists('getDB') ? getDB() : null);
    }

    $defaults = [
        'seo_90dias_enabled'     => isSeo90Active() ? '1' : '0',
        'seo_show_menu'          => '1',
        'seo_show_home_top'      => '1',
        'seo_show_home_bottom'   => '1',
        'seo_show_footer'        => '1',
        'seo_show_floating_home' => '1',
        'seo_home_title'         => 'Moratalla | Guía Turística, Patrimonio Histórico y Cultura en Murcia',
        'seo_home_desc'          => 'Descubre Moratalla: patrimonio histórico, pinturas rupestres UNESCO, castillo medieval, turismo rural, Semana Santa, tambores y rutas naturales en el corazón de Murcia.',
        'seo_global_keywords'    => 'Moratalla, Turismo en Moratalla, Qué ver en Moratalla, Moratalla Murcia, Casas rurales Moratalla, Castillo de Moratalla, Iglesia de Santa María de la Asunción, Pinturas rupestres de Moratalla, Semana Santa de Moratalla, Tamboradas de Moratalla'
    ];

    if ($pdo) {
        try {
            $keys = array_keys($defaults);
            $placeholders = implode(',', array_fill(0, count($keys), '?'));
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
            $stmt->execute($keys);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $k = $row['setting_key'];
                if ($row['setting_value'] !== null && $row['setting_value'] !== '') {
                    $defaults[$k] = $row['setting_value'];
                }
            }
        } catch (\Throwable $e) {}
    }

    return $defaults;
}

/**
 * Guarda valores de configuración SEO en la tabla settings (100% aditivo)
 */
function saveSeoConfig(PDO $pdo, array $values): void {
    foreach ($values as $k => $v) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
        $stmt->execute([$k]);
        if ((int)$stmt->fetchColumn() > 0) {
            $upd = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $upd->execute([(string)$v, $k]);
        } else {
            $ins = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $ins->execute([$k, (string)$v]);
        }
    }
    if (isset($values['seo_90dias_enabled'])) {
        setSeo90ActiveState($values['seo_90dias_enabled'] === '1');
    }
}

/**
 * Crea de forma aditiva la tabla seo_guides y siembra las guías iniciales si está vacía
 */
function ensureSeoGuidesTable(?PDO $pdo = null): void {
    if (!$pdo) {
        $pdo = (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) ? $GLOBALS['pdo'] : (function_exists('getDB') ? getDB() : null);
    }
    if (!$pdo) return;

    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `seo_guides` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(190) NOT NULL UNIQUE,
            `type` VARCHAR(50) NOT NULL DEFAULT 'articulo',
            `phase` VARCHAR(150) DEFAULT '',
            `priority_rank` INT DEFAULT 99,
            `short_title` VARCHAR(255) NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `h1` VARCHAR(255) NOT NULL,
            `subtitle` TEXT,
            `meta_description` TEXT,
            `keywords` TEXT,
            `icon` VARCHAR(100) DEFAULT 'fas fa-compass',
            `hero_image` VARCHAR(255) DEFAULT 'uploads/seo/pilar-guia-completa.svg',
            `logo_image` VARCHAR(255) DEFAULT 'uploads/seo/pilar-guia-completa.svg',
            `reading_time` VARCHAR(80) DEFAULT '8 min de lectura',
            `map_query` VARCHAR(255) DEFAULT 'Moratalla, Murcia',
            `quick_facts_json` LONGTEXT,
            `sections_json` LONGTEXT,
            `faqs_json` LONGTEXT,
            `related_cards_json` TEXT,
            `related_slugs_json` TEXT,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        $count = (int)$pdo->query("SELECT COUNT(*) FROM seo_guides")->fetchColumn();
        if ($count === 0) {
            $defaults = getDefaultSeo90Pages();
            $ins = $pdo->prepare("INSERT INTO seo_guides
                (slug, type, phase, priority_rank, short_title, title, h1, subtitle, meta_description, keywords, icon, hero_image, logo_image, reading_time, map_query, quick_facts_json, sections_json, faqs_json, related_cards_json, related_slugs_json, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            foreach ($defaults as $slug => $g) {
                $ins->execute([
                    $g['slug'] ?? $slug,
                    $g['type'] ?? 'articulo',
                    $g['phase'] ?? '',
                    (int)($g['priority_rank'] ?? 99),
                    $g['short_title'] ?? '',
                    $g['title'] ?? '',
                    $g['h1'] ?? '',
                    $g['subtitle'] ?? '',
                    $g['meta_description'] ?? '',
                    $g['keywords'] ?? '',
                    $g['icon'] ?? 'fas fa-compass',
                    $g['hero_image'] ?? 'uploads/seo/pilar-guia-completa.svg',
                    $g['logo_image'] ?? ($g['hero_image'] ?? 'uploads/seo/pilar-guia-completa.svg'),
                    $g['reading_time'] ?? '8 min de lectura',
                    $g['map_query'] ?? 'Moratalla, Murcia',
                    json_encode($g['quick_facts'] ?? [], JSON_UNESCAPED_UNICODE),
                    json_encode($g['sections'] ?? [], JSON_UNESCAPED_UNICODE),
                    json_encode($g['faqs'] ?? [], JSON_UNESCAPED_UNICODE),
                    json_encode($g['related_cards'] ?? [], JSON_UNESCAPED_UNICODE),
                    json_encode($g['related_slugs'] ?? [], JSON_UNESCAPED_UNICODE)
                ]);
            }
        }
    } catch (\Throwable $e) {
        // Silencioso en caso de fallo de conexión
    }
}

/**
 * Catálogo de Tarjetas y Fichas existentes en la Base de Datos de Producción (moratalla-murcia.com)
 * Incluye su ID real en producción, enlace directo, descripción, contador de fotografías y portada.
 */
function getProductionExistingCards(?PDO $pdo = null): array {
    $cards = [
        'castillo' => [
            'id'            => 'castillo',
            'page_id'       => 433,
            'cat_id'        => 194,
            'title'         => 'Castillo fortaleza de Moratalla',
            'category_name' => 'Patrimonio',
            'url'           => 'page.php?id=433',
            'cat_url'       => 'category.php?id=194',
            'photos_count'  => 28,
            'badge'         => 'Ficha Patrimonial · 28 fotografías',
            'icon'          => 'fas fa-chess-rook',
            'image'         => 'uploads/galerias/6a674e365ae8c_IMGP7928.JPG',
            'fallback_img'  => 'uploads/seo/guia-castillo.svg',
            'description'   => 'Tarjeta oficial del Castillo-Fortaleza de Moratalla y su Torre del Homenaje con descripción histórica y galería de 28 fotografías.'
        ],
        'asuncion' => [
            'id'            => 'asuncion',
            'page_id'       => 440,
            'cat_id'        => 194,
            'title'         => 'Parroquia de Santa María de la Asunción',
            'category_name' => 'Patrimonio',
            'url'           => 'page.php?id=440',
            'cat_url'       => 'category.php?id=194',
            'photos_count'  => 29,
            'badge'         => 'Monumento BIC · 29 fotografías',
            'icon'          => 'fas fa-church',
            'image'         => 'uploads/galerias/6a73c1aa15b1b_IMG_20211002_193016.jpg',
            'fallback_img'  => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'description'   => 'Ficha monumental de la Parroquia de Santa María de la Asunción (s. XVI) con su historia arquitectónica y 29 fotografías.'
        ],
        'casa_cristo' => [
            'id'            => 'casa_cristo',
            'page_id'       => 441,
            'cat_id'        => 194,
            'title'         => 'Santuario de la Casa de Cristo',
            'category_name' => 'Patrimonio',
            'url'           => 'page.php?id=441',
            'cat_url'       => 'category.php?id=194',
            'photos_count'  => 15,
            'badge'         => 'Santuario y Arte Rupestre · 15 fotografías',
            'icon'          => 'fas fa-place-of-worship',
            'image'         => 'uploads/galerias/6a7b94c4c709e_arcos convento.jpeg',
            'fallback_img'  => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'description'   => 'Tarjeta del Santuario y antiguo convento de la Casa de Cristo, sede del Centro de Interpretación del Arte Rupestre, con 15 fotografías.'
        ],
        'las_arcas' => [
            'id'            => 'las_arcas',
            'page_id'       => 446,
            'cat_id'        => 194,
            'title'         => 'Las Arcas (Acueducto e Ingeniería Hidráulica)',
            'category_name' => 'Patrimonio',
            'url'           => 'page.php?id=446',
            'cat_url'       => 'category.php?id=194',
            'photos_count'  => 21,
            'badge'         => 'Patrimonio Hidráulico · 21 fotografías',
            'icon'          => 'fas fa-archway',
            'image'         => 'uploads/galerias/6a7b9a5b28fe9_IMGP1707.JPG',
            'fallback_img'  => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'description'   => 'Ficha y reportaje fotográfico con 21 imágenes de Las Arcas, obra histórica de conducción de aguas en Moratalla.'
        ],
        'santa_ana' => [
            'id'            => 'santa_ana',
            'page_id'       => 434,
            'cat_id'        => 194,
            'title'         => 'Ermita de Santa Ana',
            'category_name' => 'Patrimonio',
            'url'           => 'page.php?id=434',
            'cat_url'       => 'category.php?id=194',
            'photos_count'  => 3,
            'badge'         => 'Patrimonio Religioso · 3 fotografías',
            'icon'          => 'fas fa-Pray',
            'image'         => 'uploads/galerias/6a6b1cf74a3e8_Iglesia Santa Ana fachada principal 08.jpeg',
            'fallback_img'  => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'description'   => 'Tarjeta patrimonial de la histórica Ermita de Santa Ana en el casco urbano de Moratalla con fotografías de su fachada e interior.'
        ],
        'san_francisco' => [
            'id'            => 'san_francisco',
            'page_id'       => 435,
            'cat_id'        => 194,
            'title'         => 'Convento de San Francisco',
            'category_name' => 'Patrimonio',
            'url'           => 'page.php?id=435',
            'cat_url'       => 'category.php?id=194',
            'photos_count'  => 0,
            'badge'         => 'Ficha Patrimonial e Histórica',
            'icon'          => 'fas fa-landmark',
            'image'         => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'fallback_img'  => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'description'   => 'Tarjeta dedicada a la Iglesia y antiguo Convento de San Francisco, joya del barroco en el corazón de Moratalla.'
        ],
        'puente_hellin' => [
            'id'            => 'puente_hellin',
            'page_id'       => 445,
            'cat_id'        => 194,
            'title'         => 'Puente de Hellín sobre el Río Alhárabe',
            'category_name' => 'Patrimonio',
            'url'           => 'page.php?id=445',
            'cat_url'       => 'category.php?id=194',
            'photos_count'  => 0,
            'badge'         => 'Ingeniería Histórica y Rutas',
            'icon'          => 'fas fa-water',
            'image'         => 'uploads/seo/pilar-turismo-rural.svg',
            'fallback_img'  => 'uploads/seo/pilar-turismo-rural.svg',
            'description'   => 'Ficha patrimonial del histórico Puente de Hellín sobre el río Alhárabe, paso tradicional y punto clave de rutas naturales.'
        ],
        'arte_rupestre' => [
            'id'            => 'arte_rupestre',
            'page_id'       => 451,
            'cat_id'        => 194,
            'title'         => 'Arte Rupestre de Moratalla',
            'category_name' => 'Patrimonio',
            'url'           => 'page.php?id=451',
            'cat_url'       => 'category.php?id=194',
            'photos_count'  => 0,
            'badge'         => 'Patrimonio Mundial UNESCO',
            'icon'          => 'fas fa-palette',
            'image'         => 'uploads/seo/guia-rupestre.svg',
            'fallback_img'  => 'uploads/seo/guia-rupestre.svg',
            'description'   => 'Tarjeta de Patrimonio sobre los abrigos y pinturas de Arte Rupestre Levantino y Esquemático de Moratalla.'
        ],
        'semana_santa_ficha' => [
            'id'            => 'semana_santa_ficha',
            'page_id'       => 453,
            'cat_id'        => 44,
            'title'         => 'Semana Santa de Moratalla (Crónica y Galería)',
            'category_name' => 'Semana Santa',
            'url'           => 'page.php?id=453',
            'cat_url'       => 'category.php?id=44',
            'photos_count'  => 87,
            'badge'         => 'Crónica + 87 fotografías',
            'icon'          => 'fas fa-cross',
            'image'         => 'uploads/galerias/6ad142c13f529_CIMG2926.JPG',
            'fallback_img'  => 'uploads/seo/guia-semana-santa.svg',
            'description'   => 'Tarjeta especial de la Semana Santa de Moratalla con reseña histórica de Pedro Martínez López y 87 fotografías.'
        ],
        'museo_tambor' => [
            'id'            => 'museo_tambor',
            'page_id'       => 430,
            'cat_id'        => 168,
            'title'         => 'Museo del Tambor (Moratalla)',
            'category_name' => 'Tambores y Semana Santa',
            'url'           => 'page.php?id=430',
            'cat_url'       => 'category.php?id=168',
            'photos_count'  => 15,
            'badge'         => 'Museo y Tradición · 15 fotografías',
            'icon'          => 'fas fa-drum',
            'image'         => 'uploads/galerias/6a3a6e0331044__MG_9956.jpg',
            'fallback_img'  => 'uploads/seo/guia-semana-santa.svg',
            'description'   => 'Tarjeta oficial del Museo del Tambor de Moratalla con exposición de túnicas artesanales, tambores históricos y 15 fotografías.'
        ],
        'bic_tamborada' => [
            'id'            => 'bic_tamborada',
            'page_id'       => 458,
            'cat_id'        => 44,
            'title'         => 'Declaración de Interés Cultural Inmaterial (Tamborada)',
            'category_name' => 'Tambores y Semana Santa',
            'url'           => 'page.php?id=458',
            'cat_url'       => 'category.php?id=168',
            'photos_count'  => 0,
            'badge'         => 'Reconocimiento Oficial UNESCO / BIC',
            'icon'          => 'fas fa-award',
            'image'         => 'uploads/asociaciones/asoc_tamboristas_2004_0.jpg',
            'fallback_img'  => 'uploads/seo/guia-semana-santa.svg',
            'description'   => 'Documento y tarjeta oficial sobre la Declaración de Interés Cultural Inmaterial de la Fiesta de los Tambores de Moratalla.'
        ],
        'cofradia_nazareno' => [
            'id'            => 'cofradia_nazareno',
            'page_id'       => 409,
            'cat_id'        => 181,
            'title'         => 'Ilustre Cofradía de Nuestro Padre Jesús Nazareno',
            'category_name' => 'Cofradías de Semana Santa',
            'url'           => 'page.php?id=409',
            'cat_url'       => 'category.php?id=181',
            'photos_count'  => 26,
            'badge'         => 'Cofradías · 26 fotografías',
            'icon'          => 'fas fa-church',
            'image'         => 'uploads/asociaciones/asoc_nazareno_padrejesusnazareno_0.jpg',
            'fallback_img'  => 'uploads/seo/guia-semana-santa.svg',
            'description'   => 'Tarjeta de la Cofradía de Nuestro Padre Jesús Nazareno (junto al resto de cofradías en la categoría Semana Santa) con 26 fotografías.'
        ],
        'exaltacion_tambor_2008' => [
            'id'            => 'exaltacion_tambor_2008',
            'page_id'       => 411,
            'cat_id'        => 168,
            'title'         => 'Exaltación del Tambor y el Bombo (Asociación de Tamboristas)',
            'category_name' => 'Asociación de Tamboristas',
            'url'           => 'page.php?id=411',
            'cat_url'       => 'category.php?id=168',
            'photos_count'  => 30,
            'badge'         => 'Archivo Tamborista · 30 fotografías',
            'icon'          => 'fas fa-drum',
            'image'         => 'uploads/asociaciones/asoc_tamboristas_2008_8.jpg',
            'fallback_img'  => 'uploads/seo/guia-semana-santa.svg',
            'description'   => 'Reportaje fotográfico de las Jornadas de Exaltación del Tambor y el Bombo y archivo de la Asociación de Tamboristas.'
        ],
        'calendario_fiestas' => [
            'id'            => 'calendario_fiestas',
            'page_id'       => 163,
            'cat_id'        => 39,
            'title'         => 'Calendario de Fiestas de Moratalla y Pedanías',
            'category_name' => 'Fiestas',
            'url'           => 'page.php?id=163',
            'cat_url'       => 'category.php?id=39',
            'photos_count'  => 14,
            'badge'         => 'Tarjetas de Fiestas con Fechas y GPS',
            'icon'          => 'fas fa-calendar-alt',
            'image'         => 'uploads/galerias/6aa28b4a1d43b_IMGP7446.JPG',
            'fallback_img'  => 'uploads/seo/guia-fiestas.svg',
            'description'   => 'Tarjetas interactivas de todas las fiestas de Moratalla y sus pedanías (fechas, historia, eventos destacados y coordenadas GPS).'
        ],
        'cristo_del_rayo' => [
            'id'            => 'cristo_del_rayo',
            'page_id'       => 169,
            'cat_id'        => 45,
            'title'         => 'Santísimo Cristo del Rayo (Historia, Peñas y Encierros)',
            'category_name' => 'Fiestas del Cristo del Rayo',
            'url'           => 'category.php?id=45',
            'cat_url'       => 'category.php?id=191',
            'photos_count'  => 40,
            'badge'         => 'Referencia Histórica y Fiestas',
            'icon'          => 'fas fa-bolt',
            'image'         => 'uploads/banners/banner_1778708256.jpg',
            'fallback_img'  => 'uploads/seo/guia-fiestas.svg',
            'description'   => 'Sección y fichas históricas del Santísimo Cristo del Rayo (referencia de 1621, encierros por vereda, peñas festeras y programas).'
        ],
        'san_anton_feria' => [
            'id'            => 'san_anton_feria',
            'page_id'       => 449,
            'cat_id'        => 193,
            'title'         => 'Feria de San Miguel, San Antón y Fiestas de Pedanías',
            'category_name' => 'Fiestas Tradicionales',
            'url'           => 'page.php?id=449',
            'cat_url'       => 'category.php?id=39',
            'photos_count'  => 18,
            'badge'         => 'San Miguel, San Antón y Pedanías',
            'icon'          => 'fas fa-fire',
            'image'         => 'uploads/galerias/6aa28b4a1d43b_IMGP7446.JPG',
            'fallback_img'  => 'uploads/seo/guia-fiestas.svg',
            'description'   => 'Fichas fotográficas de la Feria de San Miguel (id=449), San Antón (id=164), Calar de la Santa (id=442), El Sabinar (id=443) y Cañada de la Cruz (id=444).'
        ],
        'historia_ludena' => [
            'id'            => 'historia_ludena',
            'page_id'       => 290,
            'cat_id'        => 174,
            'title'         => 'Historia de Moratalla (Archivo Documental)',
            'category_name' => 'Historia y Cultura',
            'url'           => 'page.php?id=290',
            'cat_url'       => 'category.php?id=174',
            'photos_count'  => 12,
            'badge'         => 'Estudio Histórico de José Ludeña López',
            'icon'          => 'fas fa-scroll',
            'image'         => 'uploads/banners/banner_1778706093.jpg',
            'fallback_img'  => 'uploads/seo/pilar-historia.svg',
            'description'   => 'Ficha histórica de Moratalla documentada por José Ludeña López junto a la Bibliografía de Moratalla (page.php?id=296).'
        ],
        'banda_musica_cultura' => [
            'id'            => 'banda_musica_cultura',
            'page_id'       => 294,
            'cat_id'        => 129,
            'title'         => 'Banda de Música, Rondalla Trieta y Artesanía del Esparto',
            'category_name' => 'Cultura, Música y Artesanía',
            'url'           => 'page.php?id=294',
            'cat_url'       => 'category.php?id=127',
            'photos_count'  => 24,
            'badge'         => 'Memoria Cultural y Fotografías Antiguas',
            'icon'          => 'fas fa-music',
            'image'         => 'uploads/galerias/6a0dfcf755f3d_PIC00319.JPG',
            'fallback_img'  => 'uploads/seo/pilar-historia.svg',
            'description'   => 'Tarjetas históricas de la Banda de Música (id=294), Rondalla Trieta (id=295) y Artesanía del Esparto de Bartolomé Marín "El Granados" (id=306).'
        ],
        'gastronomia_ficha' => [
            'id'            => 'gastronomia_ficha',
            'page_id'       => 263,
            'cat_id'        => 80,
            'title'         => 'Gastronomía Moratallera',
            'category_name' => 'Gastronomía',
            'url'           => 'page.php?id=263',
            'cat_url'       => 'category.php?id=80',
            'photos_count'  => 8,
            'badge'         => 'Ficha Oficial de Gastronomía',
            'icon'          => 'fas fa-utensils',
            'image'         => 'uploads/banners/banner_1778708256.jpg',
            'fallback_img'  => 'uploads/seo/pilar-gastronomia.svg',
            'description'   => 'Tarjeta tradicional de la Gastronomía Moratallera con los sabores, productos serranos y dulces de horno del municipio.'
        ],
        'recetas_ficha' => [
            'id'            => 'recetas_ficha',
            'page_id'       => 428,
            'cat_id'        => 80,
            'title'         => 'Recetas de Moratalla (Recetario Tradicional)',
            'category_name' => 'Gastronomía',
            'url'           => 'page.php?id=428',
            'cat_url'       => 'category.php?id=80',
            'photos_count'  => 10,
            'badge'         => 'Recetario Paso a Paso',
            'icon'          => 'fas fa-book-open',
            'image'         => 'uploads/seo/pilar-gastronomia.svg',
            'fallback_img'  => 'uploads/seo/pilar-gastronomia.svg',
            'description'   => 'Tarjeta con las recetas tradicionales de Moratalla: migas, arroz con conejo y caracoles, andrajos, olla de cerdo y mazapán.'
        ],
        'alojamientos_ficha' => [
            'id'            => 'alojamientos_ficha',
            'page_id'       => 278,
            'cat_id'        => 116,
            'title'         => 'Directorio de Casas Rurales y Alojamientos de Moratalla',
            'category_name' => 'Turismo Rural',
            'url'           => 'alojamientos.php',
            'cat_url'       => 'page.php?id=278',
            'photos_count'  => 80,
            'badge'         => 'Directorio Interactivo + Tarjeta Rural',
            'icon'          => 'fas fa-bed',
            'image'         => 'uploads/seo/pilar-turismo-rural.svg',
            'fallback_img'  => 'uploads/seo/pilar-turismo-rural.svg',
            'description'   => 'Buscador interactivo de Casas Rurales, hoteles y camping en Moratalla (alojamientos.php) y ficha histórica de Casas Rurales (page.php?id=278).'
        ],
        'situacion_rutas_filippedes' => [
            'id'            => 'situacion_rutas_filippedes',
            'page_id'       => 452,
            'cat_id'        => 186,
            'title'         => 'Club Deportivo Filippedes, Ciclismo y Situación Geográfica',
            'category_name' => 'Naturaleza, Montaña y Deporte',
            'url'           => 'page.php?id=452',
            'cat_url'       => 'page.php?id=279',
            'photos_count'  => 12,
            'badge'         => 'Senderismo, Trail y Geografía',
            'icon'          => 'fas fa-hiking',
            'image'         => 'uploads/galerias/6ac8dbde3f559_Club deportivo filippedes.jpg',
            'fallback_img'  => 'uploads/seo/guia-senderismo.svg',
            'description'   => 'Tarjetas de montaña y rutas: Club Deportivo Filippedes (id=452), Club Ciclista Moratalla (id=427) y Situación Geográfica (id=279).'
        ]
    ];

    // Si estamos conectados a la BD de producción y existen las páginas, enriquecemos dinámicamente el contador o imagen de galería sin borrar ni alterar nada
    if ($pdo) {
        try {
            $pageIds = [];
            foreach ($cards as $c) {
                if (!empty($c['page_id'])) {
                    $pageIds[] = (int)$c['page_id'];
                }
            }
            $pageIds = array_values(array_unique($pageIds));
            if (!empty($pageIds)) {
                $placeholders = implode(',', array_fill(0, count($pageIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT p.id, p.title,
                           (SELECT COUNT(*) FROM page_images pi WHERE pi.page_id = p.id) AS total_imgs,
                           (SELECT pi2.image_path FROM page_images pi2 WHERE pi2.page_id = p.id ORDER BY pi2.sort_order ASC, pi2.id ASC LIMIT 1) AS first_img
                    FROM pages p
                    WHERE p.id IN ($placeholders)
                ");
                $stmt->execute($pageIds);
                $dbRows = [];
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $dbRows[(int)$r['id']] = $r;
                }
                foreach ($cards as $key => &$card) {
                    $pid = (int)$card['page_id'];
                    if (isset($dbRows[$pid])) {
                        $row = $dbRows[$pid];
                        $cnt = (int)$row['total_imgs'];
                        if ($cnt > 0) {
                            $card['photos_count'] = $cnt;
                        }
                        if (!empty($row['first_img'])) {
                            $card['image'] = $row['first_img'];
                        }
                    }
                }
                unset($card);
            }
        } catch (\Throwable $e) {
            // Lectura segura silenciosa
        }
    }

    return $cards;
}

/**
 * Devuelve las tarjetas existentes asociadas a una guía concreta
 */
function getCardsForGuide(string $slug, ?PDO $pdo = null): array {
    $allGuides = getSeo90Pages();
    $allCards  = getProductionExistingCards($pdo);
    if (!isset($allGuides[$slug])) {
        return [];
    }
    $keys = $allGuides[$slug]['related_cards'] ?? [];
    $result = [];
    foreach ($keys as $k) {
        if (isset($allCards[$k])) {
            $result[] = $allCards[$k];
        }
    }
    return $result;
}

/**
 * Optimización automática de Títulos SEO y Metadescripciones
 */
function getSeoOptimizedMeta(string $scriptName, array $context = []): array {
    $seoCfg       = getSeoConfig();
    $defaultTitle = !empty($seoCfg['seo_home_title']) ? $seoCfg['seo_home_title'] : "Moratalla (Murcia) | Guía Turística, Patrimonio Histórico y Cultura";
    $defaultDesc  = !empty($seoCfg['seo_home_desc']) ? $seoCfg['seo_home_desc'] : "Descubre Moratalla: patrimonio histórico, pinturas rupestres UNESCO, castillo medieval, turismo rural, Semana Santa, tambores y rutas naturales en el corazón de Murcia.";

    if ($scriptName === 'index.php') {
        return [
            'title'       => $defaultTitle,
            'description' => $defaultDesc,
            'schema_type' => 'TouristDestination'
        ];
    }

    if ($scriptName === 'restaurantes.php') {
        return [
            'title'       => "Dónde Comer en Moratalla | Bares, Restaurantes y Gastronomía Típica",
            'description' => "Guía completa sobre dónde comer en Moratalla y sus pedanías: bares, restaurantes, mesones tradicionales y platos típicos del Noroeste de Murcia.",
            'schema_type' => 'FoodEstablishment'
        ];
    }

    if ($scriptName === 'alojamientos.php') {
        return [
            'title'       => "Casas Rurales en Moratalla y Alojamientos | Turismo Rural en Murcia",
            'description' => "Reserva casas rurales en Moratalla, hoteles, hostales y campings en plena montaña de Murcia. El mejor descanso junto al río Alhárabe y Revolcadores.",
            'schema_type' => 'LodgingBusiness'
        ];
    }

    if ($scriptName === 'contacto.php') {
        return [
            'title'       => "Contacto y Colaboración | Archivo Histórico Digital de Moratalla",
            'description' => "Contacta con moratalla-murcia.com para aportar fotografías antiguas, documentos históricos, sugerencias turísticas o difundir eventos culturales de Moratalla.",
            'schema_type' => 'ContactPage'
        ];
    }

    if ($scriptName === 'category.php' && !empty($context['category_name'])) {
        $name = trim($context['category_name']);
        $lower = mb_strtolower($name, 'UTF-8');

        if (strpos($lower, 'ssta') !== false || strpos($lower, 'semana santa') !== false || strpos($lower, 'nazareno') !== false) {
            return [
                'title'       => "Semana Santa de Moratalla | Tambores, Nazarenos y Tradición",
                'description' => "Vive la Semana Santa de Moratalla: procesiones, cofradías, túnicas tradicionales y el estruendo de las tamboradas declaradas Patrimonio de la Humanidad.",
                'schema_type' => 'CollectionPage'
            ];
        }
        if (strpos($lower, 'tambor') !== false) {
            return [
                'title'       => "Tamboradas de Moratalla | Patrimonio Cultural Inmaterial UNESCO",
                'description' => "Historia, imágenes y tradición de los tamboristas de Moratalla. Conoce las tamboradas de Jueves Santo, Viernes Santo y Domingo de Resurrección.",
                'schema_type' => 'CollectionPage'
            ];
        }
        if (strpos($lower, 'fiestas') !== false || strpos($lower, 'stoxto') !== false || strpos($lower, 'cristo') !== false || strpos($lower, 'sananton') !== false) {
            return [
                'title'       => "Fiestas de Moratalla | Calendario Completo, Cristo del Rayo y Tradiciones",
                'description' => "Calendario completo de las Fiestas de Moratalla: Santísimo Cristo del Rayo y encierros por vereda en julio, San Antón, Semana Santa y romerías.",
                'schema_type' => 'CollectionPage'
            ];
        }
        if (strpos($lower, 'historia') !== false || strpos($lower, 'cultura') !== false) {
            return [
                'title'       => "{$name} de Moratalla | Patrimonio Histórico y Memoria Digital",
                'description' => "Explora la historia, cultura, música y literatura de Moratalla (Murcia). Archivo documental, fotografías históricas y legado cultural del municipio.",
                'schema_type' => 'CollectionPage'
            ];
        }
        if (strpos($lower, 'lugares') !== false || strpos($lower, 'turismo') !== false || strpos($lower, 'situacion') !== false) {
            return [
                'title'       => "Qué Ver en Moratalla ({$name}) | Naturaleza, Rutas y Patrimonio",
                'description' => "Lugares imprescindibles que ver en Moratalla: Castillo-Fortaleza, Río Alhárabe, Casa de Cristo, La Rogativa, Benizar y parajes naturales de Murcia.",
                'schema_type' => 'CollectionPage'
            ];
        }
        if (strpos($lower, 'gastronomia') !== false || strpos($lower, 'gastronomía') !== false) {
            return [
                'title'       => "Gastronomía de Moratalla | Recetas Tradicionales, Mazapán y Sabores",
                'description' => "Descubre la gastronomía tradicional de Moratalla: mazapán artesano, migas ruleras, arroz con conejo y caracoles, andrajos y dulces típicos.",
                'schema_type' => 'CollectionPage'
            ];
        }

        return [
            'title'       => "{$name} en Moratalla | Guía Cultural y Patrimonio Histórico",
            'description' => !empty($context['hint_text'])
                ? $context['hint_text']
                : "Descubre todo sobre {$name} en Moratalla (Murcia): historia, fotografías, recursos locales y archivo documental en moratalla-murcia.com.",
            'schema_type' => 'CollectionPage'
        ];
    }

    if ($scriptName === 'page.php' && !empty($context['page_title'])) {
        $pTitle = trim($context['page_title']);
        $catName = !empty($context['cat_name']) ? trim($context['cat_name']) : 'Patrimonio Histórico';
        $lower = mb_strtolower($pTitle . ' ' . $catName, 'UTF-8');

        if (strpos($lower, 'rupestre') !== false) {
            $seoTitle = "Pinturas Rupestres de Moratalla | Patrimonio UNESCO ({$pTitle})";
        } elseif (strpos($lower, 'castillo') !== false) {
            $seoTitle = "Castillo de Moratalla | Fortaleza Medieval y Torre del Homenaje";
        } elseif (strpos($lower, 'semana santa') !== false || strpos($lower, 'tambor') !== false) {
            $seoTitle = "{$pTitle} | Semana Santa y Tambores de Moratalla";
        } else {
            $seoTitle = "{$pTitle} | {$catName} en Moratalla (Murcia)";
        }

        $desc = !empty($context['page_description']) && mb_strlen($context['page_description']) > 45
            ? $context['page_description']
            : "Consulta {$pTitle} dentro de la sección {$catName} de Moratalla (Murcia). Archivo histórico digital, fotografías y cultura tradicional.";

        return [
            'title'       => $seoTitle,
            'description' => $desc,
            'schema_type' => 'Article'
        ];
    }

    return [
        'title'       => $defaultTitle,
        'description' => $defaultDesc,
        'schema_type' => 'WebSite'
    ];
}

/**
 * Contenido Estacional ("Moratalla las 4 Estaciones")
 */
function getSeoSeasonalPlans(): array {
    return [
        [
            'id'       => 'invierno-primavera',
            'period'   => 'Enero – Marzo',
            'season'   => 'Invierno y Cuaresma',
            'title'    => 'San Antón, Cumbres Nevadas y Semana Santa',
            'icon'     => 'fas fa-drum',
            'badge'    => 'Tradición y Tambor',
            'color'    => '#880e4f',
            'bg'       => '#fce4ec',
            'summary'  => 'Vive las hogueras de San Antón en enero, contempla la nieve en el Macizo de Revolcadores (2.015 m) y siente el retumbar de los tambores en Cuaresma y Semana Santa.',
            'highlights' => [
                'Hogueras y bendición de animales en San Antón (17 de enero)',
                'Ascensión invernal a Los Odres y Revolcadores, techo de la Región de Murcia',
                'Tamboradas de Jueves Santo, Viernes Santo y Domingo de Resurrección (Patrimonio UNESCO)',
                'Degustación de migas ruleras, gachasmigas y dulces de sartén junto a la chimenea'
            ],
            'cta_text' => 'Ver Guía de Semana Santa y Tambores',
            'cta_url'  => 'guia.php?slug=semana-santa-de-moratalla'
        ],
        [
            'id'       => 'primavera',
            'period'   => 'Abril – Junio',
            'season'   => 'Primavera y Naturaleza',
            'title'    => 'Senderismo, Cascadas, Romerías y Lavanda',
            'icon'     => 'fas fa-hiking',
            'badge'    => 'Rutas y Aire Puro',
            'color'    => '#1b4332',
            'bg'       => '#e8f5e9',
            'summary'  => 'La mejor época para recorrer el Estrecho de Bolvonegro, el Río Alhárabe, visitar las pinturas rupestres y disfrutar de la Romería de la Rogativa y la floración.',
            'highlights' => [
                'Ruta del Estrecho de Bolvonegro y gargantas del río Alhárabe y Benámor',
                'Romería de la Virgen de la Rogativa en mayo y Fiestas de San Jorge (23 de abril)',
                'Visitas guiadas a los abrigos de Arte Rupestre Levantino (Cañaica del Calar)',
                'Floración de campos aromáticos y escapadas en casas rurales con encanto'
            ],
            'cta_text' => 'Explorar Rutas de Senderismo',
            'cta_url'  => 'guia.php?slug=rutas-de-senderismo-en-moratalla'
        ],
        [
            'id'       => 'verano',
            'period'   => 'Julio – Agosto',
            'season'   => 'Verano y Fiestas Mayores',
            'title'    => 'Santísimo Cristo del Rayo, Poza de Somogil y Noches Frescas',
            'icon'     => 'fas fa-sun',
            'badge'    => 'Fiestas y Ríos',
            'color'    => '#e65100',
            'bg'       => '#fff3e0',
            'summary'  => 'En julio Moratalla vibra con las Fiestas del Santísimo Cristo del Rayo y sus históricos encierros por vereda. En agosto, los pinares y pozas naturales ofrecen el refugio climático perfecto.',
            'highlights' => [
                'Fiestas del Santísimo Cristo del Rayo (11 al 17 de julio) y encierros centenarios por vereda',
                'Baño en aguas termales y cristalinas de la Poza de Somogil y el río Alhárabe',
                'Noches de verano al fresco en el Camping La Puerta, Casa de Cristo y pedanías altas',
                'Observación astronómica (cielos limpios en Campo de San Juan y Calar de la Santa)'
            ],
            'cta_text' => 'Ver Calendario de Fiestas Populares',
            'cta_url'  => 'guia.php?slug=fiestas-populares-de-moratalla'
        ],
        [
            'id'       => 'otono',
            'period'   => 'Septiembre – Diciembre',
            'season'   => 'Otoño y Gastronomía',
            'title'    => 'Bosques de Otoño, Micología, Historia y Mazapán Artesano',
            'icon'     => 'fas fa-leaf',
            'badge'    => 'Patrimonio y Sabor',
            'color'    => '#1565c0',
            'bg'       => '#e3f2fd',
            'summary'  => 'Los bosques de ribera se tiñen de ocre, comienza la temporada de níscalos (guíscanos) y el horno tradicional perfuma el pueblo con el célebre mazapán de Moratalla.',
            'highlights' => [
                'Recolección sostenible de setas (guíscanos) en los extensos pinares de Moratalla',
                'Ruta cultural por el Castillo-Fortaleza, Iglesia de la Asunción y casco histórico',
                'Elaboración artesanal del tradicional Mazapán de Moratalla relleno de cabello de ángel y yema',
                'Puentes de otoño e invierno en alojamientos rurales con chimenea'
            ],
            'cta_text' => 'Descubrir Gastronomía Típica',
            'cta_url'  => 'guia.php?slug=gastronomia-de-moratalla'
        ]
    ];
}

/**
 * Devuelve las guías SEO desde la base de datos (tabla seo_guides), permitiendo su modificación,
 * creación y borrado lógico (activo = 0) desde el Panel de Administración.
 */
function getSeo90Pages(bool $includeInactive = false, ?PDO $pdo = null): array {
    if (!$pdo) {
        $pdo = (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) ? $GLOBALS['pdo'] : (function_exists('getDB') ? getDB() : null);
    }

    if ($pdo) {
        try {
            ensureSeoGuidesTable($pdo);
            $sql = "SELECT * FROM seo_guides" . ($includeInactive ? "" : " WHERE activo = 1") . " ORDER BY (type = 'pilar') DESC, priority_rank ASC, id ASC";
            $stmt = $pdo->query($sql);
            if ($stmt) {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($rows) || $includeInactive) {
                    $pages = [];
                    foreach ($rows as $r) {
                        $slug = $r['slug'];
                        $pages[$slug] = [
                            'id'               => (int)$r['id'],
                            'slug'             => $slug,
                            'type'             => $r['type'] ?: 'articulo',
                            'phase'            => $r['phase'] ?? '',
                            'priority_rank'    => (int)($r['priority_rank'] ?? 99),
                            'short_title'      => $r['short_title'] ?? '',
                            'title'            => $r['title'] ?? '',
                            'h1'               => $r['h1'] ?? '',
                            'subtitle'         => $r['subtitle'] ?? '',
                            'meta_description' => $r['meta_description'] ?? '',
                            'keywords'         => $r['keywords'] ?? '',
                            'icon'             => $r['icon'] ?: 'fas fa-compass',
                            'hero_image'       => $r['hero_image'] ?: 'uploads/seo/pilar-guia-completa.svg',
                            'logo_image'       => $r['logo_image'] ?: ($r['hero_image'] ?: 'uploads/seo/pilar-guia-completa.svg'),
                            'reading_time'     => $r['reading_time'] ?: '8 min de lectura',
                            'map_query'        => $r['map_query'] ?: 'Moratalla, Murcia',
                            'quick_facts'      => json_decode($r['quick_facts_json'] ?: '[]', true) ?: [],
                            'sections'         => json_decode($r['sections_json'] ?: '[]', true) ?: [],
                            'faqs'             => json_decode($r['faqs_json'] ?: '[]', true) ?: [],
                            'related_cards'    => json_decode($r['related_cards_json'] ?: '[]', true) ?: [],
                            'related_slugs'    => json_decode($r['related_slugs_json'] ?: '[]', true) ?: [],
                            'activo'           => (int)$r['activo'],
                            'updated_at'       => $r['updated_at'] ?? ''
                        ];
                    }
                    return $pages;
                }
            }
        } catch (\Throwable $e) {
            // Fallback al catálogo por defecto si no hay conexión a BD
        }
    }

    return getDefaultSeo90Pages();
}

/**
 * Catálogo base por defecto de las 5 Páginas Pilar + Guías Temáticas
 */
function getDefaultSeo90Pages(): array {
    return [
        // =========================================================================
        // LAS 5 PÁGINAS PILAR PRINCIPALES
        // =========================================================================
        'guia-completa-moratalla' => [
            'slug'             => 'guia-completa-moratalla',
            'type'             => 'pilar',
            'phase'            => 'Guía Turística Principal',
            'priority_rank'    => 1,
            'short_title'      => 'Guía Completa de Moratalla',
            'title'            => 'Qué Ver en Moratalla | Guía Completa de Turismo y Patrimonio Histórico',
            'h1'               => 'Guía Completa de Moratalla: Qué Ver, Qué Hacer y Cómo Planificar tu Visita',
            'subtitle'         => 'Todo lo que necesitas saber para descubrir el municipio con mayor riqueza forestal, histórica y rupestre de la Región de Murcia.',
            'meta_description' => '¿Buscas qué ver en Moratalla? Guía turística completa: Castillo-Fortaleza, Iglesia de la Asunción, pinturas rupestres UNESCO, río Alhárabe, dónde comer y dormir.',
            'keywords'         => 'Moratalla, qué ver en Moratalla, turismo en Moratalla, Moratalla Murcia, guía de Moratalla, qué hacer en Moratalla',
            'icon'             => 'fas fa-compass',
            'hero_image'       => 'uploads/seo/pilar-guia-completa.svg',
            'logo_image'       => 'uploads/seo/pilar-guia-completa.svg',
            'reading_time'     => '12 min de lectura',
            'word_target'      => 'Guía Principal',
            'map_query'        => 'Moratalla, Murcia, España',
            'related_cards'    => ['castillo', 'asuncion', 'casa_cristo', 'las_arcas', 'semana_santa_ficha', 'calendario_fiestas', 'historia_ludena', 'gastronomia_ficha'],
            'quick_facts'      => [
                ['label' => 'Comarca', 'value' => 'Noroeste de la Región de Murcia'],
                ['label' => 'Altitud Casco Urbano', 'value' => '681 m s. n. m. (Pico máx: 2.015 m en Los Odres)'],
                ['label' => 'Patrimonio UNESCO', 'value' => 'Arte Rupestre del Arco Mediterráneo y Tamboradas'],
                ['label' => 'Tarjetas con Galería', 'value' => 'Castillo (28 fotos), La Asunción (29 fotos), Las Arcas (21 fotos), Semana Santa (87 fotos)']
            ],
            'sections' => [
                [
                    'id'      => 'introduccion',
                    'title'   => '1. Por qué Moratalla es la joya histórica y natural del Noroeste de Murcia',
                    'content' => '<p>Situada en el corazón de la comarca del Noroeste murciano, <strong>Moratalla</strong> es uno de los destinos más fascinantes del sureste ibérico. Su término municipal —de casi 1.000 km², uno de los más extensos de España— combina un imponente <strong>conjunto histórico-artístico medieval</strong> coronado por su <a href="page.php?id=433"><strong>Castillo fortaleza de Moratalla</strong></a> con las mayores masas forestales de la Región de Murcia, ríos de aguas cristalinas como el <strong>Alhárabe y el Benámor</strong>, y las cumbres más altas de la comunidad autónoma en el <strong>Macizo de Revolcadores (2.015 m)</strong>.</p>
                    <p>Además, Moratalla ostenta un doble reconocimiento como <strong>Patrimonio Mundial por la UNESCO</strong>: por un lado, concentra más de la mitad de los abrigos con <a href="page.php?id=451"><strong>Arte Rupestre prehistórico</strong></a> de toda la Región de Murcia; por otro, sus vibrantes <a href="page.php?id=458"><strong>Tamboradas de Semana Santa</strong></a> forman parte del Patrimonio Cultural Inmaterial de la Humanidad.</p>'
                ],
                [
                    'id'      => 'imprescindibles',
                    'title'   => '2. Los 8 lugares imprescindibles que ver en Moratalla (con enlace a sus tarjetas y galerías)',
                    'content' => '<p>Si visitas Moratalla por primera vez, estos son los enclaves fundamentales que debes incluir en tu recorrido, enlazados directamente con las tarjetas descriptivas y fotográficas de nuestro archivo:</p>
                    <ul>
                        <li><strong><a href="page.php?id=433">Castillo fortaleza de Moratalla</a> y Torre del Homenaje:</strong> Fortaleza de origen islámico reconstruida en los siglos XV y XVI por la Orden de Santiago. Consulta su <a href="page.php?id=433">tarjeta patrimonial con descripción y 28 fotografías</a>.</li>
                        <li><strong><a href="page.php?id=440">Parroquia de Santa María de la Asunción</a>:</strong> Monumento declarado Bien de Interés Cultural (siglo XVI), célebre por sus columnas renacentistas. Ver <a href="page.php?id=440">ficha oficial y galería de 29 fotografías</a>.</li>
                        <li><strong><a href="page.php?id=435">Convento de San Francisco</a> y <a href="page.php?id=434">Ermita de Santa Ana</a>:</strong> Testigos del esplendor barroco y de la devoción tradicional en el casco antiguo.</li>
                        <li><strong><a href="page.php?id=441">Santuario de la Casa de Cristo</a> y Centro de Arte Rupestre:</strong> Antiguo monasterio mercedario en pleno monte público (<a href="page.php?id=441">ver tarjeta y 15 fotografías</a>).</li>
                        <li><strong><a href="page.php?id=451">Abrigos de Arte Rupestre</a> (Cañaica del Calar y Fuente del Sabuco):</strong> Santuarios prehistóricos con escenas de caza de hace más de 6.000 años (<a href="page.php?id=451">ver ficha de Arte Rupestre</a>).</li>
                        <li><strong><a href="page.php?id=446">Acueducto de Las Arcas</a> y <a href="page.php?id=445">Puente de Hellín</a>:</strong> Obras históricas de ingeniería hidráulica (<a href="page.php?id=446">ver las 21 fotografías de Las Arcas</a>).</li>
                        <li><strong><a href="page.php?id=430">Museo del Tambor</a> y <a href="page.php?id=453">Semana Santa de Moratalla</a>:</strong> Descubre las túnicas artesanales y los tambores moratalleros (<a href="page.php?id=430">15 fotos del Museo</a> y <a href="page.php?id=453">87 fotos de Semana Santa</a>).</li>
                        <li><strong>Pedanías altas y <a href="page.php?id=163">Calendario de Fiestas</a>:</strong> Benizar, Otos, Mazuza, Campo de San Juan, <a href="page.php?id=443">El Sabinar</a>, <a href="page.php?id=442">Calar de la Santa</a>, <a href="page.php?id=444">Cañada de la Cruz</a> y La Rogativa.</li>
                    </ul>'
                ],
                [
                    'id'      => 'como-organizar',
                    'title'   => '3. Cómo organizar tu visita a Moratalla paso a paso',
                    'content' => '<p>Para aprovechar al máximo tu estancia en Moratalla te recomendamos dividir la experiencia en tres ejes complementarios:</p>
                    <p><strong>Mañana de Patrimonio e Historia:</strong> Comienza en la Plaza de la Iglesia visitando la <a href="page.php?id=440">Parroquia de Santa María de la Asunción</a> y sube hacia el <a href="page.php?id=433">Castillo fortaleza de Moratalla</a> recorriendo la <a href="guia.php?slug=ruta-casco-antiguo-moratalla">ruta por el casco antiguo</a> pasando por la <a href="page.php?id=434">Ermita de Santa Ana</a> y el <a href="page.php?id=435">Convento de San Francisco</a>.</p>
                    <p><strong>Mediodía Gastronómico:</strong> Haz una parada en los <a href="restaurantes.php">bares y restaurantes de Moratalla</a> para degustar las especialidades recogidas en nuestras tarjetas de <a href="page.php?id=263">Gastronomía Moratallera</a> y <a href="page.php?id=428">Recetas de Moratalla</a>.</p>
                    <p><strong>Tarde de Naturaleza y Arte Rupestre:</strong> A solo 10 minutos en coche del casco urbano, sube al <a href="page.php?id=441">Santuario de la Casa de Cristo</a> o realiza un paseo junto al río Alhárabe cruzando el histórico <a href="page.php?id=445">Puente de Hellín</a> hacia el Estrecho de Bolvonegro.</p>'
                ],
                [
                    'id'      => 'alojamiento-y-servicios',
                    'title'   => '4. Dónde dormir y dónde comer en Moratalla',
                    'content' => '<p>Moratalla es pionera del <a href="guia.php?slug=turismo-rural-moratalla">turismo rural en la Región de Murcia</a>. En nuestro directorio actualizado encontrarás más de 80 opciones en <a href="alojamientos.php">Alojamientos y Casas Rurales en Moratalla</a> (complementado por la ficha histórica de <a href="page.php?id=278">Casas Rurales</a>) y el listado completo de <a href="restaurantes.php">Bares y Restaurantes</a> con teléfonos y ubicación en mapa.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Cuántos días se necesitan para ver Moratalla?',
                    'a' => 'En 1 día puedes conocer el Castillo-Fortaleza (page.php?id=433), la Parroquia de la Asunción (page.php?id=440), el casco antiguo y el Santuario de Casa de Cristo (page.php?id=441). Lo ideal es dedicar un fin de semana completo (2 o 3 días) para incluir el río Alhárabe, Bolvonegro y las pinturas rupestres.'
                ],
                [
                    'q' => '¿Dónde puedo ver fotografías reales de los monumentos de Moratalla?',
                    'a' => 'En cada guía encontrarás las tarjetas oficiales del archivo de moratalla-murcia.com, como el Castillo fortaleza (28 fotos), la Parroquia de la Asunción (29 fotos), Las Arcas (21 fotos), Casa de Cristo (15 fotos) o Semana Santa (87 fotos).'
                ],
                [
                    'q' => '¿Se puede visitar el Castillo de Moratalla por dentro?',
                    'a' => 'Sí, el recinto amurallado y la Torre del Homenaje del Castillo-Fortaleza de Moratalla son visitables. Puedes ver un adelanto completo en nuestra tarjeta del Castillo con 28 imágenes de su exterior e interior.'
                ]
            ],
            'related_slugs' => [
                '10-lugares-imprescindibles-que-ver-en-moratalla',
                'turismo-rural-moratalla',
                'historia-de-moratalla',
                'monumentos-y-patrimonio-moratalla',
                'gastronomia-de-moratalla'
            ]
        ],

        'turismo-rural-moratalla' => [
            'slug'             => 'turismo-rural-moratalla',
            'type'             => 'pilar',
            'phase'            => 'Naturaleza y Alojamiento Rural',
            'priority_rank'    => 2,
            'short_title'      => 'Turismo Rural en Moratalla',
            'title'            => 'Turismo Rural en Moratalla | Casas Rurales, Naturaleza y Escapadas en Murcia',
            'h1'               => 'Turismo Rural en Moratalla: Naturaleza, Casas Rurales y Montaña en Murcia',
            'subtitle'         => 'El pulmón verde de la Región de Murcia: pinares infinitos, ríos de montaña, aldeas con encanto y alojamientos rurales para desconectar.',
            'meta_description' => 'Guía de turismo rural en Moratalla (Murcia): mejores zonas donde alojarse en casas rurales, ríos Alhárabe y Benámor, Revolcadores, pedanías y naturaleza.',
            'keywords'         => 'turismo rural en Moratalla, casas rurales Moratalla, alojamientos Moratalla, naturaleza Moratalla, escapada rural Murcia',
            'icon'             => 'fas fa-tree',
            'hero_image'       => 'uploads/seo/pilar-turismo-rural.svg',
            'logo_image'       => 'uploads/seo/pilar-turismo-rural.svg',
            'reading_time'     => '10 min de lectura',
            'word_target'      => 'Guía Turismo Rural',
            'map_query'        => 'Río Alhárabe Moratalla, Murcia',
            'related_cards'    => ['alojamientos_ficha', 'casa_cristo', 'puente_hellin', 'las_arcas', 'situacion_rutas_filippedes', 'calendario_fiestas'],
            'quick_facts'      => [
                ['label' => 'Superficie Forestal', 'value' => 'Más de 55.000 hectáreas de montes y pinares'],
                ['label' => 'Cota Máxima', 'value' => 'Los Odres (1.878 m) y Revolcadores (2.015 m)'],
                ['label' => 'Ríos y Parajes', 'value' => 'Río Alhárabe, Puente de Hellín, Las Arcas y Casa de Cristo'],
                ['label' => 'Directorio Local', 'value' => 'Acceso directo a Casas Rurales, Camping y Hostales']
            ],
            'sections' => [
                [
                    'id'      => 'capital-turismo-rural',
                    'title'   => '1. Moratalla, capital del turismo rural y de interior en la Región de Murcia',
                    'content' => '<p>Al noroeste de la Región de Murcia se alza un paisaje montañoso y verde: <strong>Moratalla</strong>, conocida por su relieve escarpado, sus valles surcados por el agua y sus extensos bosques de pino carrasco, pino laricio, encinas y sabinas albares, documentados en nuestra tarjeta de <a href="page.php?id=279"><strong>Situación geográfica de Moratalla</strong></a>.</p>
                    <p>El <strong>turismo rural en Moratalla</strong> se desarrolló de forma pionera rehabilitando antiguos cortijos, molinos y casas de labranza que puedes consultar tanto en el <a href="alojamientos.php"><strong>Directorio Interactivo de Alojamientos</strong></a> como en la tarjeta tradicional de <a href="page.php?id=278"><strong>Casas Rurales de Moratalla</strong></a>.</p>'
                ],
                [
                    'id'      => 'mejores-zonas-alojamiento',
                    'title'   => '2. Mejores zonas donde alojarse en una casa rural en Moratalla',
                    'content' => '<p>Dependiendo del tipo de escapada que busques —en pareja, con niños o con amigos—, Moratalla ofrece distintas áreas naturales con patrimonio propio:</p>
                    <ul>
                        <li><strong>Entorno del Río Alhárabe, <a href="page.php?id=445">Puente de Hellín</a> y La Puerta:</strong> Ideal para familias y amantes del senderismo fluvial, cerca del Estrecho de Bolvonegro y del acueducto histórico de <a href="page.php?id=446"><strong>Las Arcas (ver 21 fotos)</strong></a>.</li>
                        <li><strong><a href="page.php?id=441">Santuario de la Casa de Cristo</a> y Sierra del Buitre:</strong> En pleno monte público, rodeado de pinares centenarios y miradores panorámicos (<a href="page.php?id=441">ver tarjeta y 15 fotos de Casa de Cristo</a>).</li>
                        <li><strong>Pedanías altas (<a href="page.php?id=443">El Sabinar</a>, <a href="page.php?id=442">Calar de la Santa</a>, <a href="page.php?id=444">Cañada de la Cruz</a> y Campo de San Juan):</strong> A más de 1.100 metros de altitud, perfectas para ver la nieve en invierno y disfrutar de noches frescas en verano.</li>
                        <li><strong>Benizar, Otos y Mazuza:</strong> Riqueza hídrica, nacimientos de agua, cascadas de travertino y el histórico Castillo de Benizar.</li>
                        <li><strong>Casco Histórico de Moratalla:</strong> Junto al <a href="page.php?id=433">Castillo fortaleza</a> y la <a href="page.php?id=440">Parroquia de la Asunción</a>.</li>
                    </ul>'
                ],
                [
                    'id'      => 'actividades-naturaleza',
                    'title'   => '3. Experiencias, senderismo y deporte en la naturaleza',
                    'content' => '<p>El entorno natural de Moratalla permite practicar <a href="guia.php?slug=rutas-de-senderismo-en-moratalla">senderismo por gargantas y cumbres</a>, rutas impulsadas por entidades locales como el <a href="page.php?id=452"><strong>Club Deportivo Filippedes Moratalla</strong></a> y el <a href="page.php?id=427"><strong>Club Ciclista de Moratalla</strong></a>, observación de aves rapaces, astroturismo y rutas micológicas en otoño.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde buscar casas rurales en Moratalla con contacto directo?',
                    'a' => 'En la sección Alojamientos (alojamientos.php) y en la ficha de Casas Rurales (page.php?id=278) dispones de acceso directo con teléfonos, correo electrónico, web y ubicación en mapa de cada alojamiento rural.'
                ],
                [
                    'q' => '¿Es Moratalla un buen destino de turismo rural con niños?',
                    'a' => 'Excelente. Paseos como el río Alhárabe junto a La Puerta y el Puente de Hellín (page.php?id=445), la explanada del Santuario de Casa de Cristo (page.php?id=441) y el Castillo de Moratalla (page.php?id=433) son planes ideales para toda la familia.'
                ]
            ],
            'related_slugs' => [
                'rutas-de-senderismo-en-moratalla',
                'que-hacer-en-moratalla-fin-de-semana',
                'guia-completa-moratalla',
                'gastronomia-de-moratalla'
            ]
        ],

        'historia-de-moratalla' => [
            'slug'             => 'historia-de-moratalla',
            'type'             => 'pilar',
            'phase'            => 'Memoria y Archivo Histórico',
            'priority_rank'    => 3,
            'short_title'      => 'Historia de Moratalla',
            'title'            => 'Historia de Moratalla (Murcia) | De la Prehistoria a la Orden de Santiago',
            'h1'               => 'Historia de Moratalla: Milenios de Memoria desde el Arte Rupestre hasta Nuestros Días',
            'subtitle'         => 'Un recorrido riguroso por las raíces prehistóricas, íberas, romanas, andalusíes y santiaguistas que forjaron la identidad de Moratalla.',
            'meta_description' => 'Conoce la historia de Moratalla (Murcia): asentamientos prehistóricos y pinturas rupestres, pasado íbero y romano, fortaleza islámica y Encomienda de la Orden de Santiago.',
            'keywords'         => 'historia de Moratalla, historia de Moratalla Murcia, Orden de Santiago Moratalla, origen de Moratalla, patrimonio histórico Moratalla',
            'icon'             => 'fas fa-landmark',
            'hero_image'       => 'uploads/seo/pilar-historia.svg',
            'logo_image'       => 'uploads/seo/pilar-historia.svg',
            'reading_time'     => '11 min de lectura',
            'word_target'      => 'Guía de Historia',
            'map_query'        => 'Castillo de Moratalla, Murcia',
            'related_cards'    => ['historia_ludena', 'arte_rupestre', 'castillo', 'asuncion', 'cristo_del_rayo', 'banda_musica_cultura'],
            'quick_facts'      => [
                ['label' => 'Primeros Pobladores', 'value' => 'Paleolítico Superior, Epipaleolítico y Neolítico'],
                ['label' => 'Hito Prehistórico', 'value' => 'Más de 60 abrigos con Arte Rupestre Levantino y Esquemático'],
                ['label' => 'Edad Media', 'value' => 'Hisn islámico (Murata) y Encomienda de la Orden de Santiago (1245)'],
                ['label' => 'Archivo en la Web', 'value' => 'Historia de José Ludeña (id=290), Bibliografía (id=296) y Patrimonio (cat=194)']
            ],
            'sections' => [
                [
                    'id'      => 'prehistoria-arte-rupestre',
                    'title'   => '1. Prehistoria: El gran santuario del Arte Rupestre en Murcia',
                    'content' => '<p>La presencia humana en las tierras de Moratalla se remonta a hace más de 10.000 años. Fruto de aquellas sociedades cazadoras-recolectoras y de los primeros pastores neolíticos son los abrigos recogidos en nuestra tarjeta de <a href="page.php?id=451"><strong>Arte Rupestre de Moratalla</strong></a> y explicados en el <a href="page.php?id=441"><strong>Santuario de la Casa de Cristo</strong></a>, declarados Patrimonio Mundial por la UNESCO en 1998.</p>
                    <p>Para profundizar en los estudios históricos y documentales del municipio, consulta directamente la tarjeta de <a href="page.php?id=290"><strong>Historia de Moratalla (por José Ludeña López)</strong></a> y la recopilación de <a href="page.php?id=296"><strong>Bibliografía sobre Moratalla</strong></a>.</p>'
                ],
                [
                    'id'      => 'iberos-romanos-andalusies',
                    'title'   => '2. Íberos, Romanos y la Murata andalusí',
                    'content' => '<p>Durante la época íbera y la posterior romanización, el territorio de Moratalla articuló villas agrícolas vinculadas al esparto, el cereal y el aceite, una tradición artesanal que ha llegado hasta nuestros días como muestra la ficha de <a href="page.php?id=306"><strong>Artesanía del Esparto de Bartolomé Marín "El Granados"</strong></a>. En época islámica (siglos IX-XIII), se consolidó el emplazamiento defensivo sobre el cerro del castillo y el de Benizar.</p>'
                ],
                [
                    'id'      => 'orden-de-santiago',
                    'title'   => '3. La Reconquista y la Encomienda de la Orden de Santiago',
                    'content' => '<p>Tras el Tratado de Alcaraz (1243), Alfonso X el Sabio entregó Moratalla a la <strong>Orden Militar de Santiago</strong>, convirtiéndose en sede de una importante Encomienda fronteriza. En el siglo XV, los comendadores santiaguistas levantaron la monumental Torre del Homenaje que puedes admirar en la tarjeta del <a href="page.php?id=433"><strong>Castillo fortaleza de Moratalla (con 28 fotografías)</strong></a>.</p>'
                ],
                [
                    'id'      => 'edad-moderna-y-contemporanea',
                    'title'   => '4. Renacimiento, el milagro del Cristo del Rayo y la memoria cultural',
                    'content' => '<p>En los siglos XVI y XVII se edificó la monumental <a href="page.php?id=440"><strong>Parroquia de Santa María de la Asunción (29 fotografías)</strong></a>, el <a href="page.php?id=435"><strong>Convento de San Francisco</strong></a>, la <a href="page.php?id=434"><strong>Ermita de Santa Ana</strong></a> y las conducciones hidráulicas de <a href="page.php?id=446"><strong>Las Arcas (21 fotografías)</strong></a>. El 15 de junio de 1621 tuvo lugar el milagro del <a href="category.php?id=45"><strong>Santísimo Cristo del Rayo</strong></a>, origen de las fiestas patronales.</p>
                    <p>Explora también la memoria musical y artística en las tarjetas de la <a href="page.php?id=294"><strong>Banda de Música de Moratalla</strong></a>, la <a href="page.php?id=295"><strong>Rondalla Trieta</strong></a>, la pintura de <a href="page.php?id=429"><strong>Manolo Martínez "Cancarín"</strong></a> y la seda de <a href="page.php?id=305"><strong>Ana María Almagro</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde consultar los textos históricos y bibliografía de Moratalla en esta web?',
                    'a' => 'Puedes acceder directamente a la tarjeta "Historia de Moratalla" de José Ludeña López (page.php?id=290), a la "Bibliografía de Moratalla" (page.php?id=296) y a la categoría completa de Patrimonio (category.php?id=194).'
                ],
                [
                    'q' => '¿Qué ocurrió en Moratalla el 15 de junio de 1621?',
                    'a' => 'Durante la octava del Corpus Christi en la Parroquia de Santa María de la Asunción (page.php?id=440), un rayo impactó en la imagen del crucificado con el templo lleno sin causar víctimas, dando origen a la devoción al Santísimo Cristo del Rayo (category.php?id=45).'
                ]
            ],
            'related_slugs' => [
                'castillo-de-moratalla',
                'pinturas-rupestres-de-moratalla',
                'monumentos-y-patrimonio-moratalla',
                'guia-completa-moratalla'
            ]
        ],

        'monumentos-y-patrimonio-moratalla' => [
            'slug'             => 'monumentos-y-patrimonio-moratalla',
            'type'             => 'pilar',
            'phase'            => 'Patrimonio Monumental',
            'priority_rank'    => 4,
            'short_title'      => 'Monumentos y Patrimonio',
            'title'            => 'Monumentos y Patrimonio de Moratalla | Castillo, Iglesias y Santuarios',
            'h1'               => 'Monumentos y Patrimonio Histórico-Artístico de Moratalla',
            'subtitle'         => 'Guía arquitectónica conectada con todas las fichas y galerías fotográficas del Patrimonio de Moratalla.',
            'meta_description' => 'Descubre los monumentos de Moratalla y accede a sus fichas con fotos: Castillo-Fortaleza, Parroquia de la Asunción, Casa de Cristo, Santa Ana, San Francisco y Las Arcas.',
            'keywords'         => 'monumentos de Moratalla, patrimonio de Moratalla, Castillo de Moratalla, Iglesia de Santa María de la Asunción Moratalla, Casa de Cristo Moratalla',
            'icon'             => 'fas fa-chess-rook',
            'hero_image'       => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'logo_image'       => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'reading_time'     => '10 min de lectura',
            'word_target'      => 'Guía de Patrimonio',
            'map_query'        => 'Iglesia de Nuestra Señora de la Asunción, Moratalla',
            'related_cards'    => ['castillo', 'asuncion', 'casa_cristo', 'las_arcas', 'santa_ana', 'san_francisco', 'puente_hellin', 'arte_rupestre'],
            'quick_facts'      => [
                ['label' => 'Categoría Oficial', 'value' => '8 fichas patrimoniales en Patrimonio (category.php?id=194)'],
                ['label' => 'Fortalezas', 'value' => 'Castillo fortaleza de Moratalla (28 fotos) y Castillo de Benizar'],
                ['label' => 'Templos y Santuarios', 'value' => 'La Asunción (29 fotos), Casa de Cristo (15 fotos), Santa Ana y San Francisco'],
                ['label' => 'Obras Civiles y Rupestres', 'value' => 'Las Arcas (21 fotos), Puente de Hellín y Arte Rupestre']
            ],
            'sections' => [
                [
                    'id'      => 'castillo-fortaleza',
                    'title'   => '1. El Castillo-Fortaleza de Moratalla y su Torre del Homenaje',
                    'content' => '<p>Coronando el cerro sobre el que se escalona la villa, el <a href="page.php?id=433"><strong>Castillo fortaleza de Moratalla</strong></a> es el emblema patrimonial del municipio. Cuenta con seis torres (la del Homenaje, la Blanca, la del Alcaide, la de los Cuatro Vientos, la Redonda y la de la Magdalena), un gran patio de armas con aljibe y la célebre <strong>Torre del Homenaje</strong> del siglo XV.</p>
                    <p><i class="fas fa-images" style="color:var(--primary);"></i> <strong>Referencia en la web:</strong> Accede a la <a href="page.php?id=433"><strong>Tarjeta oficial del Castillo fortaleza de Moratalla</strong></a> para leer su descripción patrimonial completa y ver su galería de <strong>28 fotografías</strong>.</p>'
                ],
                [
                    'id'      => 'iglesia-asuncion',
                    'title'   => '2. Parroquia de Santa María de la Asunción, Santa Ana y San Francisco',
                    'content' => '<p>Declarada Monumento Histórico-Artístico Nacional en 1981, la <a href="page.php?id=440"><strong>Parroquia de Santa María de la Asunción</strong></a> comenzó a edificarse en 1521 bajo traza renacentista. Su interior sorprende por su planta de salón sostenida por monumentales columnas y alberga la imagen del Santísimo Cristo del Rayo (<a href="page.php?id=440">ver tarjeta y 29 fotografías</a>).</p>
                    <p>En el recorrido urbano destacan asimismo dos templos históricos con ficha propia en nuestra sección de <a href="category.php?id=194">Patrimonio</a>: la <a href="page.php?id=434"><strong>Ermita de Santa Ana</strong></a> y el antiguo <a href="page.php?id=435"><strong>Convento de San Francisco</strong></a>.</p>'
                ],
                [
                    'id'      => 'santuarios-ingenieria',
                    'title'   => '3. Santuario de la Casa de Cristo, Las Arcas, Puente de Hellín y Arte Rupestre',
                    'content' => '<ul>
                        <li><strong><a href="page.php?id=441">Santuario de la Casa de Cristo</a>:</strong> Enclavado a más de 800 metros de altitud entre pinares, conserva su claustro de arcos y acoge el Centro de Interpretación del Arte Rupestre (<a href="page.php?id=441">ver ficha con 15 fotografías</a>).</li>
                        <li><strong><a href="page.php?id=446">Las Arcas</a>:</strong> Monumental obra histórica de ingeniería hidráulica que conducía el agua salvando los desniveles del terreno (<a href="page.php?id=446">ver ficha con 21 fotografías</a>).</li>
                        <li><strong><a href="page.php?id=445">Puente de Hellín</a>:</strong> Histórico puente sobre el río Alhárabe que comunicaba Moratalla con las tierras manchegas (<a href="page.php?id=445">ver tarjeta patrimonial</a>).</li>
                        <li><strong><a href="page.php?id=451">Arte Rupestre</a>:</strong> El conjunto de abrigos prehistóricos declarados Patrimonio de la Humanidad (<a href="page.php?id=451">ver tarjeta de Arte Rupestre</a>).</li>
                    </ul>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde están reunidas todas las tarjetas de patrimonio de Moratalla?',
                    'a' => 'Tanto en las tarjetas visuales de esta misma página como en la categoría Patrimonio (category.php?id=194), donde tienes acceso directo al Castillo (id=433), Santa María de la Asunción (id=440), Santa Ana (id=434), San Francisco (id=435), Casa de Cristo (id=441), Puente de Hellín (id=445), Las Arcas (id=446) y Arte Rupestre (id=451).'
                ]
            ],
            'related_slugs' => [
                'castillo-de-moratalla',
                'ruta-casco-antiguo-moratalla',
                'pinturas-rupestres-de-moratalla',
                'historia-de-moratalla'
            ]
        ],

        'gastronomia-de-moratalla' => [
            'slug'             => 'gastronomia-de-moratalla',
            'type'             => 'pilar',
            'phase'            => 'Tradición Culinaria',
            'priority_rank'    => 5,
            'short_title'      => 'Gastronomía de Moratalla',
            'title'            => 'Gastronomía de Moratalla | Platos Típicos, Mazapán y Dónde Comer',
            'h1'               => 'Gastronomía de Moratalla: Sabores de Sierra, Recetas Tradicionales y Mazapán Artesano',
            'subtitle'         => 'Descubre la cocina serrana de Moratalla: guisos de cuchara, arroces a la leña, embutidos caseros y el inconfundible mazapán moratallero.',
            'meta_description' => 'Todo sobre la gastronomía de Moratalla: platos típicos como migas, arroz con conejo y caracoles, andrajos, el famoso mazapán de Moratalla y dónde comer.',
            'keywords'         => 'gastronomía de Moratalla, dónde comer en Moratalla, platos típicos de Moratalla, mazapán de Moratalla, recetas de Moratalla',
            'icon'             => 'fas fa-utensils',
            'hero_image'       => 'uploads/seo/pilar-gastronomia.svg',
            'logo_image'       => 'uploads/seo/pilar-gastronomia.svg',
            'reading_time'     => '9 min de lectura',
            'word_target'      => 'Guía de Gastronomía',
            'map_query'        => 'Restaurantes en Moratalla, Murcia',
            'related_cards'    => ['gastronomia_ficha', 'recetas_ficha', 'san_anton_feria', 'calendario_fiestas'],
            'quick_facts'      => [
                ['label' => 'Dulce Estrella', 'value' => 'Mazapán de Moratalla (almendra, yema y cabello de ángel)'],
                ['label' => 'Platos de Cuchara y Sartén', 'value' => 'Migas ruleras, Olla de cerdo, Andrajos, Ajoharina y Arroz con conejo y caracoles'],
                ['label' => 'Tarjetas en la Web', 'value' => 'Gastronomía Moratallera (id=263) y Recetas de Moratalla (id=428)'],
                ['label' => 'Guía de Mesas', 'value' => 'Directorio completo de Bares y Restaurantes (restaurantes.php)']
            ],
            'sections' => [
                [
                    'id'      => 'identidad-culinaria',
                    'title'   => '1. La cocina de montaña del Noroeste murciano',
                    'content' => '<p>La <strong>gastronomía de Moratalla</strong> es el reflejo fiel de su geografía serrana y de su historia agrícola y pastoril. El aceite de oliva de variedades autóctonas, las hierbas aromáticas del monte (romero, tomillo, ajedrea) y la leña de encina o sarmiento marcan el carácter de cada plato.</p>
                    <p>En nuestra web dispones de dos tarjetas históricas fundamentales dentro de la categoría <a href="category.php?id=80"><strong>Gastronomía</strong></a>: la ficha de <a href="page.php?id=263"><strong>Gastronomía Moratallera</strong></a> y el compendio de <a href="page.php?id=428"><strong>Recetas de Moratalla</strong></a>.</p>'
                ],
                [
                    'id'      => 'platos-imprescindibles',
                    'title'   => '2. Los platos típicos que debes probar en Moratalla',
                    'content' => '<ul>
                        <li><strong>Arroz con conejo y caracoles serranos:</strong> Cocinado a fuego vivo con sarmientos, azafrán y caracoles chonetes o serranos.</li>
                        <li><strong>Migas ruleras (de harina o de pan):</strong> Indispensables en los días de lluvia o matanza, acompañadas de tajadas, ajos tiernos, uvas o granadas.</li>
                        <li><strong>Andrajos y Ajoharina:</strong> Guisos tradicionales de invierno con tortas finas de masa cocidas en caldo con caza, liebre, conejo o bacalao.</li>
                        <li><strong>Olla fresca o potaje moratallero:</strong> Plato de cuchara reconfortante con legumbres y embutidos tradicionales.</li>
                        <li><strong>Cordero segureño a la brasa o al horno:</strong> Criado en los pastos de altura de las pedanías de Moratalla.</li>
                    </ul>'
                ],
                [
                    'id'      => 'mazapan-y-reposteria',
                    'title'   => '3. El Mazapán de Moratalla y dónde comer',
                    'content' => '<p>El célebre <strong>Mazapán de Moratalla</strong> se elabora artesanalmente con fina pasta de almendra rellena de dulce de <strong>yema de huevo y cabello de ángel</strong>, horneado hasta lograr una corteza dorada. Junto a él brillan las tortas de Pascua, los suspiros, los rollos de vino y las flores de sartén.</p>
                    <p>Consulta todas las elaboraciones en la tarjeta <a href="page.php?id=428"><strong>Recetas de Moratalla</strong></a> y elige mesa hoy mismo en el <a href="restaurantes.php"><strong>Directorio de Bares y Restaurantes de Moratalla</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde ver las recetas tradicionales de Moratalla en la web?',
                    'a' => 'Accede directamente a la tarjeta "Recetas de Moratalla" (page.php?id=428) y a "Gastronomía Moratallera" (page.php?id=263) dentro de la sección Gastronomía (category.php?id=80).'
                ],
                [
                    'q' => '¿Dónde comer comida típica en Moratalla?',
                    'a' => 'Tanto en el casco urbano de Moratalla como en La Puerta, Casa de Cristo y pedanías como Benizar, Campo de San Juan o El Sabinar encontrarás restaurantes tradicionales listados en restaurantes.php.'
                ]
            ],
            'related_slugs' => [
                'recetas-tradicionales-gastronomia-moratalla',
                'guia-completa-moratalla',
                'turismo-rural-moratalla',
                'fiestas-populares-de-moratalla'
            ]
        ],

        // =========================================================================
        // GUÍAS ESPECIALIZADAS Y RUTAS TEMÁTICAS
        // =========================================================================
        '10-lugares-imprescindibles-que-ver-en-moratalla' => [
            'slug'             => '10-lugares-imprescindibles-que-ver-en-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Selección Imprescindibles',
            'priority_rank'    => 6,
            'short_title'      => '10 Lugares Que Ver en Moratalla',
            'title'            => '10 Lugares Imprescindibles Que Ver en Moratalla (Con Fichas y Fotografías)',
            'h1'               => '10 Lugares Imprescindibles Que Ver en Moratalla en tu Próxima Escapada',
            'subtitle'         => 'De la Torre del Homenaje a las pozas del río Alhárabe: la selección definitiva conectada con nuestras galerías fotográficas.',
            'meta_description' => 'Descubre los 10 lugares imprescindibles que ver en Moratalla (Murcia): Castillo-Fortaleza, casco antiguo, río Alhárabe, Bolvonegro, Casa de Cristo y más.',
            'keywords'         => 'qué ver en Moratalla, 10 lugares que ver en Moratalla, sitios que visitar en Moratalla, turismo Moratalla',
            'icon'             => 'fas fa-map-marked-alt',
            'hero_image'       => 'uploads/seo/pilar-guia-completa.svg',
            'logo_image'       => 'uploads/seo/pilar-guia-completa.svg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Guía de Imprescindibles',
            'map_query'        => 'Moratalla, Murcia',
            'related_cards'    => ['castillo', 'asuncion', 'casa_cristo', 'las_arcas', 'santa_ana', 'puente_hellin', 'arte_rupestre', 'museo_tambor'],
            'quick_facts'      => [
                ['label' => 'Tiempo Recomendado', 'value' => '1 a 3 días'],
                ['label' => 'Enclaves Urbanos', 'value' => 'Castillo (id=433), La Asunción (id=440), Santa Ana (id=434) y San Francisco (id=435)'],
                ['label' => 'Enclaves Naturales', 'value' => 'Casa de Cristo (id=441), Las Arcas (id=446), Puente de Hellín (id=445) y Arte Rupestre (id=451)']
            ],
            'sections' => [
                [
                    'id'      => 'top-1-a-5',
                    'title'   => 'Del 1 al 5: Patrimonio histórico y miradores urbanos',
                    'content' => '<ol>
                        <li><strong><a href="page.php?id=433">Castillo fortaleza de Moratalla</a>:</strong> Su silueta almenada y su Torre del Homenaje del siglo XV ofrecen las mejores vistas sobre el valle (<a href="page.php?id=433">ver tarjeta y 28 fotografías</a>).</li>
                        <li><strong><a href="page.php?id=440">Parroquia de Santa María de la Asunción</a>:</strong> Obra cumbre del Renacimiento en Murcia, con un interior de columnas monumentales (<a href="page.php?id=440">ver tarjeta y 29 fotografías</a>).</li>
                        <li><strong>Laberinto del Casco Antiguo y <a href="page.php?id=434">Ermita de Santa Ana</a>:</strong> Pasea sin prisas por sus calles empinadas y visita la histórica ermita (<a href="page.php?id=434">ver ficha</a>).</li>
                        <li><strong><a href="page.php?id=435">Convento de San Francisco</a> y <a href="page.php?id=430">Museo del Tambor</a>:</strong> El pulso cultural y tamborista de la villa (<a href="page.php?id=430">ver 15 fotos del Museo del Tambor</a>).</li>
                        <li><strong><a href="page.php?id=441">Santuario de la Casa de Cristo</a>:</strong> A 7 km del pueblo, rodeado de pinares y sede del Centro de Arte Rupestre (<a href="page.php?id=441">ver 15 fotografías</a>).</li>
                    </ol>'
                ],
                [
                    'id'      => 'top-6-a-10',
                    'title'   => 'Del 6 al 10: Naturaleza salvaje, ingeniería histórica y arte prehistórico',
                    'content' => '<ol start="6">
                        <li><strong>Río Alhárabe, La Puerta y <a href="page.php?id=445">Puente de Hellín</a>:</strong> Bosque de galería, aguas limpias y puentes históricos (<a href="page.php?id=445">ver ficha del Puente de Hellín</a>).</li>
                        <li><strong>Estrecho de Bolvonegro y <a href="page.php?id=446">Las Arcas</a>:</strong> Desfiladero fluvial y el imponente acueducto tradicional de <a href="page.php?id=446">Las Arcas (ver 21 fotografías)</a>.</li>
                        <li><strong>Poza Termal de Somogil:</strong> Rincón natural donde el agua brota a temperatura templada entre paredones de roca.</li>
                        <li><strong><a href="page.php?id=451">Pinturas Rupestres UNESCO</a> (Cañaica del Calar):</strong> Arte prehistórico en un entorno de montaña sobrecogedor (<a href="page.php?id=451">ver ficha</a>).</li>
                        <li><strong>Pedanías serranas (<a href="page.php?id=442">Calar de la Santa</a>, <a href="page.php?id=443">El Sabinar</a>, <a href="page.php?id=444">Cañada de la Cruz</a>, Benizar) y Revolcadores:</strong> El techo de la Región de Murcia.</li>
                    </ol>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Qué ver en Moratalla en un solo día?',
                    'a' => 'Por la mañana recorre el Castillo-Fortaleza (page.php?id=433), la Parroquia de la Asunción (page.php?id=440) y el casco antiguo; come en un restaurante tradicional del pueblo y por la tarde sube al Santuario de Casa de Cristo (page.php?id=441) o pasea junto al río Alhárabe.'
                ]
            ],
            'related_slugs' => [
                'guia-completa-moratalla',
                'ruta-casco-antiguo-moratalla',
                'castillo-de-moratalla',
                'rutas-de-senderismo-en-moratalla'
            ]
        ],

        'ruta-casco-antiguo-moratalla' => [
            'slug'             => 'ruta-casco-antiguo-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Recorrido Urbano',
            'priority_rank'    => 7,
            'short_title'      => 'Ruta por el Casco Antiguo',
            'title'            => 'Ruta por el Casco Antiguo de Moratalla | Calles, Miradores e Historia',
            'h1'               => 'Ruta a Pie por el Casco Antiguo de Moratalla: Del Convento de San Francisco al Castillo',
            'subtitle'         => 'Un itinerario circular paso a paso por las callejuelas medievales, ermitas, placetas y miradores más fotogénicos de Moratalla.',
            'meta_description' => 'Recorre la ruta por el casco antiguo de Moratalla a pie: Convento de San Francisco, Santa Ana, Iglesia de la Asunción, barrio medieval y Castillo-Fortaleza.',
            'keywords'         => 'casco antiguo de Moratalla, calles de Moratalla, ruta urbana Moratalla, miradores de Moratalla',
            'icon'             => 'fas fa-route',
            'hero_image'       => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'logo_image'       => 'uploads/seo/pilar-monumentos-patrimonio.svg',
            'reading_time'     => '7 min de lectura',
            'word_target'      => 'Itinerario Urbano',
            'map_query'        => 'Calle Mayor, Moratalla, Murcia',
            'related_cards'    => ['san_francisco', 'santa_ana', 'asuncion', 'castillo', 'museo_tambor'],
            'quick_facts'      => [
                ['label' => 'Duración del Paseo', 'value' => '1 hora y 30 minutos (a ritmo tranquilo con fotos)'],
                ['label' => 'Hitos con Tarjeta Propia', 'value' => 'San Francisco (id=435), Santa Ana (id=434), La Asunción (id=440) y Castillo (id=433)'],
                ['label' => 'Mejor Luz para Fotos', 'value' => 'Primera hora de la mañana o al atardecer']
            ],
            'sections' => [
                [
                    'id'      => 'tramo-bajo',
                    'title'   => 'Parada 1: La Corredera, Convento de San Francisco, Santa Ana y Calle Mayor',
                    'content' => '<p>El recorrido comienza en la parte baja del centro histórico, visitando el antiguo <a href="page.php?id=435"><strong>Convento de San Francisco</strong></a> y acercándose a la <a href="page.php?id=434"><strong>Ermita de Santa Ana</strong></a> y al <a href="page.php?id=430"><strong>Museo del Tambor</strong></a>, antes de adentrarse en la <strong>Calle Mayor</strong> y el Ayuntamiento, donde aún se conservan casas señoriales con escudos nobiliarios y rejería tradicional.</p>'
                ],
                [
                    'id'      => 'ascenso-castillo',
                    'title'   => 'Parada 2: Parroquia de la Asunción y subida al Castillo-Fortaleza',
                    'content' => '<p>Al llegar a la <a href="page.php?id=440"><strong>Parroquia de Santa María de la Asunción (ver 29 fotos)</strong></a>, el espacio se abre en un balcón natural sobre la vega. Desde aquí, las callejuelas estrechas y empedradas ascienden serpenteando hasta las murallas del <a href="page.php?id=433"><strong>Castillo fortaleza de Moratalla (ver 28 fotos)</strong></a>, regalando una de las estampas urbanas más auténticas de la Región de Murcia.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde aparcar para visitar el casco antiguo de Moratalla?',
                    'a' => 'Lo más cómodo es aparcar en las zonas habilitadas en la entrada del municipio o en el entorno del Parque de la Corredera, y realizar la subida hacia San Francisco (page.php?id=435), la Asunción (page.php?id=440) y el Castillo (page.php?id=433) caminando.'
                ]
            ],
            'related_slugs' => [
                'castillo-de-moratalla',
                'monumentos-y-patrimonio-moratalla',
                'guia-completa-moratalla'
            ]
        ],

        'castillo-de-moratalla' => [
            'slug'             => 'castillo-de-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Monumento BIC · Fortaleza Medieval',
            'priority_rank'    => 8,
            'short_title'      => 'Castillo de Moratalla',
            'title'            => 'Castillo de Moratalla | Historia, Torre del Homenaje y Galería Fotográfica',
            'h1'               => 'El Castillo-Fortaleza de Moratalla y su Imponente Torre del Homenaje',
            'subtitle'         => 'Descubre la historia, arquitectura gótico-militar y la tarjeta oficial con 28 fotografías de la fortaleza santiaguista de Moratalla.',
            'meta_description' => 'Guía completa del Castillo de Moratalla (Murcia) conectada con su tarjeta patrimonial y 28 fotografías: Orden de Santiago, Torre del Homenaje y Sala del Trono.',
            'keywords'         => 'Castillo de Moratalla, Fortaleza de Moratalla, Torre del Homenaje Moratalla, visitar Castillo de Moratalla',
            'icon'             => 'fas fa-chess-rook',
            'hero_image'       => 'uploads/seo/guia-castillo.svg',
            'logo_image'       => 'uploads/seo/guia-castillo.svg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Monografía del Castillo',
            'map_query'        => 'Castillo de Moratalla, Murcia',
            'related_cards'    => ['castillo', 'asuncion', 'historia_ludena', 'santa_ana', 'san_francisco'],
            'quick_facts'      => [
                ['label' => 'Tarjeta Oficial y Fotos', 'value' => 'Castillo fortaleza de Moratalla (page.php?id=433 · 28 fotografías)'],
                ['label' => 'Época de Construcción', 'value' => 'Origen islámico (s. IX-XII) y reforma santiaguista (s. XV-XVI)'],
                ['label' => 'Elemento Destacado', 'value' => 'Torre del Homenaje de 25 m de altura y Sala del Trono gótica'],
                ['label' => 'Protección', 'value' => 'Bien de Interés Cultural (BIC) y Patrimonio Histórico de España']
            ],
            'sections' => [
                [
                    'id'      => 'arquitectura-fortaleza',
                    'title'   => '1. Arquitectura militar: Las seis torres y el Patio de Armas',
                    'content' => '<p>El <a href="page.php?id=433"><strong>Castillo fortaleza de Moratalla</strong></a> se asienta sobre un peñón rocoso que domina todo el corredor natural del Alhárabe. Sobre la primitiva alcazaba andalusí, los comendadores de la <strong>Orden de Santiago</strong> levantaron en el siglo XV una fortaleza cristiana artillada capaz de defender la frontera con el Reino de Granada. Su recinto amurallado conserva seis torres, aljibe subterráneo y paseo de ronda.</p>
                    <p><i class="fas fa-camera-retro" style="color:var(--primary);"></i> <strong>Galería Fotográfica y Ficha del Castillo:</strong> Consulta la tarjeta creada sobre el <a href="page.php?id=433"><strong>Castillo fortaleza de Moratalla (page.php?id=433)</strong></a>, que incluye la descripción detallada del monumento y un reportaje de <strong>28 fotografías</strong> de sus murallas, torres e interior.</p>'
                ],
                [
                    'id'      => 'torre-del-homenaje',
                    'title'   => '2. La Torre del Homenaje y la Sala del Trono',
                    'content' => '<p>La joya del conjunto es su <strong>Torre del Homenaje</strong>, adscrita al estilo gótico-militar levantino. En su interior destaca la <strong>Sala del Trono</strong>, cubierta por una soberbia bóveda de crucería estrellada sobre ménsulas labradas, junto con sus ventanas geminadas con asientos de piedra (festejadores) desde los que se divisa la <a href="page.php?id=440">Parroquia de Santa María de la Asunción</a>, la Sierra del Buitre y la huerta de Moratalla. Puedes ampliar el contexto histórico en la ficha de <a href="page.php?id=290"><strong>Historia de Moratalla</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde puedo ver la tarjeta con la descripción y las 28 fotografías del Castillo de Moratalla?',
                    'a' => 'Haz clic directamente en la tarjeta "Castillo fortaleza de Moratalla" (page.php?id=433) que aparece destacada en esta misma guía para abrir la galería completa de 28 fotografías.'
                ],
                [
                    'q' => '¿Por qué es tan importante la Torre del Homenaje de Moratalla?',
                    'a' => 'Porque es uno de los mejores ejemplos de arquitectura militar gótica del siglo XV en el sureste español, conservando su bóveda de crucería original y su corona de matacanes y almenas.'
                ]
            ],
            'related_slugs' => [
                'monumentos-y-patrimonio-moratalla',
                'historia-de-moratalla',
                'ruta-casco-antiguo-moratalla',
                'guia-completa-moratalla'
            ]
        ],

        'pinturas-rupestres-de-moratalla' => [
            'slug'             => 'pinturas-rupestres-de-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Patrimonio Mundial UNESCO',
            'priority_rank'    => 9,
            'short_title'      => 'Pinturas Rupestres UNESCO',
            'title'            => 'Pinturas Rupestres de Moratalla | Patrimonio UNESCO y Casa de Cristo',
            'h1'               => 'Las Pinturas Rupestres de Moratalla Explicadas: Patrimonio de la Humanidad UNESCO',
            'subtitle'         => 'Con más de 60 cavidades catalogadas, Moratalla concentra el mayor tesoro de arte rupestre prehistórico de la Región de Murcia.',
            'meta_description' => 'Descubre las pinturas rupestres de Moratalla (Patrimonio UNESCO): ficha de Arte Rupestre, Santuario de la Casa de Cristo, Cañaica del Calar y Fuente del Sabuco.',
            'keywords'         => 'pinturas rupestres de Moratalla, arte rupestre Moratalla, Cañaica del Calar, Casa de Cristo Moratalla, Patrimonio UNESCO Moratalla',
            'icon'             => 'fas fa-palette',
            'hero_image'       => 'uploads/seo/guia-rupestre.svg',
            'logo_image'       => 'uploads/seo/guia-rupestre.svg',
            'reading_time'     => '9 min de lectura',
            'word_target'      => 'Guía Arte Rupestre',
            'map_query'        => 'Centro de Arte Rupestre Casa de Cristo, Moratalla',
            'related_cards'    => ['arte_rupestre', 'casa_cristo', 'historia_ludena', 'las_arcas'],
            'quick_facts'      => [
                ['label' => 'Tarjetas Oficiales', 'value' => 'Arte Rupestre (page.php?id=451) y Santuario Casa de Cristo (page.php?id=441)'],
                ['label' => 'Declaración UNESCO', 'value' => '1998 (Arte Rupestre del Arco Mediterráneo de la Península Ibérica)'],
                ['label' => 'Estilos Presentes', 'value' => 'Arte Levantino (naturalista) y Arte Esquemático'],
                ['label' => 'Conjuntos Principales', 'value' => 'Cañaica del Calar, Fuente del Sabuco, La Risca, Hondares y Molino de Capel']
            ],
            'sections' => [
                [
                    'id'      => 'importancia-unesco',
                    'title'   => '1. Más de la mitad del arte rupestre de Murcia está en Moratalla',
                    'content' => '<p>En 1998 la UNESCO declaró Patrimonio Mundial el <strong>Arte Rupestre del Arco Mediterráneo de la Península Ibérica</strong>. Dentro de la Región de Murcia, el término municipal de Moratalla alberga más del 50% de los abrigos y estaciones rupestres catalogados, recogidos en nuestra tarjeta patrimonial de <a href="page.php?id=451"><strong>Arte Rupestre de Moratalla (page.php?id=451)</strong></a>.</p>'
                ],
                [
                    'id'      => 'canaica-sabuco-casa-cristo',
                    'title'   => '2. Cañaica del Calar, Fuente del Sabuco y el Santuario de la Casa de Cristo',
                    'content' => '<p>En los abrigos de <strong>Cañaica del Calar</strong> y <strong>Fuente del Sabuco</strong> (en el entorno de <a href="page.php?id=442">Calar de la Santa</a> y <a href="page.php?id=443">El Sabinar</a>) conviven el <strong>Arte Levantino</strong> —con estilizadas figuras de arqueros, ciervos, cabras montesas y escenas de caza— y el <strong>Arte Esquemático</strong> neolítico.</p>
                    <p>Antes de subir a los abrigos, es imprescindible visitar el Centro de Interpretación ubicado en el <a href="page.php?id=441"><strong>Santuario de la Casa de Cristo (ver tarjeta y 15 fotografías)</strong></a>, que ofrece reproducciones a escala real y visitas guiadas.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde consultar las tarjetas sobre Arte Rupestre y Casa de Cristo en la web?',
                    'a' => 'Puedes abrir directamente la tarjeta de Arte Rupestre (page.php?id=451) y la del Santuario de la Casa de Cristo (page.php?id=441, con 15 fotografías) disponibles en el bloque de tarjetas de esta página.'
                ]
            ],
            'related_slugs' => [
                'historia-de-moratalla',
                'monumentos-y-patrimonio-moratalla',
                'rutas-de-senderismo-en-moratalla',
                'guia-completa-moratalla'
            ]
        ],

        'semana-santa-de-moratalla' => [
            'slug'             => 'semana-santa-de-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Semana Santa y Cofradías',
            'priority_rank'    => 10,
            'short_title'      => 'Semana Santa de Moratalla',
            'title'            => 'Semana Santa de Moratalla | Tambores, Cofradías y 87 Fotografías Históricas',
            'h1'               => 'Semana Santa de Moratalla: Pasión, Túnicas de Colores y el Estruendo del Tambor',
            'subtitle'         => 'Descubre las procesiones, cofradías y tamboradas de Moratalla junto con su tarjeta oficial de 87 fotografías y el Museo del Tambor.',
            'meta_description' => 'Guía de la Semana Santa de Moratalla conectada con la crónica y galería de 87 fotos (page.php?id=453), Museo del Tambor (id=430) y todas sus cofradías.',
            'keywords'         => 'Semana Santa de Moratalla, tambores de Moratalla, procesiones Moratalla, Jueves Santo Moratalla, Viernes Santo Moratalla',
            'icon'             => 'fas fa-church',
            'hero_image'       => 'uploads/seo/guia-semana-santa.svg',
            'logo_image'       => 'uploads/seo/guia-semana-santa.svg',
            'reading_time'     => '9 min de lectura',
            'word_target'      => 'Guía de Semana Santa',
            'map_query'        => 'Plaza de la Iglesia, Moratalla',
            'related_cards'    => ['semana_santa_ficha', 'museo_tambor', 'bic_tamborada', 'cofradia_nazareno', 'exaltacion_tambor_2008'],
            'quick_facts'      => [
                ['label' => 'Tarjeta Principal', 'value' => 'Semana Santa de Moratalla (page.php?id=453 · 87 fotografías)'],
                ['label' => 'Museo y Declaración', 'value' => 'Museo del Tambor (id=430 · 15 fotos) y Declaración BIC (id=458)'],
                ['label' => 'Días de Tamborada', 'value' => 'Jueves Santo, Viernes Santo y Domingo de Resurrección'],
                ['label' => 'Cofradías con Ficha', 'value' => 'Jesús Nazareno (id=409), Santo Sepulcro (id=454), Sangre (id=455), Soledad (id=456)']
            ],
            'sections' => [
                [
                    'id'      => 'dualidad-semana-santa',
                    'title'   => '1. Una Semana Santa con personalidad propia y fichas históricas en la web',
                    'content' => '<p>La <a href="page.php?id=453"><strong>Semana Santa de Moratalla (ver crónica y 87 fotografías)</strong></a> posee una personalidad inconfundible en el sureste español. En ella se entrelazan la solemnidad de sus cofradías pasionarias y la fuerza arrolladora de los miles de tamboristas que inundan las calles.</p>
                    <p>En nuestro archivo puedes consultar directamente las tarjetas dedicadas a cada hermandad y cofradía:</p>
                    <ul>
                        <li><a href="page.php?id=409"><strong>Ilustre Cofradía de Nuestro Padre Jesús Nazareno</strong></a> (con galería de 26 fotografías).</li>
                        <li><a href="page.php?id=454"><strong>Cofradía del Santo Sepulcro y Santo Silencio</strong></a>.</li>
                        <li><a href="page.php?id=455"><strong>Cofradía de la Preciosísima Sangre</strong></a>.</li>
                        <li><a href="page.php?id=456"><strong>Hermandad de Nuestra Señora de la Soledad</strong></a>.</li>
                        <li><a href="page.php?id=457"><strong>Patronato de Jesucristo Aparecido y Virgen de la Rogativa</strong></a>.</li>
                    </ul>'
                ],
                [
                    'id'      => 'calendario-procesiones-y-tambor',
                    'title'   => '2. Del Miércoles Santo al Domingo de Resurrección y el Museo del Tambor',
                    'content' => '<ul>
                        <li><strong>Miércoles Santo (La "Embarrá"):</strong> Preludio de hermandad en el que las peñas y cuadrillas preparan los tambores y templan los parches de piel.</li>
                        <li><strong>Jueves Santo y Viernes Santo:</strong> Desde la mañana hasta la noche, una marea multicolor de túnicas y tambores recorre La Farola, la Calle Mayor y el casco antiguo, abriendo paso respetuosamente a las procesiones.</li>
                        <li><strong>Domingo de Resurrección:</strong> Última gran jornada de tambor y júbilo popular.</li>
                    </ul>
                    <p>No dejes de visitar la tarjeta del <a href="page.php?id=430"><strong>Museo del Tambor de Moratalla (15 fotografías)</strong></a> y el documento oficial de la <a href="page.php?id=458"><strong>Declaración de Interés Cultural Inmaterial</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde ver las 87 fotografías de la Semana Santa de Moratalla?',
                    'a' => 'Accede directamente a la tarjeta oficial de Semana Santa (page.php?id=453), donde encontrarás el texto de Pedro Martínez López y la galería fotográfica de 87 imágenes.'
                ]
            ],
            'related_slugs' => [
                'tamboradas-de-moratalla',
                'fiestas-populares-de-moratalla',
                'guia-completa-moratalla'
            ]
        ],

        'tamboradas-de-moratalla' => [
            'slug'             => 'tamboradas-de-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Patrimonio Inmaterial UNESCO',
            'priority_rank'    => 11,
            'short_title'      => 'La Tradición del Tambor',
            'title'            => 'Tamboradas de Moratalla | Museo del Tambor, Túnicas y Patrimonio UNESCO',
            'h1'               => 'La Tradición del Tambor en Moratalla: Toque, Túnicas y Patrimonio de la Humanidad',
            'subtitle'         => 'Cómo el toque libre de los tambores de Moratalla se convirtió en Patrimonio Cultural Inmaterial de la Humanidad.',
            'meta_description' => 'Conoce la tradición del tambor en Moratalla: tarjeta del Museo del Tambor (page.php?id=430), Declaración BIC (id=458) y archivo de la Asociación de Tamboristas.',
            'keywords'         => 'tamboradas de Moratalla, tradición del tambor Moratalla, tambores Moratalla UNESCO, tamboristas Moratalla, Museo del Tambor Moratalla',
            'icon'             => 'fas fa-drum',
            'hero_image'       => 'uploads/seo/guia-semana-santa.svg',
            'logo_image'       => 'uploads/seo/guia-semana-santa.svg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Monografía del Tambor',
            'map_query'        => 'Museo del Tambor, Moratalla, Murcia',
            'related_cards'    => ['museo_tambor', 'bic_tamborada', 'semana_santa_ficha', 'exaltacion_tambor_2008', 'cofradia_nazareno'],
            'quick_facts'      => [
                ['label' => 'Tarjetas Destacadas', 'value' => 'Museo del Tambor (id=430 · 15 fotos) y Declaración Inmaterial (id=458)'],
                ['label' => 'Estilo de Toque', 'value' => 'Libre, espontáneo y de "pique" o diálogo entre tamboristas'],
                ['label' => 'Indumentaria', 'value' => 'Túnica de terciopelo, raso o volantes con capuz a rostro descubierto'],
                ['label' => 'Archivo Asociación', 'value' => 'Exaltaciones de 2004 (id=126, id=410) y 2008 (id=411 · 30 fotos)']
            ],
            'sections' => [
                [
                    'id'      => 'toque-y-tunica',
                    'title'   => '1. El toque moratallero, el Museo del Tambor y la Declaración Inmaterial',
                    'content' => '<p>Lo que hace única a la <strong>tamborada de Moratalla</strong> es su toque libre a rostro descubierto, con el capuz levantado y adornado con espejos, cintas y bordados artesanales. Toda esta riqueza de túnicas de volantes y tambores de artesanía se conserva y expone en la tarjeta del <a href="page.php?id=430"><strong>MUSEO DEL TAMBOR de Moratalla (page.php?id=430 · 15 fotografías)</strong></a>.</p>
                    <p>Asimismo, puedes consultar la ficha oficial de la <a href="page.php?id=458"><strong>Declaración de Interés Cultural Inmaterial (page.php?id=458)</strong></a> y los álbumes históricos de la <a href="category.php?id=168"><strong>Asociación de Tamboristas</strong></a>, como la <a href="page.php?id=411">Exaltación del Tambor y el Bombo 2008 (30 fotos)</a>, la <a href="page.php?id=126">Exaltación de 2004</a> y la <a href="page.php?id=410">Semana Santa 2004 (30 fotos)</a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde ver las fotografías del Museo del Tambor de Moratalla?',
                    'a' => 'En la tarjeta oficial del Museo del Tambor (page.php?id=430), que cuenta con descripción y 15 fotografías de su colección permanente.'
                ]
            ],
            'related_slugs' => [
                'semana-santa-de-moratalla',
                'fiestas-populares-de-moratalla',
                'historia-de-moratalla'
            ]
        ],

        'rutas-de-senderismo-en-moratalla' => [
            'slug'             => 'rutas-de-senderismo-en-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Naturaleza y Montaña',
            'priority_rank'    => 12,
            'short_title'      => 'Rutas de Senderismo',
            'title'            => 'Las Mejores Rutas de Senderismo en Moratalla | Ríos, Gargantas y Cumbres',
            'h1'               => 'Las 6 Mejores Rutas de Senderismo en Moratalla para Todos los Niveles',
            'subtitle'         => 'Desde paseos junto al río Alhárabe y el Puente de Hellín hasta la ascensión al techo de Murcia en el Macizo de Revolcadores.',
            'meta_description' => 'Guía con las mejores rutas de senderismo en Moratalla: Río Alhárabe, Puente de Hellín, Las Arcas, Bolvonegro, Casa de Cristo, Revolcadores y Club Filippedes.',
            'keywords'         => 'mejores rutas senderismo Moratalla, rutas de senderismo en Moratalla, río Alhárabe ruta, Bolvonegro Moratalla, Revolcadores ruta',
            'icon'             => 'fas fa-hiking',
            'hero_image'       => 'uploads/seo/guia-senderismo.svg',
            'logo_image'       => 'uploads/seo/guia-senderismo.svg',
            'reading_time'     => '10 min de lectura',
            'word_target'      => 'Guía de Senderismo',
            'map_query'        => 'Estrecho de Bolvonegro, Moratalla',
            'related_cards'    => ['situacion_rutas_filippedes', 'puente_hellin', 'las_arcas', 'casa_cristo', 'arte_rupestre'],
            'quick_facts'      => [
                ['label' => 'Rutas Familiares', 'value' => 'Senda del Río Alhárabe, Puente de Hellín (id=445) y Casa de Cristo (id=441)'],
                ['label' => 'Rutas Medias', 'value' => 'Estrecho de Bolvonegro, Las Arcas (id=446 · 21 fotos) y Poza de Somogil'],
                ['label' => 'Alta Montaña y Clubes', 'value' => 'Revolcadores (2.015 m), Club Filippedes (id=452) y Club Ciclista (id=427)']
            ],
            'sections' => [
                [
                    'id'      => 'rutas-fluviales',
                    'title'   => '1. Rutas del agua: Río Alhárabe, Puente de Hellín, Las Arcas y Bolvonegro',
                    'content' => '<p>El agua ha esculpido algunos de los senderos más bellos del sureste peninsular, jalonados por hitos patrimoniales con tarjeta propia en nuestra web:</p>
                    <ul>
                        <li><strong>Senda del Río Alhárabe, <a href="page.php?id=445">Puente de Hellín</a> y Estrecho de Bolvonegro:</strong> Partiendo de La Puerta o del histórico <a href="page.php?id=445">Puente de Hellín (ver ficha)</a>, el sendero acompaña al bosque de ribera hasta el cañón de Bolvonegro y el entorno del acueducto de <a href="page.php?id=446"><strong>Las Arcas (ver 21 fotografías)</strong></a>.</li>
                        <li><strong>Ruta a la Poza de Somogil:</strong> Aguas arriba del Alhárabe, donde el río forma pozas cristalinas alimentadas por manantiales templados.</li>
                        <li><strong>Ruta del Agua y Castillo de Benizar:</strong> Recorrido circular por el nacimiento de las Tosquillas, el Rincón de las Cuevas y la fortaleza roquera de Benizar.</li>
                    </ul>'
                ],
                [
                    'id'      => 'rutas-cumbres',
                    'title'   => '2. Cumbres de Moratalla: Revolcadores, Sierra del Buitre y Clubes de Montaña',
                    'content' => '<p>Para los montañeros, la ascensión al <strong>Macizo de Revolcadores y Los Odres (2.015 m)</strong> desde Inazares o <a href="page.php?id=444">Cañada de la Cruz</a> es la ruta reina de la Región de Murcia. Más cerca del casco urbano, la subida desde el <a href="page.php?id=441"><strong>Santuario de la Casa de Cristo (15 fotos)</strong></a> a la <strong>Sierra del Buitre</strong> regala panorámicas inolvidables.</p>
                    <p>Conoce la actividad montañera y deportiva del municipio en las tarjetas del <a href="page.php?id=452"><strong>Club Deportivo Filippedes - Moratalla</strong></a>, el <a href="page.php?id=427"><strong>Club Ciclista de Moratalla</strong></a> y la ficha de <a href="page.php?id=279"><strong>Situación geográfica</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Cuál es la ruta de senderismo más fácil en Moratalla para ir con niños?',
                    'a' => 'El paseo fluvial junto al río Alhárabe en La Puerta y el Puente de Hellín (page.php?id=445) o los senderos del pinar del Santuario de la Casa de Cristo (page.php?id=441) son ideales para familias.'
                ]
            ],
            'related_slugs' => [
                'turismo-rural-moratalla',
                '10-lugares-imprescindibles-que-ver-en-moratalla',
                'que-hacer-en-moratalla-fin-de-semana',
                'guia-completa-moratalla'
            ]
        ],

        'fiestas-populares-de-moratalla' => [
            'slug'             => 'fiestas-populares-de-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Calendario Festivo y Tradiciones',
            'priority_rank'    => 13,
            'short_title'      => 'Fiestas Populares y Calendario',
            'title'            => 'Fiestas de Moratalla | Calendario Completo, Cristo del Rayo y Pedanías',
            'h1'               => 'Fiestas Populares de Moratalla: Calendario Festivo Completo Mes a Mes',
            'subtitle'         => 'Conectado con el Calendario Interactivo de Fiestas (page.php?id=163), el Santísimo Cristo del Rayo, San Antón, San Miguel y las pedanías.',
            'meta_description' => 'Calendario completo de las Fiestas de Moratalla (Murcia): accede a las tarjetas del Cristo del Rayo, Calendario de Fiestas (id=163), Semana Santa, San Antón y Feria de San Miguel.',
            'keywords'         => 'Fiestas de Moratalla, Cristo del Rayo Moratalla, calendario fiestas Moratalla, encierros de Moratalla, San Antón Moratalla',
            'icon'             => 'fas fa-glass-cheers',
            'hero_image'       => 'uploads/seo/guia-fiestas.svg',
            'logo_image'       => 'uploads/seo/guia-fiestas.svg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Guía de Fiestas',
            'map_query'        => 'Calle Mayor, Moratalla',
            'related_cards'    => ['calendario_fiestas', 'cristo_del_rayo', 'semana_santa_ficha', 'san_anton_feria', 'museo_tambor'],
            'quick_facts'      => [
                ['label' => 'Calendario Interactivo', 'value' => 'Tarjeta oficial de Fiestas de Moratalla y Pedanías (page.php?id=163)'],
                ['label' => 'Fiestas Patronales', 'value' => 'Del 11 al 17 de julio: Santísimo Cristo del Rayo (category.php?id=45 y 191)'],
                ['label' => 'Invierno y Primavera', 'value' => 'San Antón (id=164), Semana Santa (id=453) y Romería de la Rogativa'],
                ['label' => 'Verano y Otoño', 'value' => 'Calar de la Santa (id=442), El Sabinar (id=443), Cañada de la Cruz (id=444) y San Miguel (id=449)']
            ],
            'sections' => [
                [
                    'id'      => 'cristo-del-rayo',
                    'title'   => '1. Fiestas del Santísimo Cristo del Rayo (Junio y Julio)',
                    'content' => '<p>El corazón festivo del verano en Moratalla late en torno al <a href="category.php?id=45"><strong>Santísimo Cristo del Rayo</strong></a>. El <strong>15 de junio</strong> se conmemora el voto del milagro de 1621 (<a href="page.php?id=169">ver referencia histórica</a>) en la <a href="page.php?id=440">Parroquia de Santa María de la Asunción</a>. Posteriormente, del <strong>11 al 17 de julio</strong>, se celebran las Fiestas Mayores (<a href="category.php?id=191">ver sección Fiestas Cristo del Rayo</a>), famosas por sus centenarios <strong>encierros taurinos por vereda</strong>.</p>
                    <p><i class="fas fa-calendar-check" style="color:var(--primary);"></i> <strong>Tarjetas Interactivas de Fiestas:</strong> Consulta la página <a href="page.php?id=163"><strong>Calendario de Fiestas de Moratalla y Pedanías (page.php?id=163)</strong></a>, que reúne las tarjetas de cada festividad con sus fechas, historia y coordenadas GPS.</p>'
                ],
                [
                    'id'      => 'calendario-anual',
                    'title'   => '2. Todas las fichas de fiestas en Moratalla y sus pedanías',
                    'content' => '<ul>
                        <li><strong>17 de enero – <a href="page.php?id=164">San Antón</a>:</strong> Tradicionales hogueras, rollos y bendición de animales (<a href="category.php?id=40">ver galería de 15 fotos de San Antón</a>).</li>
                        <li><strong>Marzo / Abril – <a href="page.php?id=453">Semana Santa (87 fotos)</a> y <a href="page.php?id=458">Tamboradas</a>:</strong> Patrimonio Cultural Inmaterial de la Humanidad.</li>
                        <li><strong>Mayo – Romería de la Virgen de la Rogativa (<a href="page.php?id=457">ver Patronato</a>):</strong> Peregrinación tradicional al Santuario de la Rogativa.</li>
                        <li><strong>Agosto – Fiestas Patronales de las Pedanías:</strong> Consulta las fichas específicas de <a href="page.php?id=442"><strong>Calar de la Santa (Virgen de la Asunción)</strong></a>, <a href="page.php?id=443"><strong>El Sabinar (San Bartolomé)</strong></a> y <a href="page.php?id=444"><strong>Cañada de la Cruz (Purísima Concepción)</strong></a>.</li>
                        <li><strong>29 de septiembre – <a href="page.php?id=449">San Miguel - Feria</a>:</strong> Histórica feria ganadera y comercial de otoño en Moratalla (<a href="page.php?id=449">ver ficha</a>).</li>
                    </ul>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde puedo ver el calendario con las tarjetas de todas las fiestas de Moratalla y sus pedanías?',
                    'a' => 'En la tarjeta interactiva de Fiestas (page.php?id=163) y en la categoría general de Fiestas (category.php?id=39), donde cada celebración cuenta con su propia ficha.'
                ]
            ],
            'related_slugs' => [
                'semana-santa-de-moratalla',
                'tamboradas-de-moratalla',
                'gastronomia-de-moratalla',
                'guia-completa-moratalla'
            ]
        ],

        'recetas-tradicionales-gastronomia-moratalla' => [
            'slug'             => 'recetas-tradicionales-gastronomia-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Recetario Tradicional',
            'priority_rank'    => 14,
            'short_title'      => 'Recetas Tradicionales',
            'title'            => 'Recetas Tradicionales de Moratalla | Cocina Casera y Dulces de Antaño',
            'h1'               => 'Gastronomía Típica de Moratalla: Recetas Tradicionales que Pasan de Generación en Generación',
            'subtitle'         => 'Conectado con las fichas de Recetas de Moratalla (page.php?id=428) y Gastronomía Moratallera (page.php?id=263).',
            'meta_description' => 'Recetas tradicionales de Moratalla (Murcia): accede a las fichas de Recetas de Moratalla (id=428) y Gastronomía Moratallera (id=263).',
            'keywords'         => 'recetas tradicionales de Moratalla, cocina típica de Moratalla, mazapán de Moratalla receta, migas de Moratalla',
            'icon'             => 'fas fa-book-open',
            'hero_image'       => 'uploads/seo/pilar-gastronomia.svg',
            'logo_image'       => 'uploads/seo/pilar-gastronomia.svg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Recetario Local',
            'map_query'        => 'Moratalla, Murcia',
            'related_cards'    => ['recetas_ficha', 'gastronomia_ficha', 'san_anton_feria'],
            'quick_facts'      => [
                ['label' => 'Tarjetas de Recetas', 'value' => 'Recetas de Moratalla (page.php?id=428) y Gastronomía Moratallera (page.php?id=263)'],
                ['label' => 'Base Culinaria', 'value' => 'Aceite de oliva local, harina candeal, almendra, caza y huerta'],
                ['label' => 'Dónde Degustarlas', 'value' => 'Directorio de Bares y Restaurantes (restaurantes.php)']
            ],
            'sections' => [
                [
                    'id'      => 'recetas-saladas',
                    'title'   => '1. El recetario salado: Del fuego de leña a la mesa',
                    'content' => '<p>Las recetas tradicionales documentadas en nuestra tarjeta de <a href="page.php?id=428"><strong>Recetas de Moratalla (page.php?id=428)</strong></a> y en <a href="page.php?id=263"><strong>Gastronomía Moratallera (page.php?id=263)</strong></a> nacieron para alimentar a las familias en las labores del campo y la sierra. Entre ellas sobresalen las <strong>migas de harina</strong> trabajadas en sartén de hierro; los <strong>andrajos</strong> con tortas finas de masa; el <strong>ajoharina</strong> con níscalos (guíscanos) de los pinares; y el <strong>arroz con conejo y caracoles</strong> aromatizado con azafrán de hebra y romero.</p>'
                ],
                [
                    'id'      => 'reposteria-tradicional',
                    'title'   => '2. Dulces de Pascua, sartén y el Mazapán de Moratalla',
                    'content' => '<p>En las panaderías y confiterías artesanas de Moratalla siguen vivos los hornos donde se cuecen el <a href="guia.php?slug=gastronomia-de-moratalla"><strong>Mazapán de Moratalla</strong></a>, los rollos de San Antón (<a href="page.php?id=164">ver fiesta de San Antón</a>), los mantecados, las tortas de chicharrones y los suspiros de almendra.</p>
                    <p>Descubre dónde probar todos estos sabores en nuestra guía de <a href="restaurantes.php"><strong>Bares y Restaurantes de Moratalla</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde consultar las fichas originales de recetas en moratalla-murcia.com?',
                    'a' => 'Haz clic en las tarjetas "Recetas de Moratalla" (page.php?id=428) y "Gastronomía Moratallera" (page.php?id=263) incluidas en esta misma página.'
                ]
            ],
            'related_slugs' => [
                'gastronomia-de-moratalla',
                'guia-completa-moratalla',
                'fiestas-populares-de-moratalla'
            ]
        ],

        'que-hacer-en-moratalla-fin-de-semana' => [
            'slug'             => 'que-hacer-en-moratalla-fin-de-semana',
            'type'             => 'articulo',
            'phase'            => 'Escapada de 2 y 3 Días',
            'priority_rank'    => 15,
            'short_title'      => 'Moratalla en un Fin de Semana',
            'title'            => 'Qué Hacer en Moratalla un Fin de Semana | Itinerario de 2 y 3 Días',
            'h1'               => 'Qué Hacer en Moratalla un Fin de Semana: El Plan Perfecto Día por Día',
            'subtitle'         => 'Itinerario práctico de viernes a domingo combinando el Castillo, la Asunción, Casa de Cristo, el río Alhárabe y la gastronomía.',
            'meta_description' => '¿No sabes qué hacer en Moratalla un fin de semana? Te proponemos el mejor itinerario de 2 y 3 días con enlaces a todas las fichas de monumentos, rutas, restaurantes y casas rurales.',
            'keywords'         => 'qué hacer en Moratalla un fin de semana, fin de semana en Moratalla, escapada Moratalla 2 días, pueblos bonitos cerca de Moratalla',
            'icon'             => 'fas fa-calendar-check',
            'hero_image'       => 'uploads/seo/pilar-turismo-rural.svg',
            'logo_image'       => 'uploads/seo/pilar-turismo-rural.svg',
            'reading_time'     => '7 min de lectura',
            'word_target'      => 'Itinerario Fin de Semana',
            'map_query'        => 'Moratalla, Murcia',
            'related_cards'    => ['castillo', 'asuncion', 'casa_cristo', 'las_arcas', 'gastronomia_ficha', 'alojamientos_ficha'],
            'quick_facts'      => [
                ['label' => 'Día 1 (Sábado)', 'value' => 'Castillo (id=433), La Asunción (id=440), Comida Típica y Casa de Cristo (id=441)'],
                ['label' => 'Día 2 (Domingo)', 'value' => 'Río Alhárabe, Puente de Hellín (id=445), Las Arcas (id=446) y Mazapán'],
                ['label' => 'Día 3 (Opcional)', 'value' => 'Arte Rupestre (id=451), Benizar, El Sabinar (id=443) o Revolcadores']
            ],
            'sections' => [
                [
                    'id'      => 'sabado-patrimonio',
                    'title'   => 'Sábado: Historia viva, Castillo medieval y pinares de Casa de Cristo',
                    'content' => '<p><strong>Por la mañana:</strong> Llegada a Moratalla y subida desde el <a href="page.php?id=435">Convento de San Francisco</a> y la <a href="page.php?id=434">Ermita de Santa Ana</a> hasta la <a href="page.php?id=440"><strong>Parroquia de Santa María de la Asunción (29 fotos)</strong></a> y el <a href="page.php?id=433"><strong>Castillo fortaleza de Moratalla (28 fotos)</strong></a>.</p>
                    <p><strong>Al mediodía:</strong> Comida tradicional en uno de los <a href="restaurantes.php">restaurantes de Moratalla</a> degustando los platos de <a href="page.php?id=263">Gastronomía Moratallera</a>.</p>
                    <p><strong>Por la tarde:</strong> Subida al <a href="page.php?id=441"><strong>Santuario de la Casa de Cristo (15 fotos)</strong></a> y visita al Centro de Interpretación del <a href="page.php?id=451">Arte Rupestre</a> contemplando el atardecer desde sus miradores.</p>'
                ],
                [
                    'id'      => 'domingo-naturaleza',
                    'title'   => 'Domingo: Senderismo en el Río Alhárabe, Las Arcas y despedida dulce',
                    'content' => '<p><strong>Por la mañana:</strong> Ruta a pie por el <a href="guia.php?slug=rutas-de-senderismo-en-moratalla">Río Alhárabe</a>, cruzando el <a href="page.php?id=445">Puente de Hellín</a> y acercándose al acueducto de <a href="page.php?id=446"><strong>Las Arcas (21 fotos)</strong></a> o excursión a Benizar.</p>
                    <p><strong>Antes de volver:</strong> No olvides llevarte un auténtico Mazapán artesanal (<a href="page.php?id=428">ver Recetas de Moratalla</a>) y reservar tu próxima estancia en <a href="alojamientos.php">Alojamientos Rurales de Moratalla</a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Qué pueblos bonitos y parajes hay cerca del casco urbano de Moratalla?',
                    'a' => 'Dentro del término de Moratalla destacan pedanías como Benizar, Otos, Mazuza, Campo de San Juan, El Sabinar (page.php?id=443), Calar de la Santa (page.php?id=442), Cañada de la Cruz (page.php?id=444) y La Rogativa.'
                ]
            ],
            'related_slugs' => [
                'guia-completa-moratalla',
                'turismo-rural-moratalla',
                'rutas-de-senderismo-en-moratalla',
                'gastronomia-de-moratalla'
            ]
        ]
    ];
}

/**
 * Devuelve las 10 Guías Prioritarias con su enlace directo
 */
function getSeoTop10PriorityLinks(): array {
    return [
        [
            'rank'  => 1,
            'title' => 'Qué ver en Moratalla',
            'desc'  => 'Guía completa de imprescindibles, mapa y fichas con fotos.',
            'icon'  => 'fas fa-compass',
            'url'   => 'guia.php?slug=guia-completa-moratalla'
        ],
        [
            'rank'  => 2,
            'title' => 'Turismo rural en Moratalla',
            'desc'  => 'Casas rurales, zonas de montaña y escapadas en la naturaleza.',
            'icon'  => 'fas fa-tree',
            'url'   => 'guia.php?slug=turismo-rural-moratalla'
        ],
        [
            'rank'  => 3,
            'title' => 'Historia de Moratalla',
            'desc'  => 'Desde la Prehistoria y Al-Ándalus a la Orden de Santiago.',
            'icon'  => 'fas fa-landmark',
            'url'   => 'guia.php?slug=historia-de-moratalla'
        ],
        [
            'rank'  => 4,
            'title' => 'Castillo de Moratalla',
            'desc'  => 'La fortaleza medieval, Torre del Homenaje y tarjeta con 28 fotos.',
            'icon'  => 'fas fa-chess-rook',
            'url'   => 'guia.php?slug=castillo-de-moratalla'
        ],
        [
            'rank'  => 5,
            'title' => 'Pinturas rupestres de Moratalla',
            'desc'  => 'Arte prehistórico UNESCO, Casa de Cristo y Cañaica del Calar.',
            'icon'  => 'fas fa-palette',
            'url'   => 'guia.php?slug=pinturas-rupestres-de-moratalla'
        ],
        [
            'rank'  => 6,
            'title' => 'Semana Santa de Moratalla',
            'desc'  => 'Cofradías, procesiones y tarjeta especial con 87 fotografías.',
            'icon'  => 'fas fa-church',
            'url'   => 'guia.php?slug=semana-santa-de-moratalla'
        ],
        [
            'rank'  => 7,
            'title' => 'Tamboradas de Moratalla',
            'desc'  => 'Museo del Tambor (15 fotos), túnicas y Declaración UNESCO.',
            'icon'  => 'fas fa-drum',
            'url'   => 'guia.php?slug=tamboradas-de-moratalla'
        ],
        [
            'rank'  => 8,
            'title' => 'Gastronomía típica y dónde comer',
            'desc'  => 'Mazapán de Moratalla, recetario tradicional y restaurantes.',
            'icon'  => 'fas fa-utensils',
            'url'   => 'guia.php?slug=gastronomia-de-moratalla'
        ],
        [
            'rank'  => 9,
            'title' => 'Rutas de senderismo',
            'desc'  => 'Río Alhárabe, Puente de Hellín, Bolvonegro y Revolcadores.',
            'icon'  => 'fas fa-hiking',
            'url'   => 'guia.php?slug=rutas-de-senderismo-en-moratalla'
        ],
        [
            'rank'  => 10,
            'title' => 'Fiestas populares',
            'desc'  => 'Cristo del Rayo, Calendario de Fiestas, San Antón y pedanías.',
            'icon'  => 'fas fa-glass-cheers',
            'url'   => 'guia.php?slug=fiestas-populares-de-moratalla'
        ]
    ];
}
