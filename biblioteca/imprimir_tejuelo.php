<?php
require 'includes/auth.php';
require 'db.php';

if (!isset($_GET['id'])) {
    die("ID de libro no especificado.");
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare('SELECT l.*, c.nombre as cdu_nombre FROM libros l LEFT JOIN cdu c ON l.cdu_id = c.id WHERE l.id = ?');
$stmt->execute([$id]);
$libro = $stmt->fetch();

if (!$libro) {
    die("Libro no encontrado.");
}

$signatura = $libro['signatura'] ? explode("\n", $libro['signatura']) : ['-','-','-'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Tejuelo - <?= htmlspecialchars($libro['titulo']) ?></title>
    <!-- Librería para generar el QR en el cliente -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f3f4f6;
        }
        .etiqueta-container {
            background: white;
            border: 1px dashed #ccc;
            padding: 12px;
            width: 62mm; /* Formato estándar de etiqueta */
            height: 29mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .tejuelo-texto {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 14px;
            line-height: 1.2;
            width: 50%;
            text-align: center;
        }
        .tejuelo-texto div {
            padding: 2px 0;
        }
        .qr-container {
            width: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .btn-imprimir {
            margin-top: 30px;
            padding: 12px 24px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-imprimir:hover {
            background: #4338ca;
        }
        .info-adicional {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
            text-align: center;
            max-width: 400px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .info-adicional p {
            margin: 5px 0;
        }
        @media print {
            body {
                background: none;
                padding: 0;
                align-items: flex-start;
            }
            .etiqueta-container {
                border: none;
                box-shadow: none;
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="etiqueta-container">
        <div class="tejuelo-texto">
            <?php foreach($signatura as $linea): ?>
                <div><?= htmlspecialchars($linea) ?></div>
            <?php endforeach; ?>
        </div>
        <div class="qr-container" id="qrcode"></div>
    </div>

    <div class="info-adicional no-print">
        <p><strong>Libro:</strong> <?= htmlspecialchars($libro['titulo']) ?></p>
        <p><strong>Autor:</strong> <?= htmlspecialchars($libro['autor']) ?></p>
        <p style="margin-top: 10px; font-size: 12px; color: #888;">💡 Recomendación: Ajusta los márgenes a "Ninguno" e imprime al 100% de escala en los ajustes de impresión del navegador para obtener el tamaño real de 62x29mm.</p>
    </div>

    <button class="btn-imprimir no-print" onclick="window.print()">
        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        Imprimir Etiqueta
    </button>

    <script>
        const qrCodeString = "BIBLIO-<?= $libro['id'] ?>";
        new QRCode(document.getElementById("qrcode"), {
            text: qrCodeString,
            width: 70,  // Tamaño ajustado para encajar bien
            height: 70,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    </script>
</body>
</html>
