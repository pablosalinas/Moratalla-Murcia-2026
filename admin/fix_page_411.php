<?php
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/../config.php';
adminHeader('Corrección de Página 411');
$pdo = getDB();

$content = '<div class="modern-page-content" style="max-width: 900px; margin: 0 auto; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); font-family: \'Inter\', sans-serif; line-height: 1.8; color: #444;">' . "\n" . 
           '<h1 style="color: var(--primary-dark); text-align: center; border-bottom: 3px solid var(--accent); padding-bottom: 1rem; margin-bottom: 2rem;">Asociación de Tamboristas (2008)</h1>' . "\n" .
           '<p style="text-align: center; font-size: 1.1rem; color: #555;">Crónica y galería de imágenes de la actividad de <strong>Asociación de Tamboristas</strong> en el periodo <strong>2008</strong>.</p>' . "\n" . 
           '</div>';

$stmt = $pdo->prepare('UPDATE pages SET content = ? WHERE id = 411');
$stmt->execute([$content]);

echo '<div class="card"><div class="alert alert-success">Página 411 (Tamboristas 2008) actualizada con éxito en la base de datos con sus textos corregidos y su nuevo diseño. Puedes borrar este archivo si quieres.</div><a href="pages.php" class="btn btn-primary">Volver a Páginas</a></div>';
adminFooter();
