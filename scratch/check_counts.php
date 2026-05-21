<?php
require __DIR__ . '/config.php';
$res = getDB()->query("SELECT COUNT(*) FROM categories")->fetchColumn();
echo "Categorias: $res\n";
$res2 = getDB()->query("SELECT COUNT(*) FROM pages")->fetchColumn();
echo "Paginas: $res2\n";
