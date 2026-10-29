<?php
require 'includes/auth.php';
require 'db.php';

// Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 40;
$offset = ($page - 1) * $perPage;

// Obtener total de categorías CDU
$totalCDU = $pdo->query('SELECT COUNT(*) FROM cdu')->fetchColumn();
$totalPages = ceil($totalCDU / $perPage);

// Obtener categorías con contador de libros
$stmt = $pdo->prepare('
    SELECT c.*, COUNT(l.id) as cantidad_libros 
    FROM cdu c 
    LEFT JOIN libros l ON c.id = l.cdu_id 
    GROUP BY c.id 
    ORDER BY c.codigo ASC 
    LIMIT ? OFFSET ?
');
$stmt->execute([$perPage, $offset]);
$categorias = $stmt->fetchAll();

$pageTitle = 'Clasificaciones CDU';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require 'includes/head.php'; ?>
</head>
<body class="text-gray-800 antialiased min-h-screen bg-gray-50 flex flex-col">
    
    <?php require 'nav.php'; ?>

    <main class="flex-grow max-w-4xl w-full mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <header class="mb-10 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div>
                <h1 class="text-3xl font-extrabold text-indigo-600 tracking-tight">Clasificación CDU</h1>
                <p class="text-gray-500 mt-1">Gestión del sistema de clasificación decimal universal.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-indigo-600 bg-indigo-50 py-1.5 px-4 rounded-full border border-indigo-100 italic transition-transform hover:scale-105 duration-300 shadow-sm cursor-default">
                    <?= $totalCDU ?> categorías en total
                </span>
            </div>
        </header>

        <!-- Formulario para agregar (Solo Admins) -->
        <?php if ($esAdmin): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Añadir Nueva Categoría</h3>
                <!-- Buscador rápido -->
                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="searchInput" placeholder="Filtrar categorías..." 
                           class="pl-9 pr-10 py-1.5 text-xs border border-gray-200 rounded-full focus:ring-2 focus:ring-indigo-500 outline-none transition-all w-48 focus:w-64">
                    <button id="clearSearchAdmin" class="clearSearch absolute inset-y-0 right-0 pr-3 flex items-center hidden text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <form action="controllers/cdu.php" method="POST" class="flex flex-col sm:flex-row gap-4">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="flex-1 space-y-1">
                        <label for="codigo" class="block text-sm font-medium text-gray-700">Código CDU</label>
                        <input type="text" name="codigo" id="codigo" required placeholder="Ej. 821.134.2" 
                               class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>

                    <div class="flex-[2] space-y-1">
                        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre de la Categoría</label>
                        <input type="text" name="nombre" id="nombre" required placeholder="Ej. Literatura española" 
                               class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full sm:w-auto px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm flex items-center justify-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Añadir
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Buscador para lectura -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 p-4 flex justify-end">
             <div class="relative">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" id="searchInput" placeholder="Buscar categoría o código..." 
                       class="pl-10 pr-10 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition-all w-80">
                <button id="clearSearchRead" class="clearSearch absolute inset-y-0 right-0 pr-3 flex items-center hidden text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabla de categorías -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <!-- Paginación Superior (PHP Inicial) -->
                <div class="px-6 py-3 border-b border-gray-100 flex justify-end items-center gap-4 bg-gray-50/30">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Página <?= $page ?> / <?= $totalPages ?></span>
                    <div id="pagination-container-top" class="flex items-center gap-1">
                        <?php if ($totalPages > 1): ?>
                            <a href="?page=1" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>" title="Primera">«</a>
                            <a href="?page=<?= max(1, $page-1) ?>" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>">‹</a>
                            <a href="?page=<?= min($totalPages, $page+1) ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>">›</a>
                            <a href="?page=<?= $totalPages ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>">»</a>
                        <?php endif; ?>
                    </div>
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">Libros</th>
                            <?php if ($esAdmin): ?><th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="cdu-tbody" class="bg-white divide-y divide-gray-200">
                        <?php foreach ($categorias as $cat): ?>
                            <tr id="cdu-<?= $cat['id'] ?>" class="hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-sm font-mono font-bold rounded shadow-sm"><?= htmlspecialchars($cat['codigo']) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($cat['nombre']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $cat['cantidad_libros'] > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= $cat['cantidad_libros'] ?> libros
                                    </span>
                                </td>
                                <?php if ($esAdmin): ?>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium min-w-[120px]">
                                    <div class="flex justify-end gap-2 transition-opacity">
                                        <a href="editar_cdu.php?id=<?= $cat['id'] ?>&page=<?= $page ?>" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg transition-colors" title="Editar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>
                                        <button type="button" 
                                                onclick="confirmarEliminar(<?= $cat['id'] ?>, '¿Seguro que quieres eliminar esta categoría CDU?')"
                                                class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg transition-colors" title="Eliminar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="no-results-msg" class="hidden">
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="h-8 w-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                    <span>No se encontraron categorías que coincidan con tu búsqueda.</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Mostrando página <?= $page ?> de <?= $totalPages ?>
                </div>
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
        const clearButtons = document.querySelectorAll('.clearSearch');
        const tbody = document.getElementById('cdu-tbody');
        const noResultsMsg = document.getElementById('no-results-msg');
        const paginationContainer = document.getElementById('pagination-container');
        
        const originalHTML = tbody.innerHTML;
        const originalPaginationHTML = paginationContainer ? paginationContainer.innerHTML : '';
        const currentPage = <?= (int)$page ?>;
        const esAdmin = <?= json_encode($esAdmin) ?>;
        let debounceTimer;

        function renderTable(categorias) {
            if (categorias.length === 0) {
                tbody.innerHTML = '';
                if(noResultsMsg) {
                    noResultsMsg.classList.remove('hidden');
                    tbody.appendChild(noResultsMsg);
                }
            } else {
                let html = '';
                categorias.forEach(cat => {
                    const badgeClass = cat.cantidad_libros > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                    
                    let accionesHtml = '';
                    if (esAdmin) {
                        accionesHtml = `
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium min-w-[120px]">
                            <div class="flex justify-end gap-2 transition-opacity">
                                <a href="editar_cdu.php?id=${cat.id}&page=${currentPage}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg transition-colors" title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <button type="button" 
                                        onclick="confirmarEliminar(${cat.id}, '¿Seguro?')"
                                        class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg transition-colors" title="Eliminar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>`;
                    }

                    html += `
                    <tr id="cdu-${cat.id}" class="hover:bg-gray-50 transition-colors group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-sm font-mono font-bold rounded shadow-sm">${escapeHtml(cat.codigo)}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">${escapeHtml(cat.nombre)}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass}">
                                ${cat.cantidad_libros} libros
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
                clearButtons.forEach(btn => btn.classList.add('hidden'));
                tbody.innerHTML = originalHTML;
                if (paginationContainer) paginationContainer.innerHTML = originalPaginationHTML;
                return;
            }

            clearButtons.forEach(btn => btn.classList.remove('hidden'));
            if (paginationContainer) paginationContainer.innerHTML = '';

            fetch(`api/buscar.php?tipo=cdu&q=${encodeURIComponent(q)}&per_page=50`)
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

        clearButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                searchInput.value = '';
                performSearch();
            });
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

<form id="delete-form" action="controllers/cdu.php" method="POST" style="display:none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="delete-id">
</form>

</body>
</html>
