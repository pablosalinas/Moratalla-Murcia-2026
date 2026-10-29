<?php
require 'db.php';

// Si se ejecuta desde /moratalla/biblioteca, sincronizar archivos actualizados hacia /biblioteca en el mismo servidor (sin borrar nada)
$candidateDirs = array_filter([
    isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/biblioteca' : '',
    dirname(dirname(__DIR__)) . '/biblioteca',
]);
$filesToSync = [
    'db.php',
    'login.php',
    'registro.php',
    'usuarios.php',
    'editar_usuario.php',
    'nav.php',
    'check_schema.php',
    'api/buscar.php',
    'controllers/usuarios.php',
    'includes/mailer.php',
];
foreach ($candidateDirs as $rootBiblio) {
    if ($rootBiblio && is_dir($rootBiblio) && realpath($rootBiblio) !== realpath(__DIR__)) {
        foreach ($filesToSync as $relFile) {
            $src = __DIR__ . '/' . $relFile;
            $dst = $rootBiblio . '/' . $relFile;
            $dstDir = dirname($dst);
            if (is_file($src)) {
                if (!is_dir($dstDir)) {
                    @mkdir($dstDir, 0755, true);
                }
                @copy($src, $dst);
            }
        }
    }
}

function checkTable($pdo, $table) {
    echo "\n--- $table ---\n";
    $stmt = $pdo->query("SHOW CREATE TABLE $table");
    $row = $stmt->fetch();
    echo $row['Create Table'] . "\n";
}
try {
    checkTable($pdo, 'usuarios');
    checkTable($pdo, 'libros');
    checkTable($pdo, 'editoriales');
    checkTable($pdo, 'cdu');
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
