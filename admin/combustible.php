<?php
/**
 * admin/combustible.php
 * Apartado exclusivo en la Administración de moratalla-murcia.com para la
 * Gestión de Control de Combustible, Vehículos, Escaneo OCR, Informes PDF A4 Vertical
 * y Asignación de Permisos de Usuario.
 *
 * REGLA ESTRICTA DE PROTECCIÓN DE DATOS:
 * - Prohibido ejecutar DELETE, DROP, TRUNCATE o alterar datos existentes.
 * - Todas las bajas usan borrado lógico (activo = 0).
 */

require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
ensureUserAccessColumns($pdo);

// Asegurar tablas aditivas veh_vehiculos y veh_repostajes
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `veh_vehiculos` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `usuario_id` INT NOT NULL,
        `nombre` VARCHAR(120) NOT NULL,
        `matricula` VARCHAR(30) DEFAULT NULL,
        `marca` VARCHAR(80) DEFAULT NULL,
        `modelo` VARCHAR(80) DEFAULT NULL,
        `tipo_combustible` VARCHAR(40) DEFAULT 'Diésel',
        `km_iniciales` DECIMAL(12, 2) DEFAULT 0.00,
        `notas` TEXT DEFAULT NULL,
        `activo` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_veh_usuario_activo` (`usuario_id`, `activo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `veh_repostajes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `vehiculo_id` INT NOT NULL,
        `usuario_id` INT NOT NULL,
        `fecha` DATE NOT NULL,
        `litros` DECIMAL(10, 3) NOT NULL DEFAULT 0.000,
        `precio_litro` DECIMAL(10, 4) NOT NULL DEFAULT 0.0000,
        `importe_total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        `km_odometro` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
        `estacion_servicio` VARCHAR(150) DEFAULT NULL,
        `notas` VARCHAR(255) DEFAULT NULL,
        `foto_ticket_path` VARCHAR(255) DEFAULT NULL,
        `foto_odometro_path` VARCHAR(255) DEFAULT NULL,
        `activo` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_rep_vehiculo_fecha` (`vehiculo_id`, `activo`, `fecha`, `km_odometro`),
        INDEX `idx_rep_usuario_activo` (`usuario_id`, `activo`),
        CONSTRAINT `fk_rep_vehiculo` FOREIGN KEY (`vehiculo_id`) REFERENCES `veh_vehiculos`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `veh_estaciones` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `usuario_id` INT NOT NULL,
        `nombre` VARCHAR(150) NOT NULL,
        `marca` VARCHAR(80) DEFAULT NULL,
        `localidad` VARCHAR(120) DEFAULT NULL,
        `direccion` VARCHAR(200) DEFAULT NULL,
        `notas` VARCHAR(255) DEFAULT NULL,
        `activo` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_est_usuario_activo` (`usuario_id`, `activo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$currentUser = getCurrentAdminUser();
$userId  = (int)$currentUser['id'];
$isSuper = isSuperUser();
$msg     = "";
$tab     = isset($_GET['tab']) ? $_GET['tab'] : 'repostajes';

// Procesar formularios POST (Alta/Modificación/Baja Lógica de Repostajes, Vehículos y Permisos)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = isset($_POST['form_action']) ? $_POST['form_action'] : '';

    // 1. Guardar Repostaje (Alta o Modificación)
    if ($formAction === 'save_repostaje') {
        $id          = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        $vehiculoId  = (int)(isset($_POST['vehiculo_id']) ? $_POST['vehiculo_id'] : 0);
        $fecha       = trim(isset($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d'));
        $km          = (float)str_replace(',', '.', isset($_POST['km_odometro']) ? $_POST['km_odometro'] : 0);
        $litros      = (float)str_replace(',', '.', isset($_POST['litros']) ? $_POST['litros'] : 0);
        $precio      = (float)str_replace(',', '.', isset($_POST['precio_litro']) ? $_POST['precio_litro'] : 0);
        $importe     = (float)str_replace(',', '.', isset($_POST['importe_total']) ? $_POST['importe_total'] : 0);
        $estacion    = trim(isset($_POST['estacion_servicio']) ? $_POST['estacion_servicio'] : '');
        $notas       = trim(isset($_POST['notas']) ? $_POST['notas'] : '');

        if ($importe <= 0 && $litros > 0 && $precio > 0) {
            $importe = round($litros * $precio, 2);
        } elseif ($litros <= 0 && $importe > 0 && $precio > 0) {
            $litros = round($importe / $precio, 3);
        } elseif ($precio <= 0 && $importe > 0 && $litros > 0) {
            $precio = round($importe / $litros, 4);
        }

        if ($vehiculoId > 0 && $litros > 0 && $precio > 0) {
            $stmtOwner = $pdo->prepare("SELECT usuario_id FROM veh_vehiculos WHERE id = ?");
            $stmtOwner->execute(array($vehiculoId));
            $ownerId = (int)$stmtOwner->fetchColumn() ?: $userId;

            if ($id > 0) {
                if ($isSuper) {
                    $stmt = $pdo->prepare("UPDATE veh_repostajes SET vehiculo_id=?, usuario_id=?, fecha=?, km_odometro=?, litros=?, precio_litro=?, importe_total=?, estacion_servicio=?, notas=? WHERE id=? AND activo=1");
                    $stmt->execute(array($vehiculoId, $ownerId, $fecha, $km, $litros, $precio, $importe, $estacion, $notas, $id));
                } else {
                    $stmt = $pdo->prepare("UPDATE veh_repostajes SET vehiculo_id=?, fecha=?, km_odometro=?, litros=?, precio_litro=?, importe_total=?, estacion_servicio=?, notas=? WHERE id=? AND usuario_id=? AND activo=1");
                    $stmt->execute(array($vehiculoId, $fecha, $km, $litros, $precio, $importe, $estacion, $notas, $id, $userId));
                }
                $msg = "Repostaje actualizado correctamente.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO veh_repostajes (vehiculo_id, usuario_id, fecha, km_odometro, litros, precio_litro, importe_total, estacion_servicio, notas, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute(array($vehiculoId, $ownerId, $fecha, $km, $litros, $precio, $importe, $estacion, $notas));
                $msg = "Nuevo repostaje registrado correctamente.";
            }
        } else {
            $msg = "Error: Debes indicar vehículo, litros y precio por litro.";
        }
    }

    // 2. Baja lógica de Repostaje (activo = 0)
    if ($formAction === 'deactivate_repostaje') {
        $id = (int)$_POST['id'];
        if ($isSuper) {
            $pdo->prepare("UPDATE veh_repostajes SET activo = 0 WHERE id = ?")->execute(array($id));
        } else {
            $pdo->prepare("UPDATE veh_repostajes SET activo = 0 WHERE id = ? AND usuario_id = ?")->execute(array($id, $userId));
        }
        $msg = "Repostaje dado de baja lógicamente (activo = 0).";
    }

    // 3. Guardar Vehículo (Alta o Modificación)
    if ($formAction === 'save_vehiculo') {
        $id          = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        $nombre      = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
        $matricula   = trim(isset($_POST['matricula']) ? $_POST['matricula'] : '');
        $marca       = trim(isset($_POST['marca']) ? $_POST['marca'] : '');
        $modelo      = trim(isset($_POST['modelo']) ? $_POST['modelo'] : '');
        $combustible = trim(isset($_POST['tipo_combustible']) ? $_POST['tipo_combustible'] : 'Diésel');
        $ownerId     = ($isSuper && !empty($_POST['usuario_id'])) ? (int)$_POST['usuario_id'] : $userId;

        if ($nombre !== '') {
            if ($id > 0) {
                if ($isSuper) {
                    $stmt = $pdo->prepare("UPDATE veh_vehiculos SET usuario_id=?, nombre=?, matricula=?, marca=?, modelo=?, tipo_combustible=? WHERE id=?");
                    $stmt->execute(array($ownerId, $nombre, $matricula, $marca, $modelo, $combustible, $id));
                } else {
                    $stmt = $pdo->prepare("UPDATE veh_vehiculos SET nombre=?, matricula=?, marca=?, modelo=?, tipo_combustible=? WHERE id=? AND usuario_id=?");
                    $stmt->execute(array($nombre, $matricula, $marca, $modelo, $combustible, $id, $userId));
                }
                $msg = "Vehículo actualizado correctamente.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO veh_vehiculos (usuario_id, nombre, matricula, marca, modelo, tipo_combustible, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute(array($ownerId, $nombre, $matricula, $marca, $modelo, $combustible));
                $msg = "Vehículo creado correctamente.";
            }
        }
        $tab = 'vehiculos';
    }

    // 4. Baja lógica de Vehículo (activo = 0)
    if ($formAction === 'deactivate_vehiculo') {
        $id = (int)$_POST['id'];
        if ($isSuper) {
            $pdo->prepare("UPDATE veh_vehiculos SET activo = 0 WHERE id = ?")->execute(array($id));
        } else {
            $pdo->prepare("UPDATE veh_vehiculos SET activo = 0 WHERE id = ? AND usuario_id = ?")->execute(array($id, $userId));
        }
        $msg = "Vehículo dado de baja lógicamente (activo = 0).";
        $tab = 'vehiculos';
    }

    // 5. Actualizar Permiso de Acceso de Usuario (Exclusivo Superusuario)
    if ($formAction === 'update_user_access' && $isSuper) {
        $targetUserId = (int)$_POST['user_id'];
        $acceso       = isset($_POST['acceso_sistema']) ? $_POST['acceso_sistema'] : 'ambos';
        if (!in_array($acceso, array('ambos', 'web', 'combustible'))) {
            $acceso = 'ambos';
        }
        $targetSuper = !empty($_POST['es_superusuario']) ? 1 : 0;
        if ($targetUserId === $userId) {
            $targetSuper = 1;
            $acceso = 'ambos';
        }
        $stmt = $pdo->prepare("UPDATE users SET acceso_sistema = ?, es_superusuario = ? WHERE id = ?");
        $stmt->execute(array($acceso, $targetSuper, $targetUserId));
        $msg = "Asignación de sistema actualizada para el usuario.";
        $tab = 'usuarios';
    }

    // 6. Guardar Estación de Servicio (Alta o Modificación)
    if ($formAction === 'save_estacion') {
        $id        = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        $nombre    = trim(isset($_POST['nombre']) ? $_POST['nombre'] : '');
        $marca     = trim(isset($_POST['marca']) ? $_POST['marca'] : '');
        $localidad = trim(isset($_POST['localidad']) ? $_POST['localidad'] : '');
        $direccion = trim(isset($_POST['direccion']) ? $_POST['direccion'] : '');
        $notas     = trim(isset($_POST['notas']) ? $_POST['notas'] : '');

        if ($nombre !== '') {
            if ($id > 0) {
                if ($isSuper) {
                    $stmt = $pdo->prepare("UPDATE veh_estaciones SET nombre=?, marca=?, localidad=?, direccion=?, notas=? WHERE id=? AND activo=1");
                    $stmt->execute(array($nombre, $marca, $localidad, $direccion, $notas, $id));
                } else {
                    $stmt = $pdo->prepare("UPDATE veh_estaciones SET nombre=?, marca=?, localidad=?, direccion=?, notas=? WHERE id=? AND usuario_id=? AND activo=1");
                    $stmt->execute(array($nombre, $marca, $localidad, $direccion, $notas, $id, $userId));
                }
                $msg = "Estación de servicio actualizada correctamente.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO veh_estaciones (usuario_id, nombre, marca, localidad, direccion, notas, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute(array($userId, $nombre, $marca, $localidad, $direccion, $notas));
                $msg = "Estación de servicio dada de alta correctamente.";
            }
        }
        $tab = 'estaciones';
    }

    // 7. Baja lógica de Estación de Servicio (activo = 0)
    if ($formAction === 'deactivate_estacion') {
        $id = (int)$_POST['id'];
        if ($isSuper) {
            $pdo->prepare("UPDATE veh_estaciones SET activo = 0 WHERE id = ?")->execute(array($id));
        } else {
            $pdo->prepare("UPDATE veh_estaciones SET activo = 0 WHERE id = ? AND usuario_id = ?")->execute(array($id, $userId));
        }
        $msg = "Estación de servicio dada de baja lógicamente (activo = 0).";
        $tab = 'estaciones';
    }
}

// Obtener lista de vehículos activos
if ($isSuper) {
    $vehiculos = $pdo->query("
        SELECT v.*, u.username AS propietario
        FROM veh_vehiculos v
        LEFT JOIN users u ON u.id = v.usuario_id
        WHERE v.activo = 1
        ORDER BY v.id ASC
    ")->fetchAll();
} else {
    $stV = $pdo->prepare("
        SELECT v.*, u.username AS propietario
        FROM veh_vehiculos v
        LEFT JOIN users u ON u.id = v.usuario_id
        WHERE v.usuario_id = ? AND v.activo = 1
        ORDER BY v.id ASC
    ");
    $stV->execute(array($userId));
    $vehiculos = $stV->fetchAll();
}

$selectedVehiculoId = isset($_GET['vehiculo_id']) ? (int)$_GET['vehiculo_id'] : 0;
$scopeName = "Todos los vehículos (Resultado Conjunto)";
foreach ($vehiculos as $vItem) {
    if ((int)$vItem['id'] === $selectedVehiculoId) {
        $scopeName = $vItem['nombre'] . ($vItem['matricula'] ? ' (' . $vItem['matricula'] . ')' : '');
        break;
    }
}

// Obtener repostajes y calcular consumos encadenados por vehículo
$sqlRep = "
    SELECT r.*, v.nombre AS vehiculo_nombre, v.matricula AS vehiculo_matricula
    FROM veh_repostajes r
    INNER JOIN veh_vehiculos v ON v.id = r.vehiculo_id AND v.activo = 1
    WHERE r.activo = 1
";
$repParams = array();
if (!$isSuper) {
    $sqlRep .= " AND r.usuario_id = ?";
    $repParams[] = $userId;
}
if ($selectedVehiculoId > 0) {
    $sqlRep .= " AND r.vehiculo_id = ?";
    $repParams[] = $selectedVehiculoId;
}
$sqlRep .= " ORDER BY r.vehiculo_id ASC, r.km_odometro ASC, r.fecha ASC, r.id ASC";
$stRep = $pdo->prepare($sqlRep);
$stRep->execute($repParams);
$rawRepostajes = $stRep->fetchAll();

// Agrupar por vehículo y calcular serie
$byVehicle = array();
foreach ($rawRepostajes as $r) {
    $vid = (int)$r['vehiculo_id'];
    if (!isset($byVehicle[$vid])) $byVehicle[$vid] = array();
    $byVehicle[$vid][] = $r;
}

$calculatedList = array();
$totalLitrosGlobal = 0.0;
$totalEurosGlobal  = 0.0;
$totalKmGlobal     = 0.0;
$litrosTramosGlobal = 0.0;
$allConsumos = array();
$allPrecios  = array();

foreach ($byVehicle as $vid => $rows) {
    $acumL = 0.0;
    $acumE = 0.0;
    $kmIni = null;
    $n = count($rows);
    for ($i = 0; $i < $n; $i++) {
        $row = $rows[$i];
        $L = (float)$row['litros'];
        $P = (float)$row['precio_litro'];
        $I = (float)$row['importe_total'];
        $K = (float)$row['km_odometro'];
        if ($kmIni === null) $kmIni = $K;

        $acumL += $L;
        $acumE += $I;
        $totalLitrosGlobal += $L;
        $totalEurosGlobal  += $I;
        if ($P > 0) $allPrecios[] = $P;

        $difKm = 0.0;
        $kmPorL = null;
        $lPor100 = null;
        $eurPorKm = null;

        if ($i > 0) {
            $prev = $rows[$i - 1];
            $prevK = (float)$prev['km_odometro'];
            $prevL = (float)$prev['litros'];
            $prevP = (float)$prev['precio_litro'];
            $difKm = $K - $prevK;
            if ($difKm > 0 && $prevL > 0) {
                $totalKmGlobal += $difKm;
                $litrosTramosGlobal += $prevL;
                $kmPorL = $difKm / $prevL;
                $lPorKm = $prevL / $difKm;
                $lPor100 = $lPorKm * 100.0;
                $eurPorKm = $lPorKm * $prevP;
                $allConsumos[] = $lPor100;
            }
        }
        $distIni = $K - ($kmIni !== null ? $kmIni : 0.0);
        $mediaEur100 = ($distIni > 0) ? (($acumE / $distIni) * 100.0) : null;

        $row['dif_km'] = $difKm;
        $row['km_por_litro'] = $kmPorL;
        $row['litros_por_100'] = $lPor100;
        $row['euros_por_km'] = $eurPorKm;
        $row['total_litros_acum'] = $acumL;
        $row['total_euros_acum'] = $acumE;
        $row['coste_medio_100_acum'] = $mediaEur100;
        $calculatedList[] = $row;
    }
}

// Ordenar más recientes primero para visualización
usort($calculatedList, function($a, $b) {
    $c = strcmp($b['fecha'], $a['fecha']);
    if ($c !== 0) return $c;
    return ($b['km_odometro'] > $a['km_odometro']) ? 1 : -1;
});

$consumoMax = !empty($allConsumos) ? max($allConsumos) : 0.0;
$consumoMin = !empty($allConsumos) ? min($allConsumos) : 0.0;
$precioMax  = !empty($allPrecios) ? max($allPrecios) : 0.0;
$precioMin  = !empty($allPrecios) ? min($allPrecios) : 0.0;
$consumoMedio = ($totalKmGlobal > 0) ? (($litrosTramosGlobal / $totalKmGlobal) * 100.0) : 0.0;
$costeMedio100 = ($totalKmGlobal > 0) ? (($totalEurosGlobal / $totalKmGlobal) * 100.0) : 0.0;

adminHeader($isSuper ? "Control de Combustible." : "Control de Combustible");
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
    .fuel-header-banner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        background: linear-gradient(135deg, #0f172a 0%, #0f766e 100%);
        color: #fff;
        padding: 1.1rem 1.35rem;
        border-radius: 16px;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.14);
    }
    .fuel-brand-box {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }
    .fuel-brand-box img {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        object-fit: cover;
    }
    .fuel-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .fuel-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .fuel-tab {
        padding: 0.6rem 1rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.86rem;
        background: #e2e8f0;
        color: #1e293b;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
    }
    .fuel-tab.active {
        background: #0f766e;
        color: #fff;
    }
    .fuel-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(165px, 1fr));
        gap: 0.85rem;
        margin-bottom: 1.35rem;
    }
    .fuel-kpi {
        background: #fff;
        padding: 0.95rem 1.05rem;
        border-radius: 14px;
        border-left: 4px solid #0f766e;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .fuel-kpi small {
        display: block;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .fuel-kpi strong {
        font-size: 1.25rem;
        color: #0f172a;
        display: block;
        margin-top: 0.2rem;
        font-variant-numeric: tabular-nums;
    }
    .fuel-kpi span {
        font-size: 0.75rem;
        color: #64748b;
    }

    /* Tabla optimizada y fija en cabecera */
    .fuel-table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-height: 68vh;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .fuel-data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.81rem;
    }
    .fuel-data-table th,
    .fuel-data-table td {
        padding: 0.55rem 0.5rem;
        text-align: right;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .fuel-data-table th:first-child,
    .fuel-data-table td:first-child,
    .fuel-data-table th:nth-child(2),
    .fuel-data-table td:nth-child(2) {
        text-align: left;
    }
    .fuel-data-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #0f172a;
        color: #fff;
        font-size: 0.72rem;
        text-transform: uppercase;
        font-weight: 700;
    }
    .fuel-data-table tbody tr:nth-child(even) { background: #f8fafc; }
    .fuel-data-table tbody tr:hover { background: #f0fdfa; }

    /* Tarjetas responsive para móvil */
    .fuel-mobile-cards {
        display: none;
        flex-direction: column;
        gap: 0.75rem;
    }
    .fuel-m-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.9rem;
        background: #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .fuel-m-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.45rem;
        background: #f8fafc;
        padding: 0.55rem;
        border-radius: 8px;
        margin-top: 0.5rem;
        font-size: 0.76rem;
    }
    .fuel-m-grid div span {
        display: block;
        color: #64748b;
        font-size: 0.68rem;
    }

    @media (max-width: 900px) {
        .admin-body { flex-direction: column; }
        .sidebar { width: 100%; height: auto; position: relative; }
        .main-content { margin-left: 0; padding: 0.75rem; }
        .fuel-kpis { grid-template-columns: repeat(2, 1fr); gap: 0.6rem; }
        .fuel-table-container.hide-on-mobile { display: none; }
        .fuel-mobile-cards { display: flex; }
    }
</style>

<!-- CABECERA CON ICONO _V2 Y ACCIONES RÁPIDAS -->
<div class="fuel-header-banner">
    <div class="fuel-brand-box">
        <img src="../combustible/assets/icons/icon-192.png" alt="Logo Control Combustible v2" onerror="this.style.display='none'">
        <div>
            <h2 style="margin:0; font-size:1.25rem; font-weight:800;">
                Control de Combustible y Consumo<?php echo $isSuper ? '.' : ''; ?>
            </h2>
            <div style="font-size:0.82rem; opacity:0.88;">
                Ámbito activo: <strong><?php echo htmlspecialchars($scopeName); ?></strong>
            </div>
        </div>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="https://github.com/pablosalinas/ControlCombustible/releases/latest/download/ControlCombustible.apk" target="_blank" class="btn" style="background:#16a34a; color:#fff; text-decoration:none; font-size:0.82rem; padding:0.55rem 0.9rem;">
            <i class="fab fa-android"></i> Descargar APK Android
        </a>
        <a href="../combustible/index.php" target="_blank" class="btn" style="background:#0d9488; color:#fff; text-decoration:none; font-size:0.82rem; padding:0.55rem 0.9rem;">
            <i class="fas fa-expand"></i> Abrir App Web / OCR
        </a>
        <button type="button" onclick="openAdmPdfModal()" class="btn" style="background:#f59e0b; color:#fff; font-size:0.82rem; padding:0.55rem 0.9rem;">
            <i class="fas fa-file-pdf"></i> Informe PDF (A4 Vertical)
        </button>
    </div>
</div>

<?php if ($msg): ?>
    <div class="card" style="background: #ccfbf1; color: #115e59; padding: 0.9rem 1.1rem; margin-bottom: 1.2rem; border-left: 4px solid #0f766e;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="fuel-topbar">
    <div class="fuel-tabs">
        <a href="?tab=repostajes&vehiculo_id=<?php echo $selectedVehiculoId; ?>" class="fuel-tab <?php echo $tab === 'repostajes' ? 'active' : ''; ?>">
            <i class="fas fa-gas-pump"></i> Repostajes (<?php echo count($calculatedList); ?>)
        </a>
        <a href="?tab=vehiculos" class="fuel-tab <?php echo $tab === 'vehiculos' ? 'active' : ''; ?>">
            <i class="fas fa-car"></i> Vehículos (<?php echo count($vehiculos); ?>)
        </a>
        <a href="?tab=estaciones" class="fuel-tab <?php echo $tab === 'estaciones' ? 'active' : ''; ?>">
            <i class="fas fa-store"></i> Estaciones
        </a>
        <a href="?tab=importar&vehiculo_id=<?php echo $selectedVehiculoId; ?>" class="fuel-tab <?php echo $tab === 'importar' ? 'active' : ''; ?>">
            <i class="fas fa-file-excel"></i> Importar Excel / CSV
        </a>
        <?php if ($isSuper): ?>
            <a href="?tab=usuarios" class="fuel-tab <?php echo $tab === 'usuarios' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> Permisos de Usuarios
            </a>
        <?php endif; ?>
    </div>

    <?php if ($tab === 'repostajes'): ?>
        <form method="GET" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; margin:0;">
            <input type="hidden" name="tab" value="repostajes">
            <select name="vehiculo_id" onchange="this.form.submit()" style="padding:0.55rem 0.85rem; border:1.5px solid #0f766e; border-radius:9px; font-weight:700; background:#fff; color:#0f172a;">
                <option value="0">📊 Todos los vehículos (Resultado Conjunto)</option>
                <?php foreach ($vehiculos as $v): ?>
                    <option value="<?php echo (int)$v['id']; ?>" <?php echo ($selectedVehiculoId == (int)$v['id']) ? 'selected' : ''; ?>>
                        🚗 <?php echo htmlspecialchars($v['nombre'] . ($v['matricula'] ? ' (' . $v['matricula'] . ')' : '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<!-- INDICADORES GLOBALES -->
<div class="fuel-kpis">
    <div class="fuel-kpi">
        <small>Consumo Medio</small>
        <strong><?php echo number_format($consumoMedio, 2, ',', '.'); ?> L/100</strong>
        <span>Coste: <?php echo number_format($costeMedio100, 2, ',', '.'); ?> €/100km</span>
    </div>
    <div class="fuel-kpi">
        <small>Consumo Máx / Mín</small>
        <strong><?php echo number_format($consumoMax, 2, ',', '.'); ?> / <?php echo number_format($consumoMin, 2, ',', '.'); ?></strong>
        <span>Dif: <?php echo number_format($consumoMax - $consumoMin, 2, ',', '.'); ?> L/100</span>
    </div>
    <div class="fuel-kpi">
        <small>Precio Máx / Mín</small>
        <strong><?php echo number_format($precioMax, 3, ',', '.'); ?> / <?php echo number_format($precioMin, 3, ',', '.'); ?> €</strong>
        <span>Dif: <?php echo number_format($precioMax - $precioMin, 3, ',', '.'); ?> €/L</span>
    </div>
    <div class="fuel-kpi">
        <small>Total Combustible</small>
        <strong><?php echo number_format($totalLitrosGlobal, 2, ',', '.'); ?> L</strong>
        <span><?php echo count($calculatedList); ?> repostajes</span>
    </div>
    <div class="fuel-kpi">
        <small>Gasto Acumulado</small>
        <strong><?php echo number_format($totalEurosGlobal, 2, ',', '.'); ?> €</strong>
        <span><?php echo number_format($totalKmGlobal, 0, ',', '.'); ?> km recorridos</span>
    </div>
</div>

<?php if ($tab === 'repostajes'): ?>
    <!-- FORMULARIO DE ALTA / EDICIÓN DE REPOSTAJE CON ESCANEO OCR INTEGRADO -->
    <div class="card" style="margin-bottom: 1.35rem; padding: 1.15rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:0.9rem;">
            <h3 id="form-rep-heading" style="margin:0; font-size:1rem;"><i class="fas fa-plus-circle" style="color:#0f766e;"></i> Registrar / Modificar Repostaje</h3>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <label class="btn btn-sm" style="background:#f0fdfa; color:#0f766e; border:1px dashed #0f766e; cursor:pointer; font-size:0.78rem;">
                    <i class="fas fa-receipt"></i> Escanear Ticket (OCR)
                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="adminScanTicketOCR(this)">
                </label>
                <label class="btn btn-sm" style="background:#fef3c7; color:#b45309; border:1px dashed #f59e0b; cursor:pointer; font-size:0.78rem;">
                    <i class="fas fa-tachometer-alt"></i> Foto Odómetro (OCR)
                    <input type="file" accept="image/*" capture="environment" style="display:none;" onchange="adminScanOdometerOCR(this)">
                </label>
            </div>
        </div>
        <div id="admin-ocr-msg" style="display:none; padding:0.55rem 0.8rem; border-radius:8px; background:#e0f2fe; color:#0369a1; font-size:0.82rem; margin-bottom:0.85rem;"></div>

<?php
if ($isSuper) {
    $estacionesList = $pdo->query("SELECT * FROM veh_estaciones WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    $lastKmStmt = $pdo->query("SELECT vehiculo_id, MAX(km_odometro) AS ultimo_km FROM veh_repostajes WHERE activo = 1 GROUP BY vehiculo_id");
} else {
    $stE = $pdo->prepare("SELECT * FROM veh_estaciones WHERE usuario_id = ? AND activo = 1 ORDER BY nombre ASC");
    $stE->execute(array($userId));
    $estacionesList = $stE->fetchAll();
    $lastKmStmt = $pdo->prepare("SELECT vehiculo_id, MAX(km_odometro) AS ultimo_km FROM veh_repostajes WHERE usuario_id = ? AND activo = 1 GROUP BY vehiculo_id");
    $lastKmStmt->execute(array($userId));
}
$ultimoKmMap = array();
while ($lk = $lastKmStmt->fetch()) {
    $ultimoKmMap[(int)$lk['vehiculo_id']] = (float)$lk['ultimo_km'];
}
?>
        <form method="POST">
            <input type="hidden" name="form_action" value="save_repostaje">
            <input type="hidden" name="id" id="rep_id" value="0">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(135px, 1fr)); gap: 0.75rem;">
                <div>
                    <label style="font-size:0.78rem; font-weight:600; display:block; margin-bottom:3px;">Vehículo *</label>
                    <select name="vehiculo_id" id="rep_vehiculo_id" required onchange="admUpdateParcial()" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
                        <?php if (count($vehiculos) > 1): ?>
                            <option value="">-- Selecciona un vehículo --</option>
                        <?php endif; ?>
                        <?php foreach ($vehiculos as $v): ?>
                            <option value="<?php echo (int)$v['id']; ?>" <?php echo (count($vehiculos) === 1) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['nombre'] . ($v['matricula'] ? ' (' . $v['matricula'] . ')' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.78rem; font-weight:600; display:block; margin-bottom:3px;">Fecha<?php echo $isSuper ? '.' : ''; ?> *</label>
                    <input type="date" name="fecha" id="rep_fecha" value="<?php echo date('Y-m-d'); ?>" required style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.78rem; font-weight:600; display:block; margin-bottom:3px;">Kilómetros (KM.) *</label>
                    <input type="number" step="0.01" name="km_odometro" id="rep_km" required placeholder="Ej: 183200" oninput="admUpdateParcial()" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
                    <div id="adm-km-parcial" style="font-size:0.74rem; margin-top:3px; font-weight:700; color:#0f766e;"></div>
                </div>
                <div>
                    <label style="font-size:0.78rem; font-weight:600; display:block; margin-bottom:3px;">Litros *</label>
                    <input type="number" step="0.001" name="litros" id="rep_litros" required placeholder="Ej: 52.12" oninput="calcRepImporte()" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.78rem; font-weight:600; display:block; margin-bottom:3px;">Precio €/L *</label>
                    <input type="number" step="0.0001" name="precio_litro" id="rep_precio" required placeholder="Ej: 1.439" oninput="calcRepImporte()" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.78rem; font-weight:600; display:block; margin-bottom:3px;">Importe Total (€) *</label>
                    <input type="number" step="0.01" name="importe_total" id="rep_importe" required placeholder="Ej: 75.00" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.78rem; font-weight:600; display:block; margin-bottom:3px;">Estación de Servicio</label>
                    <input type="text" name="estacion_servicio" id="rep_estacion" list="adm-estaciones-datalist" placeholder="Elige o escribe" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
                    <datalist id="adm-estaciones-datalist">
                        <?php foreach ($estacionesList as $est): ?>
                            <option value="<?php echo htmlspecialchars($est['nombre']); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label style="font-size:0.78rem; font-weight:600; display:block; margin-bottom:3px;">Notas / Mantenimiento</label>
                    <input type="text" name="notas" id="rep_notas" placeholder="Opcional" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
            </div>
            <div style="margin-top: 0.85rem; display:flex; gap:0.5rem;">
                <button type="submit" class="btn btn-primary" style="padding:0.55rem 1.1rem;"><i class="fas fa-save"></i> Guardar Repostaje</button>
                <button type="button" class="btn" onclick="resetRepForm()" style="padding:0.55rem 0.9rem;">Limpiar</button>
            </div>
        </form>
    </div>

    <!-- HISTÓRICO DE REPOSTAJES (TABLA COMPACTA + TARJETAS RESPONSIVE) -->
    <div class="card" style="padding: 1.15rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
            <h3 style="margin:0; font-size:1rem;"><i class="fas fa-list-ul" style="color:#0f766e;"></i> Listado Histórico y Cálculos</h3>
            <div style="display:flex; gap:0.5rem; align-items:center;">
                <input type="text" id="admin-fuel-search" placeholder="Buscar fecha, km, nota..." oninput="filterAdminFuelRows()" style="padding:0.45rem 0.75rem; border:1px solid #cbd5e1; border-radius:8px; font-size:0.82rem;">
                <button type="button" class="btn btn-sm" onclick="toggleAdminView()" style="background:#e2e8f0; color:#1e293b;">
                    <i class="fas fa-th-large"></i> Alternar Vista
                </button>
            </div>
        </div>

        <div class="fuel-table-container hide-on-mobile" id="admin-table-wrap">
            <table class="fuel-data-table" id="admin-fuel-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Vehículo</th>
                        <th>Litros</th>
                        <th>€/L</th>
                        <th>Importe</th>
                        <th>KM.</th>
                        <th>DIF.</th>
                        <th>L/100</th>
                        <th>Km/L</th>
                        <th>€/Km</th>
                        <th>T.Litros</th>
                        <th>Total €</th>
                        <th>Media €</th>
                        <th>Notas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calculatedList as $r): ?>
                        <tr class="fuel-row-item">
                            <td><strong><?php echo htmlspecialchars($r['fecha']); ?></strong></td>
                            <td><span class="badge" style="background:#ccfbf1; color:#115e59;"><?php echo htmlspecialchars($r['vehiculo_nombre']); ?></span></td>
                            <td><?php echo number_format((float)$r['litros'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format((float)$r['precio_litro'], 3, ',', '.'); ?></td>
                            <td style="font-weight:700; color:#0f766e;"><?php echo number_format((float)$r['importe_total'], 2, ',', '.'); ?> €</td>
                            <td><strong><?php echo number_format((float)$r['km_odometro'], 0, ',', '.'); ?></strong></td>
                            <td><?php echo $r['dif_km'] > 0 ? '+' . number_format((float)$r['dif_km'], 0, ',', '.') : '-'; ?></td>
                            <td>
                                <?php if ($r['litros_por_100'] !== null): ?>
                                    <span class="badge" style="background:#fef3c7; color:#b45309; font-weight:700;"><?php echo number_format((float)$r['litros_por_100'], 2, ',', '.'); ?></span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><?php echo $r['km_por_litro'] !== null ? number_format((float)$r['km_por_litro'], 2, ',', '.') : '-'; ?></td>
                            <td><?php echo $r['euros_por_km'] !== null ? number_format((float)$r['euros_por_km'], 4, ',', '.') : '-'; ?></td>
                            <td><?php echo number_format((float)$r['total_litros_acum'], 1, ',', '.'); ?></td>
                            <td><?php echo number_format((float)$r['total_euros_acum'], 2, ',', '.'); ?> €</td>
                            <td><?php echo $r['coste_medio_100_acum'] !== null ? number_format((float)$r['coste_medio_100_acum'], 2, ',', '.') : '-'; ?></td>
                            <td style="text-align:left; max-width:140px; overflow:hidden; text-overflow:ellipsis;"><?php echo htmlspecialchars((string)$r['notas']); ?></td>
                            <td style="white-space:nowrap;">
                                <button type="button" class="btn btn-sm btn-primary" style="padding:0.25rem 0.5rem;" onclick='loadRepIntoForm(<?php echo json_encode(array(
                                    "id" => $r["id"],
                                    "vehiculo_id" => $r["vehiculo_id"],
                                    "fecha" => $r["fecha"],
                                    "km_odometro" => $r["km_odometro"],
                                    "litros" => $r["litros"],
                                    "precio_litro" => $r["precio_litro"],
                                    "importe_total" => $r["importe_total"],
                                    "notas" => $r["notas"]
                                )); ?>)'><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Dar de baja lógica este repostaje? (activo = 0)');">
                                    <input type="hidden" name="form_action" value="deactivate_repostaje">
                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                    <button type="submit" class="btn btn-sm" style="color:red; padding:0.25rem 0.5rem;"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- TARJETAS RESPONSIVE PARA PANTALLAS MÓVILES / COMPACTAS -->
        <div class="fuel-mobile-cards" id="admin-cards-wrap">
            <?php foreach ($calculatedList as $r): ?>
                <div class="fuel-m-card fuel-card-item">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <strong><?php echo htmlspecialchars($r['fecha']); ?></strong>
                            <span class="badge" style="background:#ccfbf1; color:#115e59; margin-left:6px;"><?php echo htmlspecialchars($r['vehiculo_nombre']); ?></span>
                        </div>
                        <strong style="color:#0f766e; font-size:1.05rem;"><?php echo number_format((float)$r['importe_total'], 2, ',', '.'); ?> €</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:0.82rem; color:#64748b; margin-top:4px;">
                        <span><strong><?php echo number_format((float)$r['km_odometro'], 0, ',', '.'); ?> km</strong> (<?php echo $r['dif_km'] > 0 ? '+' . number_format((float)$r['dif_km'], 0, ',', '.') . ' km' : 'Inicio'; ?>)</span>
                        <span><?php echo number_format((float)$r['litros'], 2, ',', '.'); ?> L × <?php echo number_format((float)$r['precio_litro'], 3, ',', '.'); ?> €/L</span>
                    </div>
                    <div class="fuel-m-grid">
                        <div><span>Consumo L/100</span><strong><?php echo $r['litros_por_100'] !== null ? number_format((float)$r['litros_por_100'], 2, ',', '.') : '-'; ?></strong></div>
                        <div><span>Km / Litro</span><strong><?php echo $r['km_por_litro'] !== null ? number_format((float)$r['km_por_litro'], 2, ',', '.') : '-'; ?></strong></div>
                        <div><span>Coste / Km</span><strong><?php echo $r['euros_por_km'] !== null ? number_format((float)$r['euros_por_km'], 4, ',', '.') . ' €' : '-'; ?></strong></div>
                        <div><span>T. Litros</span><strong><?php echo number_format((float)$r['total_litros_acum'], 1, ',', '.'); ?> L</strong></div>
                        <div><span>Total €</span><strong><?php echo number_format((float)$r['total_euros_acum'], 2, ',', '.'); ?> €</strong></div>
                        <div><span>Media €/100</span><strong><?php echo $r['coste_medio_100_acum'] !== null ? number_format((float)$r['coste_medio_100_acum'], 2, ',', '.') . ' €' : '-'; ?></strong></div>
                    </div>
                    <?php if (!empty($r['notas'])): ?>
                        <div style="margin-top:6px; font-size:0.78rem; background:#fef3c7; color:#92400e; padding:4px 8px; border-radius:6px;">
                            <i class="fas fa-wrench"></i> <?php echo htmlspecialchars((string)$r['notas']); ?>
                        </div>
                    <?php endif; ?>
                    <div style="display:flex; justify-content:flex-end; gap:0.4rem; margin-top:8px;">
                        <button type="button" class="btn btn-sm btn-primary" onclick='loadRepIntoForm(<?php echo json_encode(array(
                            "id" => $r["id"],
                            "vehiculo_id" => $r["vehiculo_id"],
                            "fecha" => $r["fecha"],
                            "km_odometro" => $r["km_odometro"],
                            "litros" => $r["litros"],
                            "precio_litro" => $r["precio_litro"],
                            "importe_total" => $r["importe_total"],
                            "notas" => $r["notas"]
                        )); ?>)'><i class="fas fa-edit"></i> Editar</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Dar de baja lógica este repostaje? (activo = 0)');">
                            <input type="hidden" name="form_action" value="deactivate_repostaje">
                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                            <button type="submit" class="btn btn-sm" style="color:red;"><i class="fas fa-trash"></i> Baja</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

<?php elseif ($tab === 'vehiculos'): ?>
    <?php
    $allUsers = $isSuper ? $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll() : array();
    ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 style="margin-bottom: 1rem;">Alta / Modificación de Vehículo</h3>
        <form method="POST">
            <input type="hidden" name="form_action" value="save_vehiculo">
            <input type="hidden" name="id" id="veh_id" value="0">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.85rem;">
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Nombre del Vehículo *</label>
                    <input type="text" name="nombre" id="veh_nombre" required placeholder="Ej: Peugeot 308" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Matrícula</label>
                    <input type="text" name="matricula" id="veh_matricula" placeholder="Ej: 1234 HJK" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Marca / Modelo</label>
                    <input type="text" name="modelo" id="veh_modelo" placeholder="Ej: TDI 115CV" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Combustible</label>
                    <select name="tipo_combustible" id="veh_combustible" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                        <option value="Diésel">Diésel</option>
                        <option value="Gasolina 95">Gasolina 95</option>
                        <option value="Gasolina 98">Gasolina 98</option>
                        <option value="Híbrido">Híbrido</option>
                        <option value="GLP">GLP</option>
                    </select>
                </div>
                <?php if ($isSuper): ?>
                    <div>
                        <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Propietario (Usuario)</label>
                        <select name="usuario_id" id="veh_usuario_id" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Vehículo</button>
            </div>
        </form>
    </div>

    <div class="card" style="overflow-x:auto;">
        <h3>Vehículos Activos</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Matrícula</th>
                    <th>Combustible</th>
                    <th>Propietario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vehiculos as $v): ?>
                    <tr>
                        <td><?php echo (int)$v['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($v['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars((string)$v['matricula']); ?></td>
                        <td><?php echo htmlspecialchars((string)$v['tipo_combustible']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars((string)$v['propietario']); ?></span></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick='loadVehIntoForm(<?php echo json_encode($v); ?>)'><i class="fas fa-edit"></i> Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Dar de baja lógica este vehículo? (activo = 0)');">
                                <input type="hidden" name="form_action" value="deactivate_vehiculo">
                                <input type="hidden" name="id" value="<?php echo (int)$v['id']; ?>">
                                <button type="submit" class="btn btn-sm" style="color:red;"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($tab === 'usuarios' && $isSuper): ?>
    <div class="card" style="overflow-x:auto;">
        <h3><i class="fas fa-user-shield" style="color:#0f766e;"></i> Asignación de Acceso por Usuario (Exclusivo Superusuario)</h3>
        <p style="color:#64748b; font-size:0.88rem; margin: 0.4rem 0 1.5rem;">
            Desde aquí puedes decidir para cada usuario si puede utilizar <strong>tanto moratalla-murcia.com como la Gestión de Combustible</strong>, o <strong>solo una de ellas</strong>.
        </p>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Rol Actual</th>
                    <th>Configuración de Acceso Asignada</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $usersStmt = $pdo->query("SELECT id, username, role, acceso_sistema, es_superusuario FROM users ORDER BY id ASC");
                while ($u = $usersStmt->fetch()):
                    $acc = !empty($u['acceso_sistema']) ? $u['acceso_sistema'] : 'ambos';
                    $sup = !empty($u['es_superusuario']);
                ?>
                    <tr>
                        <td><?php echo (int)$u['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                            <?php if ($sup): ?>
                                <span class="badge" style="background:#fef3c7; color:#92400e; margin-left:6px;"><i class="fas fa-crown"></i> Superusuario</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($u['role']); ?></span></td>
                        <td>
                            <form method="POST" style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap; margin:0;">
                                <input type="hidden" name="form_action" value="update_user_access">
                                <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                <select name="acceso_sistema" style="padding:0.45rem 0.7rem; border:1px solid #cbd5e1; border-radius:8px; font-weight:600;">
                                    <option value="ambos" <?php echo $acc === 'ambos' ? 'selected' : ''; ?>>✅ Ambos (moratalla-murcia.com + Combustible)</option>
                                    <option value="web" <?php echo $acc === 'web' ? 'selected' : ''; ?>>🌐 Solo moratalla-murcia.com</option>
                                    <option value="combustible" <?php echo $acc === 'combustible' ? 'selected' : ''; ?>>⛽ Solo Gestión de Combustible</option>
                                </select>
                                <label style="font-size:0.85rem; display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                    <input type="checkbox" name="es_superusuario" value="1" <?php echo $sup ? 'checked' : ''; ?>> Superusuario
                                </label>
                                <button type="submit" class="btn btn-sm btn-primary">Guardar Asignación</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($tab === 'importar'): ?>
    <div class="card">
        <h3><i class="fas fa-file-excel" style="color:#16a34a;"></i> Importar Excel (.xlsx / .xls) o CSV a un Vehículo</h3>
        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:1rem; margin:0.8rem 0 1.25rem; font-size:0.85rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.45rem;">
                <strong style="color:#0f766e;"><i class="fas fa-info-circle"></i> Estructura de Columnas y Configuración Libre</strong>
                <button type="button" class="btn btn-sm" style="background:#e2e8f0; color:#0f172a;" onclick="adminDownloadTemplateCSV()">
                    <i class="fas fa-download"></i> Descargar Plantilla CSV de Ejemplo
                </button>
            </div>
            <p style="color:#475569; margin-bottom:0.35rem;">
                • <strong>Formato predeterminado (igual al de tu hoja original)</strong>:
                <code>Col A: FECHA</code> | <code>Col B: LITROS</code> | <code>Col C: PRECIO</code> | <code>Col D: IMPORTE</code> | <code>Col E: KM.</code> | <code>Notas (opcional)</code>.
            </p>
            <p style="color:#475569; margin:0;">
                • <strong>Cualquier otro formato de Excel o CSV</strong>: Selecciona tu archivo abajo y usa los desplegables para indicar en qué columna de tu archivo está cada dato. El sistema calculará automáticamente consumos, diferencias y acumulados sin borrar ningún dato previo.
            </p>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1rem; margin-bottom:1rem;">
            <div>
                <label style="font-weight:700; font-size:0.85rem; display:block; margin-bottom:5px;">1. Vehículo al que se importarán los datos *</label>
                <select id="adm-imp-vehiculo" style="width:100%; padding:0.65rem; border:1.5px solid #0f766e; border-radius:8px; font-weight:600;">
                    <?php foreach ($vehiculos as $v): ?>
                        <option value="<?php echo (int)$v['id']; ?>" <?php echo ($selectedVehiculoId == (int)$v['id']) ? 'selected' : ''; ?>>
                            🚗 <?php echo htmlspecialchars($v['nombre'] . ($v['matricula'] ? ' (' . $v['matricula'] . ')' : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-weight:700; font-size:0.85rem; display:block; margin-bottom:5px;">2. Archivo Excel (.xlsx, .xls) o CSV (.csv) *</label>
                <input type="file" id="adm-imp-file" accept=".xlsx,.xls,.csv,.txt" onchange="admHandleFile(this)" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px; background:#fff;">
            </div>
        </div>

        <div style="margin-bottom:1rem;">
            <label style="font-size:0.85rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                <input type="checkbox" id="adm-imp-header" checked onchange="admReparseSheet()">
                La primera fila del archivo contiene los títulos/encabezados de columna
            </label>
        </div>

        <div id="adm-imp-mapping-box" style="display:none; border-top:1px solid #e2e8f0; padding-top:1rem;">
            <h4 style="margin-bottom:0.75rem; font-size:0.92rem;">3. Asignar qué columna de tu Excel/CSV corresponde a cada campo:</h4>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(155px, 1fr)); gap:0.75rem; margin-bottom:1rem;">
                <div>
                    <label style="font-size:0.76rem; font-weight:700; color:#64748b; display:block;">FECHA *</label>
                    <select id="adm-map-fecha" onchange="admRenderPreview()" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:7px;"></select>
                </div>
                <div>
                    <label style="font-size:0.76rem; font-weight:700; color:#64748b; display:block;">LITROS *</label>
                    <select id="adm-map-litros" onchange="admRenderPreview()" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:7px;"></select>
                </div>
                <div>
                    <label style="font-size:0.76rem; font-weight:700; color:#64748b; display:block;">PRECIO (€/L)</label>
                    <select id="adm-map-precio" onchange="admRenderPreview()" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:7px;"></select>
                </div>
                <div>
                    <label style="font-size:0.76rem; font-weight:700; color:#64748b; display:block;">IMPORTE (€)</label>
                    <select id="adm-map-importe" onchange="admRenderPreview()" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:7px;"></select>
                </div>
                <div>
                    <label style="font-size:0.76rem; font-weight:700; color:#64748b; display:block;">KM. (Odómetro) *</label>
                    <select id="adm-map-km" onchange="admRenderPreview()" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:7px;"></select>
                </div>
                <div>
                    <label style="font-size:0.76rem; font-weight:700; color:#64748b; display:block;">NOTAS (Opcional)</label>
                    <select id="adm-map-notas" onchange="admRenderPreview()" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:7px;"></select>
                </div>
                <div>
                    <label style="font-size:0.76rem; font-weight:700; color:#64748b; display:block;">ESTACIÓN (Opcional)</label>
                    <select id="adm-map-estacion" onchange="admRenderPreview()" style="width:100%; padding:0.5rem; border:1px solid #cbd5e1; border-radius:7px;"></select>
                </div>
            </div>

            <div id="adm-imp-summary" style="font-size:0.84rem; font-weight:700; color:#0f766e; margin-bottom:0.5rem;"></div>
            <div style="overflow-x:auto; max-height:220px; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:1rem;">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Litros</th>
                            <th>Precio €/L</th>
                            <th>Importe €</th>
                            <th>KM.</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody id="adm-imp-preview-tbody"></tbody>
                </table>
            </div>

            <button type="button" id="adm-btn-import" class="btn btn-primary" onclick="admExecuteImport()">
                <i class="fas fa-cloud-upload-alt"></i> Confirmar e Importar Datos al Vehículo Seleccionado
            </button>
        </div>
    </div>

<?php elseif ($tab === 'estaciones'): ?>
    <?php
    if ($isSuper) {
        $estListTab = $pdo->query("SELECT * FROM veh_estaciones WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
    } else {
        $stEstTab = $pdo->prepare("SELECT * FROM veh_estaciones WHERE usuario_id = ? AND activo = 1 ORDER BY nombre ASC");
        $stEstTab->execute(array($userId));
        $estListTab = $stEstTab->fetchAll();
    }
    ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-store" style="color:#f59e0b;"></i> Alta / Modificación de Estación de Servicio</h3>
        <form method="POST">
            <input type="hidden" name="form_action" value="save_estacion">
            <input type="hidden" name="id" id="est_id" value="0">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.85rem;">
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Nombre *</label>
                    <input type="text" name="nombre" id="est_nombre" required placeholder="Ej: Repsol Moratalla" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Marca / Bandera</label>
                    <input type="text" name="marca" id="est_marca" placeholder="Ej: Repsol, Cepsa, Ballenoil" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Localidad</label>
                    <input type="text" name="localidad" id="est_localidad" placeholder="Ej: Moratalla" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Dirección</label>
                    <input type="text" name="direccion" id="est_direccion" placeholder="Opcional" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Notas</label>
                    <input type="text" name="notas" id="est_notas" placeholder="Descuento, horario..." style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
            </div>
            <div style="margin-top: 1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Estación</button>
            </div>
        </form>
    </div>

    <div class="card" style="overflow-x:auto;">
        <h3>Estaciones de Servicio Activas</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Marca</th>
                    <th>Localidad</th>
                    <th>Dirección</th>
                    <th>Notas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estListTab as $est): ?>
                    <tr>
                        <td><?php echo (int)$est['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($est['nombre']); ?></strong></td>
                        <td><?php echo htmlspecialchars((string)$est['marca']); ?></td>
                        <td><?php echo htmlspecialchars((string)$est['localidad']); ?></td>
                        <td><?php echo htmlspecialchars((string)$est['direccion']); ?></td>
                        <td><?php echo htmlspecialchars((string)$est['notas']); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick='loadEstIntoForm(<?php echo json_encode($est); ?>)'><i class="fas fa-edit"></i> Editar</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('¿Dar de baja lógica esta estación? (activo = 0)');">
                                <input type="hidden" name="form_action" value="deactivate_estacion">
                                <input type="hidden" name="id" value="<?php echo (int)$est['id']; ?>">
                                <button type="submit" class="btn btn-sm" style="color:red;"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- MODAL DE CONFIGURACIÓN PDF EN ADMIN (FILTRO Y ORDEN ASC/DESC POR FECHA) -->
<div id="adm-pdf-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); z-index:999; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:#fff; width:100%; max-width:460px; border-radius:16px; padding:1.35rem; box-shadow:0 20px 40px rgba(0,0,0,0.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h3 style="margin:0; font-size:1.05rem; color:#0f766e;"><i class="fas fa-file-pdf" style="color:#f59e0b;"></i> Configurar Informe PDF (A4 Vertical)</h3>
            <button type="button" onclick="closeAdmPdfModal()" class="btn btn-sm" style="background:#e2e8f0; color:#0f172a;"><i class="fas fa-times"></i></button>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.85rem;">
            <div>
                <label style="font-size:0.78rem; font-weight:700; display:block; margin-bottom:3px;">Desde Fecha</label>
                <input type="date" id="adm-pdf-from" onchange="updateAdmPdfCount()" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
            </div>
            <div>
                <label style="font-size:0.78rem; font-weight:700; display:block; margin-bottom:3px;">Hasta Fecha</label>
                <input type="date" id="adm-pdf-to" onchange="updateAdmPdfCount()" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
            </div>
        </div>
        <div style="margin-bottom:0.85rem;">
            <label style="font-size:0.78rem; font-weight:700; display:block; margin-bottom:3px;">Filtro de texto (vehículo, estación, km, nota...)</label>
            <input type="text" id="adm-pdf-search" oninput="updateAdmPdfCount()" placeholder="Opcional" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px;">
        </div>
        <div style="margin-bottom:0.9rem;">
            <label style="font-size:0.78rem; font-weight:700; display:block; margin-bottom:3px;">Ordenación por Fecha *</label>
            <select id="adm-pdf-order" style="width:100%; padding:0.55rem; border:1px solid #cbd5e1; border-radius:8px; font-weight:600;">
                <option value="desc">⬇️ Descendente (Más recientes primero)</option>
                <option value="asc">⬆️ Ascendente (Más antiguos primero)</option>
            </select>
        </div>
        <div id="adm-pdf-count" style="font-size:0.82rem; font-weight:700; color:#0f766e; background:#f0fdfa; padding:0.55rem 0.75rem; border-radius:8px; margin-bottom:1rem;"></div>
        <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
            <button type="button" onclick="closeAdmPdfModal()" class="btn" style="background:#e2e8f0; color:#0f172a;">Cancelar</button>
            <button type="button" onclick="exportFuelPdfA4Vertical()" class="btn" style="background:#f59e0b; color:#fff;"><i class="fas fa-file-pdf"></i> Generar PDF</button>
        </div>
    </div>
</div>

<script>
const pdfFullItems = <?php
    $exportItems = array();
    foreach ($calculatedList as $r) {
        $exportItems[] = array(
            'fecha' => $r['fecha'],
            'vehiculo' => (string)$r['vehiculo_nombre'],
            'estacion' => (string)($r['estacion_servicio'] ?? ''),
            'litros' => (float)$r['litros'],
            'precio' => (float)$r['precio_litro'],
            'importe' => (float)$r['importe_total'],
            'km' => (float)$r['km_odometro'],
            'dif_km' => (float)$r['dif_km'],
            'l100' => $r['litros_por_100'] !== null ? (float)$r['litros_por_100'] : null,
            'kml' => $r['km_por_litro'] !== null ? (float)$r['km_por_litro'] : null,
            'eurkm' => $r['euros_por_km'] !== null ? (float)$r['euros_por_km'] : null,
            't_litros' => (float)$r['total_litros_acum'],
            't_euros' => (float)$r['total_euros_acum'],
            'notas' => (string)($r['notas'] ?? '')
        );
    }
    echo json_encode($exportItems);
?>;

const admUltimoKmMap = <?php echo json_encode(isset($ultimoKmMap) ? $ultimoKmMap : array()); ?>;
let admEditingPrevKm = null;

function admUpdateParcial() {
    const el = document.getElementById('adm-km-parcial');
    if (!el) return;
    const vehId = document.getElementById('rep_vehiculo_id').value;
    const editId = parseInt(document.getElementById('rep_id').value || '0', 10);
    const kmVal = parseFloat(document.getElementById('rep_km').value);

    if (!vehId) {
        el.textContent = 'Selecciona un vehículo para ver su lectura anterior.';
        el.style.color = '#64748b';
        return;
    }
    const prevKm = (editId > 0 && admEditingPrevKm !== null)
        ? admEditingPrevKm
        : (admUltimoKmMap[vehId] !== undefined ? parseFloat(admUltimoKmMap[vehId]) : null);

    if (prevKm === null || isNaN(prevKm)) {
        el.textContent = 'Primer repostaje para este vehículo.';
        el.style.color = '#64748b';
        return;
    }
    if (isNaN(kmVal) || kmVal <= 0) {
        el.textContent = 'Lectura anterior: ' + prevKm.toLocaleString('es-ES') + ' km';
        el.style.color = '#64748b';
        return;
    }
    const parcial = kmVal - prevKm;
    if (parcial < 0) {
        el.textContent = '⚠️ Menor que anterior (' + prevKm.toLocaleString('es-ES') + ' km): ' + parcial.toLocaleString('es-ES') + ' km';
        el.style.color = '#dc2626';
    } else {
        el.textContent = '✓ Anterior: ' + prevKm.toLocaleString('es-ES') + ' km → Parcial: +' + parcial.toLocaleString('es-ES') + ' km';
        el.style.color = '#0f766e';
    }
}

function calcRepImporte() {
    const l = parseFloat(document.getElementById('rep_litros').value);
    const p = parseFloat(document.getElementById('rep_precio').value);
    if (l > 0 && p > 0) {
        document.getElementById('rep_importe').value = (l * p).toFixed(2);
    }
}
function loadRepIntoForm(r) {
    admEditingPrevKm = (r.dif_km && parseFloat(r.dif_km) > 0) ? (parseFloat(r.km_odometro) - parseFloat(r.dif_km)) : null;
    document.getElementById('rep_id').value = r.id;
    document.getElementById('rep_vehiculo_id').value = r.vehiculo_id;
    document.getElementById('rep_fecha').value = r.fecha;
    document.getElementById('rep_km').value = r.km_odometro;
    document.getElementById('rep_litros').value = r.litros;
    document.getElementById('rep_precio').value = r.precio_litro;
    document.getElementById('rep_importe').value = r.importe_total;
    if (document.getElementById('rep_estacion')) {
        document.getElementById('rep_estacion').value = r.estacion_servicio || '';
    }
    document.getElementById('rep_notas').value = r.notas || '';
    document.getElementById('form-rep-heading').innerHTML = '<i class="fas fa-edit" style="color:#f59e0b;"></i> Editando / Reasignando Repostaje #' + r.id;
    admUpdateParcial();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function resetRepForm() {
    admEditingPrevKm = null;
    document.getElementById('rep_id').value = '0';
    document.getElementById('rep_km').value = '';
    document.getElementById('rep_litros').value = '';
    document.getElementById('rep_precio').value = '';
    document.getElementById('rep_importe').value = '';
    if (document.getElementById('rep_estacion')) {
        document.getElementById('rep_estacion').value = '';
    }
    document.getElementById('rep_notas').value = '';
    document.getElementById('form-rep-heading').innerHTML = '<i class="fas fa-plus-circle" style="color:#0f766e;"></i> Registrar / Modificar Repostaje';
    admUpdateParcial();
}
function loadEstIntoForm(est) {
    document.getElementById('est_id').value = est.id;
    document.getElementById('est_nombre').value = est.nombre;
    document.getElementById('est_marca').value = est.marca || '';
    document.getElementById('est_localidad').value = est.localidad || '';
    document.getElementById('est_direccion').value = est.direccion || '';
    document.getElementById('est_notas').value = est.notas || '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function loadVehIntoForm(v) {
    document.getElementById('veh_id').value = v.id;
    document.getElementById('veh_nombre').value = v.nombre;
    document.getElementById('veh_matricula').value = v.matricula || '';
    document.getElementById('veh_modelo').value = v.modelo || '';
    document.getElementById('veh_combustible').value = v.tipo_combustible || 'Diésel';
    if (document.getElementById('veh_usuario_id')) {
        document.getElementById('veh_usuario_id').value = v.usuario_id;
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

let adminCardsForced = false;
function toggleAdminView() {
    adminCardsForced = !adminCardsForced;
    const tWrap = document.getElementById('admin-table-wrap');
    const cWrap = document.getElementById('admin-cards-wrap');
    if (adminCardsForced) {
        tWrap.style.display = 'none';
        cWrap.style.display = 'flex';
    } else {
        tWrap.style.display = '';
        cWrap.style.display = '';
    }
}

function filterAdminFuelRows() {
    const q = (document.getElementById('admin-fuel-search').value || '').toLowerCase().trim();
    document.querySelectorAll('.fuel-row-item, .fuel-card-item').forEach(el => {
        el.style.display = (!q || el.textContent.toLowerCase().includes(q)) ? '' : 'none';
    });
}

// ESCANEO OCR INTEGRADO EN EL PANEL ADMIN
async function adminScanTicketOCR(input) {
    if (!input.files || !input.files[0]) return;
    const msg = document.getElementById('admin-ocr-msg');
    msg.style.display = 'block';
    msg.textContent = '🔍 Escaneando ticket de combustible con OCR...';
    try {
        const result = await Tesseract.recognize(input.files[0], 'spa');
        const text = result.data.text;
        const dateMatch = text.match(/(\d{2})[\/\-](\d{2})[\/\-](20\d{2})/);
        if (dateMatch) {
            document.getElementById('rep_fecha').value = `${dateMatch[3]}-${dateMatch[2]}-${dateMatch[1]}`;
        }
        const decimals = [...text.matchAll(/(\d{1,3}[,\.]\d{2,3})/g)].map(m => parseFloat(m[1].replace(',', '.')));
        const unitPrice = decimals.find(n => n >= 0.85 && n <= 2.60);
        if (unitPrice) document.getElementById('rep_precio').value = unitPrice.toFixed(3);
        const largerNums = decimals.filter(n => n > 3.0).sort((a, b) => b - a);
        if (largerNums.length >= 2) {
            document.getElementById('rep_importe').value = largerNums[0].toFixed(2);
            document.getElementById('rep_litros').value = largerNums[1].toFixed(2);
        }
        msg.textContent = '✅ Datos extraídos del ticket. Revisa y pulsa Guardar Repostaje.';
    } catch (e) {
        msg.textContent = '⚠️ No se pudo procesar el ticket automáticamente.';
    }
}

async function adminScanOdometerOCR(input) {
    if (!input.files || !input.files[0]) return;
    const msg = document.getElementById('admin-ocr-msg');
    msg.style.display = 'block';
    msg.textContent = '🔍 Leyendo kilómetros del cuentakilómetros...';
    try {
        const result = await Tesseract.recognize(input.files[0], 'eng');
        const digits = result.data.text.replace(/\s+/g, '').match(/\d{4,7}/g);
        if (digits && digits.length > 0) {
            const bestKm = digits.sort((a, b) => b.length - a.length)[0];
            document.getElementById('rep_km').value = bestKm;
            msg.textContent = `✅ Kilometraje detectado: ${bestKm} km`;
        } else {
            msg.textContent = '⚠️ No se detectaron dígitos claros en el cuentakilómetros.';
        }
    } catch (e) {
        msg.textContent = '⚠️ Error al procesar imagen del odómetro.';
    }
}

// EXPORTACIÓN PDF OPTIMIZADA EN TAMAÑO A4 VERTICAL CON FILTRO Y ORDEN POR FECHA
function openAdmPdfModal() {
    const searchEl = document.getElementById('admin-fuel-search');
    document.getElementById('adm-pdf-search').value = searchEl ? (searchEl.value || '') : '';
    updateAdmPdfCount();
    document.getElementById('adm-pdf-modal').style.display = 'flex';
}
function closeAdmPdfModal() {
    document.getElementById('adm-pdf-modal').style.display = 'none';
}
function getAdmFilteredSortedItems() {
    const from = document.getElementById('adm-pdf-from').value || '';
    const to   = document.getElementById('adm-pdf-to').value || '';
    const q    = (document.getElementById('adm-pdf-search').value || '').toLowerCase().trim();
    const ord  = document.getElementById('adm-pdf-order').value || 'desc';

    const list = pdfFullItems.filter(it => {
        if (from && it.fecha < from) return false;
        if (to && it.fecha > to) return false;
        if (q) {
            const match = it.fecha.toLowerCase().includes(q) ||
                          String(it.km).includes(q) ||
                          it.vehiculo.toLowerCase().includes(q) ||
                          it.estacion.toLowerCase().includes(q) ||
                          it.notas.toLowerCase().includes(q);
            if (!match) return false;
        }
        return true;
    });
    list.sort((a, b) => {
        const cmp = a.fecha.localeCompare(b.fecha);
        if (cmp !== 0) return ord === 'asc' ? cmp : -cmp;
        return ord === 'asc' ? (a.km - b.km) : (b.km - a.km);
    });
    return list;
}
function updateAdmPdfCount() {
    const list = getAdmFilteredSortedItems();
    document.getElementById('adm-pdf-count').textContent = 'Se incluirán ' + list.length + ' repostaje(s) en el PDF.';
}
function fmtEs(v, dec) {
    return Number(v).toLocaleString('es-ES', { minimumFractionDigits: dec, maximumFractionDigits: dec });
}

function exportFuelPdfA4Vertical() {
    const items = getAdmFilteredSortedItems();
    if (items.length === 0) {
        alert('No hay registros que coincidan con el filtro seleccionado.');
        return;
    }
    const ord = document.getElementById('adm-pdf-order').value || 'desc';
    const from = document.getElementById('adm-pdf-from').value || '';
    const to = document.getElementById('adm-pdf-to').value || '';

    let totL = 0, totE = 0, totKm = 0;
    const cons = [], precs = [];
    items.forEach(it => {
        totL += it.litros;
        totE += it.importe;
        if (it.dif_km > 0) totKm += it.dif_km;
        if (it.l100 !== null) cons.push(it.l100);
        if (it.precio > 0) precs.push(it.precio);
    });
    const cMax = cons.length ? Math.max(...cons) : 0;
    const cMin = cons.length ? Math.min(...cons) : 0;
    const cMed = cons.length ? (cons.reduce((a, b) => a + b, 0) / cons.length) : 0;
    const pMax = precs.length ? Math.max(...precs) : 0;
    const pMin = precs.length ? Math.min(...precs) : 0;
    const coste100 = totKm > 0 ? ((totE / totKm) * 100) : 0;

    const rows = items.map(it => [
        it.fecha,
        fmtEs(it.litros, 2),
        fmtEs(it.precio, 3),
        fmtEs(it.importe, 2) + ' €',
        fmtEs(it.km, 0),
        it.dif_km > 0 ? '+' + fmtEs(it.dif_km, 0) : '-',
        it.l100 !== null ? fmtEs(it.l100, 2) : '-',
        it.kml !== null ? fmtEs(it.kml, 2) : '-',
        it.eurkm !== null ? fmtEs(it.eurkm, 3) : '-',
        fmtEs(it.t_litros, 1),
        fmtEs(it.t_euros, 2) + ' €',
        it.notas.substring(0, 22)
    ]);

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const orderLabel = ord === 'asc' ? 'Ascendente (fecha)' : 'Descendente (fecha)';
    const rangeLabel = (from || to) ? ' | Periodo: ' + (from || '...') + ' a ' + (to || '...') : '';

    doc.setFontSize(13);
    doc.setTextColor(15, 118, 110);
    doc.text('INFORME DE CONTROL DE COMBUSTIBLE Y CONSUMO', 8, 12);

    doc.setFontSize(8.2);
    doc.setTextColor(70, 70, 70);
    doc.text('Ámbito: <?php echo addslashes($scopeName); ?>' + rangeLabel + ' | Orden: ' + orderLabel + ' | ' + new Date().toLocaleDateString('es-ES'), 8, 17);

    // Caja resumen superior en A4 Vertical (194 mm ancho útil)
    doc.setDrawColor(203, 213, 225);
    doc.setFillColor(248, 250, 252);
    doc.roundedRect(8, 20, 194, 13, 2, 2, 'FD');

    doc.setFontSize(7.5);
    doc.setTextColor(15, 23, 42);
    doc.text(
        'Consumo Medio: ' + fmtEs(cMed, 2) + ' L/100km  |  Máx / Mín: ' + fmtEs(cMax, 2) + ' / ' + fmtEs(cMin, 2) + ' L/100  |  Precio Máx / Mín: ' + fmtEs(pMax, 3) + ' / ' + fmtEs(pMin, 3) + ' €/L',
        11, 25.5
    );
    doc.text(
        'Total Combustible: ' + fmtEs(totL, 2) + ' L (' + items.length + ' rep.)  |  Gasto: ' + fmtEs(totE, 2) + ' €  |  Km Recorridos: ' + fmtEs(totKm, 0) + ' km  |  Coste: ' + fmtEs(coste100, 2) + ' €/100km',
        11, 30.5
    );

    doc.autoTable({
        startY: 36,
        margin: { top: 12, right: 8, bottom: 12, left: 8 },
        head: [['Fecha', 'Litros', '€/L', 'Importe', 'KM.', 'DIF.', 'L/100', 'Km/L', '€/Km', 'T.Litros', 'Total €', 'Notas']],
        body: rows,
        styles: {
            fontSize: 6.8,
            cellPadding: 1.3,
            valign: 'middle',
            overflow: 'linebreak'
        },
        headStyles: {
            fillColor: [15, 118, 110],
            textColor: 255,
            fontStyle: 'bold',
            halign: 'right'
        },
        columnStyles: {
            0: { halign: 'left', cellWidth: 18 },
            1: { halign: 'right', cellWidth: 13 },
            2: { halign: 'right', cellWidth: 12 },
            3: { halign: 'right', cellWidth: 16, fontStyle: 'bold' },
            4: { halign: 'right', cellWidth: 16 },
            5: { halign: 'right', cellWidth: 13 },
            6: { halign: 'right', cellWidth: 13, fontStyle: 'bold' },
            7: { halign: 'right', cellWidth: 13 },
            8: { halign: 'right', cellWidth: 13 },
            9: { halign: 'right', cellWidth: 16 },
            10: { halign: 'right', cellWidth: 18 },
            11: { halign: 'left', cellWidth: 'auto' }
        },
        alternateRowStyles: { fillColor: [248, 250, 252] },
        didDrawPage: function (data) {
            const pageCount = doc.internal.getNumberOfPages();
            doc.setFontSize(7.5);
            doc.setTextColor(120, 120, 120);
            doc.text(
                'Página ' + data.pageNumber + ' de ' + pageCount + ' - Control de Combustible (A4 Vertical)',
                105,
                291,
                { align: 'center' }
            );
        }
    });

    closeAdmPdfModal();
    doc.save('Informe_Combustible_A4_' + new Date().toISOString().slice(0, 10) + '.pdf');
}

// IMPORTADOR CONFIGURABLE DE EXCEL (.XLSX / .XLS) Y CSV EN ADMIN
let admRawSheetRows = [];

function adminDownloadTemplateCSV() {
    const csvContent = "FECHA,LITROS,PRECIO,IMPORTE,KM.,NOTAS\n2025-03-01,48.50,1.429,69.31,183200,Repostaje normal\n2025-03-15,51.20,1.439,73.68,184120,Cambio filtro aire\n";
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'Plantilla_Repostajes_Combustible.csv';
    link.click();
}

function admColLetter(idx) {
    let s = '';
    let n = idx;
    while (n >= 0) {
        s = String.fromCharCode((n % 26) + 65) + s;
        n = Math.floor(n / 26) - 1;
    }
    return s;
}

function admHandleFile(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const data = new Uint8Array(e.target.result);
        const wb = XLSX.read(data, { type: 'array', raw: false, dateNF: 'yyyy-mm-dd' });
        const sheet = wb.Sheets[wb.SheetNames[0]];
        admRawSheetRows = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
        admReparseSheet();
    };
    reader.readAsArrayBuffer(input.files[0]);
}

function admReparseSheet() {
    if (!admRawSheetRows || admRawSheetRows.length === 0) return;
    const hasHeader = document.getElementById('adm-imp-header').checked;
    const firstRow = admRawSheetRows[0] || [];
    const maxCols = Math.max(...admRawSheetRows.slice(0, 10).map(r => r.length), firstRow.length);

    const ids = ['adm-map-fecha', 'adm-map-litros', 'adm-map-precio', 'adm-map-importe', 'adm-map-km', 'adm-map-notas', 'adm-map-estacion'];
    ids.forEach((id, idx) => {
        const sel = document.getElementById(id);
        sel.innerHTML = (idx >= 5) ? '<option value="-1">-- Ninguna --</option>' : '';
        for (let c = 0; c < maxCols; c++) {
            const label = hasHeader && firstRow[c] !== undefined && String(firstRow[c]).trim() !== ''
                ? `Col ${admColLetter(c)}: ${String(firstRow[c]).trim()}`
                : `Columna ${admColLetter(c)} (Ej: ${admRawSheetRows[hasHeader ? 1 : 0]?.[c] ?? ''})`;
            const opt = document.createElement('option');
            opt.value = c;
            opt.textContent = label;
            sel.appendChild(opt);
        }
    });

    let dFecha = 0, dLitros = 1, dPrecio = 2, dImporte = 3, dKm = 4, dNotas = (maxCols > 16 ? 16 : (maxCols > 5 ? 5 : -1)), dEst = -1;
    if (hasHeader) {
        firstRow.forEach((h, idx) => {
            const u = String(h || '').toUpperCase().trim();
            if (u.includes('FECHA') || u === 'DATE') dFecha = idx;
            else if (u === 'LITROS' || u === 'LTS' || u === 'VOLUMEN') dLitros = idx;
            else if (u.includes('PRECIO') || u === '€/L' || u === 'EUR/L') dPrecio = idx;
            else if (u.includes('IMPORTE') || u === 'TOTAL' || u === 'EUROS') dImporte = idx;
            else if (u === 'KM.' || u === 'KM' || u.includes('KILOMETR') || u.includes('ODOMETR')) dKm = idx;
            else if (u.includes('NOTA') || u.includes('INCIDENCIA') || u.includes('OBSERV')) dNotas = idx;
            else if (u.includes('ESTACION') || u.includes('GASOLINERA')) dEst = idx;
        });
    }

    document.getElementById('adm-map-fecha').value = String(dFecha);
    document.getElementById('adm-map-litros').value = String(dLitros);
    document.getElementById('adm-map-precio').value = String(dPrecio);
    document.getElementById('adm-map-importe').value = String(dImporte);
    document.getElementById('adm-map-km').value = String(dKm);
    document.getElementById('adm-map-notas').value = String(dNotas < maxCols ? dNotas : -1);
    document.getElementById('adm-map-estacion').value = String(dEst);

    document.getElementById('adm-imp-mapping-box').style.display = 'block';
    admRenderPreview();
}

function admGetMappedRows() {
    const hasHeader = document.getElementById('adm-imp-header').checked;
    const cF = parseInt(document.getElementById('adm-map-fecha').value, 10);
    const cL = parseInt(document.getElementById('adm-map-litros').value, 10);
    const cP = parseInt(document.getElementById('adm-map-precio').value, 10);
    const cI = parseInt(document.getElementById('adm-map-importe').value, 10);
    const cK = parseInt(document.getElementById('adm-map-km').value, 10);
    const cN = parseInt(document.getElementById('adm-map-notas').value, 10);
    const cE = parseInt(document.getElementById('adm-map-estacion').value, 10);

    const start = hasHeader ? 1 : 0;
    const out = [];
    for (let i = start; i < admRawSheetRows.length; i++) {
        const r = admRawSheetRows[i];
        if (!r || r.length === 0) continue;
        const fVal = cF >= 0 ? String(r[cF] ?? '').trim() : '';
        if (!fVal) continue;
        const rawN = cN >= 0 ? String(r[cN] ?? '').trim() : '';
        const cleanN = (rawN && isNaN(Number(rawN)) && !rawN.includes('PRECIO') && !rawN.includes('MINIMO')) ? rawN : '';
        out.push({
            fecha: fVal,
            litros: cL >= 0 ? r[cL] : '',
            precio_litro: cP >= 0 ? r[cP] : '',
            importe_total: cI >= 0 ? r[cI] : '',
            km_odometro: cK >= 0 ? r[cK] : '',
            notas: cleanN,
            estacion_servicio: cE >= 0 ? String(r[cE] ?? '').trim() : ''
        });
    }
    return out;
}

function admRenderPreview() {
    const rows = admGetMappedRows();
    document.getElementById('adm-imp-summary').textContent =
        `Detectadas ${rows.length} filas de datos. Vista previa (primeras 6 filas):`;
    const tbody = document.getElementById('adm-imp-preview-tbody');
    tbody.innerHTML = '';
    rows.slice(0, 6).forEach(m => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${m.fecha}</td><td>${m.litros}</td><td>${m.precio_litro}</td><td>${m.importe_total}</td><td>${m.km_odometro}</td><td>${m.notas || ''}</td>`;
        tbody.appendChild(tr);
    });
}

async function admExecuteImport() {
    const vehId = parseInt(document.getElementById('adm-imp-vehiculo').value || '0', 10);
    if (!vehId) {
        alert('Selecciona el vehículo destino.');
        return;
    }
    const rows = admGetMappedRows();
    if (rows.length === 0) {
        alert('No hay filas válidas para importar.');
        return;
    }
    const btn = document.getElementById('adm-btn-import');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando datos...';
    try {
        const res = await fetch('../combustible/api.php?action=import_custom_rows', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ vehiculo_id: vehId, rows })
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            window.location.href = '?tab=repostajes&vehiculo_id=' + vehId;
        } else {
            alert(data.error || 'Error en la importación.');
        }
    } catch (e) {
        alert('Error conectando con el importador: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Confirmar e Importar Datos al Vehículo Seleccionado';
    }
}
</script>

<?php adminFooter(); ?>
