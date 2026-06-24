<?php
// alojamientos.php - Página pública de Alojamientos de Moratalla
require_once 'config.php';
$pdo = getDB();

// --- API interna para AJAX de galerías ---
if (isset($_GET['api']) && $_GET['api'] === 'galeria' && isset($_GET['id'])) {
    $aid = (int)$_GET['id'];
    $nombre = $pdo->prepare("SELECT nombre FROM alojamientos WHERE id = ? AND is_visible = 1");
    $nombre->execute([$aid]);
    $alojamientoNombre = $nombre->fetchColumn();

    $imgs = $pdo->prepare("SELECT image_path, caption, is_video FROM alojamiento_images WHERE alojamiento_id = ? AND is_visible = 1 ORDER BY sort_order ASC, id ASC");
    $imgs->execute([$aid]);
    $result = $imgs->fetchAll();
    $out = array_map(fn($i) => ['src' => $i['image_path'], 'caption' => $i['caption'], 'nombre' => $alojamientoNombre, 'is_video' => (int)$i['is_video']], $result);
    header('Content-Type: application/json');
    echo json_encode($out);
    exit;
}

// Conteo de visitas global (sesión)
if (!isset($_SESSION['visited_alojamientos'])) {
    $_SESSION['visited_alojamientos'] = false;
}
if (!$_SESSION['visited_alojamientos']) {
    $_SESSION['visited_alojamientos'] = true;
}

// Filtros opcionales
$filtro_poblacion = isset($_GET['poblacion']) ? trim($_GET['poblacion']) : '';
$filtro_buscar    = isset($_GET['buscar'])    ? trim($_GET['buscar'])    : '';

// Obtener listado
$where = ['a.is_visible = 1'];
$params = [];

if ($filtro_poblacion) {
    $where[] = 'a.poblacion = ?';
    $params[] = $filtro_poblacion;
}
if ($filtro_buscar) {
    $where[] = '(a.nombre LIKE ? OR a.calle LIKE ? OR a.poblacion LIKE ?)';
    $params[] = '%' . $filtro_buscar . '%';
    $params[] = '%' . $filtro_buscar . '%';
    $params[] = '%' . $filtro_buscar . '%';
}

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT a.*,
           (SELECT image_path FROM alojamiento_images ai WHERE ai.alojamiento_id = a.id AND ai.is_cover = 1 AND ai.is_visible = 1 LIMIT 1) as cover_image,
           (SELECT COUNT(*) FROM alojamiento_images ai2 WHERE ai2.alojamiento_id = a.id AND ai2.is_visible = 1) as total_images
    FROM alojamientos a
    $whereSQL
    ORDER BY a.sort_order ASC, a.nombre ASC
");
$stmt->execute($params);
$alojamientos = $stmt->fetchAll();

// Obtener poblaciones únicas para el filtro
$poblaciones = $pdo->query("SELECT DISTINCT poblacion FROM alojamientos WHERE is_visible = 1 ORDER BY poblacion ASC")->fetchAll(PDO::FETCH_COLUMN);

// SEO
$pageTitle = 'Dónde Dormir - Alojamientos en Moratalla';
$pageDescription = 'Encuentra casas rurales, hostales, hoteles y campings en Moratalla y sus pedanías. Disfruta de una estancia inolvidable en el corazón de la naturaleza.';

require_once 'inc/header.php';
?>

<!-- HERO -->
<section class="hero-page" style="background: linear-gradient(135deg, rgba(27,67,50,0.85) 0%, rgba(8,28,21,0.95) 100%), url('uploads/theme/moratalla.jpg'); background-size: cover; background-position: center; background-attachment: fixed; padding: 3rem 0; text-align: center; color: white; border-bottom: 4px solid var(--accent); margin-bottom: 0;">
    <div class="container">
        <div style="background: rgba(255,255,255,0.05); backdrop-filter: blur(15px); padding: 1.5rem 3rem; border-radius: 15px; display: inline-block; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <p style="opacity: 0.9; margin-bottom: 0.3rem; color: var(--accent); font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px;"><i class="fas fa-bed" style="color: var(--accent);"></i> Dónde Dormir</p>
            <h1 style="color: white; font-size: 2.2rem; font-weight: 800; text-shadow: 0 2px 10px rgba(0,0,0,0.3); letter-spacing: -0.5px;">Alojamientos</h1>
            <p style="color: rgba(255,255,255,0.8); margin-top: 0.5rem; font-size: 1rem;">
                <?php echo count($alojamientos); ?> opciones de alojamiento en Moratalla y sus pedanías
            </p>
        </div>
    </div>
