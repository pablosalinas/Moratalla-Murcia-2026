<?php
// admin/inc/ai_news_panel.php
// Panel supervisado de localización de noticias de Moratalla, control anti-redundancia, redacción con Google Gemini y 3 opciones combinables de imagen
$hasGeminiKeyConfigured = !empty(getGeminiApiKey($pdo));
$autoOpenAiPanel = (isset($_GET['open_ai']) && $_GET['open_ai'] == '1');
?>

<style>
.ai-news-assistant {
    background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    border: 2px solid #c4b5fd;
    border-radius: 12px;
    padding: 1.4rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.08);
}
.ai-assistant-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.ai-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
}
.ai-chip-btn {
    background: white;
    border: 1px solid #c4b5fd;
    color: #4c1d95;
    padding: 5px 11px;
    border-radius: 20px;
    font-size: 0.8rem;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}
.ai-chip-btn:hover {
    background: #6d28d9;
    color: white;
    border-color: #6d28d9;
}
.ai-news-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.8rem;
    max-height: 440px;
    overflow-y: auto;
    padding-right: 4px;
    margin-top: 1rem;
}
.ai-news-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-left: 4px solid #6366f1;
    border-radius: 8px;
    padding: 0.9rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s;
}
.ai-news-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.ai-news-card.is-published {
    border-left-color: #10b981;
    background: #f0fdf4;
    opacity: 0.88;
}
.ai-news-card.is-discarded {
    border-left-color: #94a3b8;
    background: #f8fafc;
    opacity: 0.72;
}
.ai-img-tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid #ddd6fe;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.ai-img-tab {
    background: white;
    border: 1px solid #ddd6fe;
    border-bottom: none;
    padding: 0.65rem 1rem;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
    font-size: 0.85rem;
    color: #5b21b6;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ai-img-tab.active {
    background: #6d28d9;
    color: white;
    border-color: #6d28d9;
}
.ai-img-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(175px, 1fr));
    gap: 0.9rem;
    max-height: 360px;
    overflow-y: auto;
    padding: 4px;
}
.ai-img-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 0.4rem;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.ai-img-card img {
    width: 100%;
    height: 110px;
    object-fit: cover;
    border-radius: 6px;
    background: #f1f5f9;
}
.ai-mini-btn {
    border: none;
    padding: 5px 8px;
    border-radius: 5px;
    font-size: 0.74rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    transition: filter 0.15s;
}
.ai-mini-btn:hover {
    filter: brightness(1.08);
}
</style>

