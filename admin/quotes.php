<?php
// admin/quotes.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();
$action = $_GET['action'] ?? 'list';
$msg = "";
$errorMsg = "";

// PROCESAR ACCIONES POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action == 'add' || $action == 'edit') {
        $id = $_POST['id'] ?? '';
        $phrase = trim($_POST['phrase'] ?? '');
        $author = trim($_POST['author'] ?? '');

        if (empty($phrase)) {
            $errorMsg = "La frase no puede estar vacía.";
        } else {
            if ($action == 'add') {
                $stmt = $pdo->prepare("INSERT INTO quotes (phrase, author) VALUES (?, ?)");
                $stmt->execute([$phrase, $author]);
                $msg = "Cita creada correctamente.";
                $action = 'list';
            } else {
                $stmt = $pdo->prepare("UPDATE quotes SET phrase = ?, author = ? WHERE id = ?");
                $stmt->execute([$phrase, $author, $id]);
                $msg = "Cita actualizada correctamente.";
                $action = 'list';
            }
        }
    }
}

// ELIMINAR CITA
if ($action == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM quotes WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Cita eliminada correctamente.";
    $action = 'list';
}

adminHeader("Gestión de Citas Ilustres");
?>

<style>
    .search-container {
        display: flex;
        gap: 10px;
        margin-bottom: 1.5rem;
    }
    .search-input {
        flex: 1;
        padding: 0.8rem 1.2rem;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-family: inherit;
        font-size: 0.9rem;
    }
    .search-input:focus {
        outline: none;
        border-color: var(--primary);
    }
    .pagination-container {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 5px;
        margin-top: 2rem;
    }
    .page-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 38px;
        height: 38px;
        padding: 0 10px;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        color: var(--text);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    .page-link:hover {
        background-color: var(--gray-100);
        border-color: var(--gray-300);
    }
    .page-link.active {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    .page-link.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        pointer-events: none;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--primary);
    }
    .form-control {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-family: inherit;
        font-size: 0.95rem;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
    }
</style>

<?php if ($msg): ?>
    <div class="card" style="background: #e2f0d9; color: #385723; padding: 1rem; margin-bottom: 2rem; border-top: 4px solid #385723;">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
    </div>
<?php endif; ?>

