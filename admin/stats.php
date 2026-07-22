<?php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();

// Crear tabla si no existe (precaución adicional)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `visit_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45) NOT NULL,
        `user_agent` TEXT,
        `browser` VARCHAR(100),
        `os` VARCHAR(100),
        `page_url` VARCHAR(255),
        `referrer` VARCHAR(255),
        `country` VARCHAR(100),
        `city` VARCHAR(100),
        `is_new_session` TINYINT(1) DEFAULT 0,
        `visit_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Auto-añadir columnas si no existen (fallback)
    try {
        $pdo->exec("ALTER TABLE visit_logs ADD COLUMN country VARCHAR(100) NULL AFTER referrer");
        $pdo->exec("ALTER TABLE visit_logs ADD COLUMN city VARCHAR(100) NULL AFTER country");
    } catch(Exception $e) {}
} catch (Exception $e) {}

$totalVisits = 0;
$uniqueVisits = 0;
try {
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM visit_logs");
    $totalVisits = $stmtTotal ? (int)$stmtTotal->fetchColumn() : 0;

    $stmtUnique = $pdo->query("SELECT COUNT(*) FROM visit_logs WHERE is_new_session = 1");
    $uniqueVisits = $stmtUnique ? (int)$stmtUnique->fetchColumn() : 0;
    
    // Añadir el histórico de global_visits
    $stmtOld = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'global_visits'");
    $oldVisits = $stmtOld ? (int)$stmtOld->fetchColumn() : 0;
    
    $totalVisits += $oldVisits;
    // Las visitas únicas históricas no las tenemos exactas, pero podemos sumar el global para que no empiece de 0
    $uniqueVisits += $oldVisits;
} catch (Exception $e) {}

// Visitas por día (Últimos 15 días)
$daysData = [];
try {
    $stmtDays = $pdo->query("SELECT DATE(visit_time) as date, COUNT(*) as count FROM visit_logs WHERE visit_time >= DATE_SUB(CURDATE(), INTERVAL 15 DAY) GROUP BY DATE(visit_time) ORDER BY date ASC");
    $daysData = $stmtDays ? $stmtDays->fetchAll() : [];
} catch (Exception $e) {}
$daysLabels = [];
$daysCounts = [];
foreach ($daysData as $d) {
    $daysLabels[] = date('d/m', strtotime($d['date']));
    $daysCounts[] = $d['count'];
}

// Navegadores
$browserData = [];
try {
    $stmtBrowser = $pdo->query("SELECT browser, COUNT(*) as count FROM visit_logs GROUP BY browser ORDER BY count DESC");
    $browserData = $stmtBrowser ? $stmtBrowser->fetchAll() : [];
} catch (Exception $e) {}
$browserLabels = [];
$browserCounts = [];
foreach ($browserData as $b) {
    $browserLabels[] = $b['browser'];
    $browserCounts[] = $b['count'];
}

// Sistemas Operativos
$osData = [];
try {
    $stmtOs = $pdo->query("SELECT os, COUNT(*) as count FROM visit_logs GROUP BY os ORDER BY count DESC");
    $osData = $stmtOs ? $stmtOs->fetchAll() : [];
} catch (Exception $e) {}
$osLabels = [];
$osCounts = [];
foreach ($osData as $o) {
    $osLabels[] = $o['os'];
    $osCounts[] = $o['count'];
}

// Top Páginas
$topPages = [];
try {
    $stmtPages = $pdo->query("SELECT page_url, COUNT(*) as count FROM visit_logs GROUP BY page_url ORDER BY count DESC LIMIT 10");
    $topPagesRaw = $stmtPages ? $stmtPages->fetchAll() : [];
    
    foreach ($topPagesRaw as $p) {
        $url = $p['page_url'];
        $title = 'Desconocido';
        
        if (strpos($url, 'page.php?id=') !== false) {
            preg_match('/id=(\d+)/', $url, $matches);
            if (isset($matches[1])) {
                $stmtTitle = $pdo->prepare("SELECT title FROM pages WHERE id = ?");
                $stmtTitle->execute([$matches[1]]);
                $pageTitle = $stmtTitle->fetchColumn();
                $title = $pageTitle ? $pageTitle : 'Página eliminada';
            }
        } elseif (strpos($url, 'category.php?id=') !== false) {
            preg_match('/id=(\d+)/', $url, $matches);
            if (isset($matches[1])) {
                $stmtTitle = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
                $stmtTitle->execute([$matches[1]]);
                $catTitle = $stmtTitle->fetchColumn();
                $title = $catTitle ? 'Categoría: ' . $catTitle : 'Categoría eliminada';
            }
        } elseif (strpos($url, 'index.php') !== false || $url === '/' || $url === '/moratalla-murcia.com/') {
            $title = 'Inicio';
        }
        
        $topPages[] = [
            'page_url' => $url,
            'count' => $p['count'],
            'title' => $title
        ];
    }
} catch (Exception $e) {}

// Top Referidos
$topRefs = [];
try {
    $stmtRef = $pdo->query("SELECT referrer, COUNT(*) as count FROM visit_logs WHERE referrer != '' AND referrer IS NOT NULL GROUP BY referrer ORDER BY count DESC LIMIT 10");
    $topRefs = $stmtRef ? $stmtRef->fetchAll() : [];
} catch (Exception $e) {}

// Top Países
$topCountries = [];
try {
    $stmtCountry = $pdo->query("SELECT country, COUNT(*) as count FROM visit_logs WHERE country IS NOT NULL AND country != 'Desconocido' GROUP BY country ORDER BY count DESC LIMIT 10");
    $topCountries = $stmtCountry ? $stmtCountry->fetchAll() : [];
} catch (Exception $e) {}

$countryLabels = [];
$countryCounts = [];
foreach ($topCountries as $c) {
    $countryLabels[] = $c['country'];
    $countryCounts[] = $c['count'];
}

// Top Ciudades
$topCities = [];
try {
    $stmtCity = $pdo->query("SELECT city, country, COUNT(*) as count FROM visit_logs WHERE city IS NOT NULL AND city != 'Desconocido' GROUP BY city, country ORDER BY count DESC LIMIT 10");
    $topCities = $stmtCity ? $stmtCity->fetchAll() : [];
} catch (Exception $e) {}

adminHeader("Estadísticas de Visitas");
?>

<div class="header-admin" style="margin-bottom: 2rem;">
    <h1 style="font-size: 2rem; color: var(--primary);">Estadísticas de Visitas</h1>
</div>

<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; margin-bottom: 2rem;">
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
        <h3>Páginas Vistas (Total)</h3>
        <p style="font-size: 2.5rem; font-weight: 800; color: #10b981;"><?= number_format($totalVisits, 0, ',', '.') ?></p>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center;">
        <h3>Visitas Únicas (Aprox)</h3>
        <p style="font-size: 2.5rem; font-weight: 800; color: #3b82f6;"><?= number_format($uniqueVisits, 0, ',', '.') ?></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem;">
    <h3>Evolución de Páginas Vistas (Últimos 15 días)</h3>
    <canvas id="lineChart" style="max-height: 300px;"></canvas>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="text-align: center;">Navegadores</h3>
        <div style="max-width: 250px; margin: 0 auto;">
            <canvas id="browserChart"></canvas>
        </div>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="text-align: center;">Sistemas Operativos</h3>
        <div style="max-width: 250px; margin: 0 auto;">
            <canvas id="osChart"></canvas>
        </div>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="text-align: center;">Países</h3>
        <div style="max-width: 250px; margin: 0 auto;">
            <canvas id="countryChart"></canvas>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3>Páginas Más Visitadas</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Página</th>
                    <th style="width: 60px; text-align: right;">Vistas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($topPages as $p): ?>
                <tr>
                    <td style="word-break: break-all; font-size: 0.85rem;">
                        <strong style="display: block; color: var(--primary);"><?= htmlspecialchars($p['title']) ?></strong>
                        <a href="<?= htmlspecialchars($p['page_url']) ?>" target="_blank" style="font-size: 0.75rem; color: #6b7280;"><?= htmlspecialchars(urldecode($p['page_url'])) ?></a>
                    </td>
                    <td style="text-align: right; font-weight: bold; vertical-align: middle;"><?= $p['count'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($topPages)): ?>
                <tr><td colspan="2" style="text-align: center; color: #9ca3af;">No hay datos suficientes</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3>Fuentes de Tráfico</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Origen</th>
                    <th style="width: 60px; text-align: right;">Vistas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($topRefs as $r): ?>
                <tr>
                    <td style="word-break: break-all; font-size: 0.85rem;">
                        <a href="<?= htmlspecialchars($r['referrer']) ?>" target="_blank"><?= htmlspecialchars(urldecode($r['referrer'])) ?></a>
                    </td>
                    <td style="text-align: right; font-weight: bold;"><?= $r['count'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($topRefs)): ?>
                <tr><td colspan="2" style="text-align: center; color: #9ca3af;">No hay datos suficientes</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3>Top Ciudades</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Ciudad</th>
                    <th style="width: 60px; text-align: right;">Vistas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($topCities as $c): ?>
                <tr>
                    <td style="word-break: break-all; font-size: 0.85rem;">
                        <strong style="display: block; color: var(--primary);"><?= htmlspecialchars($c['city']) ?></strong>
                        <span style="font-size: 0.75rem; color: #6b7280;"><?= htmlspecialchars($c['country']) ?></span>
                    </td>
                    <td style="text-align: right; font-weight: bold; vertical-align: middle;"><?= $c['count'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($topCities)): ?>
                <tr><td colspan="2" style="text-align: center; color: #9ca3af;">No hay datos de ciudades</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Colores predefinidos
const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1'];

const daysLabels = <?= json_encode($daysLabels) ?>;
const daysCounts = <?= json_encode($daysCounts) ?>;
const browserLabels = <?= json_encode($browserLabels) ?>;
const browserCounts = <?= json_encode($browserCounts) ?>;
const osLabels = <?= json_encode($osLabels) ?>;
const osCounts = <?= json_encode($osCounts) ?>;
const countryLabels = <?= json_encode($countryLabels) ?>;
const countryCounts = <?= json_encode($countryCounts) ?>;

// Función auxiliar para mostrar mensaje si no hay datos
function handleEmpty(chartId, dataArray) {
    if (dataArray.length === 0) {
        const canvas = document.getElementById(chartId);
        const parent = canvas.parentElement;
        canvas.style.display = 'none';
        const msg = document.createElement('div');
        msg.style.padding = '2rem 0';
        msg.style.textAlign = 'center';
        msg.style.color = '#9ca3af';
        msg.innerText = 'No hay datos suficientes para mostrar la gráfica.';
        parent.appendChild(msg);
        return true;
    }
    return false;
}

// Gráfico de Líneas (Evolución)
if (!handleEmpty('lineChart', daysCounts)) {
    const ctxLine = document.getElementById('lineChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: daysLabels,
            datasets: [{
                label: 'Páginas Vistas',
                data: daysCounts,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });
}

// Gráfico Navegadores
if (!handleEmpty('browserChart', browserCounts)) {
    const ctxBrowser = document.getElementById('browserChart').getContext('2d');
    new Chart(ctxBrowser, {
        type: 'doughnut',
        data: {
            labels: browserLabels,
            datasets: [{
                data: browserCounts,
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            cutout: '65%'
        }
    });
}

// Gráfico OS
if (!handleEmpty('osChart', osCounts)) {
    const ctxOs = document.getElementById('osChart').getContext('2d');
    new Chart(ctxOs, {
        type: 'doughnut',
        data: {
            labels: osLabels,
            datasets: [{
                data: osCounts,
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            cutout: '65%'
        }
    });
}

// Gráfico Países
if (!handleEmpty('countryChart', countryCounts)) {
    const ctxCountry = document.getElementById('countryChart').getContext('2d');
    new Chart(ctxCountry, {
        type: 'doughnut',
        data: {
            labels: countryLabels,
            datasets: [{
                data: countryCounts,
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            cutout: '65%'
        }
    });
}
</script>

<?php adminFooter(); ?>
