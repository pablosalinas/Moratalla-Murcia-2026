<?php
/**
 * fix_local_db.php
 * ─────────────────────────────────────────────────────────────────────────────
 * Repara columnas faltantes en la base de datos LOCAL.
 * ⚠️  Solo para entorno de desarrollo. NO ejecutar en producción.
 *
 * Uso: http://localhost/Moratalla-Murcia-2026/fix_local_db.php
 * ─────────────────────────────────────────────────────────────────────────────
 */

require_once 'config.php';
$pdo = getDB();
$log = [];

function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition, array &$log): void {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            $log[] = "✅ Columna `$table`.`$column` añadida.";
        } else {
            $log[] = "ℹ️  Columna `$table`.`$column` ya existía. Sin cambios.";
        }
    } catch (PDOException $e) {
        $log[] = "❌ Error en `$table`.`$column`: " . $e->getMessage();
    }
}

function createTableIfMissing(PDO $pdo, string $table, string $sql, array &$log): void {
    try {
        $pdo->exec($sql);
        $log[] = "✅ Tabla `$table` verificada / creada.";
    } catch (PDOException $e) {
        $log[] = "❌ Error creando `$table`: " . $e->getMessage();
    }
}

// ─── categories ───────────────────────────────────────────────────────────
addColumnIfMissing($pdo, 'categories', 'is_visible',  'TINYINT(1) DEFAULT 1', $log);
addColumnIfMissing($pdo, 'categories', 'show_hint',   'TINYINT(1) DEFAULT 0', $log);
addColumnIfMissing($pdo, 'categories', 'hint_text',   'VARCHAR(255) DEFAULT NULL', $log);

// ─── pages ────────────────────────────────────────────────────────────────
addColumnIfMissing($pdo, 'pages', 'is_visible',  'TINYINT(1) DEFAULT 1', $log);
addColumnIfMissing($pdo, 'pages', 'sort_order',  'INT DEFAULT 0', $log);
addColumnIfMissing($pdo, 'pages', 'views',       'INT DEFAULT 0', $log);

// ─── page_images ──────────────────────────────────────────────────────────
addColumnIfMissing($pdo, 'page_images', 'is_visible',  'TINYINT(1) DEFAULT 1', $log);
addColumnIfMissing($pdo, 'page_images', 'sort_order',  'INT DEFAULT 0', $log);
addColumnIfMissing($pdo, 'page_images', 'caption',     'TEXT DEFAULT NULL', $log);
addColumnIfMissing($pdo, 'page_images', 'is_cover',    'TINYINT(1) DEFAULT 0', $log);

