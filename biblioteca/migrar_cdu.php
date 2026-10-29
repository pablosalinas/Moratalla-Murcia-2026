<?php
require 'includes/auth.php';
adminOnly(); // Solo admin puede ejecutar migración
require 'db.php';

// Array con subcategorías comunes de la CDU (Clasificación Decimal Universal)
$subcategorias_cdu = [
    // 0 Generalidades
    ['00', 'Ciencia y Conocimiento. Organización. Informática'],
    ['01', 'Bibliografía. Catálogos'],
    ['02', 'Biblioteconomía. Lectura'],
    ['03', 'Enciclopedias y diccionarios'],
    ['05', 'Publicaciones seriadas y revistas'],
    ['06', 'Organizaciones. Asociaciones. Congresos. Museos'],
    ['07', 'Periodismo. Prensa'],
    ['08', 'Poligrafías. Obras colectivas'],
    ['09', 'Manuscritos. Libros raros o preciosos'],

    // 1 Filosofía. Psicología
    ['11', 'Metafísica'],
    ['13', 'Filosofía de la mente y del espíritu'],
    ['14', 'Sistemas filosóficos'],
    ['159.9', 'Psicología'],
    ['16', 'Lógica. Epistemología'],
    ['17', 'Moral. Ética'],

    // 2 Religión. Teología
    ['21', 'Religiones prehistóricas. Religiones de Asia. Religiones antiguas'],
    ['22', 'Religiones del Lejano Oriente'],
    ['23', 'Religiones de la India'],
    ['24', 'Budismo'],
    ['25', 'Religiones de la antigüedad'],
    ['26', 'Judaísmo'],
    ['27', 'Cristianismo'],
    ['28', 'Islam'],
    ['29', 'Nuevos movimientos religiosos'],

    // 3 Ciencias Sociales. Estadística. Política. Economía. Derecho.
    ['30', 'Teorías, metodología y métodos en ciencias sociales'],
    ['311', 'Estadística'],
    ['314', 'Demografía'],
    ['316', 'Sociología'],
    ['32', 'Política'],
    ['33', 'Economía'],
    ['34', 'Derecho. Jurisprudencia'],
    ['35', 'Administración Pública. Gobierno. Asuntos Militares'],
    ['37', 'Educación. Enseñanza. Tiempo libre'],
    ['39', 'Antropología Cultural. Etnología. Costumbres'],

    // 5 Matemáticas. Ciencias Naturales
    ['51', 'Matemáticas'],
    ['52', 'Astronomía. Astrofísica. Investigación espacial'],
    ['53', 'Física'],
    ['54', 'Química. Cristalografía. Mineralogía'],
    ['55', 'Ciencias de la Tierra. Geología. Meteorología'],
    ['56', 'Paleontología'],
    ['57', 'Biología'],
    ['58', 'Botánica'],
    ['59', 'Zoología'],

    // 6 Ciencias Aplicadas. Medicina. Tecnología
    ['61', 'Ciencias médicas. Salud. Higiene'],
    ['62', 'Ingeniería. Tecnología en general'],
    ['63', 'Agricultura. Ciencias o técnicas afines'],
    ['64', 'Economía doméstica'],
    ['65', 'Gestión y organización de la industria y el comercio'],
    ['66', 'Ingeniería química. Industrias afines'],
    ['67', 'Industrias diversas (artículos manufacturados)'],
    ['68', 'Industrias y oficios diversos'],
    ['69', 'Industria de la construcción'],

    // 7 Arte. Bellas Artes. Juegos. Deportes
    ['71', 'Urbanismo. Arquitectura paisajista'],
    ['72', 'Arquitectura'],
    ['73', 'Artes plásticas. Escultura'],
    ['74', 'Dibujo. Diseño. Artes aplicadas'],
    ['75', 'Pintura'],
    ['76', 'Artes gráficas. Grabado'],
    ['77', 'Fotografía'],
    ['78', 'Música'],
    ['79', 'Diversiones. Espectáculos. Juegos. Deportes'],
    
    // 8 Lenguaje. Lingüística. Literatura
    ['81', 'Lingüística y lenguas'],
    ['82', 'Literatura'],
    ['821', 'Literatura de lenguas individuales'],
    ['821.111', 'Literatura inglesa'],
    ['821.134.2', 'Literatura española'],
    ['821.134.3', 'Literatura portuguesa'],
    ['821.133.1', 'Literatura francesa'],
    ['821.131.1', 'Literatura italiana'],
    ['821.112.2', 'Literatura alemana'],
    ['821.14', 'Literatura griega'],
    ['821.124', 'Literatura latina'],

    // 9 Geografía. Biografías. Historia
    ['90', 'Prehistoria. Arqueología'],
    ['91', 'Geografía. Exploración de la Tierra'],
    ['929', 'Biografías y genealogía'],
    ['93', 'Historia general'],
    ['94', 'Historia general y antigua. Historia moderna'],
];

$insertados = 0;
$existentes = 0;

try {
    // Preparar consultas
    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM cdu WHERE codigo = ?');
    $insertStmt = $pdo->prepare('INSERT INTO cdu (codigo, nombre) VALUES (?, ?)');

    $pdo->beginTransaction();

    foreach ($subcategorias_cdu as $cdu) {
        $codigo = $cdu[0];
        $nombre = $cdu[1];

        // Verificar si existe
        $checkStmt->execute([$codigo]);
        $existe = $checkStmt->fetchColumn() > 0;

        if (!$existe) {
            $insertStmt->execute([$codigo, $nombre]);
            $insertados++;
        } else {
            $existentes++;
        }
    }

    $pdo->commit();
    $mensaje = "Migración completada con éxito. Se insertaron $insertados nuevas subcategorías. $existentes ya existían.";
    $clase_alerta = "bg-green-100 text-green-800 border-green-200";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $mensaje = "Error durante la migración: " . htmlspecialchars($e->getMessage());
    $clase_alerta = "bg-red-100 text-red-800 border-red-200";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php require 'includes/head.php'; ?>
    <title>Migración de CDU</title>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <?php require 'nav.php'; ?>

    <main class="flex-grow max-w-3xl w-full mx-auto py-12 px-4">
        <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Herramienta de Poblamiento CDU</h1>
            
            <div class="p-4 mb-6 rounded-lg border <?= $clase_alerta ?>">
                <?= $mensaje ?>
            </div>

            <div class="text-gray-600 mb-8 space-y-4">
                <p>Esta herramienta ha insertado automáticamente docenas de subcategorías estándar de la Clasificación Decimal Universal.</p>
                <p>Las subcategorías permiten especificar géneros con precisión (por ejemplo, <em>821.134.2 Literatura española</em> en lugar de simplemente <em>8 Literatura</em>).</p>
            </div>

            <div class="flex gap-4">
                <a href="cdu.php" class="px-6 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                    Ir a Gestión de CDU
                </a>
                <a href="index.php" class="px-6 py-2 bg-white text-gray-700 font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors shadow-sm">
                    Volver a Libros
                </a>
            </div>
        </div>
    </main>
</body>
</html>
