<?php
require_once __DIR__ . '/config.php';
$pdo = getDB();

try {
    // Añadir columnas de fecha si no existen
    try {
        $pdo->exec("ALTER TABLE celebrations ADD COLUMN start_date DATETIME NULL AFTER is_active");
        $pdo->exec("ALTER TABLE celebrations ADD COLUMN end_date DATETIME NULL AFTER start_date");
        echo "Columnas añadidas correctamente.\n";
    } catch (Exception $e) {
        // Puede que ya existan
    }
    
    // Nuevo CSS responsivo
    $css = '#spain-banner-container {
    position: fixed;
    top: 20%;
    left: 0;
    width: 100%;
    z-index: 9999;
    pointer-events: none;
    display: flex;
    justify-content: center;
    transform: translateX(-150%);
    animation: flyAcross 15s linear infinite;
}
.spain-flag-banner {
    background: linear-gradient(to bottom, #aa151b 0%, #aa151b 28%, #f1bf00 28%, #f1bf00 72%, #aa151b 72%, #aa151b 100%);
    color: white;
    font-size: clamp(1.5rem, 5vw, 3.5rem);
    font-weight: 900;
    padding: clamp(15px, 3vw, 30px) clamp(20px, 5vw, 60px);
    text-shadow: 3px 3px 6px rgba(0,0,0,0.6), -1px -1px 2px rgba(170, 21, 27, 0.8);
    box-shadow: 0 15px 35px rgba(0,0,0,0.5);
    animation: waveEffect 1.5s ease-in-out infinite alternate;
    white-space: normal;
    text-align: center;
    max-width: 90vw;
}
@keyframes flyAcross {
    0% { transform: translateX(-150vw); }
    100% { transform: translateX(150vw); }
}
@keyframes waveEffect {
    0% { transform: translateY(0) rotate(-3deg); border-radius: 15px 40px 15px 40px; }
    100% { transform: translateY(-20px) rotate(3deg); border-radius: 40px 15px 40px 15px; }
}
@media (max-width: 768px) {
    #spain-banner-container { top: 30%; }
    @keyframes flyAcross {
        0% { transform: translateX(-150vw); }
        100% { transform: translateX(150vw); }
    }
}';

    $pdo->prepare("UPDATE celebrations SET css_content = ? WHERE name = 'Mundial España 2026'")->execute([$css]);
    echo "CSS actualizado.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
