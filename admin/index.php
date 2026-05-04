<?php
// admin/index.php
require_once 'inc/auth.php';
checkAuth();
require_once '../config.php';
require_once 'inc/layout.php';

$pdo = getDB();

// Basic stats
$catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$pageCount = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$imgCount = $pdo->query("SELECT COUNT(*) FROM page_images")->fetchColumn();

adminHeader("Dashboard");
?>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
    <div class="card stat-card">
        <h3><i class="fas fa-folder-tree" style="color: var(--primary);"></i> Categorías</h3>
        <p style="font-size: 2.5rem; font-weight: 700;"><?php echo $catCount; ?></p>
        <span class="badge badge-info">Estructura jerárquica</span>
    </div>
    <div class="card stat-card">
        <h3><i class="fas fa-file-alt" style="color: var(--primary);"></i> Páginas</h3>
        <p style="font-size: 2.5rem; font-weight: 700;"><?php echo $pageCount; ?></p>
        <span class="badge badge-info">Contenido importado</span>
    </div>
    <div class="card stat-card">
        <h3><i class="fas fa-images" style="color: var(--primary);"></i> Imágenes</h3>
        <p style="font-size: 2.5rem; font-weight: 700;"><?php echo $imgCount; ?></p>
        <span class="badge badge-info">Galería multimedia</span>
    </div>
</div>

<div class="card" style="margin-top: 3rem;">
    <h3>Últimas Páginas Importadas</h3>
    <table>
        <thead>
            <tr>
                <th>Título</th>
                <th>Categoría</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT p.*, c.name as cat_name FROM pages p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 5");
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>{$row['title']}</td>";
                echo "<td><span class='badge badge-info'>{$row['cat_name']}</span></td>";
                echo "<td>{$row['created_at']}</td>";
                echo "<td><a href='pages.php?action=edit&id={$row['id']}' class='btn btn-primary btn-sm'>Editar</a></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php adminFooter(); ?>
