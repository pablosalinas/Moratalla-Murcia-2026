<?php
// index.php - v1.3.0 (Curiosidades / Accesos externos)
require_once 'inc/header.php';

// Contador global de visitas a la web (movido a header.php)
$stmtGlobal = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'global_visits'");
$globalVisits = $stmtGlobal->fetchColumn() ?: 0;
?>

<!-- CELEBRACIONES DINÁMICAS -->
<?php
try {
    $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('celebration_days_before', 'celebration_days_after', 'celebration_cycle_time', 'celebration_cycle_limit')");
    $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
    $daysBefore = isset($settings['celebration_days_before']) ? (int)$settings['celebration_days_before'] : 5;
    $daysAfter = isset($settings['celebration_days_after']) ? (int)$settings['celebration_days_after'] : 2;
    $cycleTimeMs = isset($settings['celebration_cycle_time']) ? (int)$settings['celebration_cycle_time'] * 1000 : 5000;
    $cycleLimit = isset($settings['celebration_cycle_limit']) ? (int)$settings['celebration_cycle_limit'] : 0;

    $stmtCeleb = $pdo->query("SELECT * FROM celebrations WHERE is_active = 1");
    $now = new DateTime();
    while ($celeb = $stmtCeleb->fetch()) {
        $shouldShow = false;
        $recurrence = $celeb['recurrence'] ?? 'none';
        
        if (empty($recurrence) || $recurrence === 'none') {
            $start = $celeb['start_date'] ? new DateTime($celeb['start_date']) : null;
            $end = $celeb['end_date'] ? new DateTime($celeb['end_date']) : null;
            if ((!$start || $start <= $now) && (!$end || $end >= $now)) {
                $shouldShow = true;
            }
        } else {
            $occurrences = [];
            if ($recurrence === 'easter') {
                $year = $now->format('Y');
                $easterDays = easter_days($year);
                $easterDate = new DateTime($year . '-03-21');
                $easterDate->modify("+{$easterDays} days");
                
                $offset = (int)($celeb['easter_offset'] ?? 0);
                $easterDate->modify(($offset >= 0 ? "+{$offset}" : $offset) . " days");
                $easterDate->setTime(0, 0, 0);
                $occurrences[] = $easterDate;
            } elseif (!empty($celeb['event_date'])) {
                $baseDate = new DateTime($celeb['event_date']);
                if ($recurrence === 'annual') {
                    for ($y = -1; $y <= 1; $y++) {
                        $occ = clone $now; 
                        $occ->setDate($now->format('Y') + $y, $baseDate->format('m'), $baseDate->format('d')); 
                        $occ->setTime(0,0,0);
                        $occurrences[] = $occ;
                    }
                } elseif ($recurrence === 'monthly') {
                    for ($m = -1; $m <= 1; $m++) {
                        $occ = clone $now; 
                        $occ->setDate($now->format('Y'), $now->format('m') + $m, $baseDate->format('d')); 
                        $occ->setTime(0,0,0);
                        $occurrences[] = $occ;
                    }
                }
            }
            
            foreach ($occurrences as $occ) {
                $windowStart = clone $occ;
                $windowStart->modify("-{$daysBefore} days");
                $windowEnd = clone $occ;
                $windowEnd->modify("+{$daysAfter} days");
                $windowEnd->setTime(23, 59, 59);
                
                if ($now >= $windowStart && $now <= $windowEnd) {
                    $shouldShow = true;
                    break;
                }
            }
        }
        
        if ($shouldShow) {
            $activeCelebrations[] = $celeb;
        }
    }
    
    if (!empty($activeCelebrations)) {
        echo "<div id='celebrations-container'>\n";
        foreach ($activeCelebrations as $index => $celeb) {
            $display = $index === 0 ? 'block' : 'none';
            echo "<div class='celebration-item' id='celebration-item-{$index}' style='display: {$display};'>\n";
            if (!empty($celeb['html_content'])) {
                echo $celeb['html_content'] . "\n";
            }
            if (!empty($celeb['css_content'])) {
                echo "<style>\n" . $celeb['css_content'] . "\n</style>\n";
            }
            echo "</div>\n";
        }
        echo "</div>\n";
        
        echo "<script>\n";
        echo "document.addEventListener('DOMContentLoaded', function() {\n";
        echo "    const items = document.querySelectorAll('.celebration-item');\n";
        echo "    let celebIndex = 0;\n";
        echo "    const celebFuncs = [];\n";
        
        foreach ($activeCelebrations as $index => $celeb) {
            echo "    celebFuncs[{$index}] = function() {\n";
            echo "        document.dispatchEvent(new Event('celebrationChange'));\n";
            if (!empty($celeb['js_content'])) {
                echo "        try {\n";
                // We wrap in IIFE in case they have variable declarations to avoid collisions if eval'd
                echo "            (function() {\n";
                echo $celeb['js_content'] . "\n";
                echo "            })();\n";
                echo "        } catch(e) { console.error('Error running celebration JS', e); }\n";
            }
            echo "    };\n";
        }
        
        echo "    const cycleTimeMs = {$cycleTimeMs};\n";
        echo "    const cycleLimit = {$cycleLimit};\n";
        echo "    let cyclesCompleted = parseInt(sessionStorage.getItem('celebCyclesCompleted') || '0');\n";
        echo "    if (cycleLimit > 0 && cyclesCompleted >= cycleLimit) {\n";
        echo "        items.forEach(item => item.style.display = 'none');\n";
        echo "    } else {\n";
        echo "        if (items.length > 0) {\n";
        echo "            celebFuncs[0]();\n";
        echo "        }\n";
        echo "        if (items.length > 1) {\n";
        echo "            const intervalId = setInterval(() => {\n";
        echo "                items[celebIndex].style.display = 'none';\n";
        echo "                celebIndex = (celebIndex + 1) % items.length;\n";
        echo "                items[celebIndex].style.display = 'block';\n";
        echo "                celebFuncs[celebIndex]();\n";
        echo "                if (celebIndex === 0) {\n";
        echo "                    cyclesCompleted++;\n";
        echo "                    sessionStorage.setItem('celebCyclesCompleted', cyclesCompleted);\n";
        echo "                    if (cycleLimit > 0 && cyclesCompleted >= cycleLimit) {\n";
        echo "                        clearInterval(intervalId);\n";
        echo "                        document.dispatchEvent(new Event('celebrationChange'));\n";
        echo "                        items.forEach(item => item.style.display = 'none');\n";
        echo "                    }\n";
        echo "                }\n";
        echo "            }, cycleTimeMs);\n";
        echo "        } else if (items.length === 1 && cycleLimit > 0) {\n";
        echo "            setTimeout(() => {\n";
        echo "                cyclesCompleted++;\n";
        echo "                sessionStorage.setItem('celebCyclesCompleted', cyclesCompleted);\n";
        echo "                document.dispatchEvent(new Event('celebrationChange'));\n";
        echo "                items[0].style.display = 'none';\n";
        echo "            }, cycleTimeMs * cycleLimit);\n";
        echo "        }\n";
        echo "    }\n";
        echo "});\n";
        echo "</script>\n";
    }
} catch (Exception $e) {
    // Log or ignore silently
}
?>
<!-- FIN CELEBRACIONES -->

