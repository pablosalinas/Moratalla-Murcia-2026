<?php
require_once 'config.php';
$pdo = getDB();

$newContent = <<<HTML
<div class="content-card">
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 style="color: var(--primary); font-size: 2.5rem; font-weight: 800; margin-bottom: 1rem;">Moratalla</h2>
        <!-- Mapa interactivo de Google Maps de Moratalla -->
        <div style="border-radius: 12px; overflow: hidden; box-shadow: var(--shadow); border: 3px solid var(--primary); width: 100%; height: 400px; max-width: 800px; margin: 0 auto;">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d100344.20454645218!2d-2.029805988022791!3d38.18843254992661!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd6505ab3e9f45f9%3A0xc3924db16f4fc448!2s30440%20Moratalla%2C%20Murcia!5e0!3m2!1ses!2ses!4v1716290000000!5m2!1ses!2ses" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
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
        <p style="font-size: 0.95rem; color: var(--text-light); margin-bottom: 2rem;"><i class="fas fa-info-circle"></i> Haz clic en cualquier población para ver la ruta directa desde Moratalla en Google Maps.</p>

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
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Campo+Béjar,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Campo Béjar</a></td><td style="text-align: right;">14</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Benízar,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Benízar</a></td><td style="text-align: right;">32</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Calar+de+la+Santa,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Calar de la Santa</a></td><td style="text-align: right;">32</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Campo+de+San+Juan,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Campo de San Juan</a></td><td style="text-align: right;">21</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Cañada+de+la+Cruz,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Cañada de la Cruz</a></td><td style="text-align: right;">57</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=El+Sabinar,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>El Sabinar</a></td><td style="text-align: right;">28</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=La+Rogativa,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>La Rogativa</a></td><td style="text-align: right;">45</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Mazuza,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Mazuza</a></td><td style="text-align: right;">40</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Otos,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Otos</a></td><td style="text-align: right;">37</td></tr>
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
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Bullas,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Bullas</a></td><td style="text-align: right;">33</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Calasparra,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Calasparra</a></td><td style="text-align: right;">19</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Caravaca+de+la+Cruz,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Caravaca de la Cruz</a></td><td style="text-align: right;">13</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Cehegín,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Cehegín</a></td><td style="text-align: right;">20</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Cieza,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Cieza</a></td><td style="text-align: right;">57</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Hellín,+Albacete" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Hellín</a></td><td style="text-align: right;">75</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Lorca,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Lorca</a></td><td style="text-align: right;">71</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Mula,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Mula</a></td><td style="text-align: right;">47</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Socovos,+Albacete" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Socovos (Albacete)</a></td><td style="text-align: right;">36</td></tr>
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
    echo "<p style='color: green;'>La página 'Situación' se ha actualizado correctamente en la base de datos con mapas interactivos de Google Maps.</p>";
    echo "<p>Los acentos han sido corregidos y el diseño HTML rústico ha sido reemplazado por la versión premium.</p>";
    echo "<p><strong>Recuerda borrar este archivo (fix_mapas.php) de tu servidor por seguridad.</strong></p>";
} catch(Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