<div class="ai-news-assistant" id="aiNewsAssistantBox">
    <div class="ai-assistant-header">
        <div>
            <h4 style="color: #4c1d95; margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <i class="fas fa-magic" style="color: #7c3aed;"></i>
                <span>Localizador y Generador Supervisado de Noticias (Google Gemini IA)</span>
                <?php if ($hasGeminiKeyConfigured): ?>
                    <span class="ai-badge-pill" id="geminiKeyStatusBadge" style="background: #d1fae5; color: #065f46;">
                        <i class="fas fa-check-circle"></i> Gemini IA Activo
                    </span>
                <?php else: ?>
                    <span class="ai-badge-pill" id="geminiKeyStatusBadge" style="background: #fef3c7; color: #92400e;">
                        <i class="fas fa-key"></i> Clave Gemini Pendiente
                    </span>
                <?php endif; ?>
            </h4>
            <p style="color: #5b21b6; font-size: 0.83rem; margin: 0.3rem 0 0 0;">
                Busca noticias de Moratalla en prensa digital, filtra automáticamente las ya publicadas, redacta el borrador con IA y combina fotos (Original, IA o Archivo).
            </p>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button type="button" onclick="toggleGeminiKeyModal()" class="btn btn-sm" style="background: white; color: #4c1d95; border: 1px solid #c4b5fd; font-size: 0.8rem; padding: 0.45rem 0.8rem;">
                <i class="fas fa-key"></i> Configurar Clave Gemini
            </button>
            <button type="button" id="toggleAiBodyBtn" onclick="toggleAiAssistantBody()" class="btn btn-sm" style="background: #6d28d9; color: white; font-size: 0.85rem; padding: 0.5rem 1rem;">
                <i class="fas <?php echo $autoOpenAiPanel ? 'fa-chevron-up' : 'fa-search'; ?>"></i>
                <span><?php echo $autoOpenAiPanel ? 'Ocultar Buscador IA' : 'Abrir Buscador IA'; ?></span>
            </button>
        </div>
    </div>

    <!-- Mini panel para configurar clave API de Google Gemini sin salir de la página -->
    <div id="geminiKeyInlineBox" style="display: none; margin-top: 1rem; background: white; padding: 1rem; border-radius: 8px; border: 1px solid #c4b5fd;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <strong style="color: #4c1d95; font-size: 0.9rem;"><i class="fas fa-lock"></i> Clave API de Google Gemini (Se guarda cifrada con AES-256)</strong>
            <a href="https://aistudio.google.com/app/apikey" target="_blank" style="font-size: 0.8rem; color: #6d28d9; font-weight: 700;">
                <i class="fas fa-external-link-alt"></i> Obtener clave gratis en Google AI Studio
            </a>
        </div>
        <div style="display: flex; gap: 8px;">
            <input type="password" id="inlineGeminiApiKeyInput" placeholder="Pega aquí tu clave API de Google Gemini (AIzaSy...)" style="flex: 1; padding: 0.6rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
            <button type="button" onclick="saveInlineGeminiKey()" class="btn btn-sm" style="background: #059669; color: white; padding: 0 1rem;">
                <i class="fas fa-save"></i> Guardar Clave
            </button>
        </div>
    </div>

    <!-- Cuerpo desplegable del buscador y generador -->
    <div id="aiAssistantBody" style="display: <?php echo $autoOpenAiPanel ? 'block' : 'none'; ?>; margin-top: 1.2rem; border-top: 1px solid #ddd6fe; padding-top: 1.2rem;">
        
        <!-- PASO 1: BUSCADOR DE NOTICIAS EN INTERNET Y ENLACE DIRECTO -->
        <div style="background: white; padding: 1.1rem; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 1.2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem; margin-bottom: 0.8rem;">
                <strong style="color: #1e293b; font-size: 0.95rem;">
                    <i class="fas fa-globe-europe" style="color: #6366f1;"></i> 1. Localizar Noticias de Moratalla en Internet
                </strong>
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #334155; cursor: pointer; font-weight: 600; background: #f1f5f9; padding: 4px 10px; border-radius: 6px;">
                        <input type="checkbox" id="hideProcessedNewsChk" checked onchange="onToggleHideProcessedChk()">
                        <span>Ocultar ya publicadas o descartadas (Evitar redundancia)</span>
                    </label>
                </div>
            </div>

            <!-- Barra de búsqueda por términos -->
            <div style="display: flex; gap: 8px; margin-bottom: 0.6rem; flex-wrap: wrap;">
                <input type="text" id="aiNewsSearchQuery" value="Moratalla Murcia" placeholder="Ej: Moratalla Murcia, Ayuntamiento Moratalla, Castillo..." style="flex: 1; min-width: 220px; padding: 0.65rem 0.9rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;" onkeydown="if(event.key==='Enter'){event.preventDefault(); searchInternetNews();}">
                <button type="button" onclick="searchInternetNews()" id="btnSearchInternetNews" class="btn" style="background: #6d28d9; color: white; padding: 0.65rem 1.2rem; font-weight: 600;">
                    <i class="fas fa-search"></i> Buscar Noticias
                </button>
            </div>

            <!-- Búsqueda rápida por temas de Moratalla -->
            <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center; margin-bottom: 0.9rem;">
                <span style="font-size: 0.78rem; color: #64748b; font-weight: 600;">Temas rápidos:</span>
                <button type="button" class="ai-chip-btn" onclick="quickSearchTopic('Moratalla Murcia')">📰 General Moratalla</button>
                <button type="button" class="ai-chip-btn" onclick="quickSearchTopic('Banda de Música de Moratalla')">🎵 Banda de Música</button>
                <button type="button" class="ai-chip-btn" onclick="quickSearchTopic('Ayuntamiento de Moratalla')">🏛️ Ayuntamiento</button>
                <button type="button" class="ai-chip-btn" onclick="quickSearchTopic('Turismo Cultura Castillo Moratalla')">🏰 Turismo y Patrimonio</button>
                <button type="button" class="ai-chip-btn" onclick="quickSearchTopic('Fiestas Tambor Encierros Moratalla')">🥁 Fiestas y Tradiciones</button>
                <button type="button" class="ai-chip-btn" onclick="quickSearchTopic('Naturaleza Monte Río Alhárabe Moratalla')">🌲 Naturaleza y Pedanías</button>
            </div>

            <!-- Opción alternativa: pegar enlace directo de cualquier noticia -->
            <div style="background: #f8fafc; padding: 0.7rem 0.9rem; border-radius: 8px; border: 1px dashed #cbd5e1; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <span style="font-size: 0.82rem; font-weight: 600; color: #475569;"><i class="fas fa-link"></i> ¿Tienes el enlace directo de una noticia?</span>
                <input type="url" id="aiDirectArticleUrl" placeholder="https://www.laverdad.es/... o cualquier medio digital" style="flex: 1; min-width: 220px; padding: 0.45rem 0.7rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem;">
                <button type="button" onclick="generateFromDirectUrl()" class="btn btn-sm" style="background: #0f766e; color: white; padding: 0.45rem 0.9rem; font-size: 0.82rem;">
                    <i class="fas fa-wand-magic-sparkles"></i> Extraer y Redactar desde URL
                </button>
            </div>

            <!-- Botones interactivos de filtro y reconducción por estado (Disponibles, Publicadas, Descartadas) -->
            <div id="aiNewsStatsBar" style="display: none; margin-top: 0.9rem; padding: 0.6rem 0.8rem; background: #f8fafc; border-radius: 8px; font-size: 0.82rem; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: space-between; border: 1px solid #e2e8f0;">
                <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                    <span style="font-size: 0.78rem; color: #475569; font-weight: 700;"><i class="fas fa-filter"></i> Ver / Reconducir:</span>
                    <button type="button" id="btnFilterNew" onclick="setNewsStatusFilter('new')" style="border: 2px solid #4f46e5; background: #eef2ff; color: #3730a3; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.15s;">
                        <span id="statNewCount">🟢 0 Nuevas disponibles</span>
                    </button>
                    <button type="button" id="btnFilterPub" onclick="setNewsStatusFilter('published')" style="border: 1px solid #a7f3d0; background: #ffffff; color: #065f46; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.15s;">
                        <span id="statPubCount">🔵 0 Ya publicadas en la web</span>
                    </button>
                    <button type="button" id="btnFilterDisc" onclick="setNewsStatusFilter('discarded')" style="border: 1px solid #cbd5e1; background: #ffffff; color: #475569; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.15s;">
                        <span id="statDiscCount">⚪ 0 Descartadas</span>
                    </button>
                    <button type="button" id="btnFilterAll" onclick="setNewsStatusFilter('all')" style="border: 1px solid #ddd6fe; background: #ffffff; color: #5b21b6; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: all 0.15s;">
                        <span>📋 Ver todas</span>
                    </button>
                </div>
                <span id="activeFilterHint" style="font-size: 0.76rem; color: #64748b; font-weight: 600;">Mostrando: Nuevas disponibles</span>
            </div>

            <!-- Estado de carga de búsqueda -->
            <div id="aiSearchLoading" style="display: none; text-align: center; padding: 1.5rem; color: #5b21b6; font-weight: 600;">
                <i class="fas fa-spinner fa-spin fa-lg"></i> Buscando noticias recientes sobre Moratalla y verificando cuáles ya están publicadas...
            </div>

            <!-- Lista de resultados -->
            <div id="aiFoundNewsContainer" class="ai-news-list"></div>
        </div>

        <!-- Estado de carga al redactar borrador con Gemini -->
        <div id="aiDraftLoading" style="display: none; background: #fffbeb; border: 2px solid #f59e0b; border-radius: 10px; padding: 1.2rem; text-align: center; margin-bottom: 1.2rem; color: #92400e; font-weight: 600;">
            <i class="fas fa-robot fa-spin fa-lg" style="margin-right: 8px; color: #d97706;"></i>
            <span id="aiDraftLoadingText">Analizando noticia original, redactando borrador con Google Gemini y preparando las 3 opciones de fotografía...</span>
        </div>

        <!-- PASO 2: COMBINADOR DE LAS 3 OPCIONES DE FOTOGRAFÍA (A, B y C) -->
        <div id="aiImageCombinerPanel" style="background: white; padding: 1.1rem; border-radius: 10px; border: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.8rem;">
                <strong style="color: #1e293b; font-size: 0.95rem;">
                    <i class="fas fa-camera-retro" style="color: #7c3aed;"></i> 2. Selección y Combinación Supervisada de Fotografías (3 Opciones Combinables)
                </strong>
                <span style="font-size: 0.78rem; color: #64748b;">
                    Elige 1 como <strong>Foto Principal</strong> y añade todas las que quieras a la <strong>Galería de la Noticia</strong>.
                </span>
            </div>

            <!-- Pestañas A, B, C -->
            <div class="ai-img-tabs">
                <button type="button" class="ai-img-tab active" id="tabBtnOptA" onclick="switchAiImageTab('A')">
                    <i class="fas fa-newspaper"></i> Opción A: Fotos de la Noticia Original <span id="badgeCountOptA" class="ai-badge-pill" style="background:#ede9fe; color:#5b21b6; padding:1px 7px;">0</span>
                </button>
                <button type="button" class="ai-img-tab" id="tabBtnOptB" onclick="switchAiImageTab('B')">
                    <i class="fas fa-wand-magic-sparkles"></i> Opción B: Generar Foto con IA
                </button>
                <button type="button" class="ai-img-tab" id="tabBtnOptC" onclick="switchAiImageTab('C')">
                    <i class="fas fa-folder-open"></i> Opción C: Archivo Propio de Moratalla
                </button>
            </div>

            <!-- Contenido Opción A: Fotos extraídas de la noticia original -->
            <div id="tabContentOptA" style="display: block;">
                <div style="display: flex; gap: 8px; margin-bottom: 0.8rem; flex-wrap: wrap; align-items: center;">
                    <input type="url" id="customExternalImgUrl" placeholder="O pega aquí la dirección URL directa de cualquier imagen de prensa..." style="flex: 1; min-width: 220px; padding: 0.5rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem;">
                    <button type="button" onclick="downloadCustomExternalImage('main')" class="ai-mini-btn" style="background: #4f46e5; color: white; padding: 0.55rem 0.9rem;">
                        ⭐ Usar URL como Principal
                    </button>
                    <button type="button" onclick="downloadCustomExternalImage('gallery')" class="ai-mini-btn" style="background: #0d9488; color: white; padding: 0.55rem 0.9rem;">
                        ➕ Añadir URL a Galería
                    </button>
                </div>
                <div id="optAImagesGrid" class="ai-img-grid">
                    <div style="grid-column: 1 / -1; text-align: center; padding: 1.5rem; color: #64748b; font-size: 0.85rem;">
                        Selecciona una noticia arriba y pulsa <strong>"Redactar Borrador"</strong> para extraer automáticamente las fotos del artículo original, o pega la URL de una imagen arriba.
                    </div>
                </div>
            </div>

            <!-- Contenido Opción B: Generar imagen ilustrativa con IA -->
            <div id="tabContentOptB" style="display: none;">
                <div style="background: #f5f3ff; padding: 0.8rem; border-radius: 8px; border: 1px solid #ddd6fe; margin-bottom: 0.9rem;">
                    <label style="display: block; font-size: 0.82rem; font-weight: 700; color: #4c1d95; margin-bottom: 0.4rem;">
                        Descripción visual para generar la fotografía con IA (puedes personalizarla):
                    </label>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <input type="text" id="aiImagePromptInput" value="Photorealistic editorial photography of Moratalla, Region of Murcia, Spain, medieval castle on the hill, historic Mediterranean village and pine forest, sunny daylight, high detail" style="flex: 1; min-width: 240px; padding: 0.6rem 0.8rem; border: 1px solid #c4b5fd; border-radius: 6px; font-size: 0.88rem; background: white;">
                        <button type="button" id="btnGenerateAiImg" onclick="generateNewAiPhoto()" class="btn btn-sm" style="background: linear-gradient(135deg, #7c3aed, #db2777); color: white; font-weight: 700; padding: 0.6rem 1.1rem;">
                            <i class="fas fa-magic"></i> Generar Foto con IA Ahora
                        </button>
                    </div>
                    <small style="display: block; margin-top: 0.35rem; color: #6d28d9; font-size: 0.76rem;">
                        La imagen generada se redimensiona y recibe automáticamente la marca de agua de <code>moratalla-murcia.com</code>.
                    </small>
                </div>
                <div id="optBLoading" style="display: none; text-align: center; padding: 1.2rem; color: #7c3aed; font-weight: 600;">
                    <i class="fas fa-spinner fa-spin"></i> Generando fotografía con Inteligencia Artificial y aplicando marca de agua...
                </div>
                <div id="optBImagesGrid" class="ai-img-grid">
                    <div style="grid-column: 1 / -1; text-align: center; padding: 1.2rem; color: #64748b; font-size: 0.85rem;">
                        Pulsa <strong>"Generar Foto con IA Ahora"</strong> para crear una imagen libre de derechos adaptada a la noticia.
                    </div>
                </div>
            </div>

            <!-- Contenido Opción C: Archivo propio de Moratalla -->
            <div id="tabContentOptC" style="display: none;">
                <div style="display: flex; gap: 8px; margin-bottom: 0.8rem; flex-wrap: wrap;">
                    <input type="text" id="optCArchiveSearchInput" placeholder="Buscar en tu archivo de Moratalla (ej: castillo, tambor, alharabe, benizar, calle, iglesia...)" style="flex: 1; min-width: 220px; padding: 0.55rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.88rem;" onkeydown="if(event.key==='Enter'){event.preventDefault(); searchArchivePhotos();}">
                    <button type="button" onclick="searchArchivePhotos()" class="btn btn-sm" style="background: #4f46e5; color: white; padding: 0.55rem 1rem;">
                        <i class="fas fa-search"></i> Buscar en Archivo
                    </button>
                </div>
                <div id="optCImagesGrid" class="ai-img-grid">
                    <div style="grid-column: 1 / -1; text-align: center; padding: 1.2rem; color: #64748b; font-size: 0.85rem;">
                        Cargando fotografías del archivo de Moratalla...
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
let foundInternetNews = [];
let currentArticleSource = 'Prensa Regional';