<div class="container main-content" style="margin-top: 2rem;">

    <!-- BUSCADOR GLOBAL DE MORATALLA -->
    <div class="global-search-hero-box">
        <div class="global-search-hero-top">
            <div>
                <span class="global-search-pill-badge"><i class="fas fa-compass"></i> Buscador Global de Moratalla</span>
                <h2 class="global-search-hero-title">¿Qué información deseas localizar en la web?</h2>
                <p class="global-search-hero-sub">Busca en noticias, eventos, asociaciones, artesanos, alojamientos, restauración, historia y citas célebres.</p>
            </div>
            <button type="button" onclick="openGlobalSearchModal()" class="btn-open-search-dialog">
                <i class="fas fa-window-restore"></i> Abrir Cuadro de Búsqueda Avanzada
            </button>
        </div>

        <form onsubmit="submitHeroGlobalSearch(event)" class="global-search-bar-form">
            <div class="global-search-input-wrap">
                <i class="fas fa-search global-search-icon"></i>
                <input type="text" id="heroGlobalSearchInput" placeholder="Escribe una o varias palabras (ej: castillo, tambor, casa rural, restaurante, esparto, cita...)" autocomplete="off">
                <button type="submit" class="global-search-submit-btn">
                    <i class="fas fa-search"></i> Buscar Ahora
                </button>
            </div>

            <!-- Marca de Ámbito (Web moratalla-murcia por defecto vs Internet Global) y Filtro OR (por defecto) / AND -->
            <div class="global-search-options-row">
                <div class="global-search-mode-selector" title="Elige dónde realizar la búsqueda">
                    <span class="mode-label"><i class="fas fa-globe"></i> Dónde buscar:</span>
                    <label class="mode-radio-pill active" id="heroLblScopeWeb">
                        <input type="radio" name="hero_search_scope" value="web" checked onchange="syncSearchScope('web')">
                        <i class="fas fa-check-square"></i> <strong>Web moratalla-murcia</strong> (Por defecto)
                    </label>
                    <label class="mode-radio-pill" id="heroLblScopeInternet">
                        <input type="radio" name="hero_search_scope" value="internet" onchange="syncSearchScope('internet')">
                        <i class="far fa-square"></i> 🌍 <strong>Internet Global</strong>
                    </label>
                    <label class="mode-radio-pill" id="heroLblScopeBoth">
                        <input type="radio" name="hero_search_scope" value="both" onchange="syncSearchScope('both')">
                        <i class="far fa-square"></i> 🔄 <strong>Web + Internet</strong>
                    </label>
                </div>

                <div class="global-search-mode-selector" title="Elige cómo combinar varias palabras en la búsqueda">
                    <span class="mode-label"><i class="fas fa-sliders-h"></i> Filtro de palabras:</span>
                    <label class="mode-radio-pill active" id="heroLblModeOr">
                        <input type="radio" name="hero_search_mode" value="OR" checked onchange="syncSearchMode('OR')">
                        <i class="fas fa-check-square"></i> <strong>OR</strong> (Cualquier palabra · Por defecto)
                    </label>
                    <label class="mode-radio-pill" id="heroLblModeAnd">
                        <input type="radio" name="hero_search_mode" value="AND" onchange="syncSearchMode('AND')">
                        <i class="far fa-square"></i> <strong>AND</strong> (Todas las palabras)
                    </label>
                </div>
            </div>

            <div class="global-search-options-row" style="margin-top: 0.7rem; padding-top: 0.65rem; border-top: 1px solid rgba(255,255,255,0.15);">
                <div class="global-search-quick-tags">
                    <span>Explorar en la web:</span>
                    <button type="button" onclick="openGlobalSearchWithPreset('Moratalla', 'noticias')">📰 Noticias</button>
                    <button type="button" onclick="openGlobalSearchWithPreset('Moratalla', 'eventos')">📅 Eventos</button>
                    <button type="button" onclick="openGlobalSearchWithPreset('Asociación Club Peña', 'asociaciones')">🤝 Asociaciones</button>
                    <button type="button" onclick="openGlobalSearchWithPreset('Artesanía Esparto Pintura', 'artesanos')">🏺 Artesanos</button>
                    <button type="button" onclick="openGlobalSearchWithPreset('Casa Rural Hotel', 'alojamientos')">🛏️ Alojamientos</button>
                    <button type="button" onclick="openGlobalSearchWithPreset('Restaurante Bar', 'restauracion')">🍽️ Restauración</button>
                    <button type="button" onclick="openGlobalSearchWithPreset('Moratalla', 'citas')">💬 Citas</button>
                </div>
            </div>
        </form>
    </div>

    <!-- CUADRO DE DIÁLOGO MODAL DEL BUSCADOR GLOBAL -->
    <div id="globalSearchModal" class="gs-modal-overlay" style="display: none;" onclick="if(event.target===this) closeGlobalSearchModal()">
        <div class="gs-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gsModalTitle">
            <div class="gs-modal-header">
                <div>
                    <h3 id="gsModalTitle"><i class="fas fa-search-location" style="color: #d4af37;"></i> Buscador Global · moratalla-murcia.com</h3>
                    <p>Busca por defecto en toda la web moratalla-murcia.com (noticias, eventos, asociaciones, artesanos, alojamientos, restauración, citas) o en Internet Global.</p>
                </div>
                <button type="button" class="gs-close-btn" onclick="closeGlobalSearchModal()" title="Cerrar (Esc)">&times;</button>
            </div>

            <div class="gs-modal-controls">
                <div class="gs-dialog-input-row">
                    <div class="gs-dialog-input-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="modalGlobalSearchInput" placeholder="Escribe aquí lo que buscas (ej: tambor castillo, casa rural, restaurante, cita...)" autocomplete="off" onkeydown="if(event.key==='Enter'){event.preventDefault(); executeGlobalSearch();}">
                        <button type="button" id="gsClearBtn" onclick="clearGlobalSearchInput()" style="display:none;" title="Limpiar texto">&times;</button>
                    </div>
                    <button type="button" onclick="executeGlobalSearch()" class="gs-dialog-search-btn">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>

                <!-- Selección de Ámbito (Web moratalla-murcia por defecto / Internet Global) y Marca OR / AND -->
                <div class="gs-dialog-filter-bar">
                    <div class="gs-mode-box">
                        <span class="gs-mode-title"><i class="fas fa-globe"></i> Ámbito:</span>
                        <label class="gs-mode-option active" id="modalLblScopeWeb">
                            <input type="radio" name="modal_search_scope" value="web" checked onchange="syncSearchScope('web'); executeGlobalSearch();">
                            <span class="gs-check-mark">✓</span>
                            <span>🌐 <strong>Web moratalla-murcia</strong> (Por defecto)</span>
                        </label>
                        <label class="gs-mode-option" id="modalLblScopeInternet">
                            <input type="radio" name="modal_search_scope" value="internet" onchange="syncSearchScope('internet'); executeGlobalSearch();">
                            <span class="gs-check-mark">✓</span>
                            <span>🌍 <strong>Internet Global</strong></span>
                        </label>
                        <label class="gs-mode-option" id="modalLblScopeBoth">
                            <input type="radio" name="modal_search_scope" value="both" onchange="syncSearchScope('both'); executeGlobalSearch();">
                            <span class="gs-check-mark">✓</span>
                            <span>🔄 <strong>Web + Internet</strong></span>
                        </label>
                    </div>

                    <div class="gs-mode-box">
                        <span class="gs-mode-title"><i class="fas fa-filter"></i> Palabras:</span>
                        <label class="gs-mode-option active" id="modalLblModeOr">
                            <input type="radio" name="modal_search_mode" value="OR" checked onchange="syncSearchMode('OR'); executeGlobalSearch();">
                            <span class="gs-check-mark">✓</span>
                            <span><strong>OR</strong> (<u>Cualquiera</u> · Por defecto)</span>
                        </label>
                        <label class="gs-mode-option" id="modalLblModeAnd">
                            <input type="radio" name="modal_search_mode" value="AND" onchange="syncSearchMode('AND'); executeGlobalSearch();">
                            <span class="gs-check-mark">✓</span>
                            <span><strong>AND</strong> (<u>Todas</u>)</span>
                        </label>
                    </div>
                    <div id="gsSummaryCounter" class="gs-summary-counter"></div>
                </div>

                <!-- Pestañas de filtrado por ámbito -->
                <div class="gs-category-tabs" id="gsCategoryTabs">
                    <button type="button" class="gs-tab active" data-group="all" onclick="setGlobalSearchGroup('all')">📋 Todos <span id="gsCount_all">0</span></button>
                    <button type="button" class="gs-tab" data-group="noticias" onclick="setGlobalSearchGroup('noticias')">📰 Noticias <span id="gsCount_noticias">0</span></button>
                    <button type="button" class="gs-tab" data-group="eventos" onclick="setGlobalSearchGroup('eventos')">📅 Eventos <span id="gsCount_eventos">0</span></button>
                    <button type="button" class="gs-tab" data-group="asociaciones" onclick="setGlobalSearchGroup('asociaciones')">🤝 Asociaciones <span id="gsCount_asociaciones">0</span></button>
                    <button type="button" class="gs-tab" data-group="artesanos" onclick="setGlobalSearchGroup('artesanos')">🏺 Artesanos <span id="gsCount_artesanos">0</span></button>
                    <button type="button" class="gs-tab" data-group="alojamientos" onclick="setGlobalSearchGroup('alojamientos')">🛏️ Alojamientos <span id="gsCount_alojamientos">0</span></button>
                    <button type="button" class="gs-tab" data-group="restauracion" onclick="setGlobalSearchGroup('restauracion')">🍽️ Restauración <span id="gsCount_restauracion">0</span></button>
                    <button type="button" class="gs-tab" data-group="citas" onclick="setGlobalSearchGroup('citas')">💬 Citas <span id="gsCount_citas">0</span></button>
                    <button type="button" class="gs-tab" data-group="paginas" onclick="setGlobalSearchGroup('paginas')">📄 Otras Secciones <span id="gsCount_paginas">0</span></button>
                    <button type="button" class="gs-tab" data-group="internet" onclick="setGlobalSearchGroup('internet')">🌍 Internet Global <span id="gsCount_internet">0</span></button>
                </div>
            </div>

            <div class="gs-modal-body" id="gsResultsContainer">
                <div class="gs-empty-state">
                    <i class="fas fa-compass" style="font-size: 2.6rem; color: #2d6a4f; margin-bottom: 0.8rem;"></i>
                    <h4>Explora todo el contenido de Moratalla o busca en Internet Global</h4>
                    <p>Por defecto se busca dentro de <strong>moratalla-murcia.com</strong> con filtro <strong>OR</strong> (cualquier palabra). Puedes marcar <strong>Internet Global</strong> para buscar en toda la red o <strong>AND</strong> para exigir todas las palabras.</p>
                </div>
            </div>
        </div>
    </div>

    <style>
        .global-search-hero-box {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            border-radius: 20px;
            padding: 1.8rem 2rem;
            margin-bottom: 2.2rem;
            color: #ffffff;
            box-shadow: 0 12px 32px rgba(27, 67, 50, 0.18);
            border: 2px solid rgba(212, 175, 55, 0.45);
        }
        .global-search-hero-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.2rem;
        }
        .global-search-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(212, 175, 55, 0.22);
            color: #fde68a;
            border: 1px solid rgba(212, 175, 55, 0.5);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 0.4rem;
        }
        .global-search-hero-title {
            margin: 0;
            font-size: 1.55rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.25;
        }
        .global-search-hero-sub {
            margin: 0.3rem 0 0 0;
            font-size: 0.92rem;
            color: rgba(255, 255, 255, 0.86);
        }
        .btn-open-search-dialog {
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.35);
            padding: 0.6rem 1.1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-open-search-dialog:hover {
            background: #d4af37;
            color: #1b4332;
            border-color: #d4af37;
        }
        .global-search-input-wrap {
            display: flex;
            align-items: center;
            background: #ffffff;
            border-radius: 14px;
            padding: 6px 6px 6px 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.14);
            gap: 10px;
        }
        .global-search-icon {
            color: #2d6a4f;
            font-size: 1.15rem;
        }
        .global-search-input-wrap input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 1rem;
            color: #1e293b;
            padding: 0.65rem 0.4rem;
            font-family: inherit;
            min-width: 0;
        }
        .global-search-submit-btn {
            background: linear-gradient(135deg, #d4af37 0%, #b89428 100%);
            color: #1b4332;
            border: none;
            padding: 0.78rem 1.5rem;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.95rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: transform 0.15s, filter 0.15s;
        }
        .global-search-submit-btn:hover {
            filter: brightness(1.06);
            transform: translateY(-1px);
        }
        .global-search-options-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 1rem;
        }
        .global-search-mode-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .global-search-mode-selector .mode-label {
            font-size: 0.82rem;
            color: #fde68a;
            font-weight: 700;
        }
        .mode-radio-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.28);
            color: rgba(255, 255, 255, 0.9);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            cursor: pointer;
            user-select: none;
            transition: all 0.15s;
        }
        .mode-radio-pill input {
            display: none;
        }
        .mode-radio-pill.active {
            background: #fde68a;
            color: #1b4332;
            border-color: #fde68a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .global-search-quick-tags {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .global-search-quick-tags span {
            font-size: 0.78rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
        }
        .global-search-quick-tags button {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .global-search-quick-tags button:hover {
            background: #ffffff;
            color: #1b4332;
        }

        /* Modal del Buscador Global */
        .gs-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.72);
            backdrop-filter: blur(5px);
            z-index: 999990;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.2rem;
        }
        .gs-modal-dialog {
            background: #ffffff;
            width: 100%;
            max-width: 960px;
            max-height: 88vh;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 2px solid #2d6a4f;
        }
        .gs-modal-header {
            background: linear-gradient(135deg, #1b4332 0%, #2d6a4f 100%);
            color: #ffffff;
            padding: 1.2rem 1.6rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .gs-modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
        }
        .gs-modal-header p {
            margin: 0.2rem 0 0 0;
            font-size: 0.84rem;
            color: rgba(255, 255, 255, 0.85);
        }
        .gs-close-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: #ffffff;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .gs-close-btn:hover {
            background: #ef4444;
        }
        .gs-modal-controls {
            padding: 1.1rem 1.5rem 0.8rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .gs-dialog-input-row {
            display: flex;
            gap: 10px;
            margin-bottom: 0.85rem;
        }
        .gs-dialog-input-box {
            flex: 1;
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            padding: 0 12px;
            gap: 10px;
            transition: border-color 0.2s;
        }
        .gs-dialog-input-box:focus-within {
            border-color: #2d6a4f;
        }
        .gs-dialog-input-box i {
            color: #2d6a4f;
            font-size: 1.05rem;
        }
        .gs-dialog-input-box input {
            flex: 1;
            border: none;
            outline: none;
            padding: 0.72rem 0;
            font-size: 0.98rem;
            font-family: inherit;
            color: #0f172a;
        }
        #gsClearBtn {
            background: #e2e8f0;
            border: none;
            color: #475569;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
        }
        .gs-dialog-search-btn {
            background: #1b4332;
            color: #ffffff;
            border: none;
            padding: 0 1.4rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .gs-dialog-search-btn:hover {
            background: #2d6a4f;
        }
        .gs-dialog-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 0.85rem;
        }
        .gs-mode-box {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .gs-mode-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #334155;
        }
        .gs-mode-option {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: 0.8rem;
            cursor: pointer;
            user-select: none;
        }
        .gs-mode-option input {
            display: none;
        }
        .gs-mode-option .gs-check-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid #94a3b8;
            color: transparent;
            font-size: 0.72rem;
            font-weight: 900;
        }
        .gs-mode-option.active {
            border: 2px solid #1b4332;
            background: #ecfdf5;
            color: #1b4332;
        }
        .gs-mode-option.active .gs-check-mark {
            background: #1b4332;
            border-color: #1b4332;
            color: #ffffff;
        }
        .gs-summary-counter {
            font-size: 0.82rem;
            font-weight: 700;
            color: #1b4332;
        }
        .gs-category-tabs {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .gs-tab {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 0.79rem;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s;
        }
        .gs-tab span {
            background: #f1f5f9;
            color: #475569;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 0.72rem;
        }
        .gs-tab.active {
            background: #1b4332;
            border-color: #1b4332;
            color: #ffffff;
        }
        .gs-tab.active span {
            background: #d4af37;
            color: #1b4332;
        }
        .gs-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.2rem 1.5rem;
            background: #ffffff;
        }
        .gs-empty-state {
            text-align: center;
            padding: 2.5rem 1rem;
            color: #64748b;
        }
        .gs-empty-state h4 {
            margin: 0 0 0.4rem 0;
            color: #1e293b;
            font-size: 1.1rem;
        }
        .gs-results-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.85rem;
        }
        .gs-result-card {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            padding: 1rem 1.15rem;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        }
        .gs-result-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(27, 67, 50, 0.1);
            border-color: #2d6a4f;
        }
        .gs-result-thumb {
            width: 76px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f1f5f9;
        }
        .gs-result-icon-box {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #1b4332;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            flex-shrink: 0;
        }
        .gs-result-content {
            flex: 1;
            min-width: 0;
        }
        .gs-result-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }
        .gs-result-badge {
            background: #1b4332;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 9px;
            border-radius: 12px;
            letter-spacing: 0.4px;
        }
        .gs-result-sub {
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 600;
        }
        .gs-result-title {
            margin: 0 0 4px 0;
            font-size: 1.02rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
        }
        .gs-result-snippet {
            margin: 0;
            font-size: 0.87rem;
            color: #475569;
            line-height: 1.5;
        }
        .gs-highlight {
            background: #fef08a;
            color: #1e293b;
            padding: 0 3px;
            border-radius: 3px;
            font-weight: 700;
        }
        @media (max-width: 640px) {
            .global-search-hero-box { padding: 1.3rem 1.1rem; }
            .global-search-hero-title { font-size: 1.25rem; }
            .global-search-input-wrap { flex-direction: column; align-items: stretch; padding: 10px; }
            .global-search-icon { display: none; }
            .global-search-submit-btn { justify-content: center; }
        }
    </style>

    <script>
    let gsCurrentMode = 'OR';    // Por defecto OR
    let gsCurrentScope = 'web';  // Por defecto busca en la web moratalla-murcia.com
    let gsCurrentGroup = 'all';
    let gsCachedResults = [];
    let gsCachedWords = [];
    let gsDebounceTimer = null;

    function syncSearchMode(mode) {
        gsCurrentMode = (mode === 'AND') ? 'AND' : 'OR';

        // Actualizar radios y etiquetas en barra principal y modal
        document.querySelectorAll('input[name="hero_search_mode"], input[name="modal_search_mode"]').forEach(r => {
            r.checked = (r.value === gsCurrentMode);
        });

        const heroOr = document.getElementById('heroLblModeOr');
        const heroAnd = document.getElementById('heroLblModeAnd');
        if (heroOr && heroAnd) {
            heroOr.classList.toggle('active', gsCurrentMode === 'OR');
            heroAnd.classList.toggle('active', gsCurrentMode === 'AND');
            heroOr.querySelector('i').className = (gsCurrentMode === 'OR') ? 'fas fa-check-square' : 'far fa-square';
            heroAnd.querySelector('i').className = (gsCurrentMode === 'AND') ? 'fas fa-check-square' : 'far fa-square';
        }

        const modalOr = document.getElementById('modalLblModeOr');
        const modalAnd = document.getElementById('modalLblModeAnd');
        if (modalOr && modalAnd) {
            modalOr.classList.toggle('active', gsCurrentMode === 'OR');
            modalAnd.classList.toggle('active', gsCurrentMode === 'AND');
        }
    }

    function syncSearchScope(scope) {
        gsCurrentScope = (scope === 'internet' || scope === 'both') ? scope : 'web';

        document.querySelectorAll('input[name="hero_search_scope"], input[name="modal_search_scope"]').forEach(r => {
            r.checked = (r.value === gsCurrentScope);
        });

        const heroScopes = {
            web: document.getElementById('heroLblScopeWeb'),
            internet: document.getElementById('heroLblScopeInternet'),
            both: document.getElementById('heroLblScopeBoth')
        };
        Object.keys(heroScopes).forEach(k => {
            const el = heroScopes[k];
            if (!el) return;
            const active = (k === gsCurrentScope);
            el.classList.toggle('active', active);
            const icon = el.querySelector('i');
            if (icon) icon.className = active ? 'fas fa-check-square' : 'far fa-square';
        });

        const modalScopes = {
            web: document.getElementById('modalLblScopeWeb'),
            internet: document.getElementById('modalLblScopeInternet'),
            both: document.getElementById('modalLblScopeBoth')
        };
        Object.keys(modalScopes).forEach(k => {
            const el = modalScopes[k];
            if (el) el.classList.toggle('active', k === gsCurrentScope);
        });

        if (gsCurrentScope === 'internet') {
            setGlobalSearchGroup('internet', false);
        } else if (gsCurrentScope === 'web' && gsCurrentGroup === 'internet') {
            setGlobalSearchGroup('all', false);
        }
    }

    function openGlobalSearchModal(initialQuery = '', initialGroup = 'all') {
        const modal = document.getElementById('globalSearchModal');
        const modalInput = document.getElementById('modalGlobalSearchInput');
        if (!modal) return;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        if (initialGroup) {
            setGlobalSearchGroup(initialGroup, false);
        }
        if (initialQuery !== '') {
            modalInput.value = initialQuery;
            executeGlobalSearch();
        } else if (modalInput.value.trim() !== '') {
            executeGlobalSearch();
        }
        setTimeout(() => modalInput.focus(), 80);
    }

    function closeGlobalSearchModal() {
        const modal = document.getElementById('globalSearchModal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    function submitHeroGlobalSearch(e) {
        if (e) e.preventDefault();
        const q = document.getElementById('heroGlobalSearchInput').value.trim();
        const defaultTab = (gsCurrentScope === 'internet') ? 'internet' : 'all';
        openGlobalSearchModal(q, defaultTab);
    }

    function openGlobalSearchWithPreset(queryText, groupName) {
        syncSearchScope('web');
        document.getElementById('heroGlobalSearchInput').value = queryText;
        openGlobalSearchModal(queryText, groupName);
    }

    function clearGlobalSearchInput() {
        const inp = document.getElementById('modalGlobalSearchInput');
        inp.value = '';
        document.getElementById('gsClearBtn').style.display = 'none';
        inp.focus();
    }

    function setGlobalSearchGroup(group, reRender = true) {
        gsCurrentGroup = group;
        document.querySelectorAll('#gsCategoryTabs .gs-tab').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-group') === group);
        });

        // Si pulsa la pestaña "Internet Global" estando en ámbito solo web, activar Web + Internet y buscar
        if (group === 'internet' && gsCurrentScope === 'web' && reRender) {
            syncSearchScope('both');
            gsCurrentGroup = 'internet';
            document.querySelectorAll('#gsCategoryTabs .gs-tab').forEach(btn => {
                btn.classList.toggle('active', btn.getAttribute('data-group') === 'internet');
            });
            executeGlobalSearch();
            return;
        }

        if (reRender && gsCachedResults.length > 0) {
            renderGlobalSearchResults();
        }
    }

    function escapeGsHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function highlightSearchWords(text, words) {
        let safe = escapeGsHtml(text);
        if (!words || !words.length) return safe;
        words.forEach(w => {
            if (!w || w.length < 2) return;
            const escapedW = w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp('(' + escapedW + ')', 'gi');
            safe = safe.replace(regex, '<mark class="gs-highlight">$1</mark>');
        });
        return safe;
    }

    function executeGlobalSearch() {
        const inp = document.getElementById('modalGlobalSearchInput');
        const q = inp.value.trim();
        const clearBtn = document.getElementById('gsClearBtn');
        const container = document.getElementById('gsResultsContainer');
        const counterEl = document.getElementById('gsSummaryCounter');

        clearBtn.style.display = q.length > 0 ? 'inline-block' : 'none';
        document.getElementById('heroGlobalSearchInput').value = q;

        if (q.length < 2) {
            container.innerHTML = '<div class="gs-empty-state"><i class="fas fa-keyboard" style="font-size:2.2rem; color:#2d6a4f; margin-bottom:0.6rem;"></i><h4>Escribe al menos 2 caracteres para buscar</h4><p>Por defecto busca en <strong>moratalla-murcia.com</strong> con filtro <strong>OR</strong>. Puedes elegir <strong>Internet Global</strong> o <strong>AND</strong>.</p></div>';
            counterEl.textContent = '';
            return;
        }

        const loadingLabel = (gsCurrentScope === 'internet')
            ? 'Buscando en Internet Global...'
            : (gsCurrentScope === 'both' ? 'Buscando en moratalla-murcia.com y en Internet Global...' : 'Buscando en la web moratalla-murcia.com...');
        container.innerHTML = '<div class="gs-empty-state"><i class="fas fa-spinner fa-spin" style="font-size:2.2rem; color:#2d6a4f; margin-bottom:0.6rem;"></i><h4>' + loadingLabel + '</h4></div>';

        const url = 'search_ajax.php?q=' + encodeURIComponent(q)
            + '&mode=' + encodeURIComponent(gsCurrentMode)
            + '&scope=' + encodeURIComponent(gsCurrentScope);
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    container.innerHTML = '<div class="gs-empty-state">Error al realizar la búsqueda.</div>';
                    return;
                }
                gsCachedResults = data.results || [];
                gsCachedWords = data.words || [];
                const counts = data.counts || {};

                ['all', 'noticias', 'eventos', 'asociaciones', 'artesanos', 'alojamientos', 'restauracion', 'citas', 'paginas', 'internet'].forEach(k => {
                    const badge = document.getElementById('gsCount_' + k);
                    if (badge) badge.textContent = counts[k] || 0;
                });

                const scopeLabel = (data.scope === 'internet') ? 'Internet Global' : (data.scope === 'both' ? 'Web + Internet' : 'Web moratalla-murcia');
                counterEl.innerHTML = 'Encontrados <strong>' + (data.total || 0) + '</strong> resultados en <strong>' + scopeLabel + '</strong> (Modo <strong>' + escapeGsHtml(data.mode) + '</strong>)';
                renderGlobalSearchResults();
            })
            .catch(() => {
                container.innerHTML = '<div class="gs-empty-state">No se pudo conectar con el buscador en este momento.</div>';
            });
    }

    function renderGlobalSearchResults() {
        const container = document.getElementById('gsResultsContainer');
        const filtered = gsCachedResults.filter((it, idx) => {
            it._idx = idx;
            if (gsCurrentGroup === 'all') return true;
            return it.group === gsCurrentGroup;
        });

        if (filtered.length === 0) {
            container.innerHTML = '<div class="gs-empty-state">' +
                '<i class="fas fa-search-minus" style="font-size:2.2rem; color:#94a3b8; margin-bottom:0.6rem;"></i>' +
                '<h4>No hay resultados en este apartado</h4>' +
                '<p>' + (gsCurrentMode === 'AND' ? 'Prueba cambiando la marca superior a <strong>OR (Cualquier palabra)</strong> o seleccionando la pestaña <strong>Todos</strong>.' : 'Prueba con otras palabras o pulsa en la pestaña <strong>Todos</strong>.') + '</p>' +
                '</div>';
            return;
        }

        container.innerHTML = '<div class="gs-results-list">' + filtered.map(it => {
            let mediaHtml = '';
            if (it.image && !/\.(mp4|webm|ogg|mov|pdf)$/i.test(it.image)) {
                mediaHtml = '<img src="' + escapeGsHtml(it.image) + '" class="gs-result-thumb" alt="" onerror="this.style.display=\'none\'">';
            } else {
                let iconInner = escapeGsHtml(it.icon || '📄');
                if (/\.(svg|png|jpg|jpeg|webp|gif)$/i.test(it.icon || '')) {
                    const iconSrc = (it.icon.indexOf('uploads/') === 0) ? it.icon : 'uploads/icons/' + it.icon;
                    iconInner = '<img src="' + escapeGsHtml(iconSrc) + '" style="width:28px; height:28px; object-fit:contain;">';
                } else if ((it.icon || '').indexOf('fa-') !== -1) {
                    iconInner = '<i class="' + escapeGsHtml(it.icon) + '"></i>';
                }
                mediaHtml = '<div class="gs-result-icon-box">' + iconInner + '</div>';
            }

            const titleHighlighted = highlightSearchWords(it.title || '', gsCachedWords);
            const snippetHighlighted = highlightSearchWords(it.snippet || '', gsCachedWords);
            const subtitleHtml = it.subtitle ? '<span class="gs-result-sub">' + escapeGsHtml(it.subtitle) + '</span>' : '';

            return '<div class="gs-result-card" onclick="openGlobalSearchResult(' + it._idx + ')">' +
                mediaHtml +
                '<div class="gs-result-content">' +
                    '<div class="gs-result-meta">' +
                        '<span class="gs-result-badge">' + escapeGsHtml(it.badge || 'Contenido') + '</span>' +
                        subtitleHtml +
                    '</div>' +
                    '<h4 class="gs-result-title">' + titleHighlighted + '</h4>' +
                    (snippetHighlighted ? '<p class="gs-result-snippet">' + snippetHighlighted + '</p>' : '') +
                '</div>' +
            '</div>';
        }).join('') + '</div>';
    }

    function openGlobalSearchResult(idx) {
        const it = gsCachedResults[idx];
        if (!it) return;

        if (it.news_modal && typeof openNewsModal === 'function') {
            closeGlobalSearchModal();
            openNewsModal(it.news_modal);
            return;
        }
        if (it.group === 'citas' && !it.url) {
            if (navigator.clipboard && it.snippet) {
                navigator.clipboard.writeText(it.snippet);
                alert('Cita copiada al portapapeles:\n\n' + it.snippet);
            }
            return;
        }
        if (it.url) {
            if (it.external) {
                window.open(it.url, '_blank', 'noopener');
            } else {
                window.location.href = it.url;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const modalInp = document.getElementById('modalGlobalSearchInput');
        if (modalInp) {
            modalInp.addEventListener('input', function() {
                clearTimeout(gsDebounceTimer);
                gsDebounceTimer = setTimeout(executeGlobalSearch, 280);
            });
        }
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeGlobalSearchModal();
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                openGlobalSearchModal();
            }
        });

        // Si se llega a index.php?buscar=... abrir el cuadro automáticamente
        const params = new URLSearchParams(window.location.search);
        if (params.has('buscar')) {
            openGlobalSearchModal(params.get('buscar') || '', 'all');
        }
    });
    </script>

    <div class="content-card">
        <h2 style="font-size: 2.2rem; color: var(--primary); margin-bottom: 2rem; text-align: center;">Noticias y Eventos Destacados</h2>
        
        <div class="news-grid">
            <?php
            $stmt = $pdo->query("SELECT * FROM news_events WHERE is_active_home = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY sort_order ASC, event_date DESC, id DESC");
            $hasNews = false;
            $isFirstNews = true;
            while ($news = $stmt->fetch()) {
                $hasNews = true;
                $isEvent = !empty($news['event_date']);
                $dateText = $isEvent ? date('d/m/Y', strtotime($news['event_date'])) : date('d/m/Y', strtotime($news['created_at']));
                $excerpt = mb_strimwidth(strip_tags($news['content']), 0, 140, '...');
                
                // Obtener imágenes adicionales de galería
                $stmtG = $pdo->prepare("SELECT id, image_path, caption, author FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id DESC");
                $stmtG->execute([$news['id']]);
                $gallery = $stmtG->fetchAll(PDO::FETCH_ASSOC);
                
                $mainImagePath = $news['image_path'];
                $mainImageCaption = $news['image_caption'] ?? '';
                $mainImageAuthor = $news['image_author'] ?? '';
                
                if (!empty($news['use_latest_gallery_image']) && !empty($gallery)) {
                    $latestImage = null;
                    $maxId = -1;
                    foreach ($gallery as $gImg) {
                        if ($gImg['id'] > $maxId) {
                            $maxId = $gImg['id'];
                            $latestImage = $gImg;
                        }
                    }
                    if ($latestImage) {
                        $mainImagePath = $latestImage['image_path'];
                        $mainImageCaption = $latestImage['caption'];
                        $mainImageAuthor = $latestImage['author'] ?? '';
                    }
                }
                
                $featuredClass = $isFirstNews ? 'news-card-featured' : '';
                $isFirstNews = false;
                ?>
                <div class="news-card <?php echo $featuredClass; ?>" onclick="openNewsModal(<?php echo htmlspecialchars(json_encode([
                    'title' => $news['title'],
                    'date' => $dateText,
                    'isEvent' => $isEvent,
                    'image' => $mainImagePath ? $mainImagePath : '',
                    'image_caption' => $mainImageCaption,
                    'image_author' => $mainImageAuthor,
                    'content' => $news['content'],
                    'gallery' => $gallery
                ])); ?>)">
                    <div class="news-card-img-wrapper">
                        <span class="news-badge <?php echo $isEvent ? 'event' : ''; ?>">
                            <?php echo $isEvent ? '<i class="fas fa-calendar-alt"></i> Evento' : '<i class="fas fa-newspaper"></i> Noticia'; ?>
                        </span>
                        <?php if ($mainImagePath): 
                            $newsExt = strtolower(pathinfo($mainImagePath, PATHINFO_EXTENSION));
                            $newsIsVideo = in_array($newsExt, ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp']);
                            $newsIsPdf = ($newsExt === 'pdf');
                        ?>
                            <?php if ($newsIsPdf): ?>
                                <div style="width:100%; height:100%; background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); display:flex; align-items:center; justify-content:center; color:white;">
                                    <i class="fas fa-file-pdf" style="font-size: 4rem;"></i>
                                </div>
                            <?php elseif ($newsIsVideo): ?>
                                <div style="position:relative; width:100%; height:100%; overflow:hidden;">
                                    <video src="<?php echo htmlspecialchars($mainImagePath); ?>" class="news-card-img" style="object-fit: cover; width: 100%; height: 100%; background: #000;" autoplay loop muted playsinline></video>
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 2rem; opacity: 0.8; text-shadow: 0 2px 5px rgba(0,0,0,0.5); pointer-events: none;"><i class="fas fa-play-circle"></i></div>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($mainImagePath); ?>" alt="<?php echo htmlspecialchars($news['title']); ?>" class="news-card-img">
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="width:100%; height:100%; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); display:flex; align-items:center; justify-content:center; color:rgba(255,255,255,0.2);">
                                <i class="<?php echo $isEvent ? 'fas fa-calendar-alt' : 'fas fa-newspaper'; ?>" style="font-size: 4rem;"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="news-card-body">
                        <div class="news-card-date"><?php echo $dateText; ?></div>
                        <h3 class="news-card-title"><?php echo htmlspecialchars($news['title']); ?></h3>
                        <p class="news-card-excerpt"><?php echo htmlspecialchars($excerpt); ?></p>
                        <div class="news-card-more">Leer más <i class="fas fa-arrow-right"></i></div>
                    </div>
                </div>
                <?php
            }
            if (!$hasNews) {
                ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: var(--text-light); font-style: italic;">
                    <i class="fas fa-info-circle" style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem; display: block;"></i>
                    No hay noticias o eventos destacados en este momento.
                </div>
                <?php
            }
            ?>
        </div>

        <div style="margin-top: 5rem; padding: 3rem; background: var(--bg-alt); border-radius: 20px; text-align: center;">
            <p style="color: var(--text-light); font-style: italic; max-width: 800px; margin: 0 auto;">"Moratalla no es solo un lugar, es un conjunto de historias, gentes y paisajes que perduran en el tiempo."</p>
        </div>
    </div>
