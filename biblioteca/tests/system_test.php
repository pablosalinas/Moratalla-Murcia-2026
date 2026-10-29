<?php
/**
 * tests/system_test.php
 * Batería de pruebas para verificar la integridad del sistema Biblioteca.
 */

require_once __DIR__ . '/../db.php';

function logTest($msg, $success = true) {
    if ($success) {
        echo "[\033[32mPASS\033[0m] $msg\n";
    } else {
        echo "[\033[31mFAIL\033[0m] $msg\n";
    }
}

echo "--- INICIANDO BATERÍA DE TESTS ---\n\n";

// 1. Conexión a Base de Datos
try {
    $pdo->query("SELECT 1");
    logTest("Conexión a la base de datos establecida.");
} catch (Exception $e) {
    logTest("Fallo en la conexión: " . $e->getMessage(), false);
    exit(1);
}

// 2. Verificación de Tablas y Columnas Críticas
$tablasRequeridas = [
    'usuarios' => ['id', 'username', 'password', 'rol'],
    'libros' => ['id', 'titulo', 'autor', 'cdu', 'cdu_id', 'editorial_id', 'signatura'],
    'editoriales' => ['id', 'nombre'],
    'cdu' => ['id', 'codigo', 'nombre']
];

foreach ($tablasRequeridas as $tabla => $columnas) {
    try {
        $stmt = $pdo->query("DESCRIBE $tabla");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $missing = array_diff($columnas, $cols);
        if (empty($missing)) {
            logTest("Tabla '$tabla' verificada con sus columnas críticas.");
        } else {
            logTest("Tabla '$tabla' le faltan columnas: " . implode(', ', $missing), false);
        }
    } catch (Exception $e) {
        logTest("Error al verificar tabla '$tabla': " . $e->getMessage(), false);
    }
}

echo "\n--- PRUEBAS CRUD (Simuladas via PDO) ---\n\n";

// 3. Test CRUD Usuarios
try {
    // Create
    $testUser = "test_sys_" . time();
    $stmt = $pdo->prepare("INSERT INTO usuarios (username, password, rol) VALUES (?, 'testpass', 'consulta')");
    $stmt->execute([$testUser]);
    $userId = $pdo->lastInsertId();
    logTest("CRUD Usuarios: Inserción correcta.");

    // Update
    $stmt = $pdo->prepare("UPDATE usuarios SET rol = 'admin' WHERE id = ?");
    $stmt->execute([$userId]);
    logTest("CRUD Usuarios: Actualización correcta.");

    // Delete
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    logTest("CRUD Usuarios: Eliminación correcta.");
} catch (Exception $e) {
    logTest("CRUD Usuarios: Fallo - " . $e->getMessage(), false);
}

// 4. Test CRUD Editoriales
try {
    $testEd = "Editorial Test " . time();
    $stmt = $pdo->prepare("INSERT INTO editoriales (nombre) VALUES (?)");
    $stmt->execute([$testEd]);
    $edId = $pdo->lastInsertId();
    logTest("CRUD Editoriales: Inserción correcta.");

    $stmt = $pdo->prepare("DELETE FROM editoriales WHERE id = ?");
    $stmt->execute([$edId]);
    logTest("CRUD Editoriales: Eliminación correcta.");
} catch (Exception $e) {
    logTest("CRUD Editoriales: Fallo - " . $e->getMessage(), false);
}

// 5. Test CRUD Libros
try {
    $stmt = $pdo->prepare("INSERT INTO libros (titulo, autor, editorial_id) VALUES ('Libro Test', 'Autor Test', NULL)");
    $stmt->execute();
    $libroId = $pdo->lastInsertId();
    logTest("CRUD Libros: Inserción correcta.");

    $stmt = $pdo->prepare("UPDATE libros SET leido = 1 WHERE id = ?");
    $stmt->execute([$libroId]);
    logTest("CRUD Libros: Cambio de estado correcto.");

    $stmt = $pdo->prepare("DELETE FROM libros WHERE id = ?");
    $stmt->execute([$libroId]);
    logTest("CRUD Libros: Eliminación correcta.");
} catch (Exception $e) {
    logTest("CRUD Libros: Fallo - " . $e->getMessage(), false);
}

echo "\n--- FIN DE LA BATERÍA DE TESTS ---\n";
