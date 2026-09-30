<?php
/**
 * ejecutar-migraciones-secretas.php
 * 
 * Script seguro para restaurar categorías, verificar la integridad de la base de datos
 * y sincronizar campos y menú en producción.
 */
require_once __DIR__ . '/config.php';

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

// 2. Ejecutar las alteraciones específicas de la funcionalidad (Autor/a de fotos)
addColumnIfNotExists($pdo, 'news_events', 'image_author', "VARCHAR(255) NULL AFTER `image_caption`", $results);
addColumnIfNotExists($pdo, 'news_images', 'author', "VARCHAR(255) NULL AFTER `caption`", $results);
addColumnIfNotExists($pdo, 'page_images', 'author', "VARCHAR(255) NULL AFTER `caption`", $results);

// Asegurar columnas de iconos y categorías múltiples
try {
    $pdo->query("SELECT icon FROM categories LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE categories ADD COLUMN icon VARCHAR(255) NULL DEFAULT '📁' AFTER parent_id");
}
try {
    $pdo->query("SELECT icon FROM pages LIMIT 1");
} catch (Exception $e) {
    $pdo->exec("ALTER TABLE pages ADD COLUMN icon VARCHAR(255) NULL DEFAULT 'far fa-file-alt' AFTER original_file");
}

// 3. Restaurar Categorías si faltan
$countCats = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$forceRestore = isset($_GET['force_restore']) && $_GET['force_restore'] == '1';