function toggleAiAssistantBody() {
    const body = document.getElementById('aiAssistantBody');
    const btn = document.getElementById('toggleAiBodyBtn');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        btn.innerHTML = '<i class="fas fa-chevron-up"></i> <span>Ocultar Buscador IA</span>';
        if (foundInternetNews.length === 0) {
            searchInternetNews();
            searchArchivePhotos();
        }
    } else {
        body.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-search"></i> <span>Abrir Buscador IA</span>';
    }
}

function toggleGeminiKeyModal() {
    const box = document.getElementById('geminiKeyInlineBox');
    box.style.display = (box.style.display === 'none') ? 'block' : 'none';
}

function saveInlineGeminiKey() {
    const keyInput = document.getElementById('inlineGeminiApiKeyInput');
    const val = keyInput.value.trim();
    if (!val) {
        alert('Introduce una clave API de Google Gemini válida.');
        return;
    }
    const fd = new FormData();
    fd.append('ajax_action', 'save_gemini_key');
    fd.append('gemini_api_key', val);

    fetch('ai_news_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                keyInput.value = '';
                document.getElementById('geminiKeyInlineBox').style.display = 'none';
                const badge = document.getElementById('geminiKeyStatusBadge');
                badge.style.background = '#d1fae5';
                badge.style.color = '#065f46';
                badge.innerHTML = '<i class="fas fa-check-circle"></i> Gemini IA Activo';
                alert('Clave API de Google Gemini guardada y encriptada (AES-256) correctamente.');
            } else {
                alert(data.error || 'No se pudo guardar la clave.');
            }
        });
}

