<?php
require_once 'inc/header.php';
checkLogin();

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
        `is_new_session` TINYINT(1) DEFAULT 0,
        `visit_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (Exception $e) {}

// Obtener totales
$stmtTotal = $pdo->query("SELECT COUNT(*) FROM visit_logs");
$totalVisits = $stmtTotal ? $stmtTotal->fetchColumn() : 0;

$stmtUnique = $pdo->query("SELECT COUNT(*) FROM visit_logs WHERE is_new_session = 1");
$uniqueVisits = $stmtUnique ? $stmtUnique->fetchColumn() : 0;

// Visitas por día (Últimos 15 días)
$stmtDays = $pdo->query("SELECT DATE(visit_time) as date, COUNT(*) as count FROM visit_logs WHERE visit_time >= DATE_SUB(CURDATE(), INTERVAL 15 DAY) GROUP BY DATE(visit_time) ORDER BY date ASC");
$daysData = $stmtDays ? $stmtDays->fetchAll() : [];
$daysLabels = [];
$daysCounts = [];
foreach ($daysData as $d) {
    $daysLabels[] = date('d/m', strtotime($d['date']));
    $daysCounts[] = $d['count'];
}

// Navegadores
$stmtBrowser = $pdo->query("SELECT browser, COUNT(*) as count FROM visit_logs GROUP BY browser ORDER BY count DESC");
$browserData = $stmtBrowser ? $stmtBrowser->fetchAll() : [];
$browserLabels = [];
$browserCounts = [];
foreach ($browserData as $b) {
    $browserLabels[] = $b['browser'];
    $browserCounts[] = $b['count'];
}

// Sistemas Operativos
$stmtOs = $pdo->query("SELECT os, COUNT(*) as count FROM visit_logs GROUP BY os ORDER BY count DESC");
$osData = $stmtOs ? $stmtOs->fetchAll() : [];
$osLabels = [];
$osCounts = [];
foreach ($osData as $o) {
    $osLabels[] = $o['os'];
    $osCounts[] = $o['count'];
}

// Top Páginas
$stmtPages = $pdo->query("SELECT page_url, COUNT(*) as count FROM visit_logs GROUP BY page_url ORDER BY count DESC LIMIT 10");
$topPages = $stmtPages ? $stmtPages->fetchAll() : [];

// Top Referidos
$stmtRef = $pdo->query("SELECT referrer, COUNT(*) as count FROM visit_logs WHERE referrer != '' AND referrer IS NOT NULL GROUP BY referrer ORDER BY count DESC LIMIT 10");
$topRefs = $stmtRef ? $stmtRef->fetchAll() : [];
?>

<div class="header-admin">
    <h1>Estadísticas de Visitas</h1>
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

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="text-align: center;">Navegadores</h3>
        <div style="max-width: 300px; margin: 0 auto;">
            <canvas id="browserChart"></canvas>
        </div>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="text-align: center;">Sistemas Operativos</h3>
        <div style="max-width: 300px; margin: 0 auto;">
            <canvas id="osChart"></canvas>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3>Páginas Más Visitadas</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>URL</th>
                    <th style="width: 80px; text-align: right;">Vistas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($topPages as $p): ?>
                <tr>
                    <td style="word-break: break-all; font-size: 0.9rem;">
                        <a href="<?= htmlspecialchars($p['page_url']) ?>" target="_blank"><?= htmlspecialchars(urldecode($p['page_url'])) ?></a>
                    </td>
                    <td style="text-align: right; font-weight: bold;"><?= $p['count'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($topPages)): ?>
                <tr><td colspan="2" style="text-align: center; color: #9ca3af;">No hay datos suficientes</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h3>Fuentes de Tráfico (Referidos)</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Origen</th>
                    <th style="width: 80px; text-align: right;">Vistas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($topRefs as $r): ?>
                <tr>
                    <td style="word-break: break-all; font-size: 0.9rem;">
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
</div>

<script>
// Colores predefinidos
const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#6366f1'];

// Gráfico de Líneas (Evolución)
const ctxLine = document.getElementById('lineChart').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: <?= json_encode($daysLabels) ?>,
        datasets: [{
            label: 'Páginas Vistas',
            data: <?= json_encode($daysCounts) ?>,
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

// Gráfico Navegadores
const ctxBrowser = document.getElementById('browserChart').getContext('2d');
new Chart(ctxBrowser, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($browserLabels) ?>,
        datasets: [{
            data: <?= json_encode($browserCounts) ?>,
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

// Gráfico OS
const ctxOs = document.getElementById('osChart').getContext('2d');
new Chart(ctxOs, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($osLabels) ?>,
        datasets: [{
            data: <?= json_encode($osCounts) ?>,
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
</script>

<?php require_once 'inc/footer.php'; ?>
