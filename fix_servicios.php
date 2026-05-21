<?php
require_once 'config.php';
$pdo = getDB();

$newContent = <<<HTML
<div class="content-card">
    <div style="text-align: center; margin-bottom: 2rem;">
        <i class="fas fa-phone-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 1rem;"></i>
        <h2 style="color: var(--primary); font-size: 2.5rem; font-weight: 800; margin-bottom: 0.5rem;">Contactos de Interés</h2>
        <p style="color: var(--text-light); font-size: 1.1rem;">Servicios de urgencia y teléfonos públicos</p>
    </div>

    <div class="html-content" style="font-size: 1.1rem; line-height: 1.8; color: var(--text);">
        
        <h3 style="color: #e63946; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 2px solid #ffccd5; padding-bottom: 0.5rem;">
            <i class="fas fa-ambulance" style="margin-right: 10px;"></i> Servicios de Urgencia
        </h3>
        
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Dirección / Detalle</th>
                        <th>Teléfono</th>
                        <th>Fax</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: 600; color: #e63946;"><i class="fas fa-exclamation-triangle" style="margin-right:5px;"></i>EMERGENCIAS (Toda Europa)</td>
                        <td>Cualquier tipo de emergencia</td>
                        <td style="font-weight: bold; font-size: 1.2rem; color: #e63946;">112</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-university" style="color: var(--primary); margin-right:5px;"></i>Ayuntamiento de Moratalla</td>
                        <td>C/ Constitución</td>
                        <td>968 730 258 <br> 968 730 001</td>
                        <td>968 730 543</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-shield-alt" style="color: var(--primary); margin-right:5px;"></i>Policía Local</td>
                        <td>C/ Constitución (Moratalla)</td>
                        <td>968 730 302 <br> 687 856 187</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-building" style="color: var(--primary); margin-right:5px;"></i>Guardia Civil</td>
                        <td>C/ Cuesta de Cuartel</td>
                        <td>968 730 002 <br> 062</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-fire-extinguisher" style="color: #e63946; margin-right:5px;"></i>Bomberos</td>
                        <td>Parque de Caravaca <br> CECOP Murcia</td>
                        <td>968 702 030 <br> 968 345 000</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-hard-hat" style="color: #e63946; margin-right:5px;"></i>Protección Civil</td>
                        <td>Moratalla</td>
                        <td>968 706 775</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-clinic-medical" style="color: var(--primary); margin-right:5px;"></i>Centro de Salud</td>
                        <td>Moratalla</td>
                        <td>968 706 235</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-hospital" style="color: var(--primary); margin-right:5px;"></i>Hospital Comarcal Noroeste</td>
                        <td>Caravaca de la Cruz <br><small>Centralita / Citas</small></td>
                        <td>968 702 712 <br> 968 703 435</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-ambulance" style="color: #e63946; margin-right:5px;"></i>Ambulancias</td>
                        <td>Caravaca de la Cruz</td>
                        <td>968 707 933 <br> 968 707 580</td>
                        <td>968 708 046</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-gavel" style="color: var(--primary); margin-right:5px;"></i>Juzgado Municipal</td>
                        <td>Moratalla</td>
                        <td>968 730 003</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-tint" style="color: #0284c7; margin-right:5px;"></i>Servicio de Agua Potable</td>
                        <td>24 Horas <br> Oficina</td>
                        <td>636 993 294 <br> 968 706 151</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-bolt" style="color: #ca8a04; margin-right:5px;"></i>Iberdrola</td>
                        <td>Atención general</td>
                        <td>901 202 020</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-envelope" style="color: var(--primary); margin-right:5px;"></i>Correos</td>
                        <td>Moratalla</td>
                        <td>968 730 178</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-users" style="color: var(--primary); margin-right:5px;"></i>Hogar del Pensionista</td>
                        <td>Moratalla</td>
                        <td>968 730 590</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600;"><i class="fas fa-church" style="color: var(--primary); margin-right:5px;"></i>Parroquia de la Asunción</td>
                        <td>C/ Mayor, 1</td>
                        <td>968 730 160</td>
                        <td>-</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3 style="color: var(--primary-dark); margin-top: 3rem; margin-bottom: 1rem; border-bottom: 2px solid var(--gray-200); padding-bottom: 0.5rem;">
            <i class="fas fa-map-signs" style="margin-right: 10px;"></i> Teléfonos Públicos de Pedanías
        </h3>
        <p style="font-size: 0.95rem; color: var(--text-light); margin-bottom: 2rem;"><i class="fas fa-info-circle"></i> Haz clic en cualquier pedanía para ver la ruta en Google Maps.</p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Pedanía / Paraje</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Béjar,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Béjar (La Granja)</a></td><td>968 738 014</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Benízar,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Benízar - La Tercia</a></td><td>968 736 000</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Calar+de+la+Santa,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Calar de la Santa</a></td><td>968 738 038</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casicas+de+San+Juan,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casicas de San Juan</a></td><td>968 738 009</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Cañada+de+la+Cruz,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Cañada de la Cruz</a></td><td>968 736 307</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casa+de+Eras,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casa de Eras</a></td><td>968 738 019</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casa+de+los+Garcías,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casa de los Garcías</a></td><td>968 736 191</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casa+del+Puerto,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casa del Puerto</a></td><td>968 738 025</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casa+Nueva,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casa Nueva</a></td><td>968 738 081</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casa+Pernías,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casa Pernías</a></td><td>968 738 024</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casa+Requena,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casa Requena</a></td><td>968 736 055</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casa+Rivera,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casa Rivera</a></td><td>968 725 073</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Casicas+del+Portal,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Casicas del Portal</a></td><td>968 738 028</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=El+Arenal,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>El Arenal - Ulea</a></td><td>968 730 135</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=El+Sabinar,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>El Sabinar</a></td><td>968 738 000</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Fotuya,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Fotuya</a></td><td>968 738 001</td></tr>
                    </tbody>
                </table>
            </div>

            <div>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Pedanía / Paraje</th>
                            <th>Teléfono</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Inazares,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Inazares</a></td><td>968 725 667</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=La+Alberquilla,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>La Alberquilla</a></td><td>968 736 060</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=La+Pava,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>La Pava</a></td><td>968 738 006</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=La+Risca,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>La Risca</a></td><td>968 738 029</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=La+Rogativa,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>La Rogativa</a></td><td>968 738 005</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Las+Lorigas,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Las Lorigas</a></td><td>968 738 026</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Las+Murtas,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Las Murtas</a></td><td>968 730 015</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Los+Cantos,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Los Cantos</a></td><td>968 738 008</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Los+Odres,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Los Odres</a></td><td>968 725 165</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Mazuza,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Mazuza</a></td><td>968 736 075</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Orihuelo,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Orihuelo</a></td><td>968 738 045</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Otos,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Otos</a></td><td>968 736 004</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Rincón+de+los+Huertos,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Rincón de los Huertos</a></td><td>968 736 034</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Royo+Tercero,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Royo Tercero</a></td><td>968 738 051</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Salmerón,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Salmerón</a></td><td>968 720 007</td></tr>
                        <tr><td><a href="https://www.google.com/maps/dir/?api=1&origin=Moratalla,+Murcia&destination=Zaén+de+Arriba,+Moratalla,+Murcia" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;"><i class="fas fa-map-marker-alt" style="color: var(--accent); margin-right: 5px;"></i>Zaén de Arriba</a></td><td>968 738 027</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
HTML;

try {
    // Buscar la página por título (Contactos de interes)
    $stmt = $pdo->prepare("UPDATE pages SET content = :content WHERE title LIKE '%Contactos de interes%'");
    $stmt->execute(['content' => $newContent]);
    
    $rowCount = $stmt->rowCount();
    
    if ($rowCount > 0) {
        echo "<h1>¡Éxito!</h1>";
        echo "<p style='color: green;'>La página 'Servicios y Teléfonos' se ha actualizado correctamente en la base de datos con el nuevo diseño y mapas interactivos.</p>";
        echo "<p>Los acentos han sido corregidos y el diseño HTML rústico ha sido reemplazado por la versión premium.</p>";
        echo "<p><strong>Recuerda borrar este archivo (fix_servicios.php) de tu servidor por seguridad.</strong></p>";
    } else {
        echo "<h1>Aviso</h1>";
        echo "<p style='color: orange;'>El script se ejecutó sin errores, pero no se encontró ninguna página con el título 'Contactos de interes' o el contenido ya estaba actualizado.</p>";
    }
} catch(Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