function quickSearchTopic(topic) {
    document.getElementById('aiNewsSearchQuery').value = topic;
    searchInternetNews();
}

function searchInternetNews() {
    const q = document.getElementById('aiNewsSearchQuery').value.trim() || 'Moratalla Murcia';
    const loading = document.getElementById('aiSearchLoading');
    const container = document.getElementById('aiFoundNewsContainer');
    loading.style.display = 'block';
    container.innerHTML = '';

    const fd = new FormData();
    fd.append('ajax_action', 'search_news');
    fd.append('query', q);

    fetch('ai_news_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            if (data.success) {
                foundInternetNews = data.items || [];
                renderFoundNewsList();
            } else {
                container.innerHTML = '<div style="padding:1rem; color:#dc2626;">Error al buscar noticias: ' + (data.error || 'Desconocido') + '</div>';
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            container.innerHTML = '<div style="padding:1rem; color:#dc2626;">Error de conexión al buscar noticias.</div>';
        });
}

let currentNewsStatusFilter = 'new'; // 'new' | 'published' | 'discarded' | 'all'

function onToggleHideProcessedChk() {
    const hide = document.getElementById('hideProcessedNewsChk').checked;
    setNewsStatusFilter(hide ? 'new' : 'all');
}

function setNewsStatusFilter(filter) {
    currentNewsStatusFilter = filter;
    const chk = document.getElementById('hideProcessedNewsChk');
    if (chk) {
        chk.checked = (filter === 'new');
    }

    const btnNew  = document.getElementById('btnFilterNew');
    const btnPub  = document.getElementById('btnFilterPub');
    const btnDisc = document.getElementById('btnFilterDisc');
    const btnAll  = document.getElementById('btnFilterAll');
    const hint    = document.getElementById('activeFilterHint');

    if (btnNew) {
        btnNew.style.border = (filter === 'new') ? '2px solid #4f46e5' : '1px solid #c7d2fe';
        btnNew.style.background = (filter === 'new') ? '#eef2ff' : '#ffffff';
    }
    if (btnPub) {
        btnPub.style.border = (filter === 'published') ? '2px solid #059669' : '1px solid #a7f3d0';
        btnPub.style.background = (filter === 'published') ? '#d1fae5' : '#ffffff';
    }
    if (btnDisc) {
        btnDisc.style.border = (filter === 'discarded') ? '2px solid #475569' : '1px solid #cbd5e1';
        btnDisc.style.background = (filter === 'discarded') ? '#e2e8f0' : '#ffffff';
    }
    if (btnAll) {
        btnAll.style.border = (filter === 'all') ? '2px solid #6d28d9' : '1px solid #ddd6fe';
        btnAll.style.background = (filter === 'all') ? '#ede9fe' : '#ffffff';
    }

    if (hint) {
        const labels = {
            'new': 'Mostrando: 🟢 Nuevas disponibles',
            'published': 'Mostrando: 🔵 Ya publicadas en la web',
            'discarded': 'Mostrando: ⚪ Descartadas (puedes recuperarlas aquí)',
            'all': 'Mostrando: 📋 Todas las noticias'
        };
        hint.textContent = labels[filter] || '';
    }

    renderFoundNewsList();
}

