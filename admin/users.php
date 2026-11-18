<?php
// admin/users.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
ensureUserAccessColumns($pdo);

// Asegurar columna activo y email para baja lógica y notificaciones sin destruir registros
try {
    $colActivo = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'activo'")->fetch();
    if (!$colActivo) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `activo` TINYINT(1) NOT NULL DEFAULT 1");
    }
    $colEmail = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'email'")->fetch();
    if (!$colEmail) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `email` VARCHAR(190) DEFAULT NULL");
    }
    $colValidado = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'validado'")->fetch();
    if (!$colValidado) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `validado` TINYINT(1) NOT NULL DEFAULT 1");
    }
} catch (Exception $e) {}

$isSuper = isSuperUser();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$msg = "";

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action == 'add') {
        $user = trim($_POST['username']);
        $pass = $_POST['password'];
        $role = $_POST['role'];
        // Solo el superusuario puede asignar el ámbito de acceso y marca de superusuario
        $accesoSistema = ($isSuper && isset($_POST['acceso_sistema'])) ? $_POST['acceso_sistema'] : 'web';
        if (!in_array($accesoSistema, array('ambos', 'web', 'combustible'))) {
            $accesoSistema = 'web';
        }
        $nuevoSuper = ($isSuper && !empty($_POST['es_superusuario'])) ? 1 : 0;
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, acceso_sistema, es_superusuario, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
        try {
            $stmt->execute(array($user, $email !== '' ? $email : null, $hash, $role, $accesoSistema, $nuevoSuper));
            $msg = "Usuario creado con éxito.";
            $action = 'list';
        } catch (Exception $e) {
            $msg = "Error: El usuario ya existe.";
        }
    }

    if ($action == 'change_pass') {
        $id = (int)$_POST['id'];
        $pass = $_POST['password'];
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute(array($hash, $id));
        $msg = "Contraseña actualizada correctamente.";
        $action = 'list';
    }

    // Actualizar permisos de acceso al sistema (Exclusivo Superusuario)
    if ($action == 'update_access' && $isSuper) {
        $id = (int)$_POST['id'];
        $accesoSistema = isset($_POST['acceso_sistema']) ? $_POST['acceso_sistema'] : 'ambos';
        if (!in_array($accesoSistema, array('ambos', 'web', 'combustible'))) {
            $accesoSistema = 'ambos';
        }
        $role = isset($_POST['role']) ? $_POST['role'] : 'editor';
        $esSuperTarget = !empty($_POST['es_superusuario']) ? 1 : 0;
        $emailTarget = isset($_POST['email']) ? trim($_POST['email']) : '';

        // Evitar que el superusuario actual se quite a sí mismo su permiso de superusuario por accidente
        if ($id == (int)$_SESSION['admin_id']) {
            $esSuperTarget = 1;
            $accesoSistema = 'ambos';
        }

        $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ?, acceso_sistema = ?, es_superusuario = ? WHERE id = ?");
        $stmt->execute(array($emailTarget !== '' ? $emailTarget : null, $role, $accesoSistema, $esSuperTarget, $id));
        $msg = "Permisos y correo del usuario actualizados correctamente.";
        $action = 'list';
    }
}

// APROBAR / RECHAZAR SOLICITUD DE ALTA (Exclusivo Superusuario)
if ($action == 'approve_user' && $isSuper && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE users SET validado = 1, activo = 1 WHERE id = ?")->execute(array($id));
    $mailSent = false;
    try {
        $stmtU = $pdo->prepare("SELECT username, email, acceso_sistema FROM users WHERE id = ? LIMIT 1");
        $stmtU->execute(array($id));
        $uRow = $stmtU->fetch();
        if ($uRow && !empty($uRow['email']) && filter_var($uRow['email'], FILTER_VALIDATE_EMAIL) && file_exists(__DIR__ . '/../inc/mailer.php')) {
            require_once __DIR__ . '/../inc/mailer.php';
            $cfg = getMailConfig($pdo);
            $subj = "Tu cuenta en moratalla-murcia.com ha sido dada de alta";
            $plain = "Hola {$uRow['username']},\r\n\r\nTu solicitud de registro ha sido aprobada por el superusuario y tu usuario ya está dado de alta.\r\nYa puedes iniciar sesión en https://www.moratalla-murcia.com/moratalla/admin/login.php o en https://www.moratalla-murcia.com/combustible/\r\n";
            $html = "<p>Hola <strong>" . htmlspecialchars($uRow['username']) . "</strong>,</p><p>Tu solicitud ha sido validada por el superusuario y tu cuenta ya está <strong>dada de alta</strong>.</p><p>Ya puedes iniciar sesión en la web y en la app.</p>";
            $mailSent = smtpSendRaw($cfg['smtp_host'], $cfg['smtp_port'], $cfg['smtp_encryption'], $cfg['smtp_user'], $cfg['smtp_pass'], $cfg['smtp_user'], 'moratalla-murcia.com', $uRow['email'], $cfg['smtp_user'], 'moratalla-murcia.com', $subj, $plain, $html);
        }
    } catch (Exception $eMail) {}
    $msg = $mailSent ? "Usuario aprobado y dado de alta. Se le ha enviado correo de confirmación." : "Usuario aprobado y dado de alta.";
    $action = 'list';
}

