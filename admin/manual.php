<?php
// admin/manual.php
require_once 'inc/auth.php';
require_once 'inc/layout.php';

adminHeader("Manual de Usuario Avanzado");
?>

<style>
    :root {
        --manual-primary: #1b4332;
        --manual-secondary: #2d6a4f;
        --manual-accent: #d4af37;
        --manual-bg: #f8f9fa;
        --manual-card: #ffffff;
        --manual-text: #333333;
        --manual-light: #666666;
    }

    body {
        background-color: var(--manual-bg);
    }

    .manual-hero {
        position: relative;
        background: linear-gradient(135deg, rgba(27,67,50,0.95) 0%, rgba(8,28,21,0.95) 100%), url('../uploads/theme/moratalla.jpg');
        background-size: cover;
        background-position: center;
        border-radius: 15px;
        padding: 3rem 2rem;
        color: white;
        text-align: center;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        overflow: hidden;
    }

    .manual-hero img.logo {
        width: 120px;
        height: auto;
        border-radius: 50%;
        border: 4px solid var(--manual-accent);
        margin-bottom: 1rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    .manual-hero h1 {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
        font-weight: 800;
        letter-spacing: -1px;
    }

    .manual-hero p {
        font-size: 1.1rem;
        color: #e2e8f0;
        max-width: 600px;
        margin: 0 auto 1.5rem auto;
    }

    .manual-header-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .search-box {
        position: relative;
        width: 100%;
        max-width: 400px;
    }

    .search-box input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: none;
        border-radius: 30px;
        font-size: 1rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s;
    }

    .search-box input:focus {
        outline: none;
        box-shadow: 0 4px 20px rgba(212, 175, 55, 0.4);
    }

    .search-box i {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--manual-primary);
        font-size: 1.1rem;
    }

    .btn-pdf {
        background: #e63946;
        color: white;
        padding: 0.9rem 1.8rem;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(230, 57, 70, 0.4);
        transition: all 0.3s;
    }

    .btn-pdf:hover {
        background: #c1121f;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(230, 57, 70, 0.6);
    }

    .manual-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
    }

    .manual-card {
        background: var(--manual-card);
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.04);
        border-top: 5px solid var(--manual-secondary);
        transition: transform 0.3s, box-shadow 0.3s;
        position: relative;
    }

    .manual-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    }

    .manual-card-icon {
        position: absolute;
        top: -20px;
        right: 20px;
        background: var(--manual-accent);
        color: white;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 10px rgba(212, 175, 55, 0.4);
    }

    .manual-card h2 {
        color: var(--manual-primary);
        font-size: 1.6rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #edf2f7;
    }

    .manual-card h3 {
        color: var(--manual-secondary);
        font-size: 1.1rem;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .manual-card h3 i {
        color: var(--manual-accent);
        font-size: 0.9rem;
    }

    .manual-card p, .manual-card ul {
        color: var(--manual-light);
        line-height: 1.7;
        font-size: 0.95rem;
        margin-bottom: 1rem;
    }

    .manual-card ul {
        padding-left: 1.2rem;
    }

    .manual-card li {
        margin-bottom: 0.5rem;
    }

    .field-desc {
        background: #f1f5f9;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid var(--manual-accent);
        margin-bottom: 1rem;
    }

    .field-desc strong {
        color: var(--manual-primary);
    }

    .highlight {
        background-color: #fff3cd;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
        color: #856404;
    }

    /* Estilos de Impresión Profesional */
    @media print {
        body { background: white !important; }
        .sidebar, .top-bar, .manual-header-controls { display: none !important; }
        .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .content-wrapper { padding: 0 !important; }
        .manual-hero { 
            background: none !important; 
            color: black !important;
            box-shadow: none !important;
            border-bottom: 4px solid var(--manual-primary);
            padding: 1rem 0 2rem 0 !important;
            border-radius: 0 !important;
        }
        .manual-hero h1, .manual-hero p { color: black !important; }
        .manual-grid { display: block; }
        .manual-card {
            box-shadow: none !important;
            border: 1px solid #ccc !important;
            border-top: 4px solid var(--manual-primary) !important;
            margin-bottom: 2rem !important;
            page-break-inside: auto;
            break-inside: auto;
        }
        h2, h3 { 
            page-break-after: avoid; 
            break-after: avoid; 
        }
        .manual-card-icon { display: none; }
        @page { margin: 1.5cm; }
    }
</style>

<div class="manual-hero">
    <img src="../uploads/theme/logo.jpg" alt="Logo Moratalla" class="logo">
    <h1>Manual Oficial de Administración</h1>
    <p>Guía detallada de funcionamiento, configuración de campos y optimización del portal web. Descubre cómo aprovechar al máximo cada sección.</p>
    
    <div class="manual-header-controls">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Búsqueda rápida... (ej. categorías, imágenes)">
        </div>
        <button onclick="window.print()" class="btn-pdf" title="Exportar a PDF">
            <i class="fas fa-file-pdf"></i> PDF / Imprimir
        </button>
    </div>
</div>

<div class="manual-grid" id="manualContent">
    
    <!-- 1. Dashboard -->
    <div class="manual-card manual-section">
        <div class="manual-card-icon"><i class="fas fa-chart-line"></i></div>
        <h2>1. Panel Principal (Dashboard)</h2>
        <p>El centro de control de tu web. Aquí aterrizas nada más iniciar sesión.</p>
        
        <h3><i class="fas fa-info-circle"></i> ¿Para qué sirve?</h3>
        <p>Proporciona una radiografía instantánea del estado de tu sitio web mediante indicadores visuales numéricos.</p>
        
        <h3><i class="fas fa-list-alt"></i> Elementos Clave</h3>
        <div class="field-desc">
            <strong>Indicadores (Tarjetas Superiores):</strong> Muestran el conteo real de categorías, páginas, imágenes totales en el servidor y noticias activas. Es ideal para confirmar que tus subidas se han registrado correctamente.
        </div>
        <div class="field-desc">
            <strong>Accesos Rápidos:</strong> Botones de acción inmediata para las tareas más frecuentes: "Crear Nueva Página", "Subir Imágenes" y "Escribir Noticia". Te ahorran clics navegando por el menú.
        </div>
    </div>

    <!-- 2. Categorías -->
    <div class="manual-card manual-section">
        <div class="manual-card-icon"><i class="fas fa-folder-tree"></i></div>
        <h2>2. Gestión de Categorías</h2>
        <p>Las categorías son el <strong>esqueleto</strong> de tu web. Forman el menú de navegación principal de la página pública.</p>
        
        <h3><i class="fas fa-cogs"></i> Campos de Configuración</h3>
        <div class="field-desc">
            <strong>Nombre:</strong> El título visible en el menú. Se generará automáticamente un enlace amigable (slug) basado en este nombre.
        </div>
        <div class="field-desc">
            <strong>Categoría Superior (Parent):</strong> Sirve para anidar menús (crear sub-menús). Si eliges una categoría aquí, la que estás creando aparecerá como un desplegable dentro de la categoría padre.
        </div>
        <div class="field-desc">
            <strong>Orden:</strong> Determina la posición de la categoría en el menú de izquierda a derecha (o de arriba abajo). Un número más bajo (ej. 1) aparece antes que uno más alto (ej. 10).
        </div>
        <div class="field-desc">
            <strong>Visibilidad (Ojo):</strong> Si lo desactivas, la categoría entera (y sus páginas) desaparecerán de la web pública, pero seguirán guardadas aquí por si quieres reactivarlas en el futuro.
        </div>
        <div class="field-desc">
            <strong>Icono:</strong> Un detalle visual muy potente. Puedes seleccionar un emoji o un icono de <em>FontAwesome</em> que acompañará al nombre en el menú desplegable, haciéndolo más intuitivo y profesional.
        </div>
    </div>

    <!-- 3. Páginas -->
    <div class="manual-card manual-section">
        <div class="manual-card-icon"><i class="fas fa-file-alt"></i></div>
        <h2>3. Creación de Páginas</h2>
        <p>El corazón del contenido. Las páginas representan los artículos, la historia, la información de turismo o cualquier texto permanente.</p>
        
        <h3><i class="fas fa-pen-nib"></i> Editor Avanzado (TinyMCE)</h3>
        <p>Dispones de un procesador de textos completo. Úsalo con creatividad: justifica el texto, aplica negritas para resaltar palabras clave y crea listas para facilitar la lectura.</p>
        
        <h3><i class="fas fa-cogs"></i> Cómo configurar correctamente</h3>
        <div class="field-desc">
            <strong>Asignar Categoría:</strong> Es vital. Si no asignas una categoría, la página quedará "huérfana" y será difícil de encontrar en la web pública.
        </div>
        <div class="field-desc">
            <strong>Orden de Aparición:</strong> Al igual que en las categorías, determina qué página se lee primero cuando entras a una sección.
        </div>
        <div class="field-desc">
            <strong>Metadatos SEO (Hint/Resumen):</strong> Completa siempre este pequeño campo. Será el resumen que Google lea y muestre en los resultados de búsqueda, atrayendo más visitantes.
        </div>
    </div>

    <!-- 4. Galería -->
    <div class="manual-card manual-section">
        <div class="manual-card-icon"><i class="fas fa-images"></i></div>
        <h2>4. Galería e Imágenes</h2>
        <p>Un sitio web entra por los ojos. La gestión de imágenes es fundamental para el patrimonio histórico.</p>
        
        <h3><i class="fas fa-upload"></i> Subida y Optimización</h3>
        <div class="field-desc">
            <strong>Subida Masiva:</strong> Arrastra varias fotos a la vez. El sistema se encarga de todo: optimiza el peso para que la web cargue veloz en móviles y mantiene la calidad.
        </div>
        <div class="field-desc">
            <strong>Carátula de Página (Estrella ⭐):</strong> Cuando tienes varias fotos asociadas a una misma página, haz clic en la estrella de la mejor foto. Esa imagen será la portada y la miniatura cuando compartas el enlace por WhatsApp o Facebook.
        </div>
        <div class="field-desc">
            <strong>Asignación:</strong> En la sección general de Galería, asegúrate de asignar las imágenes a una "Página" en concreto; de lo contrario, no se mostrarán al final del artículo.
        </div>
    </div>

    <!-- 5. Noticias -->
    <div class="manual-card manual-section">
        <div class="manual-card-icon"><i class="fas fa-calendar-alt"></i></div>
        <h2>5. Noticias y Eventos</h2>
        <p>Mantén la web viva. Ideal para avisos de la banda de música, procesiones o novedades del ayuntamiento.</p>
        
        <h3><i class="fas fa-random"></i> Diferenciando Contenido</h3>
        <div class="field-desc">
            <strong>Modo Noticia:</strong> Déjalo sin fechas de inicio/fin. Será un artículo atemporal, ordenado por fecha de creación, excelente para crónicas pasadas.
        </div>
        <div class="field-desc">
            <strong>Modo Evento (Programado):</strong> Rellena la "Fecha de Inicio" (cuándo aparece) y "Fecha de Fin" (cuándo caduca). El sistema mostrará la noticia automáticamente cuando llegue el día y la ocultará cuando pase, ¡sin que tú hagas nada!
        </div>
        <div class="field-desc">
            <strong>Ubicación Cruzada:</strong> Una noticia puede aparecer en la Portada Global y, a la vez, dentro del menú de una Categoría Específica (ej. Fiestas). Utiliza el interruptor "Mostrar en esta Categoría".
        </div>
    </div>

    <!-- 6. Accesos Externos -->
    <div class="manual-card manual-section">
        <div class="manual-card-icon"><i class="fas fa-external-link-alt"></i></div>
        <h2>6. Accesos Externos y Resto</h2>
        <p>Gestión de contenido satélite y bases de datos locales.</p>
        
        <h3><i class="fas fa-link"></i> Curiosidades / Enlaces</h3>
        <div class="field-desc">
            <strong>URL Segura:</strong> Añade siempre `https://` al principio del enlace. 
            <strong>Asignación:</strong> Puedes poner un enlace global, o hacer que aparezca como un anexo dentro de una sección (ej. Enlace a "Wikipedia del Castillo" dentro de la categoría "Monumentos").
        </div>
        
        <h3><i class="fas fa-utensils"></i> Comer y Dormir</h3>
        <div class="field-desc">
            <strong>Bares y Alojamientos:</strong> Rellena los datos de contacto y enlace. Este módulo genera una cuadrícula muy visual y práctica para los turistas, con botones directos para llamar o ir a la web del establecimiento.
        </div>
    </div>

    <!-- 7. Banner y Ajustes -->
    <div class="manual-card manual-section">
        <div class="manual-card-icon"><i class="fas fa-sliders-h"></i></div>
        <h2>7. Banners y Configuración</h2>
        <p>El toque final para personalizar el comportamiento y la primera impresión.</p>
        
        <h3><i class="fas fa-images"></i> Banner Interactivo</h3>
        <div class="field-desc">
            <strong>Carrusel de Portada:</strong> Sube imágenes apaisadas (horizontales) de alta calidad. Para una experiencia perfecta en móviles, el sistema te permite subir una "versión móvil" (más vertical) para la misma foto. Si subes vídeo (MP4), asegúrate de que sea corto y sin sonido crítico, ya que se reproduce en bucle y silenciado por defecto para no asustar al visitante.
        </div>
        
        <h3><i class="fas fa-cogs"></i> Configuración General</h3>
        <div class="field-desc">
            <strong>Cinta Pasante (Ticker):</strong> El letrero verde en la cima de la web. Úsalo para dar la bienvenida o avisar de un evento urgente. Controla su velocidad (ej. 30 segundos para lectura relajada).
        </div>
    </div>

    <!-- 8. Seguridad -->
    <div class="manual-card manual-section">
        <div class="manual-card-icon"><i class="fas fa-shield-alt"></i></div>
        <h2>8. Mantenimiento y Seguridad</h2>
        <p>Protege todo el esfuerzo invertido en tu portal cultural.</p>
        
        <h3><i class="fas fa-database"></i> Copias de Seguridad (Backup)</h3>
        <div class="field-desc">
            <strong style="color: #e63946;">Protocolo Recomendado:</strong> Al menos una vez al mes, o justo después de haber creado muchas páginas, acude a "Copia de Seguridad". Descarga el archivo `.sql` (tus textos y ajustes) y el archivo `.zip` (tus imágenes). Guárdalos en tu ordenador o en un pendrive. Es tu seguro de vida digital ante imprevistos.
        </div>
        
        <h3><i class="fas fa-chart-pie"></i> Estadísticas</h3>
        <div class="field-desc">
            El sistema recopila datos de visitas reales de forma automática (respetando la privacidad, sin rastrear identidad). Analiza qué páginas son las más visitadas para saber qué contenido interesa más a tus lectores.
        </div>
    </div>

</div>

<script>
document.getElementById('searchInput').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase().trim();
    const sections = document.querySelectorAll('.manual-section');
    
    sections.forEach(section => {
        // Limpieza de resaltados previos
        const originalHtml = section.getAttribute('data-original-html');
        if (originalHtml) {
            section.innerHTML = originalHtml;
        } else {
            section.setAttribute('data-original-html', section.innerHTML);
        }

        const text = section.textContent.toLowerCase();
        
        if (text.includes(term) || term === '') {
            section.style.display = 'block';
            
            if (term.length > 2) {
                // Función recursiva segura para el resaltado
                const highlightText = (node) => {
                    if (node.nodeType === 3) {
                        const nodeText = node.nodeValue.toLowerCase();
                        if (nodeText.includes(term)) {
                            const regex = new RegExp(`(${term})`, 'gi');
                            const div = document.createElement('div');
                            div.innerHTML = node.nodeValue.replace(regex, '<span class="highlight">$1</span>');
                            while (div.firstChild) {
                                node.parentNode.insertBefore(div.firstChild, node);
                            }
                            node.parentNode.removeChild(node);
                        }
                    } else if (node.nodeType === 1 && node.nodeName !== 'SCRIPT' && node.nodeName !== 'STYLE' && !node.classList.contains('manual-card-icon')) {
                        Array.from(node.childNodes).forEach(highlightText);
                    }
                };
                highlightText(section);
            }
        } else {
            section.style.display = 'none';
        }
    });
});
</script>

<?php adminFooter(); ?>
