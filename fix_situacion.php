<?php
require_once 'config.php';
$pdo = getDB();

$newContent = <<<HTML
<div class="content-card">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 style="color: var(--primary); font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;">Moratalla</h2>
        <a href="Images/situacion.jpg" target="_blank" style="display: inline-block; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow);">
            <img src="Images/situacion.jpg" alt="Mapa de Situación" style="max-width: 100%; height: auto; display: block; border: 3px solid var(--primary);">
        </a>
    </div>

    <div class="html-content" style="font-size: 1.1rem; line-height: 1.8; color: var(--text);">
        <p><strong>El término municipal de Moratalla tiene una extensión de 961 Km², siendo uno de los más grandes de España.</strong> Está situado en el sureste español y Noroeste de la Región de Murcia, en una de las vertientes del cerro de San Jorge (38º 11' 30'' latitud Norte, 1º 47' 40'' longitud Oeste) y con una altitud media sobre el nivel del mar de 681 metros.</p>
        
        <p>La orografía pertenece a la región Subbética, siendo la zona más montañosa de la Región de Murcia, con más de 20 picos que sobrepasan los 1400 metros, entre ellos el techo de Murcia, con 2027 metros en el Pico de los Revolcadores.</p>
        
        <p>El clima es de los llamados de transición, donde encontramos zonas con clima típicamente mediterráneo y clima continental en las zonas más altas.</p>
        
        <p>Por Moratalla pasan dos ríos, Alhárabe y Benamor. Son muy largos y sus caudales oscilan bastante de una estación a otra. Actualmente el Río Benamor no tiene ningún caudal al pasar por Moratalla, debido a la gran sequía que sufre la zona. Ambos ríos se unen en el lugar llamado Molino de la Traviesa, desembocando finalmente en el Río Segura.</p>
        
        <div style="background: var(--bg-alt); padding: 1.5rem; border-left: 4px solid var(--accent); margin: 2rem 0; border-radius: 0 12px 12px 0;">
            <p style="margin: 0; font-style: italic; color: var(--text-light);">Extracto del libro: Ciclo de Formación Histórica para escolares "Villa de Moratalla". Patrocinado por la CAM.</p>
        </div>

        <h3 style="color: var(--primary-dark); margin-top: 3rem; margin-bottom: 1rem; border-bottom: 2px solid var(--gray-200); padding-bottom: 0.5rem;">Distancias Kilométricas</h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div>
                <h4 style="color: var(--primary); margin-bottom: 1rem;">A Pedanías</h4>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Pedanía</th>
                            <th style="text-align: right;">Kilómetros (aprox.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Campo Béjar</td><td style="text-align: right;">14</td></tr>
                        <tr><td>Benízar</td><td style="text-align: right;">32</td></tr>
                        <tr><td>Calar de la Santa</td><td style="text-align: right;">32</td></tr>
                        <tr><td>Campo de San Juan</td><td style="text-align: right;">21</td></tr>
                        <tr><td>Cañada de la Cruz</td><td style="text-align: right;">57</td></tr>
                        <tr><td>El Sabinar</td><td style="text-align: right;">28</td></tr>
                        <tr><td>La Rogativa</td><td style="text-align: right;">45</td></tr>
                        <tr><td>Mazuza</td><td style="text-align: right;">40</td></tr>
                        <tr><td>Otos</td><td style="text-align: right;">37</td></tr>
                    </tbody>
                </table>
            </div>

            <div>
                <h4 style="color: var(--primary); margin-bottom: 1rem;">Pueblos y Ciudades Cercanas</h4>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Localidad</th>
                            <th style="text-align: right;">Kilómetros (aprox.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Bullas</td><td style="text-align: right;">33</td></tr>
                        <tr><td>Calasparra</td><td style="text-align: right;">19</td></tr>
                        <tr><td>Caravaca de la Cruz</td><td style="text-align: right;">13</td></tr>
                        <tr><td>Cehegín</td><td style="text-align: right;">20</td></tr>
                        <tr><td>Cieza</td><td style="text-align: right;">57</td></tr>
                        <tr><td>Hellín</td><td style="text-align: right;">75</td></tr>
                        <tr><td>Lorca</td><td style="text-align: right;">71</td></tr>
                        <tr><td>Mula</td><td style="text-align: right;">47</td></tr>
                        <tr><td>Socovos (Albacete)</td><td style="text-align: right;">36</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
HTML;

try {
    // ID 279 corresponde a "Situacion geografica"
    $stmt = $pdo->prepare("UPDATE pages SET content = :content WHERE id = 279");
    $stmt->execute(['content' => $newContent]);
    
    echo "<h1>¡Éxito!</h1>";
    echo "<p style='color: green;'>La página 'Situación' se ha actualizado correctamente en la base de datos.</p>";
    echo "<p>Los acentos han sido corregidos y el diseño HTML rústico ha sido reemplazado por la versión premium.</p>";
    echo "<p><strong>Recuerda borrar este archivo (fix_situacion.php) por seguridad.</strong></p>";
} catch(Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
