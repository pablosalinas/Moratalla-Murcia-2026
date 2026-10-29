<?php
require 'includes/auth.php';
adminOnly(); // Solo los administradores pueden gestionar usuarios
require 'db.php';

// Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 40;
$offset = ($page - 1) * $perPage;

// Obtener total de usuarios
$totalUsuarios = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalPendientes = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo = 0')->fetchColumn();
$totalPages = ceil($totalUsuarios / $perPage);

// Obtener usuarios paginados (los pendientes de autorización primero)
$stmt = $pdo->prepare('SELECT id, username, fecha_registro, rol, activo FROM usuarios ORDER BY activo ASC, id ASC LIMIT ? OFFSET ?');
$stmt->execute([$perPage, $offset]);
$usuarios = $stmt->fetchAll();

$pageTitle = 'Gestión de Usuarios';
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
        <header class="mb-6 flex flex-col sm:flex-row items-center justify-between bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div>
                <h1 class="text-3xl font-extrabold text-indigo-600 tracking-tight">Gestión de Usuarios</h1>
                <p class="mt-1 text-sm text-gray-500">Administra y autoriza quién tiene acceso a la biblioteca</p>
            </div>
        </header>

        <?php if ($totalPendientes > 0): ?>
            <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center gap-3 text-amber-800 shadow-sm">
                <svg class="w-6 h-6 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div class="text-sm">
                    Hay <strong><?= $totalPendientes ?></strong> <?= $totalPendientes === 1 ? 'usuario pendiente' : 'usuarios pendientes' ?> de confirmación. Revisa la lista inferior y pulsa <strong>Autorizar</strong> para habilitar su acceso.
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulario para agregar usuario -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="text-lg font-semibold text-gray-800">Añadir Nuevo Usuario</h3>
                
                <!-- Barra de Búsqueda (Interactiva) -->
                <div class="w-full sm:w-auto flex items-center relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="searchInput" placeholder="Buscar usuario..." autocomplete="off" 
                           class="w-full sm:w-64 pl-10 pr-4 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors shadow-sm">
                    <button type="button" id="clearSearch" class="hidden absolute right-3 px-1 text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                </div>
            </div>
            <div class="p-6">
                <form action="controllers/usuarios.php" method="POST" class="space-y-4">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label for="username" class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
                            <input type="text" name="username" id="username" required placeholder="Ej. bibliotecario" 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        </div>
                        
                        <div class="space-y-1">
                            <label for="password" class="block text-sm font-medium text-gray-700">Contraseña secreta</label>
                            <input type="password" name="password" id="password" required minlength="4" placeholder="Contraseña" 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        </div>

                        <div class="space-y-1">
                            <label for="rol" class="block text-sm font-medium text-gray-700">Rol de Usuario</label>
                            <select name="rol" id="rol" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                <option value="consulta">Consulta (Lector)</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            Registrar Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de usuarios -->
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
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Registro</th>
                            <th scope="col" class="relative px-6 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody id="usuarios-tbody" class="bg-white divide-y divide-gray-200">
                    <?php if (count($usuarios) > 0): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php $esActivo = (int)($usuario['activo'] ?? 1) === 1; ?>
                            <tr id="usuario-<?= $usuario['id'] ?>" class="<?= !$esActivo ? 'bg-amber-50/40' : '' ?> hover:bg-gray-50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    #<?= htmlspecialchars($usuario['id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-8 w-8 <?= $esActivo ? 'bg-indigo-100 text-indigo-600' : 'bg-amber-100 text-amber-700' ?> rounded-full flex items-center justify-center">
                                            <span class="font-bold text-sm"><?= strtoupper(substr($usuario['username'], 0, 1)) ?></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($usuario['username']) ?></div>
                                            <?php if ($usuario['id'] == $_SESSION['user_id']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Tú</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?= $usuario['rol'] === 'admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= htmlspecialchars($usuario['rol']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($esActivo): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            Habilitado
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            Deshabilitado (Pendiente)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d M Y - H:i', strtotime($usuario['fecha_registro'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium min-w-[160px]">
                                    <div class="flex justify-end items-center gap-2 transition-opacity">
                                        <?php if (!$esActivo): ?>
                                            <button type="button"
                                                    onclick="cambiarEstadoUsuario(<?= $usuario['id'] ?>, 'autorizar')"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition-colors"
                                                    title="Autorizar y habilitar acceso">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                Autorizar
                                            </button>
                                        <?php elseif ($usuario['id'] != $_SESSION['user_id']): ?>
                                            <button type="button"
                                                    onclick="cambiarEstadoUsuario(<?= $usuario['id'] ?>, 'deshabilitar')"
                                                    class="text-amber-600 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 p-2 rounded-lg transition-colors"
                                                    title="Deshabilitar usuario">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                            </button>
                                        <?php endif; ?>

                                        <a href="editar_usuario.php?id=<?= $usuario['id'] ?>&page=<?= $page ?>" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg" title="Editar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </a>
                                        
                                        <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" 
                                                onclick="confirmarEliminar(<?= $usuario['id'] ?>, '¿Estás ABSOLUTAMENTE SEGURO de eliminar a este USUARIO de la base de datos?')"
                                                class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg" title="Eliminar">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="no-usuarios-msg" class="hidden">
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr id="no-usuarios-msg">
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    <?php endif; ?>
                        <tr id="no-results-msg" class="hidden">
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                No se encontraron usuarios que coincidan con tu búsqueda.
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
    const clearSearch = document.getElementById('clearSearch');
    const tbody = document.getElementById('usuarios-tbody');
    const noResultsMsg = document.getElementById('no-results-msg');
    const paginationContainer = document.getElementById('pagination-container');
    const currentUserId = <?= json_encode($_SESSION['user_id']) ?>;
    
    const originalHTML = tbody.innerHTML;
    const paginationContainerTop = document.getElementById('pagination-container-top');
    const originalPaginationHTML = paginationContainer ? paginationContainer.innerHTML : '';
    const currentPage = <?= (int)$page ?>;
    let debounceTimer;

    function renderTable(usuarios) {
        if (usuarios.length === 0) {
            tbody.innerHTML = '';
            if(noResultsMsg) {
                noResultsMsg.classList.remove('hidden');
                tbody.appendChild(noResultsMsg);
            }
        } else {
            let html = '';
            usuarios.forEach(usuario => {
                const initial = usuario.username.substring(0, 1).toUpperCase();
                const isCurrentUser = usuario.id == currentUserId;
                const esActivo = Number(usuario.activo ?? 1) === 1;
                const userBadge = isCurrentUser ? `<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Tú</span>` : '';
                const roleClass = usuario.rol === 'admin' ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800';
                const statusBadge = esActivo
                    ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Habilitado</span>`
                    : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Deshabilitado (Pendiente)</span>`;
                
                // Formatear la fecha
                const date = new Date(usuario.fecha_registro.replace(' ', 'T'));
                const day = String(date.getDate()).padStart(2, '0');
                const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                const month = months[date.getMonth()];
                const year = date.getFullYear();
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const formattedDate = `${day} ${month} ${year} - ${hours}:${minutes}`;

                let statusBtn = '';
                if (!esActivo) {
                    statusBtn = `
                        <button type="button"
                                onclick="cambiarEstadoUsuario(${usuario.id}, 'autorizar')"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg shadow-sm transition-colors"
                                title="Autorizar y habilitar acceso">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Autorizar
                        </button>`;
                } else if (!isCurrentUser) {
                    statusBtn = `
                        <button type="button"
                                onclick="cambiarEstadoUsuario(${usuario.id}, 'deshabilitar')"
                                class="text-amber-600 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 p-2 rounded-lg transition-colors"
                                title="Deshabilitar usuario">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        </button>`;
                }

                const deleteBtn = isCurrentUser ? '' : `
                    <button type="button" 
                            onclick="confirmarEliminar(${usuario.id}, '¿Seguro que quieres eliminar a este usuario?')"
                            class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg" title="Eliminar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                `;

                html += `
                <tr id="usuario-${usuario.id}" class="${!esActivo ? 'bg-amber-50/40' : ''} hover:bg-gray-50 transition-colors group">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#${escapeHtml(usuario.id)}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8 ${esActivo ? 'bg-indigo-100 text-indigo-600' : 'bg-amber-100 text-amber-700'} rounded-full flex items-center justify-center">
                                <span class="font-bold text-sm">${initial}</span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${escapeHtml(usuario.username)}</div>
                                ${userBadge}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium ${roleClass}">
                            ${escapeHtml(usuario.rol)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">${statusBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formattedDate}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end items-center gap-2 transition-opacity">
                            ${statusBtn}
                            <a href="editar_usuario.php?id=${usuario.id}&page=${currentPage}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg" title="Editar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            ${deleteBtn}
                        </div>
                    </td>
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

        fetch(`api/buscar.php?tipo=usuarios&q=${encodeURIComponent(q)}&per_page=50`)
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

function cambiarEstadoUsuario(id, accion) {
    const form = document.getElementById('status-form');
    document.getElementById('status-accion').value = accion;
    document.getElementById('status-id').value = id;
    form.submit();
}

function confirmarEliminar(id, msg) {
    if (confirm(msg)) {
        const form = document.getElementById('delete-form');
        document.getElementById('delete-id').value = id;
        form.submit();
    }
}
</script>

<form id="status-form" action="controllers/usuarios.php" method="POST" style="display:none;">
    <input type="hidden" name="accion" id="status-accion" value="">
    <input type="hidden" name="id" id="status-id" value="">
    <input type="hidden" name="redirect_page" value="<?= (int)$page ?>">
</form>

<form id="delete-form" action="controllers/usuarios.php" method="POST" style="display:none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="delete-id">
</form>

</body>
</html>