function renderFoundNewsList() {
    const container = document.getElementById('aiFoundNewsContainer');

    let countNew = 0, countPub = 0, countDisc = 0;
    foundInternetNews.forEach(it => {
        if (it.process_status === 'published') countPub++;
        else if (it.process_status === 'discarded') countDisc++;
        else countNew++;
    });

    const statsBar = document.getElementById('aiNewsStatsBar');
    statsBar.style.display = 'flex';
    document.getElementById('statNewCount').textContent = '🟢 ' + countNew + ' Nuevas disponibles';
    document.getElementById('statPubCount').textContent = '🔵 ' + countPub + ' Ya publicadas en la web';
    document.getElementById('statDiscCount').textContent = '⚪ ' + countDisc + ' Descartadas';

    const visibleItems = foundInternetNews.filter((it, idx) => {
        it._idx = idx;
        const st = it.process_status || 'new';
        if (currentNewsStatusFilter === 'new') return st === 'new';
        if (currentNewsStatusFilter === 'published') return st === 'published';
        if (currentNewsStatusFilter === 'discarded') return st === 'discarded';
        return true;
    });

    if (visibleItems.length === 0) {
        if (currentNewsStatusFilter === 'new' && (countPub > 0 || countDisc > 0)) {
            container.innerHTML = '<div style="padding:1.2rem; text-align:center; color:#475569; background:#f8fafc; border-radius:8px;">No hay noticias nuevas pendientes en esta búsqueda. Pulsa en los botones <strong>🔵 Ya publicadas (' + countPub + ')</strong> o <strong>⚪ Descartadas (' + countDisc + ')</strong> de arriba para verlas o reconducirlas.</div>';
        } else if (currentNewsStatusFilter === 'published') {
            container.innerHTML = '<div style="padding:1.2rem; text-align:center; color:#475569; background:#f0fdf4; border-radius:8px;">No hay noticias marcadas como publicadas en este listado.</div>';
        } else if (currentNewsStatusFilter === 'discarded') {
            container.innerHTML = '<div style="padding:1.2rem; text-align:center; color:#475569; background:#f8fafc; border-radius:8px;">No hay noticias descartadas en este momento.</div>';
        } else {
            container.innerHTML = '<div style="padding:1.2rem; text-align:center; color:#64748b;">No se encontraron noticias para esa búsqueda. Prueba con otros términos.</div>';
        }
        return;
    }

    container.innerHTML = visibleItems.map(it => {
        let statusBadge = '<span class="ai-badge-pill" style="background:#e0e7ff; color:#3730a3;"><i class="fas fa-bolt"></i> DISPONIBLE</span>';
        let cardClass = 'ai-news-card';
        let extraStatusInfo = '';

        if (it.process_status === 'published') {
            cardClass += ' is-published';
            statusBadge = '<span class="ai-badge-pill" style="background:#d1fae5; color:#065f46;"><i class="fas fa-check-double"></i> YA PUBLICADA EN LA WEB</span>';
            if (it.matched_news_id) {
                extraStatusInfo = '<div style="font-size:0.78rem; color:#047857; margin-top:5px;"><i class="fas fa-link"></i> Publicada en la web como: <a href="news.php?action=edit&id=' + it.matched_news_id + '" style="color:#065f46; font-weight:700; text-decoration:underline;">' + escapeHtml(it.matched_title || ('Noticia #' + it.matched_news_id)) + ' (Editar)</a></div>';
            }
        } else if (it.process_status === 'discarded') {
            cardClass += ' is-discarded';
            statusBadge = '<span class="ai-badge-pill" style="background:#e2e8f0; color:#475569;"><i class="fas fa-eye-slash"></i> DESCARTADA</span>';
            extraStatusInfo = '<div style="font-size:0.76rem; color:#64748b; margin-top:4px;"><i class="fas fa-info-circle"></i> Puedes recuperarla a Disponibles o redactar directamente su borrador.</div>';
        }

        const thumbHtml = it.rss_image
            ? '<img src="' + escapeHtml(it.rss_image) + '" style="width:72px; height:54px; object-fit:cover; border-radius:6px; flex-shrink:0;" onerror="this.style.display=\'none\'">'
            : '';

        let actionBtns = '';
        if (it.process_status === 'discarded') {
            actionBtns =
                '<button type="button" onclick="changeNewsStatus(' + it._idx + ', \'new\')" class="ai-mini-btn" style="background:#059669; color:white; padding:6px 11px;" title="Devolver a noticias disponibles"><i class="fas fa-undo"></i> Recuperar a Disponible</button>' +
                '<button type="button" onclick="recoverAndPrepareDraft(' + it._idx + ')" class="ai-mini-btn" style="background:#6d28d9; color:white; padding:6px 11px;" title="Recuperar y redactar borrador ahora"><i class="fas fa-wand-magic-sparkles"></i> Recuperar y Redactar</button>' +
                '<button type="button" onclick="changeNewsStatus(' + it._idx + ', \'published\')" class="ai-mini-btn" style="background:#f1f5f9; color:#065f46; border:1px solid #a7f3d0; padding:5px 9px;" title="Marcar como ya publicada"><i class="fas fa-check"></i> Pasar a Publicada</button>';
        } else if (it.process_status === 'published') {
            actionBtns =
                '<button type="button" onclick="prepareSupervisedDraft(' + it._idx + ')" class="ai-mini-btn" style="background:#6d28d9; color:white; padding:6px 11px; font-size:0.78rem;" title="Generar nuevo borrador a partir de esta noticia"><i class="fas fa-wand-magic-sparkles"></i> Volver a Redactar</button>' +
                '<button type="button" onclick="changeNewsStatus(' + it._idx + ', \'new\')" class="ai-mini-btn" style="background:#4f46e5; color:white; padding:6px 10px;" title="Reconducir de nuevo a Disponibles"><i class="fas fa-exchange-alt"></i> Reconducir a Disponible</button>' +
                '<button type="button" onclick="changeNewsStatus(' + it._idx + ', \'discarded\')" class="ai-mini-btn" style="background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; padding:5px 9px;" title="Mover a descartadas"><i class="fas fa-ban"></i> Pasar a Descartada</button>';
        } else {
            actionBtns =
                '<button type="button" onclick="prepareSupervisedDraft(' + it._idx + ')" class="ai-mini-btn" style="background:#6d28d9; color:white; padding:7px 12px; font-size:0.8rem;">' +
                '<i class="fas fa-wand-magic-sparkles"></i> Redactar Borrador' +
                '</button>' +
                '<div style="display:flex; gap:4px;">' +
                    '<button type="button" onclick="changeNewsStatus(' + it._idx + ', \'discarded\')" class="ai-mini-btn" style="flex:1; background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; padding:5px 8px;" title="Descartar esta noticia"><i class="fas fa-ban"></i> Descartar</button>' +
                    '<button type="button" onclick="changeNewsStatus(' + it._idx + ', \'published\')" class="ai-mini-btn" style="flex:1; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; padding:5px 8px;" title="Marcar como ya publicada"><i class="fas fa-check"></i> Publicada</button>' +
                '</div>';
        }

        const origLinkHtml = it.url
            ? '<a href="' + escapeHtml(it.url) + '" target="_blank" style="font-size:0.75rem; color:#2563eb; text-decoration:none; font-weight:600;"><i class="fas fa-external-link-alt"></i> Ver original</a>'
            : '';

        return '<div class="' + cardClass + '">' +
            '<div style="display:flex; gap:12px; align-items:flex-start; flex:1; min-width:0;">' +
                thumbHtml +
                '<div style="flex:1; min-width:0;">' +
                    '<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:4px;">' +
                        statusBadge +
                        '<span style="font-size:0.76rem; font-weight:700; color:#6d28d9; background:#f3e8ff; padding:2px 8px; border-radius:4px;">' + escapeHtml(it.source) + '</span>' +
                        '<span style="font-size:0.75rem; color:#64748b;"><i class="far fa-clock"></i> ' + escapeHtml(it.pub_date) + '</span>' +
                        origLinkHtml +
                    '</div>' +
                    '<div style="font-weight:700; color:#0f172a; font-size:0.94rem; line-height:1.3;">' + escapeHtml(it.title) + '</div>' +
                    (it.snippet && it.snippet !== it.title ? '<div style="font-size:0.8rem; color:#475569; margin-top:3px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">' + escapeHtml(it.snippet) + '</div>' : '') +
                    extraStatusInfo +
                '</div>' +
            '</div>' +
            '<div style="display:flex; flex-direction:column; gap:5px; flex-shrink:0;">' +
                actionBtns +
            '</div>' +
        '</div>';
    }).join('');
}

