<?php
// admin/users.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$msg = "";

// PROCESAR ACCIONES
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action == 'add') {
        $user = $_POST['username'];
        $pass = $_POST['password'];
        $role = $_POST['role'];
        
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$user, $hash, $role]);
            $msg = "Usuario creado con éxito.";
            $action = 'list';
        } catch (Exception $e) {
            $msg = "Error: El usuario ya existe.";
        }
    }
    
    if ($action == 'change_pass') {
        $id = $_POST['id'];
        $pass = $_POST['password'];
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$hash, $id]);
        $msg = "Contraseña actualizada correctamente.";
        $action = 'list';
    }
}

// ELIMINAR USUARIO
if ($action == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($id != $_SESSION['admin_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Usuario eliminado.";
    } else {
        $msg = "No puedes eliminarte a ti mismo.";
    }
    $action = 'list';
}

adminHeader("Gestión de Usuarios");
?>

<?php if ($msg): ?>
    <div class="card" style="background: #e3f2fd; color: #1976d2; padding: 1rem; margin-bottom: 2rem;">
        <i class="fas fa-info-circle"></i> <?php echo $msg; ?>
    </div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h3>Usuarios del Sistema</h3>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Nuevo Usuario</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM users ORDER BY username ASC");
                while ($u = $stmt->fetch()) {
                    $status = ($u['locked_until'] && strtotime($u['locked_until']) > time()) ? "Bloqueado" : "Activo";
                    echo "<tr>
                        <td>{$u['id']}</td>
                        <td><strong>{$u['username']}</strong></td>
                        <td><span class='badge badge-info'>{$u['role']}</span></td>
                        <td>{$status}</td>
                        <td>
                            <a href='?action=change_pass&id={$u['id']}' class='btn btn-sm btn-primary'>Pass</a>
                            <a href='?action=delete&id={$u['id']}' class='btn btn-sm' style='color:red' onclick='return confirm(\"¿Eliminar usuario?\")'><i class='fas fa-trash'></i></a>
                        </td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

<?php elseif ($action == 'add'): ?>
    <div class="card" style="max-width: 500px;">
        <h3>Añadir Nuevo Usuario</h3>
        <form method="POST">
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem;">Nombre de Usuario</label>
                <input type="text" name="username" required style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem;">Contraseña</label>
                <input type="password" name="password" required style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display:block; margin-bottom: 0.5rem;">Rol</label>
                <select name="role" style="width:100%; padding:0.8rem; border:1px solid #ddd; border-radius:8px;">
                    <option value="admin">Administrador</option>
                    <option value="editor">Editor</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Crear Usuario</button>
            <a href="users.php" class="btn">Cancelar</a>
        </form>
    </div>

<?php elseif ($action == 'change_pass'): 
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $uName = $stmt->fetchColumn();
?>
    <div class="card" style="max-width: 500px;">
        <h3>Cambiar Contraseña: <?php echo $uName; ?></h3>
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