</div>

<?php
// ─── Sección Curiosidades (accesos externos visibles) ───────────────────────
$stmtEL = $pdo->query("SELECT * FROM external_links WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC");
$externalLinks = $stmtEL ? $stmtEL->fetchAll() : [];

if (count($externalLinks) > 0):
?>
<section class="curiosidades-section">
    <div class="container">
        <div class="curiosidades-header">
            <span class="curiosidades-badge"><i class="fas fa-compass"></i> Descubre más</span>
            <h2 class="curiosidades-title">Curiosidades y Recursos</h2>
            <p class="curiosidades-subtitle">Explora enlaces de interés sobre Moratalla y su entorno</p>
        </div>
        <div class="curiosidades-grid">
            <?php
            $palettes = [
                ['bg' => '#e8f5e9', 'color' => '#2e7d32', 'icon' => 'fa-globe'],
                ['bg' => '#e3f2fd', 'color' => '#1565c0', 'icon' => 'fa-link'],
                ['bg' => '#fff8e1', 'color' => '#e65100', 'icon' => 'fa-map-marker-alt'],
                ['bg' => '#fce4ec', 'color' => '#880e4f', 'icon' => 'fa-landmark'],
                ['bg' => '#f3e5f5', 'color' => '#6a1b9a', 'icon' => 'fa-star'],
                ['bg' => '#e0f7fa', 'color' => '#006064', 'icon' => 'fa-book-open'],
            ];
            foreach ($externalLinks as $idx => $el):
                $p = $palettes[$idx % count($palettes)];
            ?>
            <a href="<?php echo htmlspecialchars($el['url']); ?>" target="_blank" rel="noopener noreferrer" class="curiosidad-card">
                <div class="curiosidad-icon" style="background:<?php echo $p['bg']; ?>; color:<?php echo $p['color']; ?>;">
                    <?php
                    $cardIcon = !empty($el['icon']) ? htmlspecialchars($el['icon']) : $p['icon'];
                    $lowerCard = strtolower($cardIcon);
                    if (preg_match('/\.(svg|png|jpg|jpeg|webp|gif)$/i', $lowerCard)) {
                        $src = (strpos($cardIcon, 'uploads/') === 0 || strpos($cardIcon, 'http') === 0) ? $cardIcon : 'uploads/icons/' . $cardIcon;
                        echo '<img src="' . htmlspecialchars($src) . '" alt="Icon" style="width: 24px; height: 24px; object-fit: contain;">';
                    } elseif (strpos($cardIcon, 'fa-') !== false) {
                        echo '<i class="' . (strpos($cardIcon, 'fas ') === false && strpos($cardIcon, 'far ') === false && strpos($cardIcon, 'fab ') === false ? 'fas ' : '') . $cardIcon . '"></i>';
                    } else {
                        echo '<span style="font-size: 1.2em;">' . $cardIcon . '</span>';
                    }
                    ?>
                </div>
                <div class="curiosidad-body">
                    <h3 class="curiosidad-title"><?php echo htmlspecialchars($el['title']); ?></h3>
                    <?php if (!empty($el['description'])): ?>
                        <p class="curiosidad-desc"><?php echo htmlspecialchars($el['description']); ?></p>
                    <?php endif; ?>
                    <span class="curiosidad-link-label">
                        Visitar enlace <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
    .curiosidades-section {
        background: linear-gradient(135deg, #f0faf4 0%, #e8f5e9 100%);
        padding: 5rem 0 4rem;
        margin-top: 3rem;
        border-top: 3px solid var(--accent, #c8a85a);
    }
    .curiosidades-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    .curiosidades-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--primary, #1b4332);
        color: white;
        padding: 6px 18px;
        border-radius: 20px;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }
    .curiosidades-title {
        font-size: 2rem;
        font-weight: 800;
        color: var(--primary, #1b4332);
        margin: 0.5rem 0;
        letter-spacing: -0.5px;
    }
    .curiosidades-subtitle {
        color: #555;
        font-size: 1rem;
        margin: 0;
    }
    .curiosidades-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }
    .curiosidad-card {
        background: white;
        border-radius: 16px;
        padding: 1.8rem;
        display: flex;
        gap: 1.2rem;
        align-items: flex-start;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        border: 1px solid rgba(0,0,0,0.05);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        position: relative;
        overflow: hidden;
    }
    .curiosidad-card::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(27,67,50,0.04) 0%, transparent 60%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .curiosidad-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.13);
    }
    .curiosidad-card:hover::after { opacity: 1; }
    .curiosidad-icon {
        flex-shrink: 0;
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
    }
    .curiosidad-body { flex: 1; min-width: 0; }
    .curiosidad-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1a1a1a;
        margin: 0 0 0.5rem;
        line-height: 1.3;
    }
    .curiosidad-desc {
        font-size: 0.88rem;
        color: #666;
        line-height: 1.5;
        margin: 0 0 0.8rem;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .curiosidad-link-label {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--primary, #1b4332);
        display: inline-flex;
        align-items: center;
        gap: 5px;
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    .curiosidad-card:hover .curiosidad-link-label { opacity: 1; }
    @media (max-width: 600px) {
        .curiosidades-grid { grid-template-columns: 1fr; }
        .curiosidades-title { font-size: 1.5rem; }
    }
</style>
<?php endif; ?>

<!-- Contador discreto de accesos globales -->
<div class="container" style="text-align: right; padding: 1rem 0; margin-top: 2rem; border-top: 1px solid var(--gray-200);">
    <span style="font-size: 0.85rem; color: var(--text-light); font-family: monospace; opacity: 0.7;">
        <i class="fas fa-chart-line"></i> Accesos totales: <?php echo number_format($globalVisits, 0, ',', '.'); ?>
    </span>
</div>

<?php
// Manejo automático de apertura de noticia por ID
if (isset($_GET['action']) && $_GET['action'] == 'ver_noticia' && isset($_GET['id'])) {
    $newsId = (int)$_GET['id'];
    $stmtVerNoticia = $pdo->prepare("SELECT * FROM news_events WHERE id = ?");
    $stmtVerNoticia->execute([$newsId]);
    $newsVer = $stmtVerNoticia->fetch();
    
    if ($newsVer) {
        $isEvent = !empty($newsVer['event_date']);
        $dateText = $isEvent ? date('d/m/Y', strtotime($newsVer['event_date'])) : date('d/m/Y', strtotime($newsVer['created_at']));
        
        $stmtG = $pdo->prepare("SELECT id, image_path, caption, author FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id DESC");
        $stmtG->execute([$newsId]);
        $gallery = $stmtG->fetchAll(PDO::FETCH_ASSOC);
        
        $mainImagePath = $newsVer['image_path'];
        $mainImageCaption = $newsVer['image_caption'] ?? '';
        $mainImageAuthor = $newsVer['image_author'] ?? '';
        
        if (!empty($newsVer['use_latest_gallery_image']) && !empty($gallery)) {
            $latestImage = null;
            $maxId = -1;
            foreach ($gallery as $gImg) {
                if ($gImg['id'] > $maxId) {
                    $maxId = $gImg['id'];
                    $latestImage = $gImg;
                }
            }
            if ($latestImage) {
                $mainImagePath = $latestImage['image_path'];
                $mainImageCaption = $latestImage['caption'];
                $mainImageAuthor = $latestImage['author'] ?? '';
            }
        }
        
        $newsData = [
            'title' => $newsVer['title'],
            'date' => $dateText,
            'isEvent' => $isEvent,
            'image' => $mainImagePath ? $mainImagePath : '',
            'image_caption' => $mainImageCaption,
            'image_author' => $mainImageAuthor,
            'content' => $newsVer['content'],
            'gallery' => $gallery
        ];
        
        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof openNewsModal === 'function') {
                openNewsModal(" . json_encode($newsData) . ");
            }
        });
        </script>";
    }
}
?>

<?php require_once 'inc/footer.php'; ?>