function changeNewsStatus(idx, targetStatus, callback) {
    const it = foundInternetNews[idx];
    if (!it) return;
    const fd = new FormData();
    fd.append('ajax_action', 'change_news_status');
    fd.append('target_status', targetStatus);
    fd.append('url', it.url || '');
    fd.append('title', it.title || '');
    fd.append('source', it.source || '');
    fd.append('url_hash', it.url_hash || '');
    fd.append('record_id', it.processed_record_id || '');

    fetch('ai_news_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                it.process_status = (targetStatus === 'available' ? 'new' : targetStatus);
                renderFoundNewsList();
                if (typeof callback === 'function') callback();
            }
        });
}

function recoverAndPrepareDraft(idx) {
    changeNewsStatus(idx, 'new', function() {
        prepareSupervisedDraft(idx);
    });
}

function discardInternetNews(idx) {
    changeNewsStatus(idx, 'discarded');
}

function restoreDiscardedNews(idx) {
    changeNewsStatus(idx, 'new');
}

function generateFromDirectUrl() {
    const url = document.getElementById('aiDirectArticleUrl').value.trim();
    if (!url) {
        alert('Pega primero el enlace (URL) de la noticia que deseas extraer.');
        return;
    }
    runDraftPreparation({
        url: url,
        title: '',
        snippet: '',
        source: '',
        rss_image: ''
    });
}

function prepareSupervisedDraft(idx) {
    const it = foundInternetNews[idx];
    if (!it) return;
    runDraftPreparation(it);
}

function runDraftPreparation(itemData) {
    const loadingBox = document.getElementById('aiDraftLoading');
    loadingBox.style.display = 'block';
    loadingBox.scrollIntoView({ behavior: 'smooth', block: 'center' });

    const fd = new FormData();
    fd.append('ajax_action', 'prepare_draft');
    fd.append('url', itemData.url || '');
    fd.append('title', itemData.title || '');
    fd.append('snippet', itemData.snippet || '');
    fd.append('source', itemData.source || '');
    fd.append('rss_image', itemData.rss_image || '');

    fetch('ai_news_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            loadingBox.style.display = 'none';
            if (!data.success) {
                alert(data.error || 'No se pudo generar el borrador.');
                return;
            }

            const draft = data.draft || {};
            const article = data.article || {};
            currentArticleSource = article.source_name || itemData.source || 'Prensa Regional';

            // 1. Rellenar campos del formulario principal para supervisión
            const titleInput = document.querySelector('input[name="title"]');
            if (titleInput && draft.title) titleInput.value = draft.title;

            // Contenido en TinyMCE y textarea
            const contentHtml = draft.content_html || '';
            const contentArea = document.getElementById('news_content');
            if (contentArea) contentArea.value = contentHtml;
            if (typeof tinymce !== 'undefined' && tinymce.get('news_content')) {
                tinymce.get('news_content').setContent(contentHtml);
            }

            // Fecha del evento (si aplica)
            const eventDateInput = document.querySelector('input[name="event_date"]');
            if (eventDateInput && draft.event_date) {
                eventDateInput.value = draft.event_date;
            }

            // Categoría sugerida
            if (draft.category_id) {
                const catSelect = document.querySelector('select[name="category_id"]');
                if (catSelect) catSelect.value = String(draft.category_id);
            }

            // Icono sugerido
            if (draft.icon) {
                const iconInput = document.getElementById('newsIconInput') || document.querySelector('input[name="icon"]');
                if (iconInput) {
                    iconInput.value = draft.icon;
                    iconInput.dispatchEvent(new Event('input'));
                }
            }

            // Pie de foto sugerido
            const captionInput = document.querySelector('input[name="image_caption"]');
            if (captionInput && draft.image_caption) {
                captionInput.value = draft.image_caption;
            }

            // Guardar metadatos ocultos de control anti-duplicados para registrar la noticia como publicada al guardar
            document.getElementById('hiddenSourceUrl').value = article.resolved_url || itemData.url || '';
            document.getElementById('hiddenAiOriginalTitle').value = article.title || itemData.title || draft.title || '';
            document.getElementById('hiddenAiSourceName').value = currentArticleSource;

            // 2. Actualizar Opción A (Imágenes originales extraídas)
            renderOptionAImages(article.original_images || []);

            // Si ya se pre-descargó la foto original de portada, asignarla como Principal por defecto
            if (data.pre_downloaded_original && data.pre_downloaded_original.local_path) {
                setPreselectedMainImage(
                    data.pre_downloaded_original.local_path,
                    data.pre_downloaded_original.caption || draft.image_caption || '',
                    data.pre_downloaded_original.author || ('Foto: ' + currentArticleSource),
                    'Opción A (Noticia Original)'
                );
            }

            // 3. Actualizar prompt de Opción B (IA)
            if (draft.ai_image_prompt) {
                document.getElementById('aiImagePromptInput').value = draft.ai_image_prompt;
            }

            // 4. Actualizar Opción C (Archivo propio de Moratalla)
            if (data.archive_keywords) {
                document.getElementById('optCArchiveSearchInput').value = data.archive_keywords;
            }
            renderOptionCImages(data.archive_images || []);

            // Mostrar aviso de supervisión
            const banner = document.getElementById('supervisedDraftReadyBanner');
            if (banner) {
                banner.style.display = 'block';
                banner.innerHTML = '<i class="fas fa-check-circle"></i> <strong>Borrador supervisado listo:</strong> Revisa el título, el texto redactado ' +
                    (draft.used_gemini ? 'con <strong>Google Gemini (' + escapeHtml(draft.model || '') + ')</strong>' : 'en modo estructurado') +
                    ' y las 3 opciones de fotografía antes de pulsar <strong>Guardar</strong>.';
                banner.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        })
        .catch(err => {
            loadingBox.style.display = 'none';
            alert('Error de red al preparar el borrador.');
        });
}

