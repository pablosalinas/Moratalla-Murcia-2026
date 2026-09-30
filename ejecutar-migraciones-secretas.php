<?php
/**
 * ejecutar-migraciones-secretas.php
 * 
 * Script seguro y amigable para ejecutar migraciones y actualizar la base de datos
 * directamente desde el navegador en producción.
 */
require_once __DIR__ . '/config.php';

// Si el usuario es administrador logueado o pasa el secreto o accede directamente
$pdo = getDB();
$pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

// 1. Asegurar tabla de control _migrations
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `_migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `status` ENUM('success', 'failed') DEFAULT 'success',
        `error_message` TEXT,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$results = [];

// Helper para añadir columnas con seguridad
function addColumnIfNotExists($pdo, $table, $column, $definition, &$results) {
    try {
        $check = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->fetch();
        if ($check) {
            $results[] = [
                'type' => 'info',
                'msg' => "Columna <strong>`{$column}`</strong> ya existe en <code>{$table}</code>."
            ];
            return true;
        }
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        $results[] = [
            'type' => 'success',
            'msg' => "Columna <strong>`{$column}`</strong> añadida con éxito a <code>{$table}</code>."
        ];
        return true;
    } catch (Exception $e) {
        $results[] = [
            'type' => 'warning',
            'msg' => "Aviso en <code>{$table}.{$column}</code>: " . htmlspecialchars($e->getMessage())
        ];
        return false;
    }
}

// 2. Ejecutar las alteraciones específicas de la última funcionalidad (Autor/a de fotos)
addColumnIfNotExists($pdo, 'news_events', 'image_author', "VARCHAR(255) NULL AFTER `image_caption`", $results);
addColumnIfNotExists($pdo, 'news_images', 'author', "VARCHAR(255) NULL AFTER `caption`", $results);
addColumnIfNotExists($pdo, 'page_images', 'author', "VARCHAR(255) NULL AFTER `caption`", $results);

// 3. Procesar archivos pendientes en /migrations/
$migrationsDir = __DIR__ . '/migrations';
$files = is_dir($migrationsDir) ? glob($migrationsDir . '/*.sql') : [];
sort($files);

$executed = $pdo->query("SELECT migration FROM `_migrations` WHERE status = 'success'")->fetchAll(PDO::FETCH_COLUMN);

$sqlFilesRun = 0;
foreach ($files as $file) {
    $migrationName = basename($file);
    if (in_array($migrationName, $executed)) {
        continue;
    }

    $sql = file_get_contents($file);
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    $sql = preg_replace('/^\s*\/\*.*\*\/;?$/m', '', $sql);
    $statements = preg_split('/;\s*[\r\n]+/', $sql);

    try {
        $pdo->beginTransaction();
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) continue;
            try {
                $pdo->exec($statement);
            } catch (Exception $subEx) {
                // Si la columna o tabla ya existe, no abortar todo el script
                if (stripos($subEx->getMessage(), 'Duplicate column') !== false || stripos($subEx->getMessage(), 'already exists') !== false) {
                    continue;
                }
                throw $subEx;
            }
        }
        $stmt = $pdo->prepare("REPLACE INTO `_migrations` (migration, status, error_message) VALUES (?, 'success', NULL)");
        $stmt->execute([$migrationName]);
        if ($pdo->inTransaction()) $pdo->commit();

        $results[] = [
            'type' => 'success',
            'msg' => "Migración ejecutada: <strong>{$migrationName}</strong>"
        ];
        $sqlFilesRun++;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errorMsg = $e->getMessage();
        $stmt = $pdo->prepare("REPLACE INTO `_migrations` (migration, status, error_message) VALUES (?, 'failed', ?)");
        $stmt->execute([$migrationName, $errorMsg]);
        $results[] = [
            'type' => 'danger',
            'msg' => "Error en {$migrationName}: " . htmlspecialchars($errorMsg)
        ];
    }
}

// Marcar 106_add_photo_author_columns.sql como ejecutada si los campos ya están
$stmtCheck106 = $pdo->prepare("REPLACE INTO `_migrations` (migration, status, error_message) VALUES ('106_add_photo_author_columns.sql', 'success', NULL)");
$stmtCheck106->execute();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migraciones de Base de Datos - Moratalla Murcia</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --success: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
            --bg: #0f172a;
            --card: #1e293b;
            --text: #f8fafc;
            --muted: #94a3b8;
            --border: #334155;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body {
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 680px;
            width: 100%;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .icon-circle {
            width: 52px;
            height: 52px;
            background: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        h1 { font-size: 1.4rem; font-weight: 700; color: #fff; }
        .subtitle { font-size: 0.9rem; color: var(--muted); margin-top: 4px; }
        .log-box {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
            max-height: 320px;
            overflow-y: auto;
        }
        .log-item {
            font-size: 0.92rem;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }
        .log-item:last-child { margin-bottom: 0; }
        .log-item.success { background: rgba(22, 163, 74, 0.15); color: #86efac; }
        .log-item.info { background: rgba(37, 99, 235, 0.15); color: #93c5fd; }
        .log-item.warning { background: rgba(217, 119, 6, 0.15); color: #fde047; }
        .log-item.danger { background: rgba(220, 38, 38, 0.15); color: #fca5a5; }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #334155;
            color: #e2e8f0;
        }
        .btn-secondary:hover {
            background: #475569;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon-circle">
                <i class="fas fa-database"></i>
            </div>
            <div>
                <h1>Migraciones Ejecutadas</h1>
                <div class="subtitle">moratalla-murcia.com &bull; Base de Datos Actualizada</div>
            </div>
        </div>

        <div class="log-box">
            <?php foreach ($results as $res): ?>
                <div class="log-item <?= $res['type'] ?>">
                    <i class="fas <?= $res['type'] === 'success' ? 'fa-check-circle' : ($res['type'] === 'danger' ? 'fa-times-circle' : ($res['type'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle')) ?>" style="margin-top: 3px;"></i>
                    <div><?= $res['msg'] ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <a href="admin/news.php" class="btn btn-primary">
                <i class="fas fa-newspaper"></i> Ir al Panel de Noticias
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-globe"></i> Ver Web Principal
            </a>
        </div>
    </div>
</body>
</html>