</section>

<div class="container" style="margin-top: 2rem;">
    <a href="index.php" class="btn-nav btn-nav-back btn-nav-sm" style="box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <i class="fas fa-arrow-left"></i> Inicio
    </a>
</div>

<!-- AVISO PROPIETARIOS -->
<div class="container" style="margin-top: 1.5rem; margin-bottom: 0.5rem;">
    <div style="background: #e8f5e9; border-left: 4px solid var(--primary); padding: 1rem 1.5rem; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="fas fa-info-circle" style="color: var(--primary); font-size: 1.2rem;"></i>
            <p style="margin: 0; color: #1b4332; font-size: 0.95rem; font-weight: 500;">
                ¿Eres el propietario de algún establecimiento y necesitas realizar cambios, corregir datos o solicitar su eliminación?
            </p>
        </div>
        <a href="contacto.php" class="btn-nav btn-nav-sm" style="background: var(--primary); color: white; border: none; font-weight: 600; text-decoration: none; padding: 0.5rem 1.2rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.5rem; transition: background 0.2s; box-shadow: 0 2px 6px rgba(27,67,50,0.2);">
            <i class="fas fa-envelope"></i> Contactar
        </a>
    </div>
</div>

<!-- FILTROS -->
<div class="container" style="margin-top: 1.5rem;">
    <div style="background: white; border-radius: 14px; padding: 1.5rem 2rem; box-shadow: 0 2px 12px rgba(0,0,0,0.07); display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        <form method="GET" action="alojamientos.php" style="display: flex; flex-wrap: wrap; gap: 1rem; width: 100%; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; font-weight: 600; color: #555; display: block; margin-bottom: 0.4rem;">
                    <i class="fas fa-search"></i> Buscar
                </label>
                <input type="text" name="buscar" value="<?php echo htmlspecialchars($filtro_buscar); ?>" placeholder="Nombre, pedanía, dirección..." style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem;">
            </div>
            <div style="min-width: 180px;">
                <label style="font-size: 0.85rem; font-weight: 600; color: #555; display: block; margin-bottom: 0.4rem;">
                    <i class="fas fa-map-marker-alt"></i> Población
                </label>
                <select name="poblacion" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; background: white;">
                    <option value="">Todas las localidades</option>
                    <?php foreach ($poblaciones as $pob): ?>
                        <option value="<?php echo htmlspecialchars($pob); ?>" <?php echo ($filtro_poblacion === $pob ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($pob); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" style="padding: 0.75rem 1.5rem; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.95rem;">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <?php if ($filtro_poblacion || $filtro_buscar): ?>
                    <a href="alojamientos.php" style="padding: 0.75rem 1.2rem; background: #f3f4f6; color: #555; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.95rem; display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- LISTADO -->
<div class="container" style="margin-top: 2rem; padding-bottom: 4rem;">

    <?php if (count($alojamientos) === 0): ?>
        <div style="text-align: center; padding: 4rem 2rem; color: #888;">
            <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; display: block; color: #ccc;"></i>
            <h3 style="margin-bottom: 0.5rem;">No se encontraron alojamientos</h3>
            <p>Prueba a cambiar los filtros de búsqueda.</p>
            <a href="alojamientos.php" class="btn-nav btn-nav-back" style="margin-top: 1rem;">Ver todos</a>
        </div>
    <?php else: ?>

    <!-- Grid de tarjetas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 2rem;">

        <?php foreach ($alojamientos as $a):
            $cover = $a['cover_image'] ?? null;
            $tel1  = $a['telefono1'] ?? null;
            $tel2  = $a['telefono2'] ?? null;
        ?>

        <div class="rest-card" id="aloj-<?php echo $a['id']; ?>" style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 3px 16px rgba(0,0,0,0.08); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column;">

            <!-- Portada / Cabecera -->
            <div style="position: relative; height: 180px; background: linear-gradient(135deg, #1b4332, #081c15); overflow: hidden; cursor: <?php echo ($a['total_images'] > 0 ? 'pointer' : 'default'); ?>;"
                 <?php if ($a['total_images'] > 0): ?>onclick="abrirGaleria(<?php echo $a['id']; ?>)" title="Ver galería de fotos"<?php endif; ?>>
                <?php if ($cover): ?>
                    <img src="<?php echo htmlspecialchars($cover); ?>" alt="<?php echo htmlspecialchars($a['nombre']); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;" class="rest-cover-img">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hotel" style="font-size: 3.5rem; color: rgba(255,255,255,0.2);"></i>
                    </div>
                <?php endif; ?>

                <!-- Badge pedanía -->
                <?php if ($a['es_pedania']): ?>
                <span style="position: absolute; top: 12px; left: 12px; background: var(--accent); color: white; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                    <i class="fas fa-map-marker-alt"></i> Pedanía
                </span>
                <?php endif; ?>

                <!-- Badge fotos -->
                <?php if ($a['total_images'] > 0): ?>
                <span style="position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); color: white; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; backdrop-filter: blur(4px);">
                    <i class="fas fa-camera"></i> <?php echo $a['total_images']; ?> fotos
                </span>
                <?php endif; ?>
            </div>

            <!-- Cuerpo -->
            <div style="padding: 1.25rem 1.5rem; flex: 1; display: flex; flex-direction: column; gap: 0.7rem;">
                <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin: 0; line-height: 1.3;">
                    <?php echo htmlspecialchars($a['nombre']); ?>
                </h2>

                <!-- Localización -->
                <div style="display: flex; align-items: flex-start; gap: 0.5rem; color: #555; font-size: 0.9rem;">
                    <i class="fas fa-map-marker-alt" style="color: var(--accent); margin-top: 2px; flex-shrink: 0;"></i>
                    <span>
                        <?php if ($a['calle']): ?><?php echo htmlspecialchars($a['calle']); ?><br><?php endif; ?>
                        <strong><?php echo htmlspecialchars($a['poblacion']); ?></strong>
                        <?php if ($a['codigo_postal']): ?> · CP <?php echo htmlspecialchars($a['codigo_postal']); ?><?php endif; ?>
                    </span>
                </div>

                <!-- Teléfonos y Email -->
                <?php if ($tel1 || !empty($a['email'])): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; font-size: 0.9rem; align-items: center;">
                    <?php if ($tel1): ?>
                    <a href="tel:<?php echo preg_replace('/\s/', '', $tel1); ?>" style="color: #1b4332; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-phone" style="color: var(--accent);"></i> <?php echo htmlspecialchars($tel1); ?>
                    </a>
                    <?php if ($tel2): ?>
                    <a href="tel:<?php echo preg_replace('/\s/', '', $tel2); ?>" style="color: #1b4332; text-decoration: none; font-weight: 600;">
                        · <?php echo htmlspecialchars($tel2); ?>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($a['email'])): ?>
                        <?php if ($tel1): ?><span style="color: #ccc;">|</span><?php endif; ?>
                        <a href="mailto:<?php echo htmlspecialchars($a['email']); ?>" style="color: #1b4332; text-decoration: none; font-weight: 600;" title="Enviar correo electrónico">
                            <i class="fas fa-envelope" style="color: var(--accent);"></i> <?php echo htmlspecialchars($a['email']); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Descripción (si tiene) -->
                <?php if (!empty($a['descripcion'])): ?>
                <p style="font-size: 0.88rem; color: #666; line-height: 1.5; margin: 0; flex: 1;">
                    <?php echo nl2br(htmlspecialchars(mb_substr($a['descripcion'], 0, 180))); ?>
                    <?php echo mb_strlen($a['descripcion']) > 180 ? '...' : ''; ?>
                </p>
                <?php endif; ?>

                <!-- Espaciador -->
                <div style="flex: 1;"></div>

                <!-- Botones de acción -->
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid #f0f0f0;">

                    <?php if ($a['gmap_url']): ?>
                    <a href="<?php echo htmlspecialchars($a['gmap_url']); ?>" target="_blank" rel="noopener" class="rest-btn rest-btn-map" title="Ver en Google Maps">
                        <i class="fas fa-map-marked-alt"></i> Mapa
                    </a>
                    <?php endif; ?>

                    <?php if ($a['web']): ?>
                    <a href="<?php echo htmlspecialchars($a['web']); ?>" target="_blank" rel="noopener" class="rest-btn rest-btn-web" title="Visitar web oficial">
                        <i class="fas fa-globe"></i> Web
                    </a>
                    <?php endif; ?>

                    <?php if ($a['facebook']): ?>
                    <a href="<?php echo htmlspecialchars($a['facebook']); ?>" target="_blank" rel="noopener" class="rest-btn rest-btn-fb" title="Ver en Facebook">
                        <i class="fab fa-facebook"></i> Facebook
                    </a>
                    <?php endif; ?>

                    <?php if ($a['tripadvisor']): ?>
                    <a href="<?php echo htmlspecialchars($a['tripadvisor']); ?>" target="_blank" rel="noopener" class="rest-btn rest-btn-ta" title="Ver en TripAdvisor">
                        <i class="fas fa-star"></i> TripAdvisor
                    </a>
                    <?php endif; ?>

                    <?php if ($a['total_images'] > 0): ?>
                    <button onclick="abrirGaleria(<?php echo $a['id']; ?>)" class="rest-btn rest-btn-gallery" title="Ver galería de fotos">
                        <i class="fas fa-images"></i> Fotos
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL GALERÍA -->
<div id="galeria-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:9999; background:rgba(0,0,0,0.95); flex-direction:column; align-items:center; justify-content:center;">
    <!-- Cerrar -->
    <div style="width:100%; padding:1.2rem 1.5rem; display:flex; justify-content:space-between; align-items:center; position:absolute; top:0; z-index:10000;">
        <div id="galeria-titulo" style="color:white; font-size:1.1rem; font-weight:700;"></div>
        <button onclick="cerrarGaleria()" style="background:#e53935; color:white; border-radius:50px; font-weight:bold; border:none; cursor:pointer; font-size:1rem; padding:0.7rem 1.4rem; box-shadow:0 4px 15px rgba(229,57,53,0.4);">
            <i class="fas fa-times"></i> Cerrar
        </button>
    </div>
    <!-- Navegación -->
    <button id="galeria-prev" onclick="galeriaNav(-1)" style="position:absolute; left:1.5rem; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.1); color:white; border:none; width:55px; height:55px; border-radius:50%; font-size:1.4rem; cursor:pointer; z-index:10001; display:flex; align-items:center; justify-content:center; transition:background 0.3s;">❮</button>
    <button id="galeria-next" onclick="galeriaNav(1)"  style="position:absolute; right:1.5rem; top:50%; transform:translateY(-50%); background:rgba(255,255,255,0.1); color:white; border:none; width:55px; height:55px; border-radius:50%; font-size:1.4rem; cursor:pointer; z-index:10001; display:flex; align-items:center; justify-content:center; transition:background 0.3s;">❯</button>
    <!-- Imagen / Vídeo -->
    <div style="max-width:95%; max-height:90vh; display:flex; flex-direction:column; justify-content:center; align-items:center; position:relative; width: 100%;">
        <img id="galeria-img" src="" style="max-width:100%; max-height:78vh; border-radius:10px; box-shadow:0 10px 40px rgba(0,0,0,0.7); transition:opacity 0.3s ease;">
        <video id="galeria-video" src="" style="display:none; max-width:100%; max-height:78vh; border-radius:10px; box-shadow:0 10px 40px rgba(0,0,0,0.7); transition:opacity 0.3s ease; background:#000;" controls preload="metadata"></video>
        <div id="galeria-caption" style="color:white; margin-top:1rem; font-size:1rem; text-align:center; max-width:700px; text-shadow:0 2px 4px rgba(0,0,0,0.8); min-height:1.5rem;"></div>
        <div id="galeria-counter" style="color:rgba(255,255,255,0.5); font-size:0.85rem; margin-top:0.5rem;"></div>
    </div>
