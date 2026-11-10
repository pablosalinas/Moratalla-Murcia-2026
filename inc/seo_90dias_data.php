<?php
// inc/seo_90dias_data.php
// Repositorio estructurado del Plan SEO 90 Días: 5 Páginas Pilar, 10 Páginas Prioritarias, 8 Artículos y Contenido Estacional.

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

    // 1. Consultar preferencia persistente en base de datos (para que se mantenga en producción aunque pasen días o despliegues)
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
 * Optimización automática de Títulos SEO y Metadescripciones (Fase 1 - Semana 1)
 */
function getSeoOptimizedMeta(string $scriptName, array $context = []): array {
    $defaultTitle = "Moratalla (Murcia) | Guía Turística, Patrimonio Histórico y Cultura";
    $defaultDesc  = "Descubre Moratalla: patrimonio histórico, pinturas rupestres UNESCO, castillo medieval, turismo rural, Semana Santa, tambores y rutas naturales en el corazón de Murcia.";

    if ($scriptName === 'index.php') {
        return [
            'title'       => "Moratalla | Guía Turística, Patrimonio Histórico y Cultura en Murcia",
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
 * Contenido Estacional para Fase 3 (Días 61-90)
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
 * Devuelve el catálogo completo de las 5 Páginas Pilar + 10 Páginas Prioritarias + 8 Artículos del Plan SEO 90 Días
 */
function getSeo90Pages(): array {
    return [
        // =========================================================================
        // FASE 1: LAS 5 PÁGINAS PILAR (SEMANAS 3 Y 4)
        // =========================================================================
        'guia-completa-moratalla' => [
            'slug'             => 'guia-completa-moratalla',
            'type'             => 'pilar',
            'phase'            => 'Fase 1 · Página Pilar #1',
            'priority_rank'    => 1,
            'short_title'      => 'Guía Completa de Moratalla',
            'title'            => 'Qué Ver en Moratalla | Guía Completa de Turismo y Patrimonio Histórico',
            'h1'               => 'Guía Completa de Moratalla: Qué Ver, Qué Hacer y Cómo Planificar tu Visita',
            'subtitle'         => 'Todo lo que necesitas saber para descubrir el municipio con mayor riqueza forestal, histórica y rupestre de la Región de Murcia.',
            'meta_description' => '¿Buscas qué ver en Moratalla? Guía turística completa: Castillo-Fortaleza, Iglesia de la Asunción, pinturas rupestres UNESCO, río Alhárabe, dónde comer y dormir.',
            'keywords'         => 'Moratalla, qué ver en Moratalla, turismo en Moratalla, Moratalla Murcia, guía de Moratalla, qué hacer en Moratalla',
            'icon'             => 'fas fa-compass',
            'hero_image'       => 'uploads/theme/moratalla.jpg',
            'reading_time'     => '12 min de lectura',
            'word_target'      => 'Guía Pilar Principal',
            'map_query'        => 'Moratalla, Murcia, España',
            'quick_facts'      => [
                ['label' => 'Comarca', 'value' => 'Noroeste de la Región de Murcia'],
                ['label' => 'Altitud Casco Urbano', 'value' => '681 m s. n. m. (Pico máx: 2.015 m en Los Odres)'],
                ['label' => 'Patrimonio UNESCO', 'value' => 'Arte Rupestre del Arco Mediterráneo y Tamboradas'],
                ['label' => 'Imprescindibles', 'value' => 'Castillo-Fortaleza, Casco Antiguo, Río Alhárabe y Casa de Cristo']
            ],
            'sections' => [
                [
                    'id'      => 'introduccion',
                    'title'   => '1. Por qué Moratalla es la joya histórica y natural del Noroeste de Murcia',
                    'content' => '<p>Situada en el corazón de la comarca del Noroeste murciano, <strong>Moratalla</strong> es uno de los destinos más fascinantes del sureste ibérico. Su término municipal —de casi 1.000 km², uno de los más extensos de España— combina un imponente <strong>conjunto histórico-artístico medieval</strong> coronado por su Castillo-Fortaleza con las mayores masas forestales de la Región de Murcia, ríos de aguas cristalinas como el <strong>Alhárabe y el Benámor</strong>, y las cumbres más altas de la comunidad autónoma en el <strong>Macizo de Revolcadores (2.015 m)</strong>.</p>
                    <p>Además, Moratalla ostenta un doble reconocimiento como <strong>Patrimonio Mundial por la UNESCO</strong>: por un lado, concentra más de la mitad de los abrigos con <strong>pinturas rupestres prehistóricas</strong> de toda la Región de Murcia; por otro, sus vibrantes <strong>Tamboradas de Semana Santa</strong> forman parte del Patrimonio Cultural Inmaterial de la Humanidad.</p>'
                ],
                [
                    'id'      => 'imprescindibles',
                    'title'   => '2. Los 8 lugares imprescindibles que ver en Moratalla',
                    'content' => '<p>Si visitas Moratalla por primera vez, estos son los enclaves fundamentales que debes incluir en tu recorrido:</p>
                    <ul>
                        <li><strong>Castillo-Fortaleza de Moratalla y Torre del Homenaje:</strong> Fortaleza de origen islámico reconstruida en los siglos XV y XVI por la Orden de Santiago. Su Torre del Homenaje de estilo gótico-militar levantino es una de las más bellas de España.</li>
                        <li><strong>Iglesia de Nuestra Señora de la Asunción:</strong> Monumento declarado Bien de Interés Cultural (siglo XVI), célebre por sus columnas renacentistas y su monumentalidad sobre el caserío.</li>
                        <li><strong>Barrio Medieval de El Castillo y Calles Empinadas:</strong> Un laberinto de callejuelas blancas, cuestas empedradas, balcones floridos y miradores panorámicos.</li>
                        <li><strong>Convento de San Francisco y Ermita de Santa Ana:</strong> Testigos del esplendor barroco y de la religiosidad popular moratallera.</li>
                        <li><strong>Centro de Interpretación del Arte Rupestre (Casa de Cristo):</strong> Ubicado en un antiguo monasterio mercedario en pleno monte, a los pies de la Sierra del Buitre.</li>
                        <li><strong>Abrigos Rupestres de Cañaica del Calar y Fuente del Sabuco:</strong> Santuarios prehistóricos con escenas de caza y fauna de hace más de 6.000 años.</li>
                        <li><strong>Río Alhárabe, Camping La Puerta y Poza de Somogil:</strong> Pozas naturales de aguas templadas y bosque de galería único en el sureste.</li>
                        <li><strong>Pedanías altas (Benizar, Otos, Mazuza, Campo de San Juan, El Sabinar, Calar de la Santa y La Rogativa):</strong> Pueblos serranos con cascadas, castillos roqueros, sabinares milenarios y cielos estrellados.</li>
                    </ul>'
                ],
                [
                    'id'      => 'como-organizar',
                    'title'   => '3. Cómo organizar tu visita a Moratalla paso a paso',
                    'content' => '<p>Para aprovechar al máximo tu estancia en Moratalla te recomendamos dividir la experiencia en tres ejes complementarios:</p>
                    <p><strong>Mañana de Patrimonio e Historia:</strong> Comienza en la Plaza de la Iglesia y sube hacia la <a href="guia.php?slug=castillo-de-moratalla">Fortaleza de Moratalla</a> recorriendo la <a href="guia.php?slug=ruta-casco-antiguo-moratalla">ruta por el casco antiguo</a>. Visita el interior de Santa María de la Asunción y asómate a los miradores de la Placeta del Castillo.</p>
                    <p><strong>Mediodía Gastronómico:</strong> Haz una parada en los <a href="restaurantes.php">bares y restaurantes de Moratalla</a> para degustar un buen plato de arroz con conejo y caracoles, migas ruleras o carnes a la brasa de la sierra, acompañados de repostería local como el famoso <a href="guia.php?slug=gastronomia-de-moratalla">mazapán de Moratalla</a>.</p>
                    <p><strong>Tarde de Naturaleza y Arte Rupestre:</strong> A solo 10 minutos en coche del casco urbano, sube al entorno del Monte Público y <a href="guia.php?slug=monumentos-y-patrimonio-moratalla">Casa de Cristo</a> o realiza un paseo junto al río Alhárabe por el <a href="guia.php?slug=rutas-de-senderismo-en-moratalla">Estrecho de Bolvonegro</a>.</p>'
                ],
                [
                    'id'      => 'alojamiento-y-servicios',
                    'title'   => '4. Dónde dormir y dónde comer en Moratalla',
                    'content' => '<p>Moratalla es pionera del <a href="guia.php?slug=turismo-rural-moratalla">turismo rural en la Región de Murcia</a>. En nuestro directorio actualizado encontrarás más de 80 opciones en <a href="alojamientos.php">Alojamientos y Casas Rurales en Moratalla</a> (tanto en el casco histórico como en La Puerta, Benizar, Campo de San Juan o El Sabinar) y el listado completo de <a href="restaurantes.php">Bares y Restaurantes</a> con teléfonos y ubicación en mapa.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Cuántos días se necesitan para ver Moratalla?',
                    'a' => 'En 1 día puedes conocer el Castillo-Fortaleza, la Iglesia de la Asunción, el casco antiguo y el paraje de Casa de Cristo o el río Alhárabe. Sin embargo, lo ideal es dedicar un fin de semana completo (2 o 3 días) para incluir una ruta de senderismo (Bolvonegro, Somogil o Revolcadores) y visitar las pinturas rupestres y pedanías como Benizar o La Rogativa.'
                ],
                [
                    'q' => '¿Cuál es la mejor época para visitar Moratalla?',
                    'a' => 'Moratalla es un destino de cuatro estaciones: en primavera destacan sus cascadas y rutas verdes; en Semana Santa sus famosas Tamboradas; del 11 al 17 de julio las Fiestas del Santísimo Cristo del Rayo; y en otoño e invierno sus bosques, setas, gastronomía de cuchara y cumbres nevadas.'
                ],
                [
                    'q' => '¿Se puede visitar el Castillo de Moratalla por dentro?',
                    'a' => 'Sí, el recinto amurallado y la Torre del Homenaje del Castillo-Fortaleza de Moratalla son visitables. Se recomienda consultar los horarios actualizados en la Oficina de Turismo de Moratalla para acceder a las visitas guiadas.'
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
            'phase'            => 'Fase 1 · Página Pilar #2',
            'priority_rank'    => 2,
            'short_title'      => 'Turismo Rural en Moratalla',
            'title'            => 'Turismo Rural en Moratalla | Casas Rurales, Naturaleza y Escapadas en Murcia',
            'h1'               => 'Turismo Rural en Moratalla: Naturaleza, Casas Rurales y Montaña en Murcia',
            'subtitle'         => 'El pulmón verde de la Región de Murcia: pinares infinitos, ríos de montaña, aldeas con encanto y alojamientos rurales para desconectar.',
            'meta_description' => 'Guía de turismo rural en Moratalla (Murcia): mejores zonas donde alojarse en casas rurales, ríos Alhárabe y Benámor, Revolcadores, pedanías y naturaleza.',
            'keywords'         => 'turismo rural en Moratalla, casas rurales Moratalla, alojamientos Moratalla, naturaleza Moratalla, escapada rural Murcia',
            'icon'             => 'fas fa-tree',
            'hero_image'       => 'uploads/banners/banner_1778708226.jpg',
            'reading_time'     => '10 min de lectura',
            'word_target'      => 'Guía Pilar Turismo Rural',
            'map_query'        => 'Río Alhárabe Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Superficie Forestal', 'value' => 'Más de 55.000 hectáreas de montes y pinares'],
                ['label' => 'Cota Máxima', 'value' => 'Los Odres (1.878 m) y Revolcadores (2.015 m)'],
                ['label' => 'Ríos Principales', 'value' => 'Río Alhárabe, Río Benámor y Arroyo Blanco'],
                ['label' => 'Directorio Local', 'value' => 'Acceso directo a Casas Rurales, Camping y Hostales']
            ],
            'sections' => [
                [
                    'id'      => 'capital-turismo-rural',
                    'title'   => '1. Moratalla, capital del turismo rural y de interior en la Región de Murcia',
                    'content' => '<p>Cuando se piensa en Murcia suele venir a la mente la costa, pero al noroeste se alza un paisaje completamente distinto: <strong>Moratalla</strong>, conocida como la "Suiza murciana" por su relieve montañoso, sus valles surcados por el agua y sus extensos bosques de pino carrasco, pino laricio, encinas y sabinas albares.</p>
                    <p>El <strong>turismo rural en Moratalla</strong> comenzó a desarrollarse de forma pionera en la década de 1990, rehabilitando antiguos cortijos, molinos harineros y casas de labranza para ofrecer una experiencia auténtica de descanso en contacto directo con la naturaleza.</p>'
                ],
                [
                    'id'      => 'mejores-zonas-alojamiento',
                    'title'   => '2. Mejores zonas donde alojarse en una casa rural en Moratalla',
                    'content' => '<p>Dependiendo del tipo de escapada que busques —en pareja, con niños o con grupo de amigos—, Moratalla ofrece distintas áreas naturales:</p>
                    <ul>
                        <li><strong>Entorno del Río Alhárabe y La Puerta:</strong> Ideal para familias y amantes del senderismo fluvial, a pocos kilómetros del Estrecho de Bolvonegro y la Poza de Somogil.</li>
                        <li><strong>Casa de Cristo y Sierra del Buitre:</strong> En pleno monte público, rodeado de pinares centenarios y miradores panorámicos sobre la vega de Moratalla.</li>
                        <li><strong>Pedanías altas (Campo de San Juan, El Sabinar, Calar de la Santa y Zaén):</strong> A más de 1.100 metros de altitud, perfectas para ver la nieve en invierno, disfrutar de noches frescas en pleno agosto y contemplar cielos estrellados sin contaminación lumínica.</li>
                        <li><strong>Benizar, Otos y Mazuza:</strong> Riqueza hídrica, nacimientos de agua, cascadas de travertino y el histórico Castillo de Benizar.</li>
                        <li><strong>Casco Histórico de Moratalla:</strong> Para quienes prefieren ir andando a los monumentos, tabernas, comercios y vivir el ambiente tradicional del pueblo.</li>
                    </ul>
                    <p>Consulta nuestro catálogo completo con contacto directo de propietarios en la sección <a href="alojamientos.php"><strong>Dónde Dormir · Alojamientos en Moratalla</strong></a>.</p>'
                ],
                [
                    'id'      => 'actividades-naturaleza',
                    'title'   => '3. Experiencias y turismo activo durante todo el año',
                    'content' => '<p>Más allá del descanso, el entorno natural de Moratalla permite practicar <a href="guia.php?slug=rutas-de-senderismo-en-moratalla">senderismo por gargantas y cumbres</a>, rutas en bicicleta de montaña (BTT), observación de aves rapaces (buitre leonado, águila real y halcón peregrino), astroturismo y rutas micológicas en otoño.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde buscar casas rurales en Moratalla con contacto directo?',
                    'a' => 'En la sección Alojamientos de moratalla-murcia.com dispones de un buscador filtrable por pedanía y localidad con teléfonos directos, correo electrónico, página web y ubicación en Google Maps de cada casa rural, camping u hostal.'
                ],
                [
                    'q' => '¿Es Moratalla un buen destino de turismo rural con niños?',
                    'a' => 'Excelente. Paseos llanos como el primer tramo del río Alhárabe junto a La Puerta, la explanada de Casa de Cristo, el Castillo de Moratalla y el Centro de Arte Rupestre son planes muy didácticos y seguros para toda la familia.'
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
            'phase'            => 'Fase 1 · Página Pilar #3',
            'priority_rank'    => 3,
            'short_title'      => 'Historia de Moratalla',
            'title'            => 'Historia de Moratalla (Murcia) | De la Prehistoria a la Orden de Santiago',
            'h1'               => 'Historia de Moratalla: Milenios de Memoria desde el Arte Rupestre hasta Nuestros Días',
            'subtitle'         => 'Un recorrido riguroso por las raíces prehistóricas, íberas, romanas, andalusíes y santiaguistas que forjaron la identidad de Moratalla.',
            'meta_description' => 'Conoce la historia de Moratalla (Murcia): asentamientos prehistóricos y pinturas rupestres, pasado íbero y romano, fortaleza islámica y Encomienda de la Orden de Santiago.',
            'keywords'         => 'historia de Moratalla, historia de Moratalla Murcia, Orden de Santiago Moratalla, origen de Moratalla, patrimonio histórico Moratalla',
            'icon'             => 'fas fa-landmark',
            'hero_image'       => 'uploads/banners/banner_1778706093.jpg',
            'reading_time'     => '11 min de lectura',
            'word_target'      => 'Guía Pilar Historia',
            'map_query'        => 'Castillo de Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Primeros Pobladores', 'value' => 'Paleolítico Superior, Epipaleolítico y Neolítico'],
                ['label' => 'Hito Prehistórico', 'value' => 'Más de 60 abrigos con Arte Rupestre Levantino y Esquemático'],
                ['label' => 'Edad Media', 'value' => 'Hisn islámico (Murata) y Encomienda de la Orden de Santiago (1245)'],
                ['label' => 'Edad Moderna', 'value' => 'Construcción de Santa María de la Asunción y voto al Cristo del Rayo (1621)']
            ],
            'sections' => [
                [
                    'id'      => 'prehistoria-arte-rupestre',
                    'title'   => '1. Prehistoria: El gran santuario del Arte Rupestre en Murcia',
                    'content' => '<p>La presencia humana en las tierras de Moratalla se remonta a hace más de 10.000 años. La abundancia de agua, caza y abrigos rocosos calizos convirtió sus sierras en un enclave privilegiado durante el Epipaleolítico, el Neolítico y el Calcolítico. Fruto de aquellas sociedades cazadoras-recolectoras y de los primeros pastores son las célebres <a href="guia.php?slug=pinturas-rupestres-de-moratalla"><strong>Pinturas Rupestres de Moratalla</strong></a> (Cañaica del Calar, Fuente del Sabuco, Risca, Hondares, Molino de Capel), declaradas Patrimonio Mundial por la UNESCO en 1998.</p>
                    <p>En el Calcolítico y la Edad del Bronce (cultura argárica) destacan poblados fortificados de altura como el de <strong>Los Molinicos</strong> (en el río Alhárabe), que controlaba las rutas naturales entre el Levante y la Alta Andalucía.</p>'
                ],
                [
                    'id'      => 'iberos-romanos-andalusies',
                    'title'   => '2. Íberos, Romanos y la Murata andalusí',
                    'content' => '<p>Durante la época íbera y la posterior romanización, el territorio de Moratalla articuló villas agrícolas vinculadas al aprovechamiento del esparto, el cereal, el aceite y la ganadería, con importantes vestigios en parajes como Los Villaricos. Ya en época islámica (siglos IX-XIII), se consolidó el emplazamiento defensivo sobre el cerro del castillo y el de Benizar, formando parte de la cora de Todmir y posteriormente de la taifa de Murcia.</p>'
                ],
                [
                    'id'      => 'orden-de-santiago',
                    'title'   => '3. La Reconquista y la Encomienda de la Orden de Santiago',
                    'content' => '<p>Tras el Tratado de Alcaraz (1243), Alfonso X el Sabio entregó Moratalla a la poderosa <strong>Orden Militar de Santiago</strong>, convirtiéndose en sede de una importante Encomienda fronteriza frente al Reino Nazarí de Granada. En el siglo XV, los comendadores santiaguistas levantaron la monumental <strong>Torre del Homenaje</strong> del <a href="guia.php?slug=castillo-de-moratalla">Castillo de Moratalla</a>, dotándola de su característica bóveda gótica y sus almenas.</p>'
                ],
                [
                    'id'      => 'edad-moderna-y-contemporanea',
                    'title'   => '4. Renacimiento, el milagro del Cristo del Rayo y la memoria contemporánea',
                    'content' => '<p>Con el fin de la frontera granadina en 1492, Moratalla vivió una etapa de expansión urbana y demográfica en los siglos XVI y XVII. Se inició la construcción de la monumental <strong>Iglesia de Nuestra Señora de la Asunción</strong> (1521), se fundó el Convento de San Francisco y el de la Casa de Cristo. El 15 de junio de 1621 tuvo lugar el histórico suceso de la tormenta y el rayo en el templo parroquial sin causar víctimas, origen de la devoción al <strong>Santísimo Cristo del Rayo</strong> y de sus centenarios encierros taurinos por vereda.</p>
                    <p>Explora todos los documentos originales, fotografías antiguas y crónicas en la sección de <a href="category.php?id=127"><strong>Cultura e Historia del Archivo Digital</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿De dónde proviene el nombre de Moratalla?',
                    'a' => 'Existen diversas hipótesis históricas y filológicas: desde topónimos prerromanos y latinos vinculados a "Murata" (lugar amurallado o fortificado) hasta su evolución fonética en época andalusí antes de pasar a la Orden de Santiago en el siglo XIII.'
                ],
                [
                    'q' => '¿Qué ocurrió en Moratalla el 15 de junio de 1621?',
                    'a' => 'Durante la celebración de la octava del Corpus Christi en la Iglesia de la Asunción, una gran tempestad descargó una centella o rayo dentro del templo repleto de fieles, impactando en la imagen del crucificado sin que ningún vecino perdiera la vida. Desde entonces se venera como el Santísimo Cristo del Rayo.'
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
            'phase'            => 'Fase 1 · Página Pilar #4',
            'priority_rank'    => 4,
            'short_title'      => 'Monumentos y Patrimonio',
            'title'            => 'Monumentos y Patrimonio de Moratalla | Castillo, Iglesias y Santuarios',
            'h1'               => 'Monumentos y Patrimonio Histórico-Artístico de Moratalla',
            'subtitle'         => 'Guía arquitectónica y patrimonial: fortalezas medievales, templos renacentistas, ermitas serranas y puentes históricos.',
            'meta_description' => 'Descubre los monumentos de Moratalla: Castillo-Fortaleza, Iglesia de Santa María de la Asunción, Santuario de la Rogativa, Casa de Cristo y Castillo de Benizar.',
            'keywords'         => 'monumentos de Moratalla, patrimonio de Moratalla, Castillo de Moratalla, Iglesia de Santa María de la Asunción Moratalla, Casa de Cristo Moratalla',
            'icon'             => 'fas fa-chess-rook',
            'hero_image'       => 'uploads/banners/banner_1778708236.jpg',
            'reading_time'     => '10 min de lectura',
            'word_target'      => 'Guía Pilar Patrimonio',
            'map_query'        => 'Iglesia de Nuestra Señora de la Asunción, Moratalla',
            'quick_facts'      => [
                ['label' => 'Bien de Interés Cultural', 'value' => 'Conjunto Histórico-Artístico, Castillo e Iglesia de la Asunción'],
                ['label' => 'Fortalezas', 'value' => 'Castillo-Fortaleza de Moratalla, Castillo de Benizar y Castellar'],
                ['label' => 'Arquitectura Religiosa', 'value' => 'Santa María de la Asunción, San Francisco, Santa Ana, Casa de Cristo y La Rogativa'],
                ['label' => 'Ingeniería Histórica', 'value' => 'Puente de Hellín sobre el río Alhárabe y acueductos tradicionales']
            ],
            'sections' => [
                [
                    'id'      => 'castillo-fortaleza',
                    'title'   => '1. El Castillo-Fortaleza de Moratalla y su Torre del Homenaje',
                    'content' => '<p>Coronando el cerro sobre el que se escalona la villa, el <a href="guia.php?slug=castillo-de-moratalla"><strong>Castillo-Fortaleza de Moratalla</strong></a> es el símbolo indiscutible del municipio. Cuenta con seis torres (la del Homenaje, la Blanca, la del Alcaide, la de los Cuatro Vientos, la Redonda y la de la Magdalena), un gran patio de armas con aljibe y la célebre <strong>Torre del Homenaje</strong> del siglo XV, dividida en tres plantas con la espectacular Sala del Trono cubierta por bóveda de crucería gótica.</p>'
                ],
                [
                    'id'      => 'iglesia-asuncion',
                    'title'   => '2. Iglesia Parroquial de Nuestra Señora de la Asunción',
                    'content' => '<p>Declarada Monumento Histórico-Artístico Nacional en 1981, la <strong>Iglesia de Santa María de la Asunción</strong> comenzó a edificarse en 1521 bajo traza de Francisco Florentín y el impulso de los maestros canteros renacentistas. Su interior sorprende por su planta de salón (<em>Hallenkirche</em>) sostenida por monumentales columnas jónicas y toscanas, y alberga la venerada imagen del Santísimo Cristo del Rayo.</p>'
                ],
                [
                    'id'      => 'santuarios-y-conventos',
                    'title'   => '3. Convento de San Francisco, Casa de Cristo y Santuario de la Rogativa',
                    'content' => '<ul>
                        <li><strong>Iglesia y Antiguo Convento de San Francisco:</strong> Joya barroca en el corazón urbano de Moratalla, muy vinculada a la historia cofrade y musical del municipio.</li>
                        <li><strong>Santuario y Monasterio de la Casa de Cristo:</strong> Enclavado a más de 800 metros de altitud entre pinares, alberga hoy el Centro Regional de Interpretación del Arte Rupestre.</li>
                        <li><strong>Santuario de Nuestra Señora de la Rogativa:</strong> Situado en un fértil valle serrano al pie de Revolcadores, conserva pinturas murales históricas y una devoción secular.</li>
                        <li><strong>Castillo de Benizar:</strong> Fortaleza roquera islámica del siglo XII alzada sobre un imponente peñón vertical que domina la vega de Benizar.</li>
                    </ul>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Cuáles son los principales monumentos de Moratalla en el casco urbano?',
                    'a' => 'Los principales monumentos del casco histórico son el Castillo-Fortaleza con su Torre del Homenaje, la Iglesia de Nuestra Señora de la Asunción, el antiguo Convento de San Francisco, la Ermita de Santa Ana, el Ayuntamiento y el trazado medieval de sus barrios.'
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
            'phase'            => 'Fase 1 · Página Pilar #5',
            'priority_rank'    => 5,
            'short_title'      => 'Gastronomía de Moratalla',
            'title'            => 'Gastronomía de Moratalla | Platos Típicos, Mazapán y Dónde Comer',
            'h1'               => 'Gastronomía de Moratalla: Sabores de Sierra, Recetas Tradicionales y Mazapán Artesano',
            'subtitle'         => 'Descubre la cocina serrana de Moratalla: guisos de cuchara, arroces con aroma a leña, embutidos caseros y el inconfundible mazapán moratallero.',
            'meta_description' => 'Todo sobre la gastronomía de Moratalla: platos típicos como migas, arroz con conejo y caracoles, andrajos, el famoso mazapán de Moratalla y dónde comer.',
            'keywords'         => 'gastronomía de Moratalla, dónde comer en Moratalla, platos típicos de Moratalla, mazapán de Moratalla, recetas de Moratalla',
            'icon'             => 'fas fa-utensils',
            'hero_image'       => 'uploads/banners/banner_1778708256.jpg',
            'reading_time'     => '9 min de lectura',
            'word_target'      => 'Guía Pilar Gastronomía',
            'map_query'        => 'Restaurantes en Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Dulce Estrella', 'value' => 'Mazapán de Moratalla (almendra, yema y cabello de ángel)'],
                ['label' => 'Platos de Cuchara y Sartén', 'value' => 'Migas ruleras, Olla de cerdo, Andrajos, Ajoharina y Arroz con conejo y caracoles'],
                ['label' => 'Productos de la Tierra', 'value' => 'Aceite de oliva virgen extra, miel de romero, almendra, cordero segureño y guíscanos'],
                ['label' => 'Guía de Mesas', 'value' => 'Directorio completo de Bares y Restaurantes + Recetario Tradicional Digital']
            ],
            'sections' => [
                [
                    'id'      => 'identidad-culinaria',
                    'title'   => '1. La cocina de montaña del Noroeste murciano',
                    'content' => '<p>La <strong>gastronomía de Moratalla</strong> es el reflejo fiel de su geografía serrana y de su historia agrícola y pastoril. Aquí confluyen la tradición huertana murciana con la contundencia de la cocina manchega, granadina y jaenera de la Sierra del Segura. El aceite de oliva de variedades autóctonas, las hierbas aromáticas del monte (romero, tomillo, ajedrea) y la leña de encina o sarmiento marcan el carácter de cada plato.</p>'
                ],
                [
                    'id'      => 'platos-imprescindibles',
                    'title'   => '2. Los platos típicos que debes probar en Moratalla',
                    'content' => '<ul>
                        <li><strong>Arroz con conejo y caracoles serranos:</strong> Cocinado a fuego vivo con sarmientos, azafrán y el toque inconfundible de los caracoles chonetes o serranos.</li>
                        <li><strong>Migas ruleras (de harina o de pan):</strong> Indispensables en los días de lluvia o frío, acompañadas de tajadas de tocino, longaniza, morcilla, ajos tiernos, uvas o granadas.</li>
                        <li><strong>Andrajos y Ajoharina:</strong> Guisos tradicionales de invierno con tortas finas de masa cocidas en un sabroso caldo con caza, liebre, conejo o bacalao.</li>
                        <li><strong>Olla fresca o potaje moratallero:</strong> Plato de cuchara reconfortante con legumbres, verduras de la huerta y embutidos de la matanza tradicional.</li>
                        <li><strong>Cordero segureño a la brasa o al horno:</strong> Criado en los pastos de altura de las pedanías de Moratalla.</li>
                    </ul>'
                ],
                [
                    'id'      => 'mazapan-y-reposteria',
                    'title'   => '3. El Mazapán de Moratalla y la repostería de horno',
                    'content' => '<p>Mención aparte merece el célebre <strong>Mazapán de Moratalla</strong>. A diferencia de otros mazapanes de la península, el de Moratalla se elabora artesanalmente en piezas grandes o individuales con una fina pasta de almendra seleccionada, rellena de un exquisito dulce de <strong>yema de huevo y cabello de ángel</strong>, horneado hasta lograr una corteza ligeramente dorada. Junto a él brillan las tortas de Pascua, los suspiros, los rollos de vino y anís, las flores de sartén y las torrijas de Semana Santa.</p>
                    <p>¿Quieres preparar estas recetas paso a paso o saber dónde degustarlas hoy mismo? Visita nuestro <a href="restaurantes.php"><strong>Directorio de Bares y Restaurantes de Moratalla</strong></a> y explora la sección de <a href="category.php?id=80"><strong>Gastronomía y Recetario Tradicional</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Qué diferencia al Mazapán de Moratalla de otros mazapanes?',
                    'a' => 'El mazapán tradicional de Moratalla se distingue por su formato de pastel horneado cuya masa de almendra y azúcar envuelve un jugoso relleno artesanal de cabello de ángel y yema confitada, consumiéndose durante todo el año y no solo en Navidad.'
                ],
                [
                    'q' => '¿Dónde comer comida típica en Moratalla?',
                    'a' => 'Tanto en el casco urbano de Moratalla como en el entorno de La Puerta, Casa de Cristo y pedanías como Benizar, Campo de San Juan o El Sabinar encontrarás mesones y restaurantes tradicionales. Puedes filtrarlos por localidad en restaurantes.php.'
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
        // FASE 2 Y 3: LOS 8 ARTÍCULOS SEO + LAS 10 PÁGINAS PRIORITARIAS
        // =========================================================================
        '10-lugares-imprescindibles-que-ver-en-moratalla' => [
            'slug'             => '10-lugares-imprescindibles-que-ver-en-moratalla',
            'type'             => 'articulo',
            'phase'            => 'Fase 2 · Artículo #1',
            'priority_rank'    => 6,
            'short_title'      => '10 Lugares Que Ver en Moratalla',
            'title'            => '10 Lugares Imprescindibles Que Ver en Moratalla (Con Mapa y Consejos)',
            'h1'               => '10 Lugares Imprescindibles Que Ver en Moratalla en tu Próxima Escapada',
            'subtitle'         => 'De la Torre del Homenaje a las pozas del río Alhárabe: la selección definitiva para no perderte nada.',
            'meta_description' => 'Descubre los 10 lugares imprescindibles que ver en Moratalla (Murcia): Castillo-Fortaleza, casco antiguo, río Alhárabe, Bolvonegro, Casa de Cristo y más.',
            'keywords'         => 'qué ver en Moratalla, 10 lugares que ver en Moratalla, sitios que visitar en Moratalla, turismo Moratalla',
            'icon'             => 'fas fa-map-marked-alt',
            'hero_image'       => 'uploads/theme/moratalla.jpg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Artículo SEO #1',
            'map_query'        => 'Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Tiempo Recomendado', 'value' => '1 a 3 días'],
                ['label' => 'Enclaves Urbanos', 'value' => 'Castillo, Iglesia de la Asunción, Casco Antiguo y San Francisco'],
                ['label' => 'Enclaves Naturales', 'value' => 'Río Alhárabe, Bolvonegro, Somogil, Casa de Cristo y Revolcadores']
            ],
            'sections' => [
                [
                    'id'      => 'top-1-a-5',
                    'title'   => 'Del 1 al 5: Patrimonio histórico y miradores urbanos',
                    'content' => '<ol>
                        <li><strong>Castillo-Fortaleza de Moratalla:</strong> Su silueta almenada y su Torre del Homenaje del siglo XV ofrecen las mejores vistas sobre el valle y la huerta.</li>
                        <li><strong>Iglesia de Nuestra Señora de la Asunción:</strong> Obra cumbre del Renacimiento en Murcia, con un interior de columnas monumentales.</li>
                        <li><strong>Laberinto del Casco Antiguo (Barrio del Castillo):</strong> Pasea sin prisas por sus calles empinadas, fachadas encaladas y rincones llenos de flores.</li>
                        <li><strong>Convento de San Francisco y Plaza de la Constitución:</strong> El pulso social e histórico de la villa.</li>
                        <li><strong>Monasterio de la Casa de Cristo y Centro de Arte Rupestre:</strong> A 7 km del pueblo, rodeado de pinares y miradores espectaculares.</li>
                    </ol>'
                ],
                [
                    'id'      => 'top-6-a-10',
                    'title'   => 'Del 6 al 10: Naturaleza salvaje, arte prehistórico y aldeas',
                    'content' => '<ol start="6">
                        <li><strong>Río Alhárabe y paraje de La Puerta:</strong> Bosque de galería, aguas limpias y senderos familiares bajo chopos y pinos.</li>
                        <li><strong>Estrecho de Bolvonegro:</strong> Un desfiladero fluvial tallado en roca oscura de enorme valor geológico y paisajístico.</li>
                        <li><strong>Poza Termal de Somogil:</strong> Rincón natural donde el agua brota a temperatura templada entre paredones de roca.</li>
                        <li><strong>Abrigos de Cañaica del Calar (Pinturas Rupestres UNESCO):</strong> Arte prehistórico en un entorno de montaña sobrecogedor.</li>
                        <li><strong>Benizar, La Rogativa y el Macizo de Revolcadores:</strong> Castillos roqueros, cascadas y la cumbre más alta de la Región de Murcia.</li>
                    </ol>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Qué ver en Moratalla en un solo día?',
                    'a' => 'Por la mañana recorre el Castillo-Fortaleza, la Iglesia de la Asunción y el casco antiguo; come en un restaurante tradicional del pueblo y por la tarde sube a Casa de Cristo o pasea junto al río Alhárabe en La Puerta.'
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
            'phase'            => 'Fase 2 · Artículo #2',
            'priority_rank'    => 7,
            'short_title'      => 'Ruta por el Casco Antiguo',
            'title'            => 'Ruta por el Casco Antiguo de Moratalla | Calles, Miradores e Historia',
            'h1'               => 'Ruta a Pie por el Casco Antiguo de Moratalla: Del Convento de San Francisco al Castillo',
            'subtitle'         => 'Un itinerario circular paso a paso por las callejuelas medievales, placetas y miradores más fotogénicos de Moratalla.',
            'meta_description' => 'Recorre la ruta por el casco antiguo de Moratalla a pie: calle Mayor, Iglesia de la Asunción, barrio medieval, miradores y subida al Castillo-Fortaleza.',
            'keywords'         => 'casco antiguo de Moratalla, calles de Moratalla, ruta urbana Moratalla, miradores de Moratalla',
            'icon'             => 'fas fa-route',
            'hero_image'       => 'uploads/banners/banner_1778708236.jpg',
            'reading_time'     => '7 min de lectura',
            'word_target'      => 'Artículo SEO #2',
            'map_query'        => 'Calle Mayor, Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Duración del Paseo', 'value' => '1 hora y 30 minutos (a ritmo tranquilo con fotos)'],
                ['label' => 'Dificultad', 'value' => 'Baja-Media (calles con pendiente hacia el Castillo)'],
                ['label' => 'Mejor Luz para Fotos', 'value' => 'Primera hora de la mañana o al atardecer']
            ],
            'sections' => [
                [
                    'id'      => 'tramo-bajo',
                    'title'   => 'Parada 1: La Corredera, San Francisco y la Calle Mayor',
                    'content' => '<p>El recorrido comienza en la parte baja del centro histórico, pasando junto al antiguo <strong>Convento de San Francisco</strong> y adentrándose en la <strong>Calle Mayor</strong> y el Ayuntamiento, donde aún se conservan casas señoriales con escudos nobiliarios y rejería tradicional.</p>'
                ],
                [
                    'id'      => 'ascenso-castillo',
                    'title'   => 'Parada 2: Plaza de la Iglesia y subida por el barrio medieval',
                    'content' => '<p>Al llegar a la <strong>Iglesia de Nuestra Señora de la Asunción</strong>, el espacio se abre en un balcón natural sobre la vega. Desde aquí, las callejuelas estrechas y empedradas ascienden serpenteando hasta las murallas del <a href="guia.php?slug=castillo-de-moratalla">Castillo-Fortaleza</a>, regalando una de las estampas urbanas más auténticas de la Región de Murcia.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde aparcar para visitar el casco antiguo de Moratalla?',
                    'a' => 'Lo más cómodo es aparcar en las zonas habilitadas en la entrada del municipio o en el entorno del Parque de la Corredera, y realizar la subida al casco histórico y al Castillo caminando.'
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
            'phase'            => 'Fase 2 · Artículo #3 · Prioritaria #4',
            'priority_rank'    => 8,
            'short_title'      => 'Castillo de Moratalla',
            'title'            => 'Castillo de Moratalla | Historia, Torre del Homenaje y Visita a la Fortaleza',
            'h1'               => 'El Castillo-Fortaleza de Moratalla y su Imponente Torre del Homenaje',
            'subtitle'         => 'Descubre la historia, arquitectura gótico-militar y leyendas de la fortaleza santiaguista más emblemática del Noroeste murciano.',
            'meta_description' => 'Guía completa del Castillo de Moratalla (Murcia): historia de la fortaleza de la Orden de Santiago, la Torre del Homenaje, la Sala del Trono y cómo visitarlo.',
            'keywords'         => 'Castillo de Moratalla, Fortaleza de Moratalla, Torre del Homenaje Moratalla, visitar Castillo de Moratalla',
            'icon'             => 'fas fa-chess-rook',
            'hero_image'       => 'uploads/banners/banner_1778706093.jpg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Artículo SEO #3 / Prioritaria',
            'map_query'        => 'Castillo de Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Época de Construcción', 'value' => 'Origen islámico (s. IX-XII) y reforma santiaguista (s. XV-XVI)'],
                ['label' => 'Elemento Destacado', 'value' => 'Torre del Homenaje de 25 m de altura y Sala del Trono gótica'],
                ['label' => 'Protección', 'value' => 'Bien de Interés Cultural (BIC) y Patrimonio Histórico de España']
            ],
            'sections' => [
                [
                    'id'      => 'arquitectura-fortaleza',
                    'title'   => '1. Arquitectura militar: Las seis torres y el Patio de Armas',
                    'content' => '<p>El <strong>Castillo-Fortaleza de Moratalla</strong> se asienta sobre un peñón rocoso que domina todo el corredor natural del Alhárabe. Sobre la primitiva alcazaba andalusí, los comendadores de la <strong>Orden de Santiago</strong> levantaron en el siglo XV una fortaleza cristiana artillada capaz de defender la frontera con el Reino de Granada. Su recinto amurallado conserva seis torres, aljibe subterráneo y paseo de ronda.</p>'
                ],
                [
                    'id'      => 'torre-del-homenaje',
                    'title'   => '2. La Torre del Homenaje y la Sala del Trono',
                    'content' => '<p>La joya del conjunto es su <strong>Torre del Homenaje</strong>, adscrita al llamado estilo gótico-militar levantino (emparentada con la de Villena o Biar). En su interior destaca la <strong>Sala del Trono</strong>, cubierta por una soberbia bóveda de crucería estrellada sobre ménsulas labradas, junto con sus ventanas geminadas con asientos de piedra (festejadores) desde los que se divisa la Sierra del Buitre y la huerta de Moratalla.</p>'
                ]
            ],
            'faqs' => [
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
            'phase'            => 'Fase 2 · Artículo #4 · Prioritaria #5',
            'priority_rank'    => 9,
            'short_title'      => 'Pinturas Rupestres UNESCO',
            'title'            => 'Pinturas Rupestres de Moratalla | Patrimonio UNESCO y Centro de Arte Rupestre',
            'h1'               => 'Las Pinturas Rupestres de Moratalla Explicadas: Patrimonio de la Humanidad UNESCO',
            'subtitle'         => 'Con más de 60 cavidades catalogadas, Moratalla concentra el mayor tesoro de arte rupestre prehistórico de la Región de Murcia.',
            'meta_description' => 'Descubre las pinturas rupestres de Moratalla (Patrimonio UNESCO): Cañaica del Calar, Fuente del Sabuco, Casa de Cristo y cómo visitar el arte levantino.',
            'keywords'         => 'pinturas rupestres de Moratalla, arte rupestre Moratalla, Cañaica del Calar, Casa de Cristo Moratalla, Patrimonio UNESCO Moratalla',
            'icon'             => 'fas fa-palette',
            'hero_image'       => 'uploads/banners/banner_1778708226.jpg',
            'reading_time'     => '9 min de lectura',
            'word_target'      => 'Artículo SEO #4 / Prioritaria',
            'map_query'        => 'Centro de Arte Rupestre Casa de Cristo, Moratalla',
            'quick_facts'      => [
                ['label' => 'Declaración UNESCO', 'value' => '1998 (Arte Rupestre del Arco Mediterráneo de la Península Ibérica)'],
                ['label' => 'Estilos Presentes', 'value' => 'Arte Levantino (naturalista) y Arte Esquemático'],
                ['label' => 'Conjuntos Principales', 'value' => 'Cañaica del Calar, Fuente del Sabuco, La Risca, Hondares y Molino de Capel'],
                ['label' => 'Punto de Partida', 'value' => 'Centro de Interpretación del Arte Rupestre en Casa de Cristo']
            ],
            'sections' => [
                [
                    'id'      => 'importancia-unesco',
                    'title'   => '1. Más de la mitad del arte rupestre de Murcia está en Moratalla',
                    'content' => '<p>En 1998 la UNESCO declaró Patrimonio Mundial el <strong>Arte Rupestre del Arco Mediterráneo de la Península Ibérica</strong>. Dentro de la Región de Murcia, el término municipal de Moratalla alberga más del 50% de los abrigos y estaciones rupestres catalogados, convirtiéndose en una referencia arqueológica europea para comprender la vida y las creencias de las comunidades prehistóricas entre el 6000 y el 2000 a. C.</p>'
                ],
                [
                    'id'      => 'canaica-sabuco-casa-cristo',
                    'title'   => '2. Cañaica del Calar, Fuente del Sabuco y el Centro de Casa de Cristo',
                    'content' => '<p>En los abrigos de <strong>Cañaica del Calar</strong> y <strong>Fuente del Sabuco</strong> (en el entorno de Calar de la Santa y El Sabinar) conviven el <strong>Arte Levantino</strong> —con estilizadas figuras de arqueros, ciervos, cabras montesas y escenas de caza llenas de movimiento— y el <strong>Arte Esquemático</strong> de los primeros agricultores y pastores neolíticos.</p>
                    <p>Antes de subir a los abrigos, es imprescindible visitar el <strong>Centro de Interpretación del Arte Rupestre en Casa de Cristo</strong>, que ofrece reproducciones a escala real, recursos interactivos y visitas guiadas.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Dónde ver las pinturas rupestres de Moratalla?',
                    'a' => 'La visita debe iniciarse en el Centro Regional de Interpretación del Arte Rupestre situado en el Monasterio de la Casa de Cristo (a 7 km de Moratalla), donde se explica todo el legado prehistórico y se coordinan las rutas interpretativas hacia conjuntos como Cañaica del Calar.'
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
            'phase'            => 'Fase 1 y 3 · Prioritaria #6',
            'priority_rank'    => 10,
            'short_title'      => 'Semana Santa de Moratalla',
            'title'            => 'Semana Santa de Moratalla | Tambores, Cofradías y Tradición Única',
            'h1'               => 'Semana Santa de Moratalla: Pasión, Túnicas de Colores y el Estruendo del Tambor',
            'subtitle'         => 'Descubre cómo conviven el recogimiento de las procesiones centenarias y la explosión colectiva de las Tamboradas de Moratalla.',
            'meta_description' => 'Guía de la Semana Santa de Moratalla: días de tamborada (Jueves Santo, Viernes Santo y Domingo de Resurrección), cofradías, procesiones y tradición.',
            'keywords'         => 'Semana Santa de Moratalla, tambores de Moratalla, procesiones Moratalla, Jueves Santo Moratalla, Viernes Santo Moratalla',
            'icon'             => 'fas fa-church',
            'hero_image'       => 'uploads/asociaciones/asoc_nazareno_padrejesusnazareno_0.jpg',
            'reading_time'     => '9 min de lectura',
            'word_target'      => 'Página Prioritaria Semana Santa',
            'map_query'        => 'Plaza de la Iglesia, Moratalla',
            'quick_facts'      => [
                ['label' => 'Días de Tamborada', 'value' => 'Jueves Santo, Viernes Santo y Domingo de Resurrección'],
                ['label' => 'Reconocimiento', 'value' => 'Patrimonio Cultural Inmaterial de la Humanidad (UNESCO, 2018)'],
                ['label' => 'Singularidad', 'value' => 'Túnicas artesanales con capuz de rostro descubierto y motivos bordados o de volante']
            ],
            'sections' => [
                [
                    'id'      => 'dualidad-semana-santa',
                    'title'   => '1. Una Semana Santa con personalidad propia',
                    'content' => '<p>La <strong>Semana Santa de Moratalla</strong> posee una personalidad inconfundible en el sureste español. En ella se entrelazan dos mundos que se respetan y complementan: la solemnidad de sus cofradías pasionarias —como la de <a href="category.php?id=166">Nuestro Padre Jesús Nazareno</a>, el Cristo de la Sangre, la Virgen de los Dolores, San Juan o el Santo Sepulcro— y la fuerza arrolladora de los miles de tamboristas que inundan las calles en <a href="guia.php?slug=tamboradas-de-moratalla">las Tamboradas</a>.</p>'
                ],
                [
                    'id'      => 'calendario-procesiones-y-tambor',
                    'title'   => '2. Los momentos clave: Del Miércoles Santo al Domingo de Resurrección',
                    'content' => '<ul>
                        <li><strong>Miércoles Santo (La "Embarrá"):</strong> Preludio de hermandad en el que las peñas y cuadrillas preparan los tambores, templan los parches de piel y velan la llegada del Jueves Santo.</li>
                        <li><strong>Jueves Santo y Viernes Santo:</strong> Desde la mañana hasta la noche, una marea multicolor de túnicas y tambores recorre La Farola, la Calle Mayor y el casco antiguo, abriendo paso respetuosamente al cortejo de las procesiones.</li>
                        <li><strong>Domingo de Resurrección:</strong> Última gran jornada de tambor y júbilo popular para despedir la Semana Santa.</li>
                    </ul>
                    <p>Explora el archivo fotográfico histórico en las secciones de <a href="category.php?id=44"><strong>Semana Santa</strong></a> y <a href="category.php?id=168"><strong>Asociación de Tamboristas</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Qué días se toca el tambor en la Semana Santa de Moratalla?',
                    'a' => 'En Moratalla se toca el tambor oficialmente durante tres jornadas completas: Jueves Santo, Viernes Santo y Domingo de Resurrección.'
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
            'phase'            => 'Fase 2 · Artículo #5 · Prioritaria #7',
            'priority_rank'    => 11,
            'short_title'      => 'La Tradición del Tambor',
            'title'            => 'Tamboradas de Moratalla | La Tradición del Tambor Patrimonio UNESCO',
            'h1'               => 'La Tradición del Tambor en Moratalla: Toque, Túnicas y Patrimonio de la Humanidad',
            'subtitle'         => 'Cómo el toque libre y anárquico de los tambores de Moratalla se convirtió en Patrimonio Cultural Inmaterial de la Humanidad.',
            'meta_description' => 'Conoce la tradición del tambor en Moratalla: origen de las tamboradas, las túnicas tradicionales, el toque moratallero y su declaración como Patrimonio UNESCO.',
            'keywords'         => 'tamboradas de Moratalla, tradición del tambor Moratalla, tambores Moratalla UNESCO, tamboristas Moratalla',
            'icon'             => 'fas fa-drum',
            'hero_image'       => 'uploads/asociaciones/asoc_tamboristas_2004_0.jpg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Artículo SEO #5 / Prioritaria',
            'map_query'        => 'Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Estilo de Toque', 'value' => 'Libre, espontáneo y de "pique" o diálogo entre tamboristas'],
                ['label' => 'Indumentaria', 'value' => 'Túnica de terciopelo, raso o volantes con capuz sin antifaz (rostro descubierto)'],
                ['label' => 'Hitos Nacionales', 'value' => 'Sede de las Jornadas Nacionales de Exaltación del Tambor y el Bombo']
            ],
            'sections' => [
                [
                    'id'      => 'toque-y-tunica',
                    'title'   => '1. El toque moratallero y la riqueza artesanal de sus túnicas',
                    'content' => '<p>Lo que hace única a la <strong>tamborada de Moratalla</strong> es que no sigue un guion rígido ni un toque único uniformado: los tamboristas tocan a rostro descubierto, con el capuz levantado y adornado con espejos, cintas y bordados artesanales. En la calle se forman corros espontáneos donde los tamboristas "se pican" amistosamente manteniendo el ritmo durante horas.</p>
                    <p>En noviembre de 2018, la UNESCO inscribió las Tamboradas —y con ellas la tradición de Moratalla impulsada junto al Consorcio Nacional de los Pueblos del Tambor y la <a href="category.php?id=168">Asociación de Tamboristas de Moratalla</a>— en la Lista Representativa del Patrimonio Cultural Inmaterial de la Humanidad.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Cómo es la túnica tradicional del tamborista de Moratalla?',
                    'a' => 'A diferencia de los penitentes de procesión, el tamborista de Moratalla lleva el rostro descubierto bajo un capuz alto ricamente decorado, y túnicas de vivos colores confeccionadas en raso, terciopelo o con miles de pequeños volantes cosidos a mano.'
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
            'phase'            => 'Fase 2 · Artículo #6 · Prioritaria #9',
            'priority_rank'    => 12,
            'short_title'      => 'Rutas de Senderismo',
            'title'            => 'Las Mejores Rutas de Senderismo en Moratalla | Ríos, Gargantas y Cumbres',
            'h1'               => 'Las 6 Mejores Rutas de Senderismo en Moratalla para Todos los Niveles',
            'subtitle'         => 'Desde paseos familiares junto al río Alhárabe hasta la ascensión al techo de la Región de Murcia en el Macizo de Revolcadores.',
            'meta_description' => 'Guía con las mejores rutas de senderismo en Moratalla: Río Alhárabe, Estrecho de Bolvonegro, Poza de Somogil, Revolcadores, Sierra del Buitre y Benizar.',
            'keywords'         => 'mejores rutas senderismo Moratalla, rutas de senderismo en Moratalla, río Alhárabe ruta, Bolvonegro Moratalla, Revolcadores ruta',
            'icon'             => 'fas fa-hiking',
            'hero_image'       => 'uploads/banners/banner_1778708226.jpg',
            'reading_time'     => '10 min de lectura',
            'word_target'      => 'Artículo SEO #6 / Prioritaria',
            'map_query'        => 'Estrecho de Bolvonegro, Moratalla',
            'quick_facts'      => [
                ['label' => 'Rutas Familiares', 'value' => 'Senda del Río Alhárabe (La Puerta) y Entorno de Casa de Cristo'],
                ['label' => 'Rutas Medias', 'value' => 'Estrecho de Bolvonegro, Poza de Somogil y Rincón de las Cuevas (Benizar)'],
                ['label' => 'Alta Montaña', 'value' => 'Macizo de Revolcadores y Los Odres (2.015 m) y Sierra del Buitre']
            ],
            'sections' => [
                [
                    'id'      => 'rutas-fluviales',
                    'title'   => '1. Rutas del agua: Río Alhárabe, Estrecho de Bolvonegro y Somogil',
                    'content' => '<p>El agua ha esculpido algunos de los senderos más bellos del sureste peninsular:</p>
                    <ul>
                        <li><strong>Senda del Río Alhárabe y Estrecho de Bolvonegro (PR-MU):</strong> Partiendo de las inmediaciones del Camping La Puerta o del Puente de Hellín, el sendero acompaña al bosque de ribera hasta el sobrecogedor cañón de rocas oscuras (ofitas y margas) de Bolvonegro.</li>
                        <li><strong>Ruta a la Poza de Somogil:</strong> Aguas arriba del Alhárabe, donde el río forma pozas cristalinas alimentadas por manantiales templados.</li>
                        <li><strong>Ruta del Agua y Castillo de Benizar:</strong> Recorrido circular por el nacimiento de las Tosquillas, el Rincón de las Cuevas y la fortaleza roquera de Benizar.</li>
                    </ul>'
                ],
                [
                    'id'      => 'rutas-cumbres',
                    'title'   => '2. Cumbres de Moratalla: Revolcadores (Los Odres) y Sierra del Buitre',
                    'content' => '<p>Para los montañeros, la ascensión al <strong>Macizo de Revolcadores y el Pico de Los Odres (2.015 m)</strong> desde Inazares o Cañada de la Cruz es la ruta reina de la Región de Murcia. Más cerca del casco urbano, la subida desde <a href="guia.php?slug=monumentos-y-patrimonio-moratalla">Casa de Cristo</a> a la <strong>Sierra del Buitre (1.427 m)</strong> o al Pico del Fraile regala panorámicas inolvidables.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Cuál es la ruta de senderismo más fácil en Moratalla para ir con niños?',
                    'a' => 'El paseo fluvial junto al río Alhárabe en la zona de La Puerta hasta el inicio del Estrecho de Bolvonegro y los senderos señalizados en el pinar de Casa de Cristo son las mejores opciones para familias.'
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
            'phase'            => 'Fase 2 · Artículo #7 · Prioritaria #10',
            'priority_rank'    => 13,
            'short_title'      => 'Fiestas Populares y Calendario',
            'title'            => 'Fiestas de Moratalla | Calendario Completo, Cristo del Rayo y San Antón',
            'h1'               => 'Fiestas Populares de Moratalla: Calendario Festivo Completo Mes a Mes',
            'subtitle'         => 'Del fuego de San Antón y los tambores de Semana Santa a los históricos encierros por vereda del Santísimo Cristo del Rayo en julio.',
            'meta_description' => 'Calendario completo de las Fiestas de Moratalla (Murcia): Fiestas del Santísimo Cristo del Rayo en julio, Semana Santa, San Antón, San Jorge y romerías.',
            'keywords'         => 'Fiestas de Moratalla, Cristo del Rayo Moratalla, calendario fiestas Moratalla, encierros de Moratalla, San Antón Moratalla',
            'icon'             => 'fas fa-glass-cheers',
            'hero_image'       => 'uploads/banners/banner_1778708256.jpg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Artículo SEO #7 / Prioritaria',
            'map_query'        => 'Calle Mayor, Moratalla',
            'quick_facts'      => [
                ['label' => 'Fiestas Patronales', 'value' => 'Del 11 al 17 de julio (Santísimo Cristo del Rayo)'],
                ['label' => 'Día del Milagro', 'value' => '15 de junio (Bajada del Santísimo Cristo del Rayo)'],
                ['label' => 'Invierno y Primavera', 'value' => 'San Antón (17 enero), Semana Santa, San Jorge (23 abril) y Romería de la Rogativa (mayo)']
            ],
            'sections' => [
                [
                    'id'      => 'cristo-del-rayo',
                    'title'   => '1. Fiestas del Santísimo Cristo del Rayo (Junio y Julio)',
                    'content' => '<p>El corazón festivo del verano en Moratalla late en torno al <strong>Santísimo Cristo del Rayo</strong>. El <strong>15 de junio</strong> se conmemora el voto del milagro de 1621 con la solemne Bajada de la venerada imagen. Posteriormente, del <strong>11 al 17 de julio</strong>, se celebran las Fiestas Mayores, famosas en toda España por sus <strong>encierros taurinos por vereda</strong> —documentados desde el siglo XVII—, donde las reses son conducidas a caballo desde los montes hasta las calles acotadas del pueblo entre peñas, música de banda y un ambiente único.</p>
                    <p>Puedes consultar las crónicas y programas históricos en la sección <a href="category.php?id=39"><strong>Fiestas de Moratalla</strong></a>.</p>'
                ],
                [
                    'id'      => 'calendario-anual',
                    'title'   => '2. Calendario anual de fiestas y romerías en Moratalla y sus pedanías',
                    'content' => '<ul>
                        <li><strong>17 de enero – San Antón:</strong> Tradicionales hogueras ("luminarias") en los barrios, asados vecinales y bendición de animales.</li>
                        <li><strong>Marzo / Abril – Semana Santa y Tamboradas:</strong> Procesiones y toque del tambor declarados Patrimonio de la Humanidad.</li>
                        <li><strong>23 de abril – San Jorge:</strong> Patrón histórico de la villa, con romería y jornada de convivencia en el monte.</li>
                        <li><strong>Mayo – Romería de la Virgen de la Rogativa y San Isidro:</strong> Peregrinación tradicional al Santuario de la Rogativa.</li>
                        <li><strong>Agosto y Septiembre – Fiestas de las Pedanías:</strong> Celebraciones populares en Benizar, Campo de San Juan, El Sabinar, Calar de la Santa, Béjar y Mazuza.</li>
                        <li><strong>Diciembre – Santa Bárbara y Navidad:</strong> Dulces de Pascua, aguilando tradicional y belenes.</li>
                    </ul>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Cuándo son las fiestas patronales de Moratalla?',
                    'a' => 'Las fiestas mayores en honor al Santísimo Cristo del Rayo tienen su día grande religioso el 15 de junio y su semana grande de fiestas populares y encierros por vereda del 11 al 17 de julio.'
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
            'phase'            => 'Fase 2 · Artículo #8',
            'priority_rank'    => 14,
            'short_title'      => 'Recetas Tradicionales',
            'title'            => 'Recetas Tradicionales de Moratalla | Cocina Casera y Dulces de Antaño',
            'h1'               => 'Gastronomía Típica de Moratalla: Recetas Tradicionales que Pasan de Generación en Generación',
            'subtitle'         => 'Secretos de los fogones moratalleros: cómo se elaboran las migas, los andrajos, la olla de cerdo y los dulces de horno.',
            'meta_description' => 'Recetas tradicionales de Moratalla (Murcia): aprende los secretos del arroz con conejo y caracoles, migas ruleras, andrajos, mazapán y dulces típicos.',
            'keywords'         => 'recetas tradicionales de Moratalla, cocina típica de Moratalla, mazapán de Moratalla receta, migas de Moratalla',
            'icon'             => 'fas fa-book-open',
            'hero_image'       => 'uploads/banners/banner_1778708256.jpg',
            'reading_time'     => '8 min de lectura',
            'word_target'      => 'Artículo SEO #8',
            'map_query'        => 'Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Base Culinaria', 'value' => 'Aceite de oliva local, harina candeal, almendra, caza y huerta'],
                ['label' => 'Acceso Digital', 'value' => 'Vinculado con la sección de Gastronomía y el directorio de Restaurantes']
            ],
            'sections' => [
                [
                    'id'      => 'recetas-saladas',
                    'title'   => '1. El recetario salado: Del fuego de leña a la mesa',
                    'content' => '<p>Las recetas tradicionales de Moratalla nacieron para alimentar a las familias en las labores del campo y la sierra. Entre ellas sobresalen las <strong>migas de harina</strong> trabajadas con paciencia en sartén de hierro hasta quedar sueltas y doradas; los <strong>andrajos</strong> con tortas finas de masa; el <strong>ajoharina</strong> con níscalos (guíscanos) de los pinares; y el <strong>arroz con conejo y caracoles</strong> aromatizado con azafrán de hebra y romero.</p>'
                ],
                [
                    'id'      => 'reposteria-tradicional',
                    'title'   => '2. Dulces de Pascua, sartén y el Mazapán de Moratalla',
                    'content' => '<p>En las panaderías y confiterías artesanas de Moratalla siguen vivos los hornos de leña donde se cuecen el <a href="guia.php?slug=gastronomia-de-moratalla"><strong>Mazapán de Moratalla</strong></a>, los rollos de naranja, los mantecados, las tortas de chicharrones y los suspiros de almendra.</p>
                    <p>Descubre dónde probar todos estos sabores en nuestra guía de <a href="restaurantes.php"><strong>Bares y Restaurantes de Moratalla</strong></a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Qué dulce típico comprar en Moratalla para llevar de recuerdo?',
                    'a' => 'Sin duda, una pieza de Mazapán artesanal de Moratalla relleno de cabello de ángel y yema, junto con miel de romero de la sierra, aceite de oliva local y embutidos caseros.'
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
            'phase'            => 'Fase 1 · Long-Tail Prioritaria',
            'priority_rank'    => 15,
            'short_title'      => 'Moratalla en un Fin de Semana',
            'title'            => 'Qué Hacer en Moratalla un Fin de Semana | Itinerario de 2 y 3 Días',
            'h1'               => 'Qué Hacer en Moratalla un Fin de Semana: El Plan Perfecto Día por Día',
            'subtitle'         => 'Itinerario práctico de viernes a domingo combinando patrimonio medieval, senderismo fluvial, arte rupestre y gastronomía.',
            'meta_description' => '¿No sabes qué hacer en Moratalla un fin de semana? Te proponemos el mejor itinerario de 2 y 3 días con rutas, monumentos, dónde comer y dónde dormir.',
            'keywords'         => 'qué hacer en Moratalla un fin de semana, fin de semana en Moratalla, escapada Moratalla 2 días, pueblos bonitos cerca de Moratalla',
            'icon'             => 'fas fa-calendar-check',
            'hero_image'       => 'uploads/theme/moratalla.jpg',
            'reading_time'     => '7 min de lectura',
            'word_target'      => 'Guía Long-Tail Fin de Semana',
            'map_query'        => 'Moratalla, Murcia',
            'quick_facts'      => [
                ['label' => 'Día 1 (Sábado)', 'value' => 'Castillo-Fortaleza, Casco Antiguo, Comida Típica y Casa de Cristo'],
                ['label' => 'Día 2 (Domingo)', 'value' => 'Río Alhárabe, Estrecho de Bolvonegro y compra de Mazapán de Moratalla'],
                ['label' => 'Día 3 (Opcional)', 'value' => 'Benizar, Cañaica del Calar o ascensión a Revolcadores']
            ],
            'sections' => [
                [
                    'id'      => 'sabado-patrimonio',
                    'title'   => 'Sábado: Historia viva, Castillo medieval y pinares de Casa de Cristo',
                    'content' => '<p><strong>Por la mañana:</strong> Llegada a Moratalla y subida por el <a href="guia.php?slug=ruta-casco-antiguo-moratalla">casco antiguo</a> hasta la <a href="guia.php?slug=monumentos-y-patrimonio-moratalla">Iglesia de la Asunción</a> y el <a href="guia.php?slug=castillo-de-moratalla">Castillo-Fortaleza</a>.</p>
                    <p><strong>Al mediodía:</strong> Comida tradicional en uno de los <a href="restaurantes.php">restaurantes de Moratalla</a>.</p>
                    <p><strong>Por la tarde:</strong> Subida al Santuario de la Casa de Cristo y visita al <a href="guia.php?slug=pinturas-rupestres-de-moratalla">Centro de Interpretación del Arte Rupestre</a> contemplando el atardecer desde sus miradores.</p>'
                ],
                [
                    'id'      => 'domingo-naturaleza',
                    'title'   => 'Domingo: Senderismo en el Río Alhárabe y despedida dulce',
                    'content' => '<p><strong>Por la mañana:</strong> Ruta a pie por el <a href="guia.php?slug=rutas-de-senderismo-en-moratalla">Río Alhárabe y el Estrecho de Bolvonegro</a> o excursión a la pedanía de Benizar.</p>
                    <p><strong>Antes de volver:</strong> No olvides pasar por las panaderías del pueblo para llevarte un auténtico <a href="guia.php?slug=gastronomia-de-moratalla">Mazapán de Moratalla</a> y reservar tu próxima estancia en <a href="alojamientos.php">Alojamientos Rurales de Moratalla</a>.</p>'
                ]
            ],
            'faqs' => [
                [
                    'q' => '¿Qué pueblos bonitos y parajes hay cerca del casco urbano de Moratalla?',
                    'a' => 'Dentro del propio término de Moratalla destacan pedanías con mucho encanto como Benizar, Otos, Mazuza, Campo de San Juan, El Sabinar, Calar de la Santa y La Rogativa, además de su cercanía con Caravaca de la Cruz, Calasparra y Cehegín en la comarca del Noroeste.'
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
 * Devuelve las 10 Páginas SEO Prioritarias del Plan de 90 Días con su enlace directo
 */
function getSeoTop10PriorityLinks(): array {
    return [
        [
            'rank'  => 1,
            'title' => 'Qué ver en Moratalla',
            'desc'  => 'Guía completa de imprescindibles, mapa e itinerarios.',
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
            'desc'  => 'La fortaleza medieval y su emblemática Torre del Homenaje.',
            'icon'  => 'fas fa-chess-rook',
            'url'   => 'guia.php?slug=castillo-de-moratalla'
        ],
        [
            'rank'  => 5,
            'title' => 'Pinturas rupestres de Moratalla',
            'desc'  => 'Arte prehistórico Patrimonio de la Humanidad UNESCO.',
            'icon'  => 'fas fa-palette',
            'url'   => 'guia.php?slug=pinturas-rupestres-de-moratalla'
        ],
        [
            'rank'  => 6,
            'title' => 'Semana Santa de Moratalla',
            'desc'  => 'Cofradías, procesiones y tradición centenaria.',
            'icon'  => 'fas fa-church',
            'url'   => 'guia.php?slug=semana-santa-de-moratalla'
        ],
        [
            'rank'  => 7,
            'title' => 'Tamboradas de Moratalla',
            'desc'  => 'Toque libre, túnicas artesanales y Patrimonio Inmaterial UNESCO.',
            'icon'  => 'fas fa-drum',
            'url'   => 'guia.php?slug=tamboradas-de-moratalla'
        ],
        [
            'rank'  => 8,
            'title' => 'Gastronomía típica y dónde comer',
            'desc'  => 'Mazapán de Moratalla, platos de sierra y restaurantes.',
            'icon'  => 'fas fa-utensils',
            'url'   => 'guia.php?slug=gastronomia-de-moratalla'
        ],
        [
            'rank'  => 9,
            'title' => 'Rutas de senderismo',
            'desc'  => 'Río Alhárabe, Estrecho de Bolvonegro, Somogil y Revolcadores.',
            'icon'  => 'fas fa-hiking',
            'url'   => 'guia.php?slug=rutas-de-senderismo-en-moratalla'
        ],
        [
            'rank'  => 10,
            'title' => 'Fiestas populares',
            'desc'  => 'Santísimo Cristo del Rayo, encierros por vereda y calendario.',
            'icon'  => 'fas fa-glass-cheers',
            'url'   => 'guia.php?slug=fiestas-populares-de-moratalla'
        ]
    ];
}
