<?php
require 'includes/auth.php';
require 'db.php';

// Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 40;
$offset = ($page - 1) * $perPage;

$estado = $_GET['estado'] ?? 'todos';

// Obtener total de libros para la paginación inicial
$sqlCount = 'SELECT COUNT(*) FROM libros l';
$countParams = [];

if ($estado === 'leidos') {
    $sqlCount .= ' WHERE EXISTS (SELECT 1 FROM libros_leidos ll WHERE ll.libro_id = l.id AND ll.usuario_id = ?)';
    $countParams[] = $_SESSION['user_id'];
} elseif ($estado === 'no_leidos') {
    $sqlCount .= ' WHERE NOT EXISTS (SELECT 1 FROM libros_leidos ll WHERE ll.libro_id = l.id AND ll.usuario_id = ?)';
    $countParams[] = $_SESSION['user_id'];
}

$stmtTotal = $pdo->prepare($sqlCount);
$stmtTotal->execute($countParams);
$totalLibros = $stmtTotal->fetchColumn();

$totalPages = ceil($totalLibros / $perPage);

// Obtener total de libros leídos por el usuario activo
$stmtLeidos = $pdo->prepare('SELECT COUNT(*) FROM libros_leidos WHERE usuario_id = ?');
$stmtLeidos->execute([$_SESSION['user_id']]);
$totalLeidos = (int)$stmtLeidos->fetchColumn();

// Obtener libros con nombre de editorial y nombre de categoría CDU (JOIN)
$sql = '
    SELECT l.*, e.nombre as editorial_nombre, c.nombre as cdu_nombre,
           (SELECT COUNT(*) FROM libros_leidos ll WHERE ll.libro_id = l.id AND ll.usuario_id = ?) as leido_user
    FROM libros l 
    LEFT JOIN editoriales e ON l.editorial_id = e.id 
    LEFT JOIN cdu c ON l.cdu_id = c.id
';

$execParams = [$_SESSION['user_id']];

if ($estado === 'leidos') {
    $sql .= ' WHERE EXISTS (SELECT 1 FROM libros_leidos ll WHERE ll.libro_id = l.id AND ll.usuario_id = ?)';
    $execParams[] = $_SESSION['user_id'];
} elseif ($estado === 'no_leidos') {
    $sql .= ' WHERE NOT EXISTS (SELECT 1 FROM libros_leidos ll WHERE ll.libro_id = l.id AND ll.usuario_id = ?)';
    $execParams[] = $_SESSION['user_id'];
}

