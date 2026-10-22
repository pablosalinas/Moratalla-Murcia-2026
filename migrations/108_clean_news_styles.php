<?php
/**
 * Migración 108: Saneamiento de estilos intrusivos en contenidos de noticias y eventos.
 * 
 * Limpia bloques <style>, <script> y envolturas HTML de páginas externas
 * para garantizar la correcta respuesta y adaptabilidad responsiva en monitores y móviles,
 * SIN BORRAR NINGÚN REGISTRO NI DATO de la base de datos (únicamente actualiza el campo `content`).
 */
if (!isset($pdo)) {
    require_once dirname(__DIR__) . '/config.php';
    $pdo = getDB();
}

try {
    $stmt = $pdo->query("SELECT id, content FROM news_events WHERE content LIKE '%<style%' OR content LIKE '%<script%' OR content LIKE '%<!DOCTYPE%'");
    $dirtyRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($dirtyRows)) {
        $upd = $pdo->prepare("UPDATE news_events SET content = ? WHERE id = ?");
        $updatedCount = 0;

        foreach ($dirtyRows as $row) {
            $cleaned = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $row['content']);
            $cleaned = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $cleaned);
            $cleaned = preg_replace('/<link\b[^>]*>/i', '', $cleaned);
            $cleaned = preg_replace('/<!DOCTYPE[^>]*>/i', '', $cleaned);
            $cleaned = preg_replace('/<\/?(?:html|head|body)[^>]*>/i', '', $cleaned);
            $cleaned = trim($cleaned);

            $upd->execute([$cleaned, $row['id']]);
            $updatedCount++;
        }

        if (function_exists('output')) {
            output("Migración 108: Se sanearon $updatedCount noticias en news_events (sin borrar datos).");
        }
    } else {
        if (function_exists('output')) {
            output("Migración 108: Ninguna noticia contenía etiquetas intrusivas.");
        }
    }
} catch (Exception $e) {
    if (function_exists('output')) {
        output("Aviso en Migración 108: " . $e->getMessage());
    }
}