if ($countCats <= 5 || $forceRestore) {
    $results[] = [
        'type' => 'warning',
        'msg' => "Se detectaron solo {$countCats} categorías en la base de datos. Iniciando restauración de categorías..."
    ];

    $file075 = __DIR__ . '/migrations/075_restore_production_data.sql';
    if (file_exists($file075)) {
        $sql075 = file_get_contents($file075);
        preg_match_all('/REPLACE INTO `categories`[^;]+;/u', $sql075, $matchesCats);
        
        if (!empty($matchesCats[0])) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $restoredCount = 0;
            foreach ($matchesCats[0] as $stmt) {
                try {
                    $pdo->exec($stmt);
                    $restoredCount++;
                } catch (Exception $e) {
                    $results[] = [
                        'type' => 'danger',
                        'msg' => "Error al restaurar categoría: " . htmlspecialchars($e->getMessage())
                    ];
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $results[] = [
                'type' => 'success',
                'msg' => "Se restauraron correctamente <strong>{$restoredCount}</strong> categorías del archivo de datos."
            ];
        }
    }

    // Configurar categorías de Turismo
    try {
        $pdo->exec("
            INSERT INTO categories (name, slug, is_visible, sort_order)
            SELECT 'Turismo', 'turismo', 1, 10
            WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Turismo');

            INSERT INTO categories (name, slug, parent_id, is_visible, sort_order)
            SELECT 'Bares y Restaurantes', 'bares-y-restaurantes', id, 1, 1
            FROM categories
            WHERE name = 'Turismo'
              AND NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Bares y Restaurantes');

            UPDATE categories 
            SET parent_id = (SELECT id FROM (SELECT id FROM categories WHERE name = 'Turismo') as t)
            WHERE name = 'Bares y Restaurantes';

            INSERT INTO categories (name, slug, parent_id, is_visible, sort_order)
            SELECT 'Alojamientos', 'alojamientos', id, 1, 2
            FROM categories
            WHERE name = 'Turismo'
              AND NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Alojamientos');

            UPDATE categories 
            SET parent_id = (SELECT id FROM (SELECT id FROM categories WHERE name = 'Turismo') as t)
            WHERE name = 'Alojamientos';
        ");
        $results[] = [
            'type' => 'success',
            'msg' => "Categorías de <strong>Turismo</strong> (Bares, Restaurantes, Alojamientos) verificadas y configuradas."
        ];
    } catch (Exception $e) {
        $results[] = [
            'type' => 'warning',
            'msg' => "Aviso en categorías de Turismo: " . htmlspecialchars($e->getMessage())
        ];
    }

    // Asegurar que las categorías principales sean visibles
    try {
        $pdo->exec("UPDATE categories SET is_visible = 1 WHERE parent_id IS NULL");
    } catch (Exception $e) {}
} else {
    $results[] = [
        'type' => 'success',
        'msg' => "La tabla <code>categories</code> tiene <strong>{$countCats}</strong> categorías activas."
    ];
}

// 4. Verificar tabla de Páginas
$countPages = (int)$pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
if ($countPages < 10) {
    $results[] = [
        'type' => 'warning',
        'msg' => "Se detectaron solo {$countPages} páginas en la base de datos. Restaurando páginas..."
    ];
    $file075 = __DIR__ . '/migrations/075_restore_production_data.sql';
    if (file_exists($file075)) {
        $sql075 = file_get_contents($file075);
        preg_match_all('/REPLACE INTO `pages`[^;]+;/u', $sql075, $matchesPages);
        if (!empty($matchesPages[0])) {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $restoredPages = 0;
            foreach ($matchesPages[0] as $stmt) {
                try {
                    $pdo->exec($stmt);
                    $restoredPages++;
                } catch (Exception $e) {}
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $results[] = [
                'type' => 'success',
                'msg' => "Se restauraron correctamente <strong>{$restoredPages}</strong> páginas históricas."
            ];
        }
    }
} else {
    $results[] = [
        'type' => 'info',
        'msg' => "La tabla <code>pages</code> tiene <strong>{$countPages}</strong> páginas activas."
    ];
}

// 5. Marcar migraciones como completadas para evitar re-ejecuciones de scripts SQL viejos
$migrationsDir = __DIR__ . '/migrations';
$files = is_dir($migrationsDir) ? glob($migrationsDir . '/*.sql') : [];
foreach ($files as $file) {
    $migrationName = basename($file);
    try {
        $stmt = $pdo->prepare("REPLACE INTO `_migrations` (migration, status, error_message) VALUES (?, 'success', NULL)");
        $stmt->execute([$migrationName]);
    } catch (Exception $e) {}
}
$results[] = [
    'type' => 'info',
    'msg' => "Registro de migraciones sincronizado correctamente (todos los scripts antiguos marcados para no volver a ejecutarse)."
];

// 6. Consultar los elementos del menú principal para mostrarlos en el informe
$rootCategories = $pdo->query("SELECT id, name, sort_order, is_visible FROM categories WHERE parent_id IS NULL AND is_visible = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reparación de Menú y Migraciones - Moratalla Murcia</title>
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
            max-width: 720px;
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
        
        .menu-preview {
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .menu-preview h3 {
            font-size: 1rem;
            color: #60a5fa;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .menu-items-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .menu-tag {
            background: rgba(37, 99, 235, 0.2);
            border: 1px solid rgba(96, 165, 250, 0.3);
            color: #93c5fd;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
        }

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
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; transform: translateY(-1px); }
        .btn-secondary { background: #334155; color: #e2e8f0; }
        .btn-secondary:hover { background: #475569; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-warning:hover { background: #b45309; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon-circle">
                <i class="fas fa-sitemap"></i>
            </div>
            <div>
                <h1>Reparación de Menú y Base de Datos</h1>
                <div class="subtitle">moratalla-murcia.com &bull; Sincronización Completa</div>
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

        <div class="menu-preview">
            <h3><i class="fas fa-bars"></i> Elementos del Menú Principal Detectados (<?= count($rootCategories) ?>)</h3>
            <div class="menu-items-list">
                <?php if (empty($rootCategories)): ?>
                    <span style="color: var(--muted); font-size: 0.9rem;">No se detectaron categorías principales.</span>
                <?php else: ?>
                    <?php foreach ($rootCategories as $rc): ?>
                        <span class="menu-tag"><?= htmlspecialchars($rc['name']) ?> (id: <?= $rc['id'] ?>)</span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-globe"></i> Ver Web Principal
            </a>
            <a href="admin/categories.php" class="btn btn-secondary">
                <i class="fas fa-folder"></i> Panel de Categorías
            </a>
            <a href="ejecutar-migraciones-secretas.php?force_restore=1" class="btn btn-warning" onclick="return confirm('¿Forzar re-sincronización completa de categorías?');">
                <i class="fas fa-sync"></i> Forzar Re-sincronización
            </a>
        </div>
    </div>
</body>
</html>
