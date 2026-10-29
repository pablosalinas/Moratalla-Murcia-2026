<?php
require 'includes/auth.php';
adminOnly();
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: cdu.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare('SELECT * FROM cdu WHERE id = ?');
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header("Location: cdu.php");
    exit;
}

$pageTitle = 'Editar Categoría CDU';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require 'includes/head.php'; ?>
</head>
<body class="text-gray-800 antialiased min-h-screen bg-gray-50 flex flex-col">
    
    <?php require 'nav.php'; ?>

    <main class="flex-grow max-w-4xl w-full mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        <header class="mb-10 flex items-center justify-between bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h1 class="text-3xl font-extrabold text-indigo-600 tracking-tight">Editar Categoría</h1>
            <a href="cdu.php?page=<?= (int)($_GET['page'] ?? 1) ?>" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver
            </a>
        </header>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-semibold text-gray-800">Modificar Clasificación</h3>
            </div>
            <div class="p-6">
                <form action="controllers/cdu.php" method="POST" class="space-y-6">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?= $categoria['id'] ?>">
                    <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($_GET['page'] ?? '1') ?>">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label for="codigo" class="block text-sm font-medium text-gray-700">Código CDU</label>
                            <input type="text" name="codigo" id="codigo" required value="<?= htmlspecialchars($categoria['codigo']) ?>" 
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all font-mono font-bold">
                        </div>

                        <div class="space-y-1">
                            <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre de la Categoría</label>
                            <input type="text" name="nombre" id="nombre" required value="<?= htmlspecialchars($categoria['nombre']) ?>" 
                                   class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all">
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center pt-4">
                        <button type="button" 
                                onclick="confirmarEliminar(<?= $categoria['id'] ?>, '¿Seguro que quieres eliminar esta categoría CDU?')"
                                class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Eliminar Categoría
                        </button>

                        <div class="flex gap-3">
                            <a href="cdu.php" class="px-6 py-2 bg-white hover:bg-gray-50 text-gray-700 font-medium border border-gray-300 rounded-lg transition-colors shadow-sm">
                                Cancelar
                            </a>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Actualizar Categoría
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
    <script>
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
