/**
 * app.js — Funciones JavaScript compartidas para Biblioteca Dalias y Tortas
 */

/* ── Utilidades básicas ─────────────────────────────────────── */

function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return unsafe.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Formatea una fecha "YYYY-MM-DD HH:MM:SS" al estilo "DD Mon YYYY - HH:MM"
 */
function formatDate(raw) {
    if (!raw) return '';
    const d = new Date(raw.replace(' ', 'T'));
    const months = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const day    = String(d.getDate()).padStart(2, '0');
    const month  = months[d.getMonth()];
    const year   = d.getFullYear();
    const hh     = String(d.getHours()).padStart(2, '0');
    const mm     = String(d.getMinutes()).padStart(2, '0');
    return `${day} ${month} ${year} - ${hh}:${mm}`;
}

/* ── Paginación ─────────────────────────────────────────────── */

/**
 * Renderiza los controles de paginación dentro de `containerId`.
 *
 * @param {string}   containerId  ID del elemento contenedor
 * @param {number}   total        Total de registros
 * @param {number}   page         Página actual (1-indexed)
 * @param {number}   perPage      Registros por página
 * @param {Function} onPageChange Callback(newPage) cuando se cambia de página
 */
function renderPaginacion(containerId, total, page, perPage, onPageChange) {
    const container = document.getElementById(containerId);
    if (!container) return;

    const totalPages = Math.ceil(total / perPage);

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    // Calcular rango de botones a mostrar (máximo 5 alrededor de la página actual)
    let start = Math.max(1, page - 2);
    let end   = Math.min(totalPages, page + 2);
    if (end - start < 4) {
        if (start === 1) end = Math.min(totalPages, start + 4);
        else start = Math.max(1, end - 4);
    }

    let html = `<div class="flex items-center gap-1 flex-wrap">`;

    // Botón "Primera"
    html += `<button class="pagination-btn" ${page === 1 ? 'disabled' : ''} data-page="1" title="Primera página">«</button>`;
    // Botón "Anterior"
    html += `<button class="pagination-btn" ${page === 1 ? 'disabled' : ''} data-page="${page - 1}" title="Página anterior">‹</button>`;

    if (start > 1) html += `<span class="pagination-btn" style="pointer-events:none;border:none;background:none;">…</span>`;

    for (let i = start; i <= end; i++) {
        html += `<button class="pagination-btn ${i === page ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }

    if (end < totalPages) html += `<span class="pagination-btn" style="pointer-events:none;border:none;background:none;">…</span>`;

    // Botón "Siguiente"
    html += `<button class="pagination-btn" ${page === totalPages ? 'disabled' : ''} data-page="${page + 1}" title="Página siguiente">›</button>`;
    // Botón "Última"
    html += `<button class="pagination-btn" ${page === totalPages ? 'disabled' : ''} data-page="${totalPages}" title="Última página">»</button>`;

    html += `</div>`;
    container.innerHTML = html;

    // Evento de clic
    container.querySelectorAll('button[data-page]').forEach(btn => {
        btn.addEventListener('click', () => {
            const newPage = parseInt(btn.dataset.page, 10);
            if (!isNaN(newPage)) onPageChange(newPage);
        });
    });
}

/**
 * Función universal para confirmar y enviar un borrado.
 * Requiere que exista un <form id="delete-form-{module}"> con un <input id="delete-id-{module}">
 */
function confirmarEliminar(id, msg, module) {
    if (confirm(msg)) {
        const formId = 'delete-form-' + module;
        const inputId = 'delete-id-' + module;
        const form = document.getElementById(formId);
        const input = document.getElementById(inputId);
        
        if (form && input) {
            input.value = id;
            form.submit();
        } else {
            console.error('No se encontró el formulario de borrado:', formId);
            alert('Error técnico: No se pudo localizar el formulario de borrado para ' + module);
        }
    }
}
