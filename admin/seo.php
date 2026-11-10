<?php
// admin/seo.php - Gestor de Configuración SEO, Páginas Pilar y Guías Temáticas
require_once 'inc/auth.php';
require_once 'inc/layout.php';
require_once '../config.php';
require_once '../inc/seo_90dias_data.php';

$pdo = getDB();
ensureSeoGuidesTable($pdo);

$message = '';
$errorMessage = '';

// Lista de SVGs e imágenes disponibles en uploads/seo/
$availableSvgs = [
    'uploads/seo/pilar-guia-completa.svg'         => 'Ilustración: Guía Completa de Moratalla',
    'uploads/seo/pilar-turismo-rural.svg'         => 'Ilustración: Turismo Rural y Naturaleza',
    'uploads/seo/pilar-historia.svg'              => 'Ilustración: Historia de Moratalla',
    'uploads/seo/pilar-monumentos-patrimonio.svg' => 'Ilustración: Monumentos y Patrimonio',
    'uploads/seo/pilar-gastronomia.svg'           => 'Ilustración: Gastronomía Tradicional',
    'uploads/seo/guia-castillo.svg'               => 'Ilustración: Castillo-Fortaleza',
    'uploads/seo/guia-arte-rupestre.svg'          => 'Ilustración: Pinturas Rupestres UNESCO',
    'uploads/seo/guia-semana-santa.svg'           => 'Ilustración: Semana Santa y Tambores',
    'uploads/seo/guia-senderismo.svg'             => 'Ilustración: Rutas de Senderismo y Montaña',
    'uploads/seo/guia-fiestas.svg'                => 'Ilustración: Fiestas Populares y Cristo del Rayo'
];

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['seo_action'] ?? '';

    // 1. Guardar configuración global SEO
    if ($action === 'save_global_config') {
        $seoUpdate = [
            'seo_90dias_enabled'     => isset($_POST['seo_90dias_enabled']) ? '1' : '0',
            'seo_show_menu'          => isset($_POST['seo_show_menu']) ? '1' : '0',
            'seo_show_home_top'      => isset($_POST['seo_show_home_top']) ? '1' : '0',
            'seo_show_home_bottom'   => isset($_POST['seo_show_home_bottom']) ? '1' : '0',
            'seo_show_footer'        => isset($_POST['seo_show_footer']) ? '1' : '0',
            'seo_show_floating_home' => isset($_POST['seo_show_floating_home']) ? '1' : '0',
            'seo_home_title'         => trim($_POST['seo_home_title'] ?? ''),
            'seo_home_desc'          => trim($_POST['seo_home_desc'] ?? ''),
            'seo_global_keywords'    => trim($_POST['seo_global_keywords'] ?? '')
        ];
        saveSeoConfig($pdo, $seoUpdate);
        $message = "✅ Configuración global de visibilidad SEO guardada correctamente.";
    }

    // 2. Activar / Ocultar (Borrado lógico activo = 0 / 1) una guía
    elseif ($action === 'toggle_active') {
        $guideId = (int)($_POST['guide_id'] ?? 0);
        $newState = (int)($_POST['new_state'] ?? 0) === 1 ? 1 : 0;
        if ($guideId > 0) {
            $stmt = $pdo->prepare("UPDATE seo_guides SET activo = ? WHERE id = ?");
            $stmt->execute([$newState, $guideId]);
            $message = $newState === 1
                ? "✅ Guía activada y visible nuevamente en la web."
                : "✅ Guía ocultada (marcada como inactiva) sin borrar sus datos.";
        }
    }

    // 3. Crear o Actualizar una Guía / Página Pilar
    elseif ($action === 'save_guide') {
        $guideId       = (int)($_POST['guide_id'] ?? 0);
        $shortTitle    = trim($_POST['short_title'] ?? '');
        $slugInput     = trim($_POST['slug'] ?? '');
        if ($slugInput === '' && $shortTitle !== '') {
            $slugInput = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $shortTitle)), '-'));
        }
        $slug          = preg_replace('/[^a-z0-9\-]/', '', strtolower($slugInput));
        $type          = ($_POST['type'] ?? 'articulo') === 'pilar' ? 'pilar' : 'articulo';
        $phase         = trim($_POST['phase'] ?? ($type === 'pilar' ? 'Guía Principal' : 'Guía Temática'));
        $priorityRank  = (int)($_POST['priority_rank'] ?? 10);
        $title         = trim($_POST['title'] ?? $shortTitle);
        $h1            = trim($_POST['h1'] ?? $title);
        $subtitle      = trim($_POST['subtitle'] ?? '');
        $metaDesc      = trim($_POST['meta_description'] ?? $subtitle);
        $keywords      = trim($_POST['keywords'] ?? '');
        $icon          = trim($_POST['icon'] ?? 'fas fa-compass');
        $readingTime   = trim($_POST['reading_time'] ?? '8 min de lectura');
        $mapQuery      = trim($_POST['map_query'] ?? 'Moratalla, Murcia');
        $activo        = isset($_POST['activo']) ? 1 : 0;

        $heroImage     = trim($_POST['hero_image'] ?? 'uploads/seo/pilar-guia-completa.svg');
        // Si el usuario sube una imagen personalizada
        if (isset($_FILES['custom_image']) && $_FILES['custom_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['custom_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['svg', 'jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $destDir = dirname(__DIR__) . '/uploads/seo';
                if (!is_dir($destDir)) {
                    @mkdir($destDir, 0775, true);
                }
                $fileName = 'seo_' . ($slug ?: time()) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['custom_image']['tmp_name'], $destDir . '/' . $fileName)) {
                    $heroImage = 'uploads/seo/' . $fileName;
                }
            }
        }
        $logoImage = $heroImage;

        // Procesar Quick Facts
        $quickFacts = [];
        if (!empty($_POST['qf_label']) && is_array($_POST['qf_label'])) {
            foreach ($_POST['qf_label'] as $idx => $lbl) {
                $lbl = trim($lbl);
                $val = trim($_POST['qf_value'][$idx] ?? '');
                if ($lbl !== '' || $val !== '') {
                    $quickFacts[] = ['label' => $lbl, 'value' => $val];
                }
            }
        }

        // Procesar Secciones de Contenido
        $sections = [];
        if (!empty($_POST['sec_h2']) && is_array($_POST['sec_h2'])) {
            foreach ($_POST['sec_h2'] as $idx => $secH2) {
                $secH2 = trim($secH2);
                $secContent = trim($_POST['sec_content'][$idx] ?? '');
                $secId = trim($_POST['sec_id'][$idx] ?? '');
                if ($secId === '' && $secH2 !== '') {
                    $secId = 'seccion-' . ($idx + 1);
                }
                if ($secH2 !== '' || $secContent !== '') {
                    $sections[] = [
                        'id'      => $secId ?: ('seccion-' . ($idx + 1)),
                        'h2'      => $secH2 ?: ('Apartado ' . ($idx + 1)),
                        'content' => $secContent
                    ];
                }
            }
        }

        // Procesar FAQs
        $faqs = [];
        if (!empty($_POST['faq_q']) && is_array($_POST['faq_q'])) {
            foreach ($_POST['faq_q'] as $idx => $fq) {
                $fq = trim($fq);
                $fa = trim($_POST['faq_a'][$idx] ?? '');
                if ($fq !== '' && $fa !== '') {
                    $faqs[] = ['q' => $fq, 'a' => $fa];
                }
            }
        }

        // Procesar Tarjetas de Producción vinculadas
        $relatedCards = isset($_POST['related_cards']) && is_array($_POST['related_cards'])
            ? array_values(array_filter(array_map('trim', $_POST['related_cards'])))
            : [];

        // Procesar Guías relacionadas
        $relatedSlugs = isset($_POST['related_slugs']) && is_array($_POST['related_slugs'])
            ? array_values(array_filter(array_map('trim', $_POST['related_slugs'])))
            : [];

        if ($slug === '' || $shortTitle === '') {
            $errorMessage = "❌ El título corto y el identificador (slug) son obligatorios.";
        } else {
            try {
                if ($guideId > 0) {
                    $upd = $pdo->prepare("UPDATE seo_guides SET
                        slug = ?, type = ?, phase = ?, priority_rank = ?, short_title = ?, title = ?, h1 = ?,
                        subtitle = ?, meta_description = ?, keywords = ?, icon = ?, hero_image = ?, logo_image = ?,
                        reading_time = ?, map_query = ?, quick_facts_json = ?, sections_json = ?, faqs_json = ?,
                        related_cards_json = ?, related_slugs_json = ?, activo = ?
                        WHERE id = ?");
                    $upd->execute([
                        $slug, $type, $phase, $priorityRank, $shortTitle, $title, $h1,
                        $subtitle, $metaDesc, $keywords, $icon, $heroImage, $logoImage,
                        $readingTime, $mapQuery,
                        json_encode($quickFacts, JSON_UNESCAPED_UNICODE),
                        json_encode($sections, JSON_UNESCAPED_UNICODE),
                        json_encode($faqs, JSON_UNESCAPED_UNICODE),
                        json_encode($relatedCards, JSON_UNESCAPED_UNICODE),
                        json_encode($relatedSlugs, JSON_UNESCAPED_UNICODE),
                        $activo,
                        $guideId
                    ]);
                    $message = "✅ Guía «" . htmlspecialchars($shortTitle) . "» actualizada correctamente.";
                } else {
                    $ins = $pdo->prepare("INSERT INTO seo_guides
                        (slug, type, phase, priority_rank, short_title, title, h1, subtitle, meta_description, keywords, icon, hero_image, logo_image, reading_time, map_query, quick_facts_json, sections_json, faqs_json, related_cards_json, related_slugs_json, activo)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([
                        $slug, $type, $phase, $priorityRank, $shortTitle, $title, $h1,
                        $subtitle, $metaDesc, $keywords, $icon, $heroImage, $logoImage,
                        $readingTime, $mapQuery,
                        json_encode($quickFacts, JSON_UNESCAPED_UNICODE),
                        json_encode($sections, JSON_UNESCAPED_UNICODE),
                        json_encode($faqs, JSON_UNESCAPED_UNICODE),
                        json_encode($relatedCards, JSON_UNESCAPED_UNICODE),
                        json_encode($relatedSlugs, JSON_UNESCAPED_UNICODE),
                        $activo
                    ]);
                    $message = "✅ Nueva guía «" . htmlspecialchars($shortTitle) . "» creada correctamente.";
                }
            } catch (\Throwable $e) {
                $errorMessage = "❌ Error al guardar la guía: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

$seoCfg = getSeoConfig($pdo);
$allGuides = getSeo90Pages(true, $pdo); // Incluye activas e inactivas
$allProdCards = getProductionExistingCards($pdo);

// Si se solicita editar o crear una guía
$editSlug = isset($_GET['edit']) ? trim($_GET['edit']) : '';
$isNew = isset($_GET['new']) && $_GET['new'] === '1';
$editingGuide = null;

if ($editSlug !== '' && isset($allGuides[$editSlug])) {
    $editingGuide = $allGuides[$editSlug];
} elseif ($isNew) {
    $editingGuide = [
        'id'               => 0,
        'slug'             => '',
        'type'             => 'articulo',
        'phase'            => 'Guía Temática',
        'priority_rank'    => count($allGuides) + 1,
        'short_title'      => '',
        'title'            => '',
        'h1'               => '',
        'subtitle'         => '',
        'meta_description' => '',
        'keywords'         => 'Moratalla, Turismo en Moratalla',
        'icon'             => 'fas fa-compass',
        'hero_image'       => 'uploads/seo/pilar-guia-completa.svg',
        'logo_image'       => 'uploads/seo/pilar-guia-completa.svg',
        'reading_time'     => '6 min de lectura',
        'map_query'        => 'Moratalla, Murcia',
        'quick_facts'      => [
            ['label' => 'Ubicación', 'value' => 'Moratalla (Región de Murcia)'],
            ['label' => 'Categoría', 'value' => 'Patrimonio y Naturaleza']
        ],
        'sections'         => [
            [
                'id'      => 'introduccion',
                'h2'      => 'Introducción y atractivos principales',
                'content' => '<p>Escribe aquí el contenido de la sección...</p>'
            ]
        ],
        'faqs'             => [
            ['q' => '¿Cuál es la mejor época para visitarlo?', 'a' => 'Cualquier época del año es ideal para descubrir este rincón de Moratalla.']
        ],
        'related_cards'    => [],
        'related_slugs'    => ['guia-completa-moratalla'],
        'activo'           => 1
    ];
}

adminHeader("SEO y Guías Turísticas");
?>

<div class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div>
            <h2 style="margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-compass" style="color: var(--primary);"></i> Gestión de SEO, Páginas Pilar y Guías Turísticas
            </h2>
            <p style="margin: 0.3rem 0 0; color: #64748b;">
                Modifica textos, imágenes, preguntas frecuentes, tarjetas vinculadas o activa/oculta cualquier sección SEO de la web.
            </p>
        </div>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="../guia.php" target="_blank" class="btn" style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; text-decoration: none; font-weight: 600;">
                <i class="fas fa-external-link-alt"></i> Ver Índice de Guías
            </a>
            <a href="seo.php?new=1#editor_guia" class="btn btn-primary" style="text-decoration: none; font-weight: 700;">
                <i class="fas fa-plus"></i> Crear Nueva Guía / Página Pilar
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div style="background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600;">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <!-- FORMULARIO RÁPIDO DE VISIBILIDAD Y OPCIONES GLOBALES SEO -->
    <form method="POST" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem;">
        <input type="hidden" name="seo_action" value="save_global_config">
        <h3 style="margin: 0 0 0.9rem; font-size: 1.05rem; color: #1e293b;">
            <i class="fas fa-sliders-h" style="color: var(--primary);"></i> Opciones de Visibilidad en Portada, Menú y Pie de Página
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 0.75rem; margin-bottom: 1.1rem;">
            <label style="display: flex; align-items: center; gap: 8px; background: white; padding: 0.7rem 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer; font-weight: 700;">
                <input type="checkbox" name="seo_90dias_enabled" value="1" <?php echo (($seoCfg['seo_90dias_enabled'] ?? '1') === '1') ? 'checked' : ''; ?>>
                <span>Módulo SEO Global Activo</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; background: white; padding: 0.7rem 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;">
                <input type="checkbox" name="seo_show_menu" value="1" <?php echo (($seoCfg['seo_show_menu'] ?? '1') === '1') ? 'checked' : ''; ?>>
                <span>Mostrar "Guías" en Menú Principal</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; background: white; padding: 0.7rem 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;">
                <input type="checkbox" name="seo_show_home_top" value="1" <?php echo (($seoCfg['seo_show_home_top'] ?? '1') === '1') ? 'checked' : ''; ?>>
                <span>Panel Superior en Portada</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; background: white; padding: 0.7rem 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;">
                <input type="checkbox" name="seo_show_home_bottom" value="1" <?php echo (($seoCfg['seo_show_home_bottom'] ?? '1') ? 'checked' : ''); ?>>
                <span>Tarjetas Inferiores en Portada</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; background: white; padding: 0.7rem 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;">
                <input type="checkbox" name="seo_show_footer" value="1" <?php echo (($seoCfg['seo_show_footer'] ?? '1') === '1') ? 'checked' : ''; ?>>
                <span>Enlaces en Pie de Página</span>
            </label>
            <label style="display: flex; align-items: center; gap: 8px; background: white; padding: 0.7rem 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1; cursor: pointer;">
                <input type="checkbox" name="seo_show_floating_home" value="1" <?php echo (($seoCfg['seo_show_floating_home'] ?? '1') === '1') ? 'checked' : ''; ?>>
                <span>Botón Flotante "Página Principal"</span>
            </label>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 0.9rem; margin-bottom: 1rem;">
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.25rem;">Título SEO Portada</label>
                <input type="text" name="seo_home_title" value="<?php echo htmlspecialchars($seoCfg['seo_home_title'] ?? ''); ?>" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div>
                <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.25rem;">Palabras Clave Globales</label>
                <input type="text" name="seo_global_keywords" value="<?php echo htmlspecialchars($seoCfg['seo_global_keywords'] ?? ''); ?>" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
        </div>
        <div style="margin-bottom: 1rem;">
            <label style="display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.25rem;">Metadescripción de Portada</label>
            <textarea name="seo_home_desc" rows="2" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;"><?php echo htmlspecialchars($seoCfg['seo_home_desc'] ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.2rem;">
            <i class="fas fa-save"></i> Guardar Visibilidad y Metadatos
        </button>
    </form>
</div>

<?php if ($editingGuide !== null): ?>
<!-- EDITOR COMPLETO DE GUÍA / PÁGINA PILAR -->
<div class="card" id="editor_guia" style="margin-bottom: 2rem; border: 2px solid var(--primary);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.2rem; padding-bottom: 0.8rem; border-bottom: 1px solid #e2e8f0;">
        <h3 style="margin: 0; color: var(--primary-dark);">
            <i class="fas fa-edit"></i>
            <?php echo $editingGuide['id'] > 0 ? 'Editando Guía: ' . htmlspecialchars($editingGuide['short_title']) : 'Crear Nueva Guía / Página Pilar'; ?>
        </h3>
        <a href="seo.php" class="btn" style="background: #f1f5f9; color: #334155; text-decoration: none;">
            <i class="fas fa-times"></i> Cerrar Editor
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="seo_action" value="save_guide">
        <input type="hidden" name="guide_id" value="<?php echo (int)$editingGuide['id']; ?>">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.2rem;">
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Título Corto (Menú y Tarjetas) *</label>
                <input type="text" name="short_title" required value="<?php echo htmlspecialchars($editingGuide['short_title']); ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Identificador URL (Slug) *</label>
                <input type="text" name="slug" required value="<?php echo htmlspecialchars($editingGuide['slug']); ?>" placeholder="ej: castillo-de-moratalla" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Tipo de Página</label>
                <select name="type" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
                    <option value="pilar" <?php echo ($editingGuide['type'] === 'pilar') ? 'selected' : ''; ?>>Página Pilar Principal</option>
                    <option value="articulo" <?php echo ($editingGuide['type'] !== 'pilar') ? 'selected' : ''; ?>>Guía Especializada / Artículo</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Etiqueta Discreta Superior</label>
                <input type="text" name="phase" value="<?php echo htmlspecialchars($editingGuide['phase']); ?>" placeholder="Ej: Guía Turística Principal" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.2rem;">
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Orden de Prioridad (1 = primero)</label>
                <input type="number" name="priority_rank" value="<?php echo (int)$editingGuide['priority_rank']; ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Icono FontAwesome</label>
                <input type="text" name="icon" value="<?php echo htmlspecialchars($editingGuide['icon']); ?>" placeholder="fas fa-compass" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Tiempo de Lectura</label>
                <input type="text" name="reading_time" value="<?php echo htmlspecialchars($editingGuide['reading_time']); ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Consulta de Mapa (Google Maps)</label>
                <input type="text" name="map_query" value="<?php echo htmlspecialchars($editingGuide['map_query']); ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 1.2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Título SEO (Meta Title)</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($editingGuide['title']); ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
        </div>

        <div style="margin-bottom: 1.2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Encabezado Principal (H1)</label>
            <input type="text" name="h1" value="<?php echo htmlspecialchars($editingGuide['h1']); ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.2rem;">
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Subtítulo / Resumen Visible</label>
                <textarea name="subtitle" rows="2" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit;"><?php echo htmlspecialchars($editingGuide['subtitle']); ?></textarea>
            </div>
            <div>
                <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Metadescripción SEO (Meta Description)</label>
                <textarea name="meta_description" rows="2" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit;"><?php echo htmlspecialchars($editingGuide['meta_description']); ?></textarea>
            </div>
        </div>

        <div style="margin-bottom: 1.2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.3rem;">Palabras Clave (Keywords separadas por comas)</label>
            <input type="text" name="keywords" value="<?php echo htmlspecialchars($editingGuide['keywords']); ?>" style="width: 100%; padding: 0.7rem; border: 1px solid #cbd5e1; border-radius: 8px;">
        </div>

        <!-- IMAGEN DE REFERENCIA / LOGO CONTEXTUAL -->
        <div style="background: #f8fafc; padding: 1.1rem; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 1.4rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">
                <i class="fas fa-image"></i> Imagen / Ilustración de Referencia Contextual
            </label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; align-items: center;">
                <div>
                    <small style="display: block; margin-bottom: 0.3rem; color: #475569;">Seleccionar ilustración existente o escribir ruta:</small>
                    <input type="text" name="hero_image" id="hero_image_input" value="<?php echo htmlspecialchars($editingGuide['hero_image']); ?>" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 0.5rem;">
                    <select onchange="if(this.value) document.getElementById('hero_image_input').value = this.value;" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">-- Elegir ilustración prediseñada --</option>
                        <?php foreach ($availableSvgs as $svgPath => $svgLabel): ?>
                            <option value="<?php echo htmlspecialchars($svgPath); ?>" <?php echo ($editingGuide['hero_image'] === $svgPath) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($svgLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <small style="display: block; margin-bottom: 0.3rem; color: #475569;">O subir una nueva imagen desde tu equipo (SVG, JPG, PNG, WebP):</small>
                    <input type="file" name="custom_image" accept=".svg,.jpg,.jpeg,.png,.webp" style="width: 100%; padding: 0.5rem; background: white; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>
        </div>

        <!-- FICHA RÁPIDA (QUICK FACTS) -->
        <div style="background: #f8fafc; padding: 1.1rem; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 1.4rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <label style="font-weight: 700; margin: 0;"><i class="fas fa-list-ul"></i> Datos Rápidos de la Ficha Lateral</label>
                <button type="button" onclick="addQuickFactRow()" class="btn btn-sm" style="background: #e2e8f0; color: #0f172a; font-weight: 600;">
                    <i class="fas fa-plus"></i> Añadir Dato
                </button>
            </div>
            <div id="qf_container" style="display: grid; gap: 0.6rem;">
                <?php foreach ($editingGuide['quick_facts'] as $qf): ?>
                <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 0.5rem; align-items: center;">
                    <input type="text" name="qf_label[]" value="<?php echo htmlspecialchars($qf['label'] ?? ''); ?>" placeholder="Etiqueta (ej: Altitud)" style="padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <input type="text" name="qf_value[]" value="<?php echo htmlspecialchars($qf['value'] ?? ''); ?>" placeholder="Valor (ej: 681 metros)" style="padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <button type="button" onclick="this.parentElement.remove()" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SECCIONES DE CONTENIDO (H2 + HTML) -->
        <div style="background: #f8fafc; padding: 1.1rem; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 1.4rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <label style="font-weight: 700; margin: 0;"><i class="fas fa-align-left"></i> Secciones de Contenido de la Guía</label>
                <button type="button" onclick="addSectionRow()" class="btn btn-sm" style="background: #e2e8f0; color: #0f172a; font-weight: 600;">
                    <i class="fas fa-plus"></i> Añadir Sección
                </button>
            </div>
            <div id="sections_container" style="display: grid; gap: 1rem;">
                <?php foreach ($editingGuide['sections'] as $sec): ?>
                <div style="background: white; padding: 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <div style="display: flex; gap: 0.6rem; margin-bottom: 0.5rem; align-items: center;">
                        <input type="hidden" name="sec_id[]" value="<?php echo htmlspecialchars($sec['id'] ?? ''); ?>">
                        <input type="text" name="sec_h2[]" value="<?php echo htmlspecialchars($sec['h2'] ?? ''); ?>" placeholder="Título del apartado (H2)" style="flex: 1; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700;">
                        <button type="button" onclick="this.parentElement.parentElement.remove()" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;">Quitar apartado</button>
                    </div>
                    <textarea name="sec_content[]" rows="4" placeholder="Contenido (admite párrafos <p>, listas <ul><li>, enlaces <a> y negritas <strong>)" style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 0.9rem;"><?php echo htmlspecialchars($sec['content'] ?? ''); ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PREGUNTAS FRECUENTES (FAQS) -->
        <div style="background: #f8fafc; padding: 1.1rem; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 1.4rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <label style="font-weight: 700; margin: 0;"><i class="fas fa-question-circle"></i> Preguntas Frecuentes (FAQ Schema)</label>
                <button type="button" onclick="addFaqRow()" class="btn btn-sm" style="background: #e2e8f0; color: #0f172a; font-weight: 600;">
                    <i class="fas fa-plus"></i> Añadir Pregunta
                </button>
            </div>
            <div id="faqs_container" style="display: grid; gap: 0.75rem;">
                <?php foreach ($editingGuide['faqs'] as $faq): ?>
                <div style="background: white; padding: 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.4rem;">
                        <input type="text" name="faq_q[]" value="<?php echo htmlspecialchars($faq['q'] ?? ''); ?>" placeholder="Pregunta frecuente..." style="flex: 1; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700;">
                        <button type="button" onclick="this.parentElement.parentElement.remove()" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;">&times;</button>
                    </div>
                    <textarea name="faq_a[]" rows="2" placeholder="Respuesta..." style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;"><?php echo htmlspecialchars($faq['a'] ?? ''); ?></textarea>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TARJETAS EXISTENTES DE PRODUCCIÓN VINCULADAS -->
        <div style="background: #f8fafc; padding: 1.1rem; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 1.4rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">
                <i class="fas fa-images"></i> Tarjetas y Galerías Fotográficas de Producción Vinculadas a esta Guía
            </label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 0.55rem;">
                <?php foreach ($allProdCards as $cardKey => $cardInfo):
                    $checkedCard = in_array($cardKey, $editingGuide['related_cards'] ?? [], true);
                ?>
                <label style="display: flex; align-items: center; gap: 8px; background: white; padding: 0.55rem 0.75rem; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.85rem; cursor: pointer;">
                    <input type="checkbox" name="related_cards[]" value="<?php echo htmlspecialchars($cardKey); ?>" <?php echo $checkedCard ? 'checked' : ''; ?>>
                    <span><?php echo htmlspecialchars($cardInfo['title']); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: 700; cursor: pointer;">
                <input type="checkbox" name="activo" value="1" <?php echo ((int)$editingGuide['activo'] === 1) ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
                <span>Guía Activa y Visible al Público</span>
            </label>

            <div style="display: flex; gap: 0.75rem;">
                <a href="seo.php" class="btn" style="background: #e2e8f0; color: #1e293b; text-decoration: none;">Cancelar</a>
                <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 0.75rem 1.5rem;">
                    <i class="fas fa-save"></i> Guardar Guía
                </button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- LISTADO DE TODAS LAS PÁGINAS PILAR Y GUÍAS TEMÁTICAS -->
<div class="card">
    <h3 style="margin-top: 0; margin-bottom: 1rem;">
        <i class="fas fa-layer-group" style="color: var(--primary);"></i> Catálogo de Páginas Pilar y Guías Especializadas (<?php echo count($allGuides); ?>)
    </h3>

    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem;">
            <thead>
                <tr style="background: #f1f5f9; text-align: left; border-bottom: 2px solid #cbd5e1;">
                    <th style="padding: 0.75rem;">Imagen</th>
                    <th style="padding: 0.75rem;">Título / URL</th>
                    <th style="padding: 0.75rem;">Tipo</th>
                    <th style="padding: 0.75rem;">Tarjetas Vinculadas</th>
                    <th style="padding: 0.75rem;">Estado</th>
                    <th style="padding: 0.75rem; text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allGuides as $slug => $g):
                    $isActive = ((int)($g['activo'] ?? 1) === 1);
                ?>
                <tr style="border-bottom: 1px solid #e2e8f0; <?php echo !$isActive ? 'opacity: 0.6; background: #f8fafc;' : ''; ?>">
                    <td style="padding: 0.75rem; width: 70px;">
                        <img src="../<?php echo htmlspecialchars($g['logo_image'] ?? $g['hero_image']); ?>" alt="" style="width: 60px; height: 42px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; background: #081c15;">
                    </td>
                    <td style="padding: 0.75rem;">
                        <strong style="color: #0f172a; font-size: 0.96rem;"><?php echo htmlspecialchars($g['short_title']); ?></strong>
                        <div style="font-size: 0.8rem; color: #64748b;"><code>guia.php?slug=<?php echo htmlspecialchars($slug); ?></code></div>
                    </td>
                    <td style="padding: 0.75rem;">
                        <?php if ($g['type'] === 'pilar'): ?>
                            <span style="background: #dcfce7; color: #166534; padding: 3px 9px; border-radius: 50px; font-size: 0.76rem; font-weight: 700;">Página Pilar</span>
                        <?php else: ?>
                            <span style="background: #e0f2fe; color: #075985; padding: 3px 9px; border-radius: 50px; font-size: 0.76rem; font-weight: 700;">Guía Temática</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem;">
                        <span style="font-size: 0.82rem; color: #475569;">
                            <?php echo count($g['related_cards'] ?? []); ?> tarjetas · <?php echo count($g['sections'] ?? []); ?> secciones
                        </span>
                    </td>
                    <td style="padding: 0.75rem;">
                        <?php if ($isActive): ?>
                            <span style="background: #d1fae5; color: #065f46; padding: 3px 10px; border-radius: 50px; font-size: 0.76rem; font-weight: 700;">Activa</span>
                        <?php else: ?>
                            <span style="background: #fee2e2; color: #991b1b; padding: 3px 10px; border-radius: 50px; font-size: 0.76rem; font-weight: 700;">Oculta</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 0.75rem; text-align: right; white-space: nowrap;">
                        <a href="../guia.php?slug=<?php echo urlencode($slug); ?>" target="_blank" class="btn btn-sm" style="background: #f1f5f9; color: #0f172a; text-decoration: none; margin-right: 4px;" title="Ver en la web">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="seo.php?edit=<?php echo urlencode($slug); ?>#editor_guia" class="btn btn-sm" style="background: var(--primary); color: white; text-decoration: none; margin-right: 4px;">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <?php if (!empty($g['id'])): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="seo_action" value="toggle_active">
                            <input type="hidden" name="guide_id" value="<?php echo (int)$g['id']; ?>">
                            <input type="hidden" name="new_state" value="<?php echo $isActive ? 0 : 1; ?>">
                            <button type="submit" class="btn btn-sm" style="background: <?php echo $isActive ? '#fef3c7' : '#dcfce7'; ?>; color: <?php echo $isActive ? '#92400e' : '#166534'; ?>; font-weight: 600;" title="<?php echo $isActive ? 'Ocultar guía sin borrar datos' : 'Reactivar guía'; ?>">
                                <i class="fas <?php echo $isActive ? 'fa-eye-slash' : 'fa-check'; ?>"></i>
                                <?php echo $isActive ? 'Ocultar' : 'Activar'; ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function addQuickFactRow() {
    const c = document.getElementById('qf_container');
    if (!c) return;
    const div = document.createElement('div');
    div.style.cssText = 'display: grid; grid-template-columns: 1fr 2fr auto; gap: 0.5rem; align-items: center;';
    div.innerHTML = '<input type="text" name="qf_label[]" placeholder="Etiqueta" style="padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 6px;">' +
                    '<input type="text" name="qf_value[]" placeholder="Valor" style="padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 6px;">' +
                    '<button type="button" onclick="this.parentElement.remove()" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;">&times;</button>';
    c.appendChild(div);
}

function addSectionRow() {
    const c = document.getElementById('sections_container');
    if (!c) return;
    const div = document.createElement('div');
    div.style.cssText = 'background: white; padding: 0.9rem; border-radius: 8px; border: 1px solid #cbd5e1;';
    div.innerHTML = '<div style="display: flex; gap: 0.6rem; margin-bottom: 0.5rem; align-items: center;">' +
                    '<input type="hidden" name="sec_id[]" value="">' +
                    '<input type="text" name="sec_h2[]" placeholder="Título del apartado (H2)" style="flex: 1; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700;">' +
                    '<button type="button" onclick="this.parentElement.parentElement.remove()" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;">Quitar apartado</button>' +
                    '</div>' +
                    '<textarea name="sec_content[]" rows="4" placeholder="Contenido HTML..." style="width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 0.9rem;"></textarea>';
    c.appendChild(div);
}

function addFaqRow() {
    const c = document.getElementById('faqs_container');
    if (!c) return;
    const div = document.createElement('div');
    div.style.cssText = 'background: white; padding: 0.8rem; border-radius: 8px; border: 1px solid #cbd5e1;';
    div.innerHTML = '<div style="display: flex; gap: 0.5rem; margin-bottom: 0.4rem;">' +
                    '<input type="text" name="faq_q[]" placeholder="Pregunta frecuente..." style="flex: 1; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700;">' +
                    '<button type="button" onclick="this.parentElement.parentElement.remove()" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;">&times;</button>' +
                    '</div>' +
                    '<textarea name="faq_a[]" rows="2" placeholder="Respuesta..." style="width: 100%; padding: 0.55rem; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;"></textarea>';
    c.appendChild(div);
}
</script>

<?php adminFooter(); ?>
