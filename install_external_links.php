<?php
// install_external_links.php
// Ejecutar UNA SOLA VEZ desde el navegador para crear la tabla external_links
// Eliminar este archivo después de usarlo.
require_once 'config.php';

$pdo = getDB();
$results = [];

$sql = "CREATE TABLE IF NOT EXISTS `external_links` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `url` VARCHAR(2048) NOT NULL,
    `is_visible` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $pdo->exec($sql);
    $results[] = ['ok' => true, 'msg' => 'Tabla <strong>external_links</strong> creada (o ya existía).'];
} catch (PDOException $e) {
    $results[] = ['ok' => false, 'msg' => 'Error al crear la tabla: ' . $e->getMessage()];
}

// Verificar que la tabla existe
try {
    $count = $pdo->query("SELECT COUNT(*) FROM external_links")->fetchColumn();
    $results[] = ['ok' => true, 'msg' => "Tabla verificada. Registros actuales: <strong>{$count}</strong>."];
} catch (PDOException $e) {
    $results[] = ['ok' => false, 'msg' => 'No se pudo verificar la tabla: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instalación - Accesos Externos</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f5f5f5; display:flex; justify-content:center; align-items:center; min-height:100vh; margin:0; }
        .box { background:white; padding:2.5rem 3rem; border-radius:16px; box-shadow:0 8px 30px rgba(0,0,0,0.1); max-width:550px; width:100%; }
        h1 { color:#1b4332; margin-top:0; font-size:1.6rem; }
        .result { display:flex; gap:0.8rem; align-items:flex-start; padding:0.9rem 1rem; border-radius:10px; margin-bottom:0.8rem; font-size:0.95rem; }
        .result.ok  { background:#e8f5e9; color:#2e7d32; }
        .result.err { background:#fce4ec; color:#c62828; }
        .result i { margin-top:2px; flex-shrink:0; }
        .actions { margin-top:1.5rem; display:flex; gap:1rem; flex-wrap:wrap; }
        .btn { padding:0.8rem 1.5rem; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.9rem; display:inline-flex; align-items:center; gap:6px; }
        .btn-primary { background:#1b4332; color:white; }
        .btn-secondary { background:#eee; color:#333; }
        .warning { background:#fff8e1; border-left:4px solid #f57f17; padding:0.8rem 1rem; border-radius:8px; font-size:0.88rem; color:#6d4c00; margin-top:1.5rem; }
    </style>
</head>
<body>
<div class="box">
    <h1>🔧 Instalación: Accesos Externos</h1>
    <?php foreach ($results as $r): ?>
        <div class="result <?php echo $r['ok'] ? 'ok' : 'err'; ?>">
            <i>📌</i>
            <span><?php echo $r['msg']; ?></span>
        </div>
    <?php endforeach; ?>

    <div class="actions">
        <a href="admin/external_links.php" class="btn btn-primary">🔗 Ir a Accesos Externos</a>
        <a href="index.php" class="btn btn-secondary">← Inicio</a>
    </div>

    <div class="warning">
        ⚠️ <strong>Recuerda eliminar este archivo</strong> (<code>install_external_links.php</code>) una vez completada la instalación.
    </div>
</div>
</body>
</html>
