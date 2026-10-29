<!-- nav.php -->
<?php
// Ya se asume que session_start() y $rolUsuario / $esAdmin vienen de includes/auth.php
?>
<nav class="bg-indigo-600 shadow-md">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo o Título -->
                <a href="index.php" class="flex-shrink-0 flex items-center gap-2 text-white font-bold text-xl tracking-tight hover:text-indigo-100 transition-colors">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <span>Biblioteca Dalias y Tortas</span>
                </a>
                
                <!-- Enlaces del Menú Principal -->
                <div class="hidden sm:ml-10 sm:flex sm:space-x-8">
                    <!-- Link a Libros -->
                    <a href="index.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'editar.php') ? 'border-b-2 border-white text-white' : 'text-indigo-100 hover:text-white hover:border-b-2 hover:border-indigo-300' ?> inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors">
                        Libros
                    </a>
                    
                    <?php if ($esAdmin): ?>
                        <?php
                        $navPendientes = 0;
                        if (isset($pdo)) {
                            try {
                                $navPendientes = (int)$pdo->query('SELECT COUNT(*) FROM usuarios WHERE activo = 0')->fetchColumn();
                            } catch (\Exception $e) {
                                $navPendientes = 0;
                            }
                        }
                        ?>
                        <!-- Link a Usuarios (Solo Admin) -->
                        <a href="usuarios.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'editar_usuario.php') ? 'border-b-2 border-white text-white' : 'text-indigo-100 hover:text-white hover:border-b-2 hover:border-indigo-300' ?> inline-flex items-center gap-1.5 px-1 pt-1 text-sm font-medium transition-colors">
                            <span>Usuarios</span>
                            <?php if ($navPendientes > 0): ?>
                                <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[11px] font-bold leading-none text-amber-900 bg-amber-300 rounded-full" title="<?= $navPendientes ?> alta(s) pendiente(s) de autorización"><?= $navPendientes ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Link a Editoriales (Visible para todos, admin puede editar) -->
                    <a href="editoriales.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'editoriales.php' || basename($_SERVER['PHP_SELF']) == 'editar_editorial.php') ? 'border-b-2 border-white text-white' : 'text-indigo-100 hover:text-white hover:border-b-2 hover:border-indigo-300' ?> inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors">
                        Editoriales
                    </a>

                    <!-- Link a CDU (Visible para todos) -->
                    <a href="cdu.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'cdu.php' || basename($_SERVER['PHP_SELF']) == 'editar_cdu.php') ? 'border-b-2 border-white text-white' : 'text-indigo-100 hover:text-white hover:border-b-2 hover:border-indigo-300' ?> inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors">
                        CDU
                    </a>
                </div>
            </div>
            
            <!-- Zona Derecha (Perfil y Logout) -->
            <div class="flex items-center gap-4">
                <div class="text-sm font-medium text-indigo-100 hidden sm:block">
                    <span class="text-white font-semibold flex items-center gap-1 inline-flex">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                        <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] uppercase font-bold <?= $esAdmin ? 'bg-indigo-800 text-indigo-100' : 'bg-green-700 text-green-100' ?>">
                            <?= $rolUsuario ?>
                        </span>
                    </span>
                </div>
                <a href="logout.php" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-sm font-medium text-indigo-600 bg-white hover:bg-gray-50 rounded-md transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-indigo-600 focus:ring-white">
                    <svg class="w-4 h-4 sm:hidden" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="hidden sm:inline">Salir</span>
                </a>
            </div>
        </div>
        
        <!-- Menú Móvil (Simple) -->
        <div class="sm:hidden pb-3 flex gap-4">
             <a href="index.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'editar.php') ? 'text-white font-bold' : 'text-indigo-200' ?> text-sm">Libros</a>
             <?php if ($esAdmin): ?>
                <a href="usuarios.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'usuarios.php' || basename($_SERVER['PHP_SELF']) == 'editar_usuario.php') ? 'text-white font-bold' : 'text-indigo-200' ?> text-sm">Usuarios</a>
             <?php endif; ?>
             <a href="editoriales.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'editoriales.php' || basename($_SERVER['PHP_SELF']) == 'editar_editorial.php') ? 'text-white font-bold' : 'text-indigo-200' ?> text-sm">Editoriales</a>
             <a href="cdu.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'cdu.php' || basename($_SERVER['PHP_SELF']) == 'editar_cdu.php') ? 'text-white font-bold' : 'text-indigo-200' ?> text-sm">CDU</a>
        </div>
    </div>
</nav>