</div>

<style>
/* ---- Tarjetas ---- */
.rest-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.14);
}
.rest-card:hover .rest-cover-img {
    transform: scale(1.05);
}

/* ---- Botones inline ---- */
.rest-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 13px; border-radius: 20px; font-size: 0.8rem;
    font-weight: 600; text-decoration: none; border: none;
    cursor: pointer; transition: all 0.25s ease; white-space: nowrap;
}
.rest-btn:hover { transform: translateY(-1px); filter: brightness(1.1); }

.rest-btn-map     { background:#e8f5e9; color:#2e7d32; }
.rest-btn-web     { background:#e3f2fd; color:#1565c0; }
.rest-btn-fb      { background:#e8eaf6; color:#283593; }
.rest-btn-ta      { background:#fff3e0; color:#e65100; }
.rest-btn-gallery { background:#f3e5f5; color:#6a1b9a; }

/* ---- Modal galería ---- */
#galeria-prev:hover, #galeria-next:hover { background: rgba(255,255,255,0.25); }

/* ---- Responsive ---- */
@media (max-width: 600px) {
    .hero-page h1 { font-size: 1.6rem; }
}
</style>

<script>
// Datos de galerías (cargados por AJAX en demanda)
const galerias = {};
let galeriaActual = null;
let galeriaIdx = 0;
let galeriaTimer = null;

async function abrirGaleria(id) {
    if (!galerias[id]) {
        // Cargar imágenes por AJAX
        const resp = await fetch('alojamientos.php?api=galeria&id=' + id);
        const data = await resp.json();
        galerias[id] = data;
    }
    galeriaActual = id;
    galeriaIdx = 0;
    document.getElementById('galeria-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    mostrarImagen();
}

function cerrarGaleria() {
    document.getElementById('galeria-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    clearInterval(galeriaTimer);
    const vid = document.getElementById('galeria-video');
    if (vid) {
        vid.pause();
        vid.style.display = 'none';
    }
    galeriaActual = null;
}

function mostrarImagen() {
    const items = galerias[galeriaActual];
    if (!items || items.length === 0) return;
    const item = items[galeriaIdx];
    const img = document.getElementById('galeria-img');
    const vid = document.getElementById('galeria-video');
    
    const isVideo = parseInt(item.is_video) === 1 || ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', '3gp'].includes(item.src.split('.').pop().toLowerCase());

    if (isVideo) {
        clearInterval(galeriaTimer); // Detener autoplay si es un vídeo
    } else {
        reiniciarAutoplay(); // Autoplay para imágenes
    }

    if (isVideo) {
        if (img) img.style.display = 'none';
        if (vid) {
            vid.src = item.src;
            vid.style.display = 'block';
            vid.style.opacity = 0;
            vid.autoplay = true;
            vid.load();
            vid.onended = function() {
                galeriaNav(1);
            };
        }
        setTimeout(() => {
            if (vid) vid.style.opacity = 1;
            document.getElementById('galeria-caption').textContent = item.caption || '';
            document.getElementById('galeria-counter').textContent = (galeriaIdx + 1) + ' / ' + items.length;
            document.getElementById('galeria-titulo').textContent = item.nombre || '';
        }, 150);
    } else {
        if (vid) {
            vid.pause();
            vid.style.display = 'none';
        }
        if (img) {
            img.style.display = 'block';
            img.style.opacity = 0;
        }
        setTimeout(() => {
            if (img) {
                img.src = item.src;
                img.style.opacity = 1;
            }
            document.getElementById('galeria-caption').textContent = item.caption || '';
            document.getElementById('galeria-counter').textContent = (galeriaIdx + 1) + ' / ' + items.length;
            document.getElementById('galeria-titulo').textContent = item.nombre || '';
        }, 150);
    }
}

function galeriaNav(dir) {
    const items = galerias[galeriaActual];
    if (!items) return;
    galeriaIdx = (galeriaIdx + dir + items.length) % items.length;
    mostrarImagen();
}

function iniciarAutoplay() {
    galeriaTimer = setInterval(() => galeriaNav(1), 5000);
}
function reiniciarAutoplay() {
    clearInterval(galeriaTimer);
    iniciarAutoplay();
}

document.addEventListener('keydown', e => {
    if (!galeriaActual) return;
    if (e.key === 'ArrowRight') galeriaNav(1);
    if (e.key === 'ArrowLeft')  galeriaNav(-1);
    if (e.key === 'Escape')     cerrarGaleria();
});
document.getElementById('galeria-modal').addEventListener('click', e => {
    if (e.target === document.getElementById('galeria-modal')) cerrarGaleria();
});
</script>

<?php
require_once 'inc/footer.php';
?>