function switchAiImageTab(tab) {
    ['A', 'B', 'C'].forEach(t => {
        document.getElementById('tabContentOpt' + t).style.display = (t === tab) ? 'block' : 'none';
        const btn = document.getElementById('tabBtnOpt' + t);
        if (t === tab) btn.classList.add('active');
        else btn.classList.remove('active');
    });
    if (tab === 'C' && document.getElementById('optCImagesGrid').children.length <= 1) {
        searchArchivePhotos();
    }
}

function renderOptionAImages(images) {
    const grid = document.getElementById('optAImagesGrid');
    document.getElementById('badgeCountOptA').textContent = images.length;
    if (!images || images.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 1.2rem; color: #64748b; font-size: 0.85rem;">No se detectaron fotos abiertas en el artículo original. Puedes usar la <strong>Opción B (Generar con IA)</strong> o la <strong>Opción C (Archivo de Moratalla)</strong>.</div>';
        return;
    }
    grid.innerHTML = images.map((img, i) => {
        const safeUrl = escapeHtml(img.url);
        const safeLabel = escapeHtml(img.label || ('Imagen ' + (i + 1)));
        return '<div class="ai-img-card">' +
            '<img src="' + safeUrl + '" alt="' + safeLabel + '" onerror="this.parentElement.style.display=\'none\'">' +
            '<div style="font-size:0.74rem; color:#334155; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="' + safeLabel + '">' + safeLabel + '</div>' +
            '<div style="display:flex; flex-direction:column; gap:4px;">' +
                '<button type="button" onclick="selectExternalImage(\'' + safeUrl.replace(/'/g, "\\'") + '\', \'main\')" class="ai-mini-btn" style="background:#4f46e5; color:white;">⭐ Usar como Principal</button>' +
                '<button type="button" onclick="selectExternalImage(\'' + safeUrl.replace(/'/g, "\\'") + '\', \'gallery\')" class="ai-mini-btn" style="background:#0d9488; color:white;">➕ Añadir a Galería</button>' +
            '</div>' +
        '</div>';
    }).join('');
}

function downloadCustomExternalImage(targetMode) {
    const url = document.getElementById('customExternalImgUrl').value.trim();
    if (!url) {
        alert('Pega primero la URL de la imagen.');
        return;
    }
    selectExternalImage(url, targetMode);
}

function selectExternalImage(imageUrl, targetMode) {
    const fd = new FormData();
    fd.append('ajax_action', 'download_original_image');
    fd.append('image_url', imageUrl);
    fd.append('source_name', currentArticleSource);

    fetch('ai_news_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || 'No se pudo descargar esa imagen.');
                return;
            }
            const titleVal = document.querySelector('input[name="title"]').value || 'Noticia Moratalla';
            const authorVal = data.author || ('Foto: ' + currentArticleSource);
            if (targetMode === 'main') {
                setPreselectedMainImage(data.path, titleVal, authorVal, 'Opción A (Noticia Original)');
            } else {
                addPreselectedGalleryImage(data.path, titleVal, authorVal, 'Opción A (Original)');
            }
        });
}

function generateNewAiPhoto() {
    const prompt = document.getElementById('aiImagePromptInput').value.trim();
    const loading = document.getElementById('optBLoading');
    const btn = document.getElementById('btnGenerateAiImg');
    loading.style.display = 'block';
    btn.disabled = true;

    const fd = new FormData();
    fd.append('ajax_action', 'generate_ai_image');
    fd.append('prompt', prompt);

    fetch('ai_news_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            loading.style.display = 'none';
            btn.disabled = false;
            if (!data.success) {
                alert(data.error || 'Error generando imagen con IA.');
                return;
            }
            const grid = document.getElementById('optBImagesGrid');
            // Limpiar mensaje vacío inicial si existe
            if (grid.querySelector('div[style*="grid-column"]')) {
                grid.innerHTML = '';
            }
            const safePath = escapeHtml(data.path);
            const engineLabel = escapeHtml(data.engine || 'IA Generativa');
            const card = document.createElement('div');
            card.className = 'ai-img-card';
            card.innerHTML =
                '<img src="../' + safePath + '">' +
                '<div style="font-size:0.74rem; color:#6d28d9; font-weight:700;">✨ ' + engineLabel + '</div>' +
                '<div style="display:flex; flex-direction:column; gap:4px;">' +
                    '<button type="button" onclick="setPreselectedMainImage(\'' + safePath + '\', document.querySelector(\'input[name=title]\').value, \'Imagen ilustrativa generada por IA\', \'Opción B (IA)\')" class="ai-mini-btn" style="background:#4f46e5; color:white;">⭐ Usar como Principal</button>' +
                    '<button type="button" onclick="addPreselectedGalleryImage(\'' + safePath + '\', document.querySelector(\'input[name=title]\').value, \'Imagen ilustrativa generada por IA\', \'Opción B (IA)\')" class="ai-mini-btn" style="background:#0d9488; color:white;">➕ Añadir a Galería</button>' +
                '</div>';
            grid.prepend(card);

            // Si aún no hay imagen principal seleccionada, seleccionarla automáticamente
            if (!document.getElementById('preselectedImagePathInput').value) {
                setPreselectedMainImage(data.path, document.querySelector('input[name="title"]').value || '', 'Imagen ilustrativa generada por IA', 'Opción B (IA)');
            }
        })
        .catch(err => {
            loading.style.display = 'none';
            btn.disabled = false;
            alert('Error de conexión al generar imagen con IA.');
        });
}

function searchArchivePhotos() {
    const q = document.getElementById('optCArchiveSearchInput').value.trim();
    const grid = document.getElementById('optCImagesGrid');
    grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:1rem; color:#6d28d9;"><i class="fas fa-spinner fa-spin"></i> Buscando en el archivo de Moratalla...</div>';

    const fd = new FormData();
    fd.append('ajax_action', 'search_archive_images');
    fd.append('query', q);

    fetch('ai_news_ajax.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderOptionCImages(data.images || []);
            }
        });
}

