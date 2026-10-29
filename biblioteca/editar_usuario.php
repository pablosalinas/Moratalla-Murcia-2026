<?php
require 'includes/auth.php';
adminOnly(); // Solo los administradores pueden gestionar usuarios

require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: usuarios.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare('SELECT id, username, rol, activo FROM usuarios WHERE id = ?');
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header("Location: usuarios.php");
    exit;
}
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
        
        <header class="mb-10 flex items-center justify-between bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h1 class="text-3xl font-extrabold text-indigo-600 tracking-tight">Editar Usuario</h1>
            <a href="usuarios.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-lg transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver
            </a>
        </header>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-lg font-semibold text-gray-800">Modificar Datos de Acceso</h3>
            </div>
            <div class="p-6">
                <form action="controllers/usuarios.php" method="POST" class="space-y-4">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                    <input type="hidden" name="redirect_page" value="<?= htmlspecialchars($_GET['page'] ?? '1') ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label for="username" class="block text-sm font-medium text-gray-700">Nombre de Usuario</label>
                            <input type="text" name="username" id="username" required value="<?= htmlspecialchars($usuario['username']) ?>" 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        </div>
                        
                        <div class="space-y-1">
                            <label for="password" class="block text-sm font-medium text-gray-700 flex justify-between">
                                <span>Nueva Contraseña</span>
                                <span class="text-xs text-gray-400 font-normal">(Opcional)</span>
                            </label>
                            <input type="password" name="password" id="password" placeholder="Dejar en blanco para mantener actual" 
                                   class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        </div>

                        <div class="space-y-1">
                            <label for="rol" class="block text-sm font-medium text-gray-700">Rol de Usuario</label>
                            <select name="rol" id="rol" class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                <option value="consulta" <?= $usuario['rol'] === 'consulta' ? 'selected' : '' ?>>Consulta (Lector)</option>
                                <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>

                        <div class="space-y-1">
                            <label for="activo" class="block text-sm font-medium text-gray-700">Estado de la Cuenta</label>
                            <select name="activo" id="activo" <?= $usuario['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?> class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                <option value="1" <?= (int)($usuario['activo'] ?? 1) === 1 ? 'selected' : '' ?>>Habilitado (Autorizado)</option>
                                <option value="0" <?= (int)($usuario['activo'] ?? 1) === 0 ? 'selected' : '' ?>>Deshabilitado (Pendiente de autorización)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap justify-between items-center gap-4 pt-4">
                        <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                        <button type="button" 
                                onclick="confirmarEliminar(<?= $usuario['id'] ?>, '¿Seguro que quieres eliminar este usuario? No podrá acceder a la biblioteca.')"
                                class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Eliminar Usuario
                        </button>
                        <?php else: ?>
                        <div></div> <!-- Espaciador si no puede borrar -->
                        <?php endif; ?>

                        <div class="flex gap-3">
                            <a href="usuarios.php" class="px-6 py-2 bg-white hover:bg-gray-50 text-gray-700 font-medium border border-gray-300 rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200">
                                Cancelar
                            </a>
                            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 flex items-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Actualizar Usuario
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
    </main>

    <script>
    function confirmarEliminar(id, msg) {
        if (confirm(msg)) {
            const form = document.getElementById('delete-form');
            document.getElementById('delete-id').value = id;
            form.submit();
        }
    }
    </script>

    <form id="delete-form" action="controllers/usuarios.php" method="POST" style="display:none;">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="id" id="delete-id">
    </form>

</body>
</html>
