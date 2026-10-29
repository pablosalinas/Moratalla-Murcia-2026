<?php
require 'db.php';
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
