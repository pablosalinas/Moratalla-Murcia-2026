<?php
require 'db.php';

// Definición de palabras clave para las clases principales del CDU
$mapping = [
    0 => ['diccionario', 'enciclopedia', 'archivo', 'biblioteca', 'generalidades', 'información'],
    1 => ['filosofía', 'psicología', 'ética', 'moral', 'pensamiento', 'mente', 'lógica'],
    2 => ['religión', 'teología', 'dios', 'biblia', 'iglesia', 'espiritualidad', 'sagrados', 'católica', 'cristianismo'],
    3 => ['economía', 'política', 'derecho', 'sociología', 'educación', 'pedagogía', 'ley', 'social', 'sociedad'],
    // 4 vacía en CDU estándar (integrada en 8)
    5 => ['ciencia', 'matemáticas', 'física', 'química', 'biología', 'naturaleza', 'astronomía', 'geología'],
    6 => ['tecnología', 'medicina', 'ingeniería', 'industria', 'agricultura', 'cocina', 'salud', 'técnica', 'informática'],
    7 => ['arte', 'pintura', 'escultura', 'música', 'deporte', 'fútbol', 'arquitectura', 'cine', 'teatro', 'fotografía'],
    8 => ['literatura', 'poesía', 'novela', 'cuento', 'teatro', 'lengua', 'lingüística', 'hablar', 'idioma', 'gramática', 'literaria'],
    9 => ['historia', 'geografía', 'biografía', 'viaje', 'atlas', 'siglo', 'guerra', 'crónica']
];

try {
    $stmt = $pdo->query("SELECT id, titulo, autor FROM libros");
    $libros = $stmt->fetchAll();
    
    $total = 0;
    foreach ($libros as $libro) {
        $found = false;
        $txt = mb_strtolower($libro['titulo'] . ' ' . $libro['autor'], 'UTF-8');
        
        // Prioridad: 8 (Literatura) es el cajón de sastre para muchos libros de esta biblioteca
        // Buscamos coincidencias
        foreach ($mapping as $cdu_id => $keywords) {
            foreach ($keywords as $kw) {
                if (strpos($txt, $kw) !== false) {
                    $pdo->prepare("UPDATE libros SET cdu_id = ?, cdu = ? WHERE id = ?")
                        ->execute([$cdu_id, (string)$cdu_id, $libro['id']]);
                    $found = true;
                    $total++;
                    break 2;
                }
            }
        }
        
        // Si no se encuentra, por defecto asignamos 8 si parece literatura o nada.
        // Pero el usuario dijo "según aprecies".
    }
    
    echo "Clasificación completada: $total libros actualizados.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