function renderOptionCImages(images) {
    const grid = document.getElementById('optCImagesGrid');
    if (!images || images.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:1rem; color:#64748b;">No se encontraron fotos con ese término en el archivo.</div>';
        return;
    }
    grid.innerHTML = images.map(img => {
        const safePath = escapeHtml(img.path);
        const safeCap = escapeHtml(img.caption || img.origin || 'Archivo Moratalla');
        const safeAuth = escapeHtml(img.author || 'Archivo moratalla-murcia.com');
        return '<div class="ai-img-card">' +
            '<img src="../' + safePath + '" alt="' + safeCap + '">' +
            '<div style="font-size:0.73rem; color:#1e293b; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="' + safeCap + '">' + safeCap + '</div>' +
            '<div style="display:flex; flex-direction:column; gap:4px;">' +
                '<button type="button" onclick="setPreselectedMainImage(\'' + safePath.replace(/'/g, "\\'") + '\', \'' + safeCap.replace(/'/g, "\\'") + '\', \'' + safeAuth.replace(/'/g, "\\'") + '\', \'Opción C (Archivo Moratalla)\')" class="ai-mini-btn" style="background:#4f46e5; color:white;">⭐ Usar como Principal</button>' +
                '<button type="button" onclick="addPreselectedGalleryImage(\'' + safePath.replace(/'/g, "\\'") + '\', \'' + safeCap.replace(/'/g, "\\'") + '\', \'' + safeAuth.replace(/'/g, "\\'") + '\', \'Opción C (Archivo)\')" class="ai-mini-btn" style="background:#0d9488; color:white;">➕ Añadir a Galería</button>' +
            '</div>' +
        '</div>';
    }).join('');
}

function setPreselectedMainImage(path, caption, author, originLabel) {
    const input = document.getElementById('preselectedImagePathInput');
    const box = document.getElementById('preselectedMainImagePreviewBox');
    const img = document.getElementById('preselectedMainImageThumb');
    const badge = document.getElementById('preselectedMainImageOriginBadge');

    input.value = path;
    img.src = '../' + path;
    badge.textContent = originLabel || 'Imagen Seleccionada';
    box.style.display = 'flex';

    const capInput = document.querySelector('input[name="image_caption"]');
    const authInput = document.querySelector('input[name="image_author"]');
    if (capInput && caption && !capInput.value) capInput.value = caption;
    if (authInput && author) authInput.value = author;
}

function clearPreselectedMainImage() {
    document.getElementById('preselectedImagePathInput').value = '';
    document.getElementById('preselectedMainImagePreviewBox').style.display = 'none';
}

function addPreselectedGalleryImage(path, caption, author, originLabel) {
    const container = document.getElementById('preselectedGalleryItemsContainer');
    const wrapper = document.getElementById('preselectedGalleryWrapper');
    wrapper.style.display = 'block';

    // Evitar duplicar la misma ruta en la galería preseleccionada
    const existingInputs = container.querySelectorAll('input[name="preselected_gallery_paths[]"]');
    for (let i = 0; i < existingInputs.length; i++) {
        if (existingInputs[i].value === path) {
            alert('Esta imagen ya está añadida a la galería adicional de la noticia.');
            return;
        }
    }

    const div = document.createElement('div');
    div.style.cssText = 'border: 1px solid #c4b5fd; border-radius: 8px; padding: 0.5rem; background: #f5f3ff; text-align: center; position: relative;';
    div.innerHTML =
        '<img src="../' + escapeHtml(path) + '" style="width: 100%; height: 85px; object-fit: cover; border-radius: 4px; margin-bottom: 0.3rem;">' +
        '<div style="font-size: 0.68rem; color: #5b21b6; font-weight: 700; margin-bottom: 0.3rem;">' + escapeHtml(originLabel || 'Añadida') + '</div>' +
        '<input type="hidden" name="preselected_gallery_paths[]" value="' + escapeHtml(path) + '">' +
        '<input type="text" name="preselected_gallery_captions[]" value="' + escapeHtml(caption || '') + '" placeholder="Descripción" style="width: 100%; padding: 4px; font-size: 0.74rem; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 0.3rem;">' +
        '<input type="text" name="preselected_gallery_authors[]" value="' + escapeHtml(author || '') + '" placeholder="Autor/a" style="width: 100%; padding: 4px; font-size: 0.74rem; border: 1px solid #cbd5e1; border-radius: 4px; margin-bottom: 0.4rem;">' +
        '<button type="button" onclick="this.parentElement.remove()" class="ai-mini-btn" style="width: 100%; background: #fee2e2; color: #b91c1c;"><i class="fas fa-times"></i> Quitar</button>';
    container.appendChild(div);
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

<?php if ($autoOpenAiPanel): ?>
document.addEventListener('DOMContentLoaded', function() {
    searchInternetNews();
    searchArchivePhotos();

    const params = new URLSearchParams(window.location.search);
    const prefillUrl = params.get('prefill_url') || '';
    const prefillTitle = params.get('prefill_title') || '';
    const prefillSource = params.get('prefill_source') || '';
    const prefillImg = params.get('prefill_img') || '';
    const quickAction = params.get('quick_action') || '';

    if (prefillUrl || prefillTitle) {
        if (quickAction === 'discard') {
            const fd = new FormData();
            fd.append('ajax_action', 'change_news_status');
            fd.append('target_status', 'discarded');
            fd.append('url', prefillUrl);
            fd.append('title', prefillTitle);
            fd.append('source', prefillSource);
            fetch('ai_news_ajax.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(() => {
                    searchInternetNews();
                    const banner = document.getElementById('supervisedDraftReadyBanner');
                    if (banner) {
                        banner.style.display = 'block';
                        banner.innerHTML = '<i class="fas fa-ban"></i> <strong>Noticia descartada desde el boletín de correo:</strong> «' + escapeHtml(prefillTitle || prefillUrl) + '» ha quedado registrada en la pestaña <strong>⚪ Descartadas</strong> para no volver a repetirla.';
                    }
                });
        } else {
            runDraftPreparation({
                url: prefillUrl,
                title: prefillTitle,
                snippet: '',
                source: prefillSource,
                rss_image: prefillImg
            });
        }
    }
});
<?php endif; ?>
</script>
