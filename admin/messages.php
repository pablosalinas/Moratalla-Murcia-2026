<?php
// admin/messages.php
require_once 'inc/auth.php';
require_once 'inc/layout.php';
require_once '../config.php';

$pdo = getDB();
$message = '';

// Borrar mensaje
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
    $message = "Mensaje eliminado correctamente.";
}

// Marcar como leído
if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
}

// Obtener mensajes
$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

adminHeader("Bandeja de Mensajes");
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2><i class="fas fa-inbox"></i> Bandeja de Mensajes</h2>
            <p>Aquí puedes ver los mensajes enviados desde el formulario de contacto.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if (count($messages) === 0): ?>
        <div style="text-align: center; padding: 3rem; background: var(--bg-alt); border-radius: 12px; color: var(--text-light);">
            <i class="fas fa-envelope-open-text" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No tienes ningún mensaje en tu bandeja.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <?php foreach ($messages as $msg): ?>
                <div style="background: <?php echo $msg['is_read'] ? 'white' : '#f0f9ff'; ?>; border: 1px solid <?php echo $msg['is_read'] ? 'var(--gray-200)' : '#bae6fd'; ?>; border-radius: 12px; padding: 1.5rem; transition: all 0.3s;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h4 style="margin: 0; color: var(--primary-dark); font-size: 1.2rem;">
                                <?php if (!$msg['is_read']): ?>
                                    <span style="background: var(--primary); color: white; font-size: 0.7rem; padding: 2px 8px; border-radius: 20px; vertical-align: middle; margin-right: 5px;">NUEVO</span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($msg['name']); ?>
                            </h4>
                            <div style="color: var(--text-light); font-size: 0.9rem; margin-top: 0.5rem;">
                                <span><i class="fas fa-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color: inherit; text-decoration: underline;"><?php echo htmlspecialchars($msg['email']); ?></a></span>
                                <?php if (!empty($msg['phone'])): ?>
                                    <span style="margin-left: 1rem;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($msg['phone']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="text-align: right; color: var(--text-light); font-size: 0.85rem;">
                            <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                            <div style="margin-top: 0.8rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                                <?php if (!$msg['is_read']): ?>
                                    <a href="messages.php?read=<?php echo $msg['id']; ?>" class="btn-action" style="background: var(--primary); color: white; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-size: 0.8rem;"><i class="fas fa-check"></i> Marcar leído</a>
                                <?php endif; ?>
                                <a href="messages.php?delete=<?php echo $msg['id']; ?>" onclick="return confirm('¿Seguro que quieres borrar este mensaje?');" class="btn-action btn-delete" style="padding: 5px 10px; border-radius: 6px; text-decoration: none; font-size: 0.8rem;"><i class="fas fa-trash"></i> Borrar</a>
                            </div>
                        </div>
                    </div>
                    <div style="background: var(--bg-alt); padding: 1rem; border-radius: 8px; border-left: 4px solid var(--primary); color: var(--text); font-size: 1rem; line-height: 1.6; white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php adminFooter(); ?>