// ─── external_links ───────────────────────────────────────────────────────
createTableIfMissing($pdo, 'external_links', "
    CREATE TABLE IF NOT EXISTS `external_links` (
        `id`               INT AUTO_INCREMENT PRIMARY KEY,
        `category_id`      INT DEFAULT NULL,
        `title`            VARCHAR(255) NOT NULL,
        `url`              VARCHAR(500) NOT NULL,
        `show_in_category` TINYINT(1) DEFAULT 1,
        `is_visible`       TINYINT(1) DEFAULT 1,
        `sort_order`       INT DEFAULT 0,
        `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
", $log);

// ─── banners ──────────────────────────────────────────────────────────────
createTableIfMissing($pdo, 'banners', "
    CREATE TABLE IF NOT EXISTS `banners` (
        `id`         INT AUTO_INCREMENT PRIMARY KEY,
        `title`      VARCHAR(255) DEFAULT NULL,
        `image_path` VARCHAR(500) NOT NULL,
        `is_active`  TINYINT(1) DEFAULT 1,
        `sort_order` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
", $log);

// ─── quotes ───────────────────────────────────────────────────────────────
createTableIfMissing($pdo, 'quotes', "
    CREATE TABLE IF NOT EXISTS `quotes` (
        `id`        INT AUTO_INCREMENT PRIMARY KEY,
        `phrase`    TEXT NOT NULL,
        `author`    VARCHAR(255) DEFAULT NULL,
        `is_active` TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
", $log);

// ─── restaurantes ─────────────────────────────────────────────────────────
createTableIfMissing($pdo, 'restaurantes', "
    CREATE TABLE IF NOT EXISTS `restaurantes` (
        `id`            INT AUTO_INCREMENT PRIMARY KEY,
        `nombre`        VARCHAR(255) NOT NULL,
        `calle`         VARCHAR(255) DEFAULT NULL,
        `poblacion`     VARCHAR(100) DEFAULT NULL,
        `es_pedania`    TINYINT(1) DEFAULT 0,
        `municipio`     VARCHAR(100) DEFAULT 'Moratalla',
        `provincia`     VARCHAR(100) DEFAULT 'Murcia',
        `codigo_postal` VARCHAR(10) DEFAULT NULL,
        `telefono1`     VARCHAR(30) DEFAULT NULL,
        `telefono2`     VARCHAR(30) DEFAULT NULL,
        `web`           VARCHAR(500) DEFAULT NULL,
        `facebook`      VARCHAR(500) DEFAULT NULL,
        `tripadvisor`   VARCHAR(500) DEFAULT NULL,
        `gmap_url`      VARCHAR(500) DEFAULT NULL,
        `descripcion`   TEXT DEFAULT NULL,
        `is_visible`    TINYINT(1) DEFAULT 1,
        `sort_order`    INT DEFAULT 0,
        `views`         INT DEFAULT 0,
        `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
", $log);

createTableIfMissing($pdo, 'restaurante_images', "
    CREATE TABLE IF NOT EXISTS `restaurante_images` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `restaurante_id` INT NOT NULL,
        `image_path`     VARCHAR(500) NOT NULL,
        `caption`        TEXT DEFAULT NULL,
        `is_cover`       TINYINT(1) DEFAULT 0,
        `is_visible`     TINYINT(1) DEFAULT 1,
        `sort_order`     INT DEFAULT 0,
        `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
", $log);

// ─── Insertar datos de restaurantes si la tabla está vacía ────────────────
$countRest = (int)$pdo->query("SELECT COUNT(*) FROM restaurantes")->fetchColumn();
if ($countRest === 0) {
    $datos = [
        ['Restaurante Alhárabe – Camping La Puerta','Camping La Puerta, km 8','La Puerta',1,'Moratalla','Murcia','30440','968730008',NULL,'https://www.campinglapuerta.com/restaurante.html',NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+Alharabe+Camping+La+Puerta+Moratalla+Murcia',1,10],
        ['Restaurante El Cortijo','Ctra. De Nerpio – Campo de San Juan','Campo de San Juan',1,'Moratalla','Murcia','30441','678770082',NULL,NULL,'https://www.facebook.com/Restaurante-El-cortijo-campo-de-san-juan-103949274526103/',NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+El+Cortijo+Campo+San+Juan+Moratalla+Murcia',1,20],
        ['Restaurante Casa Pernías','MU-702, km 23','Moratalla',0,'Moratalla','Murcia','30441','968726311',NULL,'https://casapernias.com/',NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+Casa+Pernias+Moratalla+Murcia',1,30],
        ['Bar Restaurante La Terraza','C/ Iglesia, 5','El Sabinar',1,'Moratalla','Murcia','30440','968738052',NULL,NULL,NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Bar+Restaurante+La+Terraza+El+Sabinar+Moratalla',1,40],
        ['Bar El Jardín','Plaza de la Ventas, 1','El Sabinar',1,'Moratalla','Murcia','30440','649050127',NULL,NULL,'https://www.facebook.com/p/Bar-jard%C3%ADnSabinar-100064168687697/',NULL,'https://www.google.com/maps/search/?api=1&query=Bar+El+Jardin+El+Sabinar+Moratalla+Murcia',1,50],
        ['Bar-Restaurante Piñero','Callejón Escalera','Benizar',1,'Moratalla','Murcia','30442','616369258',NULL,NULL,'https://www.facebook.com/PineroBenizar',NULL,'https://www.google.com/maps/search/?api=1&query=Bar+Restaurante+Pinero+Benizar+Moratalla+Murcia',1,60],
        ['Terraza Revolcadores','Calle Cementerio','Cañada de la Cruz',1,'Moratalla','Murcia','30414','660797539',NULL,NULL,'https://www.facebook.com/terrazarevolcadores/?locale=es_ES',NULL,'https://www.google.com/maps/search/?api=1&query=Terraza+Revolcadores+Canada+de+la+Cruz+Moratalla+Murcia',1,70],
        ['Restaurante El Nogal','Inazares','Inazares',1,'Moratalla','Murcia','30413','968736379',NULL,NULL,'https://www.facebook.com/elnogal.inazares/',NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+El+Nogal+Inazares+Moratalla+Murcia',1,80],
        ['Bar Alhárabe','Ctra. Campo de San Juan','Alhárabe',1,'Moratalla','Murcia','30440','680461024',NULL,NULL,NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Bar+Alharabe+Moratalla+Murcia',1,90],
        ['Bar Los Cazadores','Ctra. Calasparra, 40','Moratalla',0,'Moratalla','Murcia','30440','641197477',NULL,NULL,NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Bar+Los+Cazadores+Moratalla+Murcia',1,100],
        ['Mesón La Farola','C. Mayor, 66','Moratalla',0,'Moratalla','Murcia','30440','617887817',NULL,NULL,NULL,NULL,'https://maps.app.goo.gl/GFazWRtUHKYdq19r7',1,110],
        ['Retiro Gastro – Rural','Ctra. La Puerta, km 2','Moratalla',0,'Moratalla','Murcia','30440','619109424',NULL,NULL,NULL,NULL,'https://maps.app.goo.gl/6e7WFg2gYnt3jRvz5',1,120],
        ['Bombay Kebab','Ctra. San Juan, 6','Moratalla',0,'Moratalla','Murcia','30440','968706147',NULL,NULL,NULL,NULL,'https://maps.app.goo.gl/cS6orUJfMEWDwFVs8',1,130],
        ['Bar – Salón Social – Calar de la Santa','Calar de la Santa','Calar de la Santa',1,'Moratalla','Murcia','30440','649418873',NULL,NULL,NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Calar+de+la+Santa+Moratalla+Murcia',1,140],
        ['Restaurante Albergue La Pava','Caserio La Pava, 5','La Pava',1,'Moratalla','Murcia','30440','616055547',NULL,NULL,NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+Albergue+La+Pava+Moratalla+Murcia',1,150],
        ['Kebab House','Carr. Campo de San Juan, 36','Moratalla',0,'Moratalla','Murcia','30440','868665134',NULL,'https://moratallakebabhouse.es/',NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Kebab+House+Moratalla+Murcia',1,160],
        ['Restaurante La Cilla','Santuario Casa de Cristo, km 7','Moratalla',0,'Moratalla','Murcia','30440','601854121',NULL,'https://lacillamoratalla.es/',NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+La+Cilla+Moratalla+Murcia',1,170],
        ['Taberna Tau Chen','C. B, 13','La Tercia',1,'Moratalla','Murcia','30442','679052712',NULL,NULL,NULL,'https://www.tripadvisor.com/Restaurant_Review-g1047904-d15543112-Reviews-Taberna_Tau_Chen-Moratalla.html','https://www.google.com/maps/search/?api=1&query=Taberna+Tau+Chen+La+Tercia+Moratalla+Murcia',1,180],
        ['Restaurante El Sitio','Ctra. Caravaca, 26','Moratalla',0,'Moratalla','Murcia','30440','608621680',NULL,'https://elsitiodemoratalla.es',NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+El+Sitio+Moratalla+Murcia',1,190],
        ['Restaurante Pizzería Il Pipiolo','Ctra. Campo de San Juan, 73','Moratalla',0,'Moratalla','Murcia','30440','968730228',NULL,'https://pipiolopizzerias.com',NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Pizzeria+Il+Pipiolo+Moratalla+Murcia',1,200],
        ['Taberna Moncloa','Ctra. Campo de San Juan, 34','Moratalla',0,'Moratalla','Murcia','30440','968706233','660392104',NULL,'https://www.facebook.com/tabernamoncloa/',NULL,'https://www.google.com/maps/search/?api=1&query=Taberna+Moncloa+Moratalla+Murcia',1,210],
        ['Restaurante Montebenamor','Carr. Campo de San Juan, 67','Moratalla',0,'Moratalla','Murcia','30440','630121087',NULL,NULL,'https://m.facebook.com/Restaurante-Montebenamor-1125264384159505/',NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+Montebenamor+Moratalla+Murcia',1,220],
        ['Restaurante Alanshé','C/ Tomás Aguilera (frente Jardín La Glorieta)','Moratalla',0,'Moratalla','Murcia','30440','689797819','618917988',NULL,'https://www.facebook.com/p/Alansh%C3%A9-Bar-Taperia-61555311501496/',NULL,'https://www.google.com/maps/search/?api=1&query=Restaurante+Alanshe+Moratalla+Murcia',1,230],
        ['Palike','C/ Lucas Egea, 2','Moratalla',0,'Moratalla','Murcia','30440','653795674',NULL,NULL,'https://www.facebook.com/palikemoratalla/',NULL,'https://www.google.com/maps/search/?api=1&query=Palike+Moratalla+Murcia',1,240],
        ['Brasería Casa Manolo','Carretera de Caravaca, 62','Moratalla',0,'Moratalla','Murcia','30440','685739868',NULL,NULL,'https://www.facebook.com/p/Braser%C3%ADa-Casa-Manolo-100063577151356/',NULL,'https://www.google.com/maps/search/?api=1&query=Braseria+Casa+Manolo+Moratalla+Murcia',1,250],
        ['Tasca El Coto','Ctra. de Caravaca, 20','Moratalla',0,'Moratalla','Murcia','30440','670455543',NULL,NULL,NULL,'https://www.tripadvisor.es/Restaurant_Review-g1047904-d10161547-Reviews-Tasca_El_Coto-Moratalla.html','https://www.google.com/maps/search/?api=1&query=Tasca+El+Coto+Moratalla+Murcia',1,260],
        ['Café Bar Reyes','C. Don Tomás el Cura, 7','Moratalla',0,'Moratalla','Murcia','30440','691071598',NULL,NULL,NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Bar+Reyes+Moratalla',1,270],
        ['Bar El Agarre','Ctra. del Canal, 8','Moratalla',0,'Moratalla','Murcia','30440','650535399',NULL,NULL,NULL,NULL,'https://www.google.com/maps/search/?api=1&query=Bar+El+Agarre+Moratalla',1,280],
        ['Bar Carlos Sixto','Pl. de Tamayo, 2','Moratalla',0,'Moratalla','Murcia','30440','652982567',NULL,NULL,'https://www.facebook.com/barcarlossixto/',NULL,'https://www.google.com/maps/search/?api=1&query=Bar+Carlos+Sixto+Moratalla+Murcia',1,290],
    ];
    $stmt = $pdo->prepare("
        INSERT INTO restaurantes
            (nombre,calle,poblacion,es_pedania,municipio,provincia,codigo_postal,
             telefono1,telefono2,web,facebook,tripadvisor,gmap_url,is_visible,sort_order)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $ins = 0;
    foreach ($datos as $d) { try { $stmt->execute($d); $ins++; } catch(PDOException $e) { $log[] = "❌ " . $e->getMessage(); } }
    $log[] = "✅ Insertados $ins restaurantes.";
} else {
    $log[] = "ℹ️  Ya había $countRest restaurantes. No se insertaron datos duplicados.";
}

// ─── Carpeta de uploads ────────────────────────────────────────────────────
$dir = __DIR__ . '/uploads/restaurantes/';
if (!is_dir($dir)) { mkdir($dir, 0755, true); $log[] = "✅ Carpeta uploads/restaurantes/ creada."; }
else { $log[] = "ℹ️  Carpeta uploads/restaurantes/ ya existía."; }

?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Fix Local DB</title>
<style>
body{font-family:monospace;background:#0d1117;color:#58e04a;padding:2rem;font-size:0.95rem;line-height:1.9;}
h1{color:#fff;border-bottom:1px solid #333;padding-bottom:.5rem;}
.warn{color:#ffa500;background:#1a1000;padding:1rem;border-left:3px solid #ffa500;margin:1rem 0;border-radius:4px;}
.box{border:1px solid #333;border-radius:6px;padding:1rem;margin:1.5rem 0;}
a{color:#58a6ff;}
</style>
</head>
<body>
<h1>🔧 Reparación BD Local</h1>
<div class="warn">⚠️ Solo para desarrollo LOCAL. No ejecutar en producción.</div>
<div class="box">
<?php foreach ($log as $line) echo "<p>$line</p>"; ?>
</div>
<div class="box">
<h3>Estado BD:</h3>
<?php
try {
    echo "<p>Categorías: <b>" . $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() . "</b></p>";
    echo "<p>Páginas: <b>" . $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn() . "</b></p>";
    echo "<p>Restaurantes: <b>" . $pdo->query("SELECT COUNT(*) FROM restaurantes")->fetchColumn() . "</b></p>";
} catch(PDOException $e) { echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>"; }
?>
</div>
<div class="box">
<h3>Ir a:</h3>
<p>→ <a href="index.php">Inicio de la web</a></p>
<p>→ <a href="restaurantes.php">Bares y Restaurantes (frontend)</a></p>
<p>→ <a href="admin/restaurantes.php">Admin – Bares y Restaurantes</a></p>
</div>
</body>
</html>
