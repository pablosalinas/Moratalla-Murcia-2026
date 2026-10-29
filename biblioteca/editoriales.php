<?php
require 'includes/auth.php';
require 'db.php';

// Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 40;
$offset = ($page - 1) * $perPage;

// Obtener total de editoriales
$totalEditoriales = $pdo->query('SELECT COUNT(*) FROM editoriales')->fetchColumn();
$totalPages = ceil($totalEditoriales / $perPage);

// Obtener editoriales paginadas con conteo de libros
$stmt = $pdo->prepare('
    SELECT e.id, e.nombre, COUNT(l.id) AS cantidad_libros 
    FROM editoriales e 
    LEFT JOIN libros l ON e.id = l.editorial_id 
    GROUP BY e.id 
    ORDER BY e.nombre ASC
    LIMIT ? OFFSET ?
');
$stmt->execute([$perPage, $offset]);
$editoriales = $stmt->fetchAll();

$pageTitle = 'Gestión de Editoriales';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require 'includes/head.php'; ?>
</head>
<body class="text-gray-800 antialiased min-h-screen bg-gray-50 flex flex-col">
    
    <!-- Menú Principal -->
    <?php require 'nav.php'; ?>

    <main class="flex-grow max-w-4xl w-full mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <!-- Cabecera -->
        <header class="mb-10 flex flex-col sm:flex-row items-center justify-between bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div>
                <h1 class="text-3xl font-extrabold text-indigo-600 tracking-tight">Editoriales</h1>
                <p class="mt-1 text-sm text-gray-500">Listado centralizado de editoriales del catálogo</p>
            </div>
        </header>

        <!-- Formulario para agregar editorial (Solo Admin) -->
        <?php if ($esAdmin): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Añadir Nueva Editorial</h3>
                    <p class="text-sm text-gray-500 mt-1">También se añaden automáticamente al crear un libro con una nueva.</p>
                </div>
                
                <!-- Barra de Búsqueda (Interactiva) -->
                <div class="w-full sm:w-auto flex items-center relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="searchInput" placeholder="Buscar editorial..." autocomplete="off"
                           class="w-full sm:w-64 pl-10 pr-4 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors shadow-sm">
                    <button type="button" id="clearSearch" class="hidden absolute right-3 px-1 text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                </div>
            </div>
            <div class="p-6">
                <form action="controllers/editoriales.php" method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="flex-grow space-y-1 w-full">
                        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre de la Editorial</label>
                        <input type="text" name="nombre" id="nombre" required placeholder="Ej. Impedimenta" 
                               class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    
                    <div class="w-full sm:w-auto">
                        <button type="submit" class="w-full sm:w-auto px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex items-center justify-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Barra de Búsqueda para usuarios no-admin (Solo lectura) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 p-6 flex justify-between items-center bg-gray-50/30">
            <h3 class="text-lg font-semibold text-gray-800">Editoriales</h3>
            <div class="w-full sm:w-auto flex items-center relative">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" id="searchInput" placeholder="Buscar editorial..." autocomplete="off"
                       class="w-full sm:w-80 pl-10 pr-4 py-2 text-sm bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors shadow-sm">
                <button type="button" id="clearSearch" class="hidden absolute right-3 px-1 text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de editoriales -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <span class="text-sm font-medium text-gray-500 bg-white border border-gray-200 py-1 px-3 rounded-full"><?= $totalEditoriales ?> editoriales</span>
                
                <!-- Paginación UI -->
                <div id="pagination-container" class="flex items-center gap-1">
                    <?php if ($totalPages > 1): ?>
                        <a href="?page=1" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>" title="Primera">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                        </a>
                        <a href="?page=<?= max(1, $page-1) ?>" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>" title="Anterior">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                        <span class="px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-md mx-1"><?= $page ?> / <?= $totalPages ?></span>
                        <a href="?page=<?= min($totalPages, $page+1) ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>" title="Siguiente">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="?page=<?= $totalPages ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>" title="Última">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre Variante</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Libros Asociados</th>
                            <?php if ($esAdmin): ?><th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="editoriales-tbody" class="bg-white divide-y divide-gray-200">
                    <?php if (count($editoriales) > 0): ?>
                        <?php foreach ($editoriales as $editorial): ?>
                            <tr id="editorial-<?= $editorial['id'] ?>" class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    #<?= htmlspecialchars($editorial['id']) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        <?= htmlspecialchars($editorial['nombre']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $editorial['cantidad_libros'] > 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $editorial['cantidad_libros'] ?> libros
                                    </span>
                                </td>
                                <?php if ($esAdmin): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium min-w-[120px]">
                                    <div class="flex justify-end gap-2 transition-opacity">
                                        <a href="editar_editorial.php?id=<?= $editorial['id'] ?>&page=<?= $page ?>" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg" title="Editar Nombre">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>
                                        
                                        <button type="button" 
                                                onclick="confirmarEliminar(<?= $editorial['id'] ?>, '¿Seguro que quieres eliminar esta editorial? Se desvinculará de todos los libros.')"
                                                class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg" title="Eliminar Editorial">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="no-editoriales-msg">
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay editoriales registradas.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr id="no-results-msg" class="hidden">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                            No se encontraron editoriales que coincidan con tu búsqueda.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación Inferior (PHP Inicial) -->
            <div id="pagination-container-bottom" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                <?php if ($totalPages > 1): ?>
                    <div class="flex items-center gap-1">
                        <a href="?page=1" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>" title="Primera">«</a>
                        <a href="?page=<?= max(1, $page-1) ?>" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>">‹</a>
                        <span class="px-3 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-md mx-1"><?= $page ?> / <?= $totalPages ?></span>
                        <a href="?page=<?= min($totalPages, $page+1) ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>">›</a>
                        <a href="?page=<?= $totalPages ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>" title="Última">»</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </main>
<script>
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe.toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const clearSearch = document.getElementById('clearSearch');
    const tbody = document.getElementById('editoriales-tbody');
    const noResultsMsg = document.getElementById('no-results-msg');
    const paginationContainer = document.getElementById('pagination-container');
    
    // Configuración
    const esAdmin = <?= json_encode($esAdmin) ?>;
    const originalHTML = tbody.innerHTML;
    const originalPaginationHTML = paginationContainer ? paginationContainer.innerHTML : '';

    let debounceTimer;

    function renderTable(editoriales) {
        if (editoriales.length === 0) {
            tbody.innerHTML = '';
            if(noResultsMsg) {
                noResultsMsg.classList.remove('hidden');
                tbody.appendChild(noResultsMsg);
            }
        } else {
            let html = '';
            editoriales.forEach(editorial => {
                const badgeClass = editorial.cantidad_libros > 0 ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800';
                
                let accionesHtml = '';
                if (esAdmin) {
                    accionesHtml = `
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end gap-2 transition-opacity">
                            <a href="editar_editorial.php?id=${editorial.id}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg" title="Editar Nombre">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <button type="button" 
                                    onclick="confirmarEliminar(${editorial.id}, '¿Seguro?')"
                                    class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg" title="Eliminar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </td>`;
                }

                html += `
                <tr class="hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#${escapeHtml(editorial.id)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            ${escapeHtml(editorial.nombre)}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}">
                            ${escapeHtml(editorial.cantidad_libros)} libros
                        </span>
                    </td>
                    ${accionesHtml}
                </tr>`;
            });
            tbody.innerHTML = html;
        }
    }

    function performSearch() {
        const q = searchInput.value.trim();
        
        if (q === '') {
            clearSearch.classList.add('hidden');
            tbody.innerHTML = originalHTML;
            if (paginationContainer) paginationContainer.innerHTML = originalPaginationHTML;
            return;
        }

        clearSearch.classList.remove('hidden');
        if (paginationContainer) paginationContainer.innerHTML = '';

        fetch(`api/buscar.php?tipo=editoriales&q=${encodeURIComponent(q)}&per_page=50`)
            .then(res => res.json())
            .then(res => {
                renderTable(res.data);
            })
            .catch(err => console.error("Error:", err));
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(performSearch, 300);
    });

    clearSearch.addEventListener('click', () => {
        searchInput.value = '';
        performSearch();
    });
});

function confirmarEliminar(id, msg) {
    if (confirm(msg)) {
        const form = document.getElementById('delete-form');
        document.getElementById('delete-id').value = id;
        form.submit();
    }
}
</script>

<form id="delete-form" action="controllers/editoriales.php" method="POST" style="display:none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="delete-id">
</form>

</body>
</html>