<?php if ($errorMsg): ?>
    <div class="card" style="background: #fce4d6; color: #c65911; padding: 1rem; margin-bottom: 2rem; border-top: 4px solid #c65911;">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMsg); ?>
    </div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    <?php
    // Obtener parámetros de búsqueda y paginación
    $search = trim($_GET['q'] ?? '');
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 30;
    $offset = ($page - 1) * $limit;

    // Construir la consulta con o sin filtro
    if ($search !== '') {
        $countQuery = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE phrase LIKE ? OR author LIKE ?");
        $countQuery->execute(["%$search%", "%$search%"]);
        $totalCount = $countQuery->fetchColumn();

        $dataQuery = $pdo->prepare("SELECT * FROM quotes WHERE phrase LIKE ? OR author LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?");
        // En PDO con EMULATE_PREPARES = false, LIMIT y OFFSET deben pasarse como enteros o con bindValue
        $dataQuery->bindValue(1, "%$search%", PDO::PARAM_STR);
        $dataQuery->bindValue(2, "%$search%", PDO::PARAM_STR);
        $dataQuery->bindValue(3, (int)$limit, PDO::PARAM_INT);
        $dataQuery->bindValue(4, (int)$offset, PDO::PARAM_INT);
        $dataQuery->execute();
        $quotes = $dataQuery->fetchAll();
    } else {
        $totalCount = $pdo->query("SELECT COUNT(*) FROM quotes")->fetchColumn();

        $dataQuery = $pdo->prepare("SELECT * FROM quotes ORDER BY id DESC LIMIT ? OFFSET ?");
        $dataQuery->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $dataQuery->bindValue(2, (int)$offset, PDO::PARAM_INT);
        $dataQuery->execute();
        $quotes = $dataQuery->fetchAll();
    }

    $totalPages = ceil($totalCount / $limit);
    ?>

    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <h3>Listado de Citas Ilustres (Total: <?php echo $totalCount; ?>)</h3>
            <a href="?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Nueva Cita</a>
        </div>

        <form method="GET" class="search-container">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar por texto de cita o autor..." class="search-input">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Buscar</button>
            <?php if ($search !== ''): ?>
                <a href="quotes.php" class="btn"><i class="fas fa-times"></i> Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (empty($quotes)): ?>
            <p style="text-align: center; color: var(--gray-300); padding: 2rem 0;">No se encontraron citas.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Cita</th>
                            <th style="width: 250px;">Autor</th>
                            <th style="width: 150px; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotes as $q): ?>
                            <tr>
                                <td><?php echo $q['id']; ?></td>
                                <td>
                                    <span style="font-style: italic; color: var(--text);">
                                        "<?php echo htmlspecialchars($q['phrase']); ?>"
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($q['author'] ?? 'Anónimo'); ?></strong>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="?action=edit&id=<?php echo $q['id']; ?>" class="btn btn-sm btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-edit"></i></a>
                                    <a href="?action=delete&id=<?php echo $q['id']; ?>" class="btn btn-sm" style="color:red; padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="return confirm('¿Seguro que deseas eliminar esta cita?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <?php
                    // Rango de páginas a mostrar
                    $range = 2;
                    $startPage = max(1, $page - $range);
                    $endPage = min($totalPages, $page + $range);

                    // Parámetro de búsqueda
                    $searchParam = $search !== '' ? '&q=' . urlencode($search) : '';
                    ?>

                    <!-- Anterior -->
                    <a href="?page=<?php echo $page - 1; ?><?php echo $searchParam; ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i></a>

                    <!-- Primera Página -->
                    <?php if ($startPage > 1): ?>
                        <a href="?page=1<?php echo $searchParam; ?>" class="page-link">1</a>
                        <?php if ($startPage > 2): ?>
                            <span style="padding: 0 5px; color: var(--gray-300);">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Páginas Intermedias -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $searchParam; ?>" class="page-link <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <!-- Última Página -->
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span style="padding: 0 5px; color: var(--gray-300);">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $totalPages; ?><?php echo $searchParam; ?>" class="page-link"><?php echo $totalPages; ?></a>
                    <?php endif; ?>

                    <!-- Siguiente -->
                    <a href="?page=<?php echo $page + 1; ?><?php echo $searchParam; ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<?php elseif ($action == 'add' || $action == 'edit'): ?>
    <?php
    $id = '';
    $phrase = '';
    $author = '';
    $titleText = "Nueva Cita Ilustre";

    if ($action == 'edit' && isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM quotes WHERE id = ?");
        $stmt->execute([$id]);
        $quote = $stmt->fetch();
        if ($quote) {
            $phrase = $quote['phrase'];
            $author = $quote['author'];
            $titleText = "Editar Cita Ilustre (ID: $id)";
        }
    }
    ?>

    <div class="card" style="max-width: 700px; margin: 0 auto 2rem auto;">
        <h3><?php echo htmlspecialchars($titleText); ?></h3>
        <br>
        <form method="POST" action="?action=<?php echo $action; ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            
            <div class="form-group">
                <label for="phrase">Frase / Cita</label>
                <textarea id="phrase" name="phrase" rows="5" class="form-control" required><?php echo htmlspecialchars($phrase); ?></textarea>
            </div>

            <div class="form-group">
                <label for="author">Autor</label>
                <input type="text" id="author" name="author" class="form-control" value="<?php echo htmlspecialchars($author); ?>" placeholder="Ej: Confucio (551-479 a. C.); filósofo chino">
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cita</button>
                <a href="quotes.php" class="btn"><i class="fas fa-arrow-left"></i> Volver al listado</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php adminFooter(); ?>
