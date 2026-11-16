<?php
/**
 * admin/combustible.php
 * Apartado exclusivo en la Administración de moratalla-murcia.com para la
 * Gestión de Control de Combustible, Vehículos y Asignación de Permisos de Usuario.
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
                    $stmt = $pdo->prepare("UPDATE veh_repostajes SET vehiculo_id=?, fecha=?, km_odometro=?, litros=?, precio_litro=?, importe_total=?, estacion_servicio=?, notas=? WHERE id=? AND activo=1");
                    $stmt->execute(array($vehiculoId, $fecha, $km, $litros, $precio, $importe, $estacion, $notas, $id));
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

<style>
    .fuel-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .fuel-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .fuel-tab {
        padding: 0.65rem 1.1rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        background: #e2e8f0;
        color: #1e293b;
    }
    .fuel-tab.active {
        background: #0f766e;
        color: #fff;
    }
    .fuel-kpis {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .fuel-kpi {
        background: #fff;
        padding: 1.1rem;
        border-radius: 14px;
        border-left: 4px solid #0f766e;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .fuel-kpi small {
        display: block;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .fuel-kpi strong {
        font-size: 1.35rem;
        color: #0f172a;
        display: block;
        margin-top: 0.25rem;
    }
    .fuel-kpi span {
        font-size: 0.78rem;
        color: #64748b;
    }
</style>

<?php if ($msg): ?>
    <div class="card" style="background: #ccfbf1; color: #115e59; padding: 1rem; margin-bottom: 1.5rem; border-left: 4px solid #0f766e;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<div class="fuel-topbar">
    <div class="fuel-tabs">
        <a href="?tab=repostajes&vehiculo_id=<?php echo $selectedVehiculoId; ?>" class="fuel-tab <?php echo $tab === 'repostajes' ? 'active' : ''; ?>">
            <i class="fas fa-gas-pump"></i> Repostajes y Consumos
        </a>
        <a href="?tab=vehiculos" class="fuel-tab <?php echo $tab === 'vehiculos' ? 'active' : ''; ?>">
            <i class="fas fa-car"></i> Vehículos (<?php echo count($vehiculos); ?>)
        </a>
        <?php if ($isSuper): ?>
            <a href="?tab=usuarios" class="fuel-tab <?php echo $tab === 'usuarios' ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> Permisos de Usuarios (Web / Combustible)
            </a>
        <?php endif; ?>
    </div>

    <div style="display: flex; gap: 0.6rem; flex-wrap: wrap;">
        <a href="https://github.com/pablosalinas/ControlCombustible/releases/latest/download/ControlCombustible.apk" target="_blank" class="btn" style="background:#16a34a; color:#fff; text-decoration:none;">
            <i class="fab fa-android"></i> Descargar APK Android
        </a>
        <a href="../combustible/" target="_blank" class="btn" style="background:#0f766e; color:#fff; text-decoration:none;">
            <i class="fas fa-mobile-alt"></i> Abrir App Web / OCR
        </a>
        <button type="button" onclick="exportFuelPdf()" class="btn" style="background:#f59e0b; color:#fff;">
            <i class="fas fa-file-pdf"></i> Informe PDF
        </button>
    </div>
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
    <!-- FORMULARIO DE ALTA / EDICIÓN DE REPOSTAJE -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 id="form-rep-heading" style="margin-bottom: 1rem;"><i class="fas fa-plus-circle" style="color:#0f766e;"></i> Registrar / Modificar Repostaje</h3>
        <form method="POST">
            <input type="hidden" name="form_action" value="save_repostaje">
            <input type="hidden" name="id" id="rep_id" value="0">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.85rem;">
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Vehículo *</label>
                    <select name="vehiculo_id" id="rep_vehiculo_id" required style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                        <?php foreach ($vehiculos as $v): ?>
                            <option value="<?php echo (int)$v['id']; ?>" <?php echo ($selectedVehiculoId == (int)$v['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($v['nombre'] . ($v['matricula'] ? ' (' . $v['matricula'] . ')' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Fecha<?php echo $isSuper ? '.' : ''; ?> *</label>
                    <input type="date" name="fecha" id="rep_fecha" value="<?php echo date('Y-m-d'); ?>" required style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Kilómetros (KM.) *</label>
                    <input type="number" step="0.01" name="km_odometro" id="rep_km" required placeholder="Ej: 183200" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Litros *</label>
                    <input type="number" step="0.001" name="litros" id="rep_litros" required placeholder="Ej: 52.12" oninput="calcRepImporte()" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Precio €/L *</label>
                    <input type="number" step="0.0001" name="precio_litro" id="rep_precio" required placeholder="Ej: 1.439" oninput="calcRepImporte()" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Importe Total (€) *</label>
                    <input type="number" step="0.01" name="importe_total" id="rep_importe" required placeholder="Ej: 75.00" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
                <div>
                    <label style="font-size:0.82rem; font-weight:600; display:block; margin-bottom:4px;">Notas / Mantenimiento</label>
                    <input type="text" name="notas" id="rep_notas" placeholder="Opcional" style="width:100%; padding:0.65rem; border:1px solid #cbd5e1; border-radius:8px;">
                </div>
            </div>
            <div style="margin-top: 1rem; display:flex; gap:0.5rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Repostaje</button>
                <button type="button" class="btn" onclick="resetRepForm()">Limpiar formulario</button>
            </div>
        </form>
    </div>

    <!-- FILTRO E HISTÓRICO DE REPOSTAJES -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.2rem;">
            <h3>Histórico de Repostajes</h3>
            <form method="GET" style="display:flex; gap:0.5rem; align-items:center;">
                <input type="hidden" name="tab" value="repostajes">
                <label style="font-weight:600; font-size:0.85rem;">Filtrar Vehículo:</label>
                <select name="vehiculo_id" onchange="this.form.submit()" style="padding:0.5rem 0.8rem; border:1px solid #cbd5e1; border-radius:8px; font-weight:600;">
                    <option value="0">📊 Todos los vehículos (Resultado Conjunto)</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <option value="<?php echo (int)$v['id']; ?>" <?php echo ($selectedVehiculoId == (int)$v['id']) ? 'selected' : ''; ?>>
                            🚗 <?php echo htmlspecialchars($v['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table id="admin-fuel-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Vehículo</th>
                        <th>Litros</th>
                        <th>Precio</th>
                        <th>Importe</th>
                        <th>KM.</th>
                        <th>DIF.</th>
                        <th>LTS.x.100</th>
                        <th>KM.x.LTS</th>
                        <th>€ x 1KM.</th>
                        <th>T.Litros</th>
                        <th>Total €</th>
                        <th>Notas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calculatedList as $r): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($r['fecha']); ?></strong></td>
                            <td><?php echo htmlspecialchars($r['vehiculo_nombre']); ?></td>
                            <td><?php echo number_format((float)$r['litros'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format((float)$r['precio_litro'], 3, ',', '.'); ?> €</td>
                            <td><strong><?php echo number_format((float)$r['importe_total'], 2, ',', '.'); ?> €</strong></td>
                            <td><?php echo number_format((float)$r['km_odometro'], 0, ',', '.'); ?></td>
                            <td><?php echo $r['dif_km'] > 0 ? '+' . number_format((float)$r['dif_km'], 0, ',', '.') : '-'; ?></td>
                            <td>
                                <?php if ($r['litros_por_100'] !== null): ?>
                                    <span class="badge" style="background:#fef3c7; color:#b45309;"><?php echo number_format((float)$r['litros_por_100'], 2, ',', '.'); ?></span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><?php echo $r['km_por_litro'] !== null ? number_format((float)$r['km_por_litro'], 2, ',', '.') : '-'; ?></td>
                            <td><?php echo $r['euros_por_km'] !== null ? number_format((float)$r['euros_por_km'], 4, ',', '.') . ' €' : '-'; ?></td>
                            <td><?php echo number_format((float)$r['total_litros_acum'], 1, ',', '.'); ?></td>
                            <td><?php echo number_format((float)$r['total_euros_acum'], 2, ',', '.'); ?> €</td>
                            <td><?php echo htmlspecialchars((string)$r['notas']); ?></td>
                            <td style="white-space:nowrap;">
                                <button type="button" class="btn btn-sm btn-primary" onclick='loadRepIntoForm(<?php echo json_encode(array(
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
                                    <button type="submit" class="btn btn-sm" style="color:red;"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

    <div class="card">
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
    <div class="card">
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
<?php endif; ?>

<script>
function calcRepImporte() {
    const l = parseFloat(document.getElementById('rep_litros').value);
    const p = parseFloat(document.getElementById('rep_precio').value);
    if (l > 0 && p > 0) {
        document.getElementById('rep_importe').value = (l * p).toFixed(2);
    }
}
function loadRepIntoForm(r) {
    document.getElementById('rep_id').value = r.id;
    document.getElementById('rep_vehiculo_id').value = r.vehiculo_id;
    document.getElementById('rep_fecha').value = r.fecha;
    document.getElementById('rep_km').value = r.km_odometro;
    document.getElementById('rep_litros').value = r.litros;
    document.getElementById('rep_precio').value = r.precio_litro;
    document.getElementById('rep_importe').value = r.importe_total;
    document.getElementById('rep_notas').value = r.notas || '';
    document.getElementById('form-rep-heading').innerHTML = '<i class="fas fa-edit" style="color:#f59e0b;"></i> Editando Repostaje #' + r.id;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function resetRepForm() {
    document.getElementById('rep_id').value = '0';
    document.getElementById('rep_km').value = '';
    document.getElementById('rep_litros').value = '';
    document.getElementById('rep_precio').value = '';
    document.getElementById('rep_importe').value = '';
    document.getElementById('rep_notas').value = '';
    document.getElementById('form-rep-heading').innerHTML = '<i class="fas fa-plus-circle" style="color:#0f766e;"></i> Registrar / Modificar Repostaje';
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
function exportFuelPdf() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
    doc.setFontSize(15);
    doc.setTextColor(15, 118, 110);
    doc.text('Informe de Control de Combustible - moratalla-murcia.com', 14, 15);
    doc.autoTable({
        html: '#admin-fuel-table',
        startY: 22,
        styles: { fontSize: 7.5 },
        headStyles: { fillColor: [15, 118, 110] }
    });
    doc.save('Informe_Combustible_' + new Date().toISOString().slice(0, 10) + '.pdf');
}
</script>

<?php adminFooter(); ?>
