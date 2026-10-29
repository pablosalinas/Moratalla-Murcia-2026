<?php
$host = 'db5020045022.hosting-data.io';
$db = 'dbs15457960';
$user = 'dbu4210147'; // Usuario por defecto de XAMPP
$pass = 'Tortas01.56196798-Salinas_2026'; // Sin contraseña por defecto en XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
}
catch (\PDOException $e) {
    // Si falla la conexión, mostramos el error
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
