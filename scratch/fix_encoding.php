<?php
require_once __DIR__ . '/../config.php';
$pdo = getDB();

$replacements = [
    "Clasificaci??n" => "Clasificación",
    "Categor??a" => "Categoría",
    "F??tbol" => "Fútbol",
    "F??ltol" => "Fútbol",
    "1??" => "1ª",
    "2??" => "2ª",
    "3??" => "3ª",
    "mayordom??a" => "mayordomía",
    "Mayordom??a" => "Mayordomía",
    "Pedan??as" => "Pedanías",
    "Mar??a" => "María",
    "Bartolom??" => "Bartolomé",
    "Mar??n" => "Marín",
    "Pe??as" => "Peñas",
    "Jes??s" => "Jesús",
    "Asociaci??n" => "Asociación",
    "Divisi??n" => "División"
];

$tables = ['categories' => ['name'], 'pages' => ['title', 'content']];

foreach ($replacements as $broken => $fixed) {
    echo "Replacing '$broken' with '$fixed'...\n";
    
    // Categories
    $stmt = $pdo->prepare("UPDATE categories SET name = REPLACE(name, ?, ?), slug = REPLACE(slug, ?, ?) WHERE name LIKE ? OR slug LIKE ?");
    $brokenSlug = strtolower(str_replace('??', '', $broken)); // El slugify original eliminó los ??.
    // En realidad, si el slug no tiene ??, solo actualizamos el name.
    $stmt = $pdo->prepare("UPDATE categories SET name = REPLACE(name, ?, ?) WHERE name LIKE ?");
    $stmt->execute([$broken, $fixed, "%$broken%"]);
    $count = $stmt->rowCount();
    if ($count > 0) echo "  - Categories name updated: $count\n";
    
    // Pages title
    $stmt = $pdo->prepare("UPDATE pages SET title = REPLACE(title, ?, ?) WHERE title LIKE ?");
    $stmt->execute([$broken, $fixed, "%$broken%"]);
    $count = $stmt->rowCount();
    if ($count > 0) echo "  - Pages title updated: $count\n";
    
    // Pages content
    $stmt = $pdo->prepare("UPDATE pages SET content = REPLACE(content, ?, ?) WHERE content LIKE ?");
    $stmt->execute([$broken, $fixed, "%$broken%"]);
    $count = $stmt->rowCount();
    if ($count > 0) echo "  - Pages content updated: $count\n";
}

echo "Done!\n";