if ($action == 'reject_user' && $isSuper && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id != (int)$_SESSION['admin_id']) {
        $pdo->prepare("UPDATE users SET validado = -1 WHERE id = ?")->execute(array($id));
        $msg = "Solicitud de usuario rechazada.";
    }
    $action = 'list';
}

// BAJA LÓGICA DE USUARIO (Sin borrar datos)
if ($action == 'deactivate' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id != (int)$_SESSION['admin_id']) {
        $stmt = $pdo->prepare("UPDATE users SET activo = 0 WHERE id = ?");
        $stmt->execute(array($id));
        $msg = "Usuario dado de baja (inactivo).";
    } else {
        $msg = "No puedes darte de baja a ti mismo.";
    }
    $action = 'list';
}

adminHeader("Gestión de Usuarios");
?>

<?php if ($msg): ?>
    <div class="card" style="background: #e3f2fd; color: #1976d2; padding: 1rem; margin-bottom: 2rem;">
        <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3>Usuarios del Sistema</h3>
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;">
                    Asignación de acceso a <strong>moratalla-murcia.com</strong>, <strong>Control de Combustible</strong> o <strong>Ambos</strong> (ajustable exclusivamente por el Superusuario).
                </p>
            </div>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Usuario</a>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Acceso Asignado (Marca Superusuario)</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT * FROM users WHERE activo = 1 AND (validado IS NULL OR validado >= 0) ORDER BY (validado = 0) DESC, id ASC");
                    while ($u = $stmt->fetch()):
                        $isPending = isset($u['validado']) && (int)$u['validado'] === 0;
                        $status = $isPending ? "Pendiente validar" : ((!empty($u['locked_until']) && strtotime($u['locked_until']) > time()) ? "Bloqueado" : "Activo");
                        $acceso = !empty($u['acceso_sistema']) ? $u['acceso_sistema'] : 'ambos';
                        $uSuper = !empty($u['es_superusuario']);

                        $labelAcceso = "Ambos (Web + Combustible)";
                        $badgeStyle  = "background:#ccfbf1; color:#115e59;";
                        if ($acceso === 'web') {
                            $labelAcceso = "Solo moratalla-murcia.com";
                            $badgeStyle  = "background:#dbeafe; color:#1e40af;";
                        } elseif ($acceso === 'combustible') {
                            $labelAcceso = "Solo Control de Combustible";
                            $badgeStyle  = "background:#fef3c7; color:#b45309;";
                        }
                    ?>
                        <tr>
                            <td><?php echo (int)$u['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                <?php if ($uSuper): ?>
                                    <span class="badge" style="background:#fef3c7; color:#92400e; font-size:0.72rem; margin-left:6px;"><i class="fas fa-crown"></i> Superusuario</span>
                                <?php endif; ?>
                                <?php if ($isPending): ?>
                                    <span class="badge" style="background:#ffedd5; color:#c2410c; font-size:0.72rem; margin-left:6px;">Pendiente de aprobación</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($u['role']); ?></span></td>
                            <td>
                                <?php if ($isSuper): ?>
                                    <form method="POST" action="?action=update_access" style="display:flex; gap:0.4rem; align-items:center; flex-wrap:wrap; margin:0;">
                                        <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($u['role']); ?>">
                                        <input type="email" name="email" value="<?php echo htmlspecialchars(isset($u['email']) ? $u['email'] : ''); ?>" placeholder="correo@ejemplo.com" style="padding:0.35rem 0.5rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.8rem; width:165px;">
                                        <select name="acceso_sistema" style="padding:0.35rem 0.5rem; border:1px solid #cbd5e1; border-radius:6px; font-size:0.82rem;">
                                            <option value="ambos" <?php echo $acceso === 'ambos' ? 'selected' : ''; ?>>✅ Ambos (Web + Combustible)</option>
                                            <option value="web" <?php echo $acceso === 'web' ? 'selected' : ''; ?>>🌐 Solo moratalla-murcia.com</option>
                                            <option value="combustible" <?php echo $acceso === 'combustible' ? 'selected' : ''; ?>>⛽ Solo Gestión Combustible</option>
                                        </select>
                                        <label style="font-size:0.78rem; display:inline-flex; align-items:center; gap:4px; cursor:pointer;" title="Superusuario (acceso total y ajuste de permisos)">
                                            <input type="checkbox" name="es_superusuario" value="1" <?php echo $uSuper ? 'checked' : ''; ?>> Super
                                        </label>
                                        <button type="submit" class="btn btn-sm btn-primary" style="padding:0.3rem 0.6rem; font-size:0.78rem;">Guardar</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge" style="<?php echo $badgeStyle; ?> padding:0.3rem 0.6rem; border-radius:6px;">
                                        <?php echo htmlspecialchars($labelAcceso); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $status; ?></td>
                            <td>
                                <?php if ($isPending && $isSuper): ?>
                                    <a href="?action=approve_user&id=<?php echo (int)$u['id']; ?>" class="btn btn-sm" style="background:#16a34a; color:#fff;" onclick="return confirm('¿Validar y dar de alta a este usuario?')">Aprobar</a>
                                    <a href="?action=reject_user&id=<?php echo (int)$u['id']; ?>" class="btn btn-sm" style="background:#dc2626; color:#fff;" onclick="return confirm('¿Rechazar esta solicitud?')">Rechazar</a>
                                <?php endif; ?>
                                <a href="?action=change_pass&id=<?php echo (int)$u['id']; ?>" class="btn btn-sm btn-primary">Contraseña</a>
                                <?php if ($isSuper && (int)$u['id'] !== (int)$_SESSION['admin_id']): ?>
                                    <a href="?action=deactivate&id=<?php echo (int)$u['id']; ?>" class="btn btn-sm" style="color:red" onclick="return confirm('¿Dar de baja a este usuario?')"><i class="fas fa-user-slash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($action == 'add'): ?>
    <div class="card" style="max-width: 540px;">
        <h3>Añadir Nuevo Usuario</h3>
        <form method="POST">
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom: 0.4rem; font-weight:600;">Nombre de Usuario</label>
                <input type="text" name="username" required style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
            </div>
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom: 0.4rem; font-weight:600;">Correo Electrónico (para avisos de repostaje y tareas)</label>
                <input type="email" name="email" placeholder="usuario@ejemplo.com" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
            </div>
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom: 0.4rem; font-weight:600;">Contraseña</label>
                <input type="password" name="password" required style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
            </div>
            <div style="margin-bottom: 1.2rem;">
                <label style="display:block; margin-bottom: 0.4rem; font-weight:600;">Rol</label>
                <select name="role" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
                    <option value="editor">Editor / Usuario</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <?php if ($isSuper): ?>
                <div style="margin-bottom: 1.2rem; padding: 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                    <label style="display:block; margin-bottom: 0.4rem; font-weight:700; color:#0f766e;">
                        <i class="fas fa-shield-alt"></i> Asignación de Sistema (Exclusivo Superusuario)
                    </label>
                    <select name="acceso_sistema" style="width:100%; padding:0.8rem; border:1px solid #cbd5e1; border-radius:8px; margin-bottom: 0.75rem;">
                        <option value="ambos">✅ Ambos (moratalla-murcia.com + Gestión de Combustible)</option>
                        <option value="web">🌐 Solo moratalla-murcia.com</option>
                        <option value="combustible">⛽ Solo Gestión de Combustible</option>
                    </select>
                    <label style="display:flex; align-items:center; gap:8px; font-size:0.88rem; cursor:pointer;">
                        <input type="checkbox" name="es_superusuario" value="1">
                        Otorgar marca de Superusuario (acceso global a todos los vehículos y permisos)
                    </label>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Crear Usuario</button>
            <a href="users.php" class="btn">Cancelar</a>
        </form>
    </div>

<?php elseif ($action == 'change_pass'):
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute(array($id));
    $uName = $stmt->fetchColumn();
?>
    <div class="card" style="max-width: 500px;">
        <h3>Cambiar Contraseña: <?php echo htmlspecialchars($uName); ?></h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem;">Nueva Contraseña</label>
                <input type="password" name="password" required style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
            <a href="users.php" class="btn">Cancelar</a>
        </form>
    </div>
<?php endif; ?>

<?php adminFooter(); ?>