$sql .= ' ORDER BY l.id DESC LIMIT ? OFFSET ?';
$execParams[] = $perPage;
$execParams[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($execParams);
$libros = $stmt->fetchAll();

// Obtener todas las editoriales para el datalist
$stmtEditoriales = $pdo->query('SELECT nombre FROM editoriales ORDER BY nombre ASC');
$editoriales = $stmtEditoriales->fetchAll();

// Obtener todas las categorías CDU para el datalist
$stmtCDU = $pdo->query('SELECT codigo, nombre FROM cdu ORDER BY codigo ASC');
$cdu_list = $stmtCDU->fetchAll();

$pageTitle = 'Biblioteca Dalias y Tortas';
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
        
        <!-- Estadísticas Rápidas -->
        <div class="mb-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5 hover:shadow-md transition-shadow">
                <div class="bg-indigo-50 p-3.5 rounded-xl text-indigo-600 ring-4 ring-indigo-50/50">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-0.5">Disponibles</p>
                    <p class="text-3xl font-extrabold text-gray-900 leading-none" id="stats-total"><?= $totalLibros ?></p>
                </div>
            </div>
            
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-5 hover:shadow-md transition-shadow">
                <div class="bg-emerald-50 p-3.5 rounded-xl text-emerald-600 ring-4 ring-emerald-50/50">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest mb-0.5">Leídos por mí</p>
                    <p class="text-3xl font-extrabold text-gray-900 leading-none">
                        <span id="stats-leidos"><?= $totalLeidos ?></span> 
                        <span class="text-lg font-medium text-gray-300 ml-1">/ <span id="stats-total-leidos"><?= $totalLibros ?></span></span>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Formulario para agregar (Solo Admins) -->
        <?php if ($esAdmin): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row justify-between items-center gap-4">
                <h3 class="text-lg font-semibold text-gray-800">Añadir Nuevo Libro</h3>
                
                <!-- Barra de Búsqueda (Interactiva) -->
                <div class="w-full sm:w-auto flex items-center relative">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" id="searchInput" placeholder="Buscar título, autor..." autocomplete="off"
                           class="w-full sm:w-64 pl-10 pr-4 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors shadow-sm">
                    <button type="button" id="clearSearch" class="hidden absolute right-3 px-1 text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
                </div>
            </div>
            <div class="p-6">
                <form action="controllers/libros.php" method="POST" class="space-y-4">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div class="space-y-1">
                            <label for="titulo" class="block text-sm font-medium text-gray-700">Título</label>
                            <input type="text" name="titulo" id="titulo" required placeholder="Ej. El Señor de los Anillos" 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        </div>
                        
                        <div class="space-y-1">
                            <label for="autor" class="block text-sm font-medium text-gray-700">Autor</label>
                            <input type="text" name="autor" id="autor" required placeholder="Ej. J.R.R. Tolkien" 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        </div>
                        
                        <div class="space-y-1">
                            <label for="cdu" class="block text-sm font-medium text-gray-700">Clasificación CDU</label>
                            <input type="text" name="cdu" id="cdu" list="cdu-list" placeholder="Selecciona o escribe..." 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            <datalist id="cdu-list">
                                <?php foreach ($cdu_list as $c): ?>
                                    <option value="<?= htmlspecialchars($c['codigo']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="space-y-1">
                            <label for="isbn" class="block text-sm font-medium text-gray-700">ISBN</label>
                            <input type="text" name="isbn" id="isbn" placeholder="Ej. 978-84-450-7032-4" 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        </div>

                        <div class="space-y-1">
                            <label for="editorial" class="block text-sm font-medium text-gray-700">Editorial</label>
                            <input type="text" name="editorial" id="editorial" list="editoriales-list" placeholder="Selecciona o escribe una nueva..." 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            <datalist id="editoriales-list">
                                <?php foreach ($editoriales as $ed): ?>
                                    <option value="<?= htmlspecialchars($ed['nombre']) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="space-y-1">
                                <label for="anio" class="block text-sm font-medium text-gray-700">Año</label>
                                <input type="number" name="anio" id="anio" placeholder="1954" 
                                       class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            </div>
                            <div class="space-y-1">
                                <label for="paginas" class="block text-sm font-medium text-gray-700">Páginas</label>
                                <input type="number" name="paginas" id="paginas" placeholder="1392" 
                                       class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex items-center gap-2">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                            Guardar Libro y Generar Tejuelo
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <!-- Barra de Búsqueda para usuarios no-admin (Solo lectura) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 p-6 flex justify-between items-center bg-gray-50/30">
            <h3 class="text-lg font-semibold text-gray-800">Explorar Catálogo</h3>
            <div class="w-full sm:w-auto flex items-center relative">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" id="searchInput" placeholder="Buscar título, autor..." autocomplete="off"
                       class="w-full sm:w-80 pl-10 pr-4 py-2 text-sm bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors shadow-sm">
                <button type="button" id="clearSearch" class="hidden absolute right-3 px-1 text-gray-400 hover:text-gray-600 transition-colors">&times;</button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Filtros de Estado -->
        <div class="mb-6 flex gap-2 overflow-x-auto pb-2" id="estado-tabs">
            <button type="button" onclick="cambiarEstado('todos')" data-estado="todos" class="tab-estado <?= $estado === 'todos' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap shadow-sm flex items-center gap-2">
                Todos los libros
            </button>
            <button type="button" onclick="cambiarEstado('no_leidos')" data-estado="no_leidos" class="tab-estado <?= $estado === 'no_leidos' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap shadow-sm flex items-center gap-2">
                No leídos
            </button>
            <button type="button" onclick="cambiarEstado('leidos')" data-estado="leidos" class="tab-estado <?= $estado === 'leidos' ? 'bg-indigo-600 text-white shadow-md' : 'bg-white text-gray-600 hover:bg-indigo-50 hover:text-indigo-600 border border-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors whitespace-nowrap shadow-sm flex items-center gap-2">
                Leídos
            </button>
        </div>
        
        <!-- Lista de libros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                <span id="contador-libros" class="text-sm font-medium text-gray-500 bg-white border border-gray-200 py-1 px-3 rounded-full"><?= $totalLibros ?> libros</span>
                
                <!-- Paginación (PHP Inicial) -->
                <div id="pagination-container" class="flex items-center gap-1" data-total="<?= $totalLibros ?>" data-page="<?= $page ?>" data-per-page="<?= $perPage ?>">
                    <?php if ($totalPages > 1): ?>
                        <!-- Primero -->
                        <a href="?estado=<?= urlencode($estado) ?>&page=1" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>" title="Primera página">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
                        </a>
                        <!-- Anterior -->
                        <a href="?estado=<?= urlencode($estado) ?>&page=<?= max(1, $page - 1) ?>" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>" title="Anterior">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>

                        <span class="px-3 py-1 text-sm font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-md">
                            <?= $page ?> / <?= $totalPages ?>
                        </span>

                        <!-- Siguiente -->
                        <a href="?estado=<?= urlencode($estado) ?>&page=<?= min($totalPages, $page + 1) ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>" title="Siguiente">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                        <!-- Último -->
                        <a href="?estado=<?= urlencode($estado) ?>&page=<?= $totalPages ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>" title="Última página">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <ul id="libros-list" class="divide-y divide-gray-100 <?php if (count($libros) === 0) echo 'hidden'; ?>">
                <?php foreach ($libros as $libro): ?>
                    <li id="libro-<?= $libro['id'] ?>" class="p-4 sm:p-6 hover:bg-gray-50 transition-colors flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between group">
                            
                            <div class="flex items-start gap-4 flex-1">
                                <!-- Tejuelo Visual -->
                                <?php if ($libro['signatura']): ?>
                                    <?php $partes_tejuelo = explode("\n", $libro['signatura']); ?>
                                    <div class="tejuelo" title="Tejuelo (Signatura Topográfica)">
                                        <?php foreach($partes_tejuelo as $linea): ?>
                                            <div class="tejuelo-line"><?= htmlspecialchars($linea) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                     <div class="flex-shrink-0 w-16 h-16 bg-gray-100 border-2 border-dashed border-gray-300 rounded flex items-center justify-center mt-1">
                                        <span class="text-[10px] text-gray-400">Sin Tejuelo</span>
                                     </div>
                                <?php endif; ?>

                                <!-- Marcar leído (Cualquier Usuario Logueado) -->
                                <form action="controllers/libros.php" method="POST" class="mt-1" onsubmit="marcarLeidoAjax(event, this)">
                                    <input type="hidden" name="accion" value="cambiar_estado">
                                    <input type="hidden" name="id" value="<?= $libro['id'] ?>">
                                    <input type="hidden" name="leido" value="<?= $libro['leido_user'] ? '0' : '1' ?>">
                                    <button type="submit" class="transition-colors flex-shrink-0 focus:outline-none" title="Marcar como <?= $libro['leido_user'] ? 'no leído' : 'leído' ?>">
                                        <?php if ($libro['leido_user']): ?>
                                            <svg class="w-7 h-7 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                        <?php else: ?>
                                            <svg class="w-7 h-7 text-gray-300 hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <?php endif; ?>
                                    </button>
                                </form>

                                <div class="flex-1 mt-1">
                                    <h4 class="text-lg font-semibold leading-tight <?= $libro['leido_user'] ? 'text-gray-400 line-through' : 'text-gray-900' ?>">
                                        <?= htmlspecialchars($libro['titulo']) ?>
                                    </h4>
                                    
                                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                        <span class="flex items-center gap-1 text-gray-700 font-medium">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            <?= htmlspecialchars($libro['autor']) ?>
                                        </span>
                                        <?php if ($libro['anio']): ?>
                                            <span class="flex items-center gap-1 before:content-['•'] before:text-gray-300 before:mr-2">
                                                <?= htmlspecialchars($libro['anio']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($libro['editorial_nombre']): ?>
                                            <span class="flex items-center gap-1 before:content-['•'] before:text-gray-300 before:mr-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                <?= htmlspecialchars($libro['editorial_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-gray-400">
                                        <?php if ($libro['isbn']): ?>
                                            <span title="ISBN">ISBN: <?= htmlspecialchars($libro['isbn']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($libro['paginas']): ?>
                                            <span class="<?= $libro['isbn'] ? "before:content-['•'] before:text-gray-200 before:mr-2" : "" ?>"><?= htmlspecialchars($libro['paginas']) ?> págs.</span>
                                        <?php endif; ?>
                                        <?php if ($libro['cdu_nombre']): ?>
                                            <span class="<?= ($libro['isbn'] || $libro['paginas']) ? "before:content-['•'] before:text-gray-200 before:mr-2" : "" ?> text-indigo-400 font-medium" title="Categoría CDU">
                                                CDU: <?= htmlspecialchars($libro['cdu']) ?> (<?= htmlspecialchars($libro['cdu_nombre']) ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($esAdmin): ?>
                            <!-- Botones admin -->
                             <div class="flex items-center gap-2 transition-opacity">
                                <a href="imprimir_tejuelo.php?id=<?= $libro['id'] ?>" target="_blank" class="text-emerald-600 hover:text-emerald-900 bg-emerald-50 p-2 rounded-lg transition-colors focus:outline-none" title="Imprimir Tejuelo y QR">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                </a>
                                <a href="editar.php?id=<?= $libro['id'] ?>&page=<?= $page ?>" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg transition-colors focus:outline-none" title="Editar Libro">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <button type="button" 
                                        onclick="confirmarEliminar(<?= $libro['id'] ?>, '¿Seguro que quieres eliminar este libro de tu biblioteca?')"
                                        class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg transition-colors focus:outline-none" title="Eliminar Libro">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            <?php endif; ?>
                            
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Paginación Inferior (PHP Inicial) -->
            <div id="pagination-container-bottom" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                <?php if ($totalPages > 1): ?>
                    <div class="flex items-center gap-1">
                        <!-- Primero -->
                        <a href="?estado=<?= urlencode($estado) ?>&page=1" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>" title="Primera página">«</a>
                        <!-- Anterior -->
                        <a href="?estado=<?= urlencode($estado) ?>&page=<?= max(1, $page - 1) ?>" class="pagination-btn <?= $page == 1 ? 'pointer-events-none opacity-40' : '' ?>">‹</a>

                        <span class="px-3 py-1 text-sm font-semibold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-md">
                            <?= $page ?> / <?= $totalPages ?>
                        </span>

                        <!-- Siguiente -->
                        <a href="?estado=<?= urlencode($estado) ?>&page=<?= min($totalPages, $page + 1) ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>">›</a>
                        <!-- Último -->
                        <a href="?estado=<?= urlencode($estado) ?>&page=<?= $totalPages ?>" class="pagination-btn <?= $page == $totalPages ? 'pointer-events-none opacity-40' : '' ?>">»</a>
                    </div>
                <?php endif; ?>
            </div>

            
            <!-- Mensaje Sin Resultados Búsqueda -->
            <div id="no-results-msg" class="hidden py-12 px-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <h3 class="mt-4 text-sm font-medium text-gray-900">No hay coincidencias</h3>
                <p class="mt-1 text-sm text-gray-500">No se encontraron libros que coincidan con tu búsqueda.</p>
            </div>

            <!-- Mensaje 0 Libros Totales -->
            <div id="no-books-msg" class="<?php if (count($libros) > 0) echo 'hidden'; ?> py-12 px-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                <h3 class="mt-4 text-sm font-medium text-gray-900">No hay libros</h3>
                <p class="mt-1 text-sm text-gray-500">Empieza tu biblioteca añadiendo tu primer libro arriba.</p>
            </div>
            
        </div>
        
    </main>

<script>
let estadoActual = <?= json_encode($estado) ?>;
window.tabModificado = false;

function cambiarEstado(nuevoEstado) {
    estadoActual = nuevoEstado;
    
    // Actualizar estilos botones
    document.querySelectorAll('.tab-estado').forEach(el => {
        if(el.dataset.estado === nuevoEstado) {
            el.classList.add('bg-indigo-600', 'text-white', 'shadow-md');
            el.classList.remove('bg-white', 'text-gray-600', 'hover:bg-indigo-50', 'hover:text-indigo-600', 'border', 'border-gray-200');
        } else {
            el.classList.remove('bg-indigo-600', 'text-white', 'shadow-md');
            el.classList.add('bg-white', 'text-gray-600', 'hover:bg-indigo-50', 'hover:text-indigo-600', 'border', 'border-gray-200');
        }
    });

    // Actualizar URL sin refrescar
    const url = new URL(window.location);
    url.searchParams.set('estado', nuevoEstado);
    url.searchParams.delete('page');
    window.history.pushState({}, '', url);

    if (window.ejecutarBusqueda) window.ejecutarBusqueda();
}

function marcarLeidoAjax(event, form) {
    event.preventDefault();
    const formData = new FormData(form);
    const libroId = formData.get('id');
    const leido = formData.get('leido');
    
    fetch('controllers/libros.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const isLeidoAction = leido === '1';
            
            // Actualizar contadores
            const statsLeidosEl = document.getElementById('stats-leidos');
            if (statsLeidosEl) {
                let currentLeidos = parseInt(statsLeidosEl.textContent);
                statsLeidosEl.textContent = isLeidoAction ? currentLeidos + 1 : Math.max(0, currentLeidos - 1);
            }

            const li = document.getElementById('libro-' + libroId);
            if (!li) return;

            if (estadoActual === 'leidos' && leido === '0') {
                li.remove();
            } else if (estadoActual === 'no_leidos' && leido === '1') {
                li.remove();
            } else {
                const isLeido = leido === '1';
                form.querySelector('input[name="leido"]').value = isLeido ? '0' : '1';
                const btn = form.querySelector('button');
                btn.title = isLeido ? 'Marcar como no leído' : 'Marcar como leído';

                const title = li.querySelector('h4');
                if (isLeido) {
                    btn.innerHTML = `<svg class="w-7 h-7 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>`;
                    title.classList.add('text-gray-400', 'line-through');
                    title.classList.remove('text-gray-900');
                } else {
                    btn.innerHTML = `<svg class="w-7 h-7 text-gray-300 hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
                    title.classList.remove('text-gray-400', 'line-through');
                    title.classList.add('text-gray-900');
                }
            }
        }
    })
    .catch(err => console.error(err));
}

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
    const list = document.getElementById('libros-list');
    const noResultsMsg = document.getElementById('no-results-msg');
    const noBooksMsg = document.getElementById('no-books-msg');
    const contadorLibros = document.getElementById('contador-libros');
    const paginationContainer = document.getElementById('pagination-container');
    
    // Configuración desde PHP
    const esAdmin = <?= json_encode($esAdmin) ?>;
    const originalHTML = list.innerHTML;
    const originalCount = <?= (int)$totalLibros ?>;
    const originalPaginationHTML = paginationContainer ? paginationContainer.innerHTML : '';

    let debounceTimer;

    function renderList(libros, total) {
        const currentPage = paginationContainer ? parseInt(paginationContainer.dataset.page || 1) : 1;
        if (libros.length === 0) {
            list.innerHTML = '';
            list.classList.add('hidden');
            noResultsMsg.classList.remove('hidden');
            if (paginationContainer) paginationContainer.innerHTML = '';
            const bottomPag = document.getElementById('pagination-container-bottom');
            if (bottomPag) bottomPag.innerHTML = '';
            if(noBooksMsg) noBooksMsg.classList.add('hidden');
        } else {
            noResultsMsg.classList.add('hidden');
            if(noBooksMsg) noBooksMsg.classList.add('hidden');
            list.classList.remove('hidden');
            let html = '';
            libros.forEach(libro => {
                const signaturaHtml = libro.signatura ? 
                    `<div class="tejuelo" title="Tejuelo">
                        ${escapeHtml(libro.signatura).split('\n').map(l => `<div class="tejuelo-line">${escapeHtml(l)}</div>`).join('')}
                    </div>` : 
                    `<div class="flex-shrink-0 w-16 h-16 bg-gray-100 border-2 border-dashed border-gray-300 rounded flex items-center justify-center mt-1"><span class="text-[10px] text-gray-400">Sin Tejuelo</span></div>`;
                
                const editorialHtml = libro.editorial_nombre ? 
                    `<span class="flex items-center gap-1 before:content-['•'] before:text-gray-300 before:mr-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        ${escapeHtml(libro.editorial_nombre)}
                    </span>` : '';

                const anioHtml = libro.anio ? 
                    `<span class="flex items-center gap-1 before:content-['•'] before:text-gray-300 before:mr-2">${escapeHtml(libro.anio)}</span>` : '';

                const isbnHtml = libro.isbn ? `<span title="ISBN">ISBN: ${escapeHtml(libro.isbn)}</span>` : '';
                const paginasHtml = libro.paginas ? `<span class="${libro.isbn ? "before:content-['•'] before:text-gray-200 before:mr-2" : ""}">${escapeHtml(libro.paginas)} págs.</span>` : '';
                const cduHtml = libro.cdu_nombre ? `<span class="${(libro.isbn || libro.paginas) ? "before:content-['•'] before:text-gray-200 before:mr-2" : ""} text-indigo-400 font-medium" title="CDU">CDU: ${escapeHtml(libro.cdu)} (${escapeHtml(libro.cdu_nombre)})</span>` : '';

                // Botones dinámicos según ROL
                let accionesHtml = '';
                const leidoPorMi = parseInt(libro.leido_user) > 0;
                const leidoIconHtml = leidoPorMi ? 
                    `<svg class="w-7 h-7 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>` : 
                    `<svg class="w-7 h-7 text-gray-300 hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;

                const leidoBtnHtml = `
                    <form action="controllers/libros.php" method="POST" class="mt-1" onsubmit="marcarLeidoAjax(event, this)">
                        <input type="hidden" name="accion" value="cambiar_estado">
                        <input type="hidden" name="id" value="${libro.id}">
                        <input type="hidden" name="leido" value="${leidoPorMi ? '0' : '1'}">
                        <button type="submit" class="transition-colors flex-shrink-0 focus:outline-none" title="Marcar como ${leidoPorMi ? 'no leído' : 'leído'}">
                            ${leidoIconHtml}
                        </button>
                    </form>`;

                if (esAdmin) {
                    accionesHtml = `
                        <div class="flex flex-row items-center gap-2 transition-opacity w-full sm:w-auto justify-end mt-4 sm:mt-0">
                            <a href="imprimir_tejuelo.php?id=${libro.id}" target="_blank" class="text-emerald-600 hover:text-emerald-900 bg-emerald-50 p-2 rounded-lg transition-colors focus:outline-none" title="Imprimir Tejuelo y QR">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                            </a>
                            <a href="editar.php?id=${libro.id}&page=${currentPage}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-2 rounded-lg transition-colors focus:outline-none" title="Editar Libro">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                        <button type="button" 
                                onclick="confirmarEliminar(${libro.id}, '¿Estás seguro de eliminar este libro de tu biblioteca?')"
                                class="text-red-600 hover:text-red-900 bg-red-50 p-2 rounded-lg transition-colors focus:outline-none" title="Eliminar Libro">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                        </div>`;
                }

                const titleClass = leidoPorMi ? 'text-gray-400 line-through' : 'text-gray-900';

                html += `
                <li id="libro-${libro.id}" class="p-4 sm:p-6 hover:bg-gray-50 transition-colors flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between group">
                    <div class="flex items-start gap-4 flex-1">
                        ${signaturaHtml}
                        ${leidoBtnHtml}
                        <div class="flex-1 mt-1">
                            <h4 class="text-lg font-semibold leading-tight ${titleClass}">${escapeHtml(libro.titulo)}</h4>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-gray-500">
                                <span class="flex items-center gap-1 text-gray-700 font-medium">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    ${escapeHtml(libro.autor)}
                                </span>
                                ${anioHtml}
                                ${editorialHtml}
                            </div>
                            <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-gray-400">
                                ${isbnHtml}${paginasHtml}${cduHtml}
                            </div>
                        </div>
                    </div>
                    ${accionesHtml}
                </li>`;
            });
            list.innerHTML = html;
        }
        contadorLibros.textContent = (searchInput.value.trim() !== '') ? `${total} libros encontrados` : `${originalCount} libros`;
    }

    function performSearch() {
        const q = searchInput.value.trim();
        
        if (q === '' && !window.tabModificado) {
            clearSearch.classList.add('hidden');
            list.innerHTML = originalHTML;
            if (paginationContainer) paginationContainer.innerHTML = originalPaginationHTML;
            if (document.getElementById('pagination-container-bottom')) {
                document.getElementById('pagination-container-bottom').innerHTML = originalPaginationHTML;
            }
            noResultsMsg.classList.add('hidden');
            if (originalCount > 0) {
                list.classList.remove('hidden');
            } else if (noBooksMsg) {
                noBooksMsg.classList.remove('hidden');
            }
            contadorLibros.textContent = `${originalCount} libros`;
            return;
        }

        if (q === '') {
            clearSearch.classList.add('hidden');
        } else {
            clearSearch.classList.remove('hidden');
        }
        
        if (paginationContainer) paginationContainer.innerHTML = '';
        const bottomPag = document.getElementById('pagination-container-bottom');
        if (bottomPag) bottomPag.innerHTML = '';

        fetch(`api/buscar.php?tipo=libros&q=${encodeURIComponent(q)}&estado=${encodeURIComponent(estadoActual)}&per_page=50`)
            .then(res => res.json())
            .then(res => {
                renderList(res.data, res.total);
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

    window.ejecutarBusqueda = () => {
        window.tabModificado = true;
        performSearch();
    };
});

function confirmarEliminar(id, msg) {
    if (confirm(msg)) {
        const form = document.getElementById('delete-form');
        document.getElementById('delete-id').value = id;
        form.submit();
    }
}
</script>

<form id="delete-form" action="controllers/libros.php" method="POST" style="display:none;">
    <input type="hidden" name="accion" value="eliminar">
    <input type="hidden" name="id" id="delete-id">
</form>

</body>
</html>
