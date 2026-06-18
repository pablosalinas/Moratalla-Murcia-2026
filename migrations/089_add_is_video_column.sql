-- Migración 089: Añadir columna is_video a las tablas de imágenes/galería
-- Fecha: 2026-06-18

ALTER TABLE restaurante_images ADD COLUMN is_video TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE alojamiento_images ADD COLUMN is_video TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE page_images ADD COLUMN is_video TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE news_images ADD COLUMN is_video TINYINT(1) NOT NULL DEFAULT 0;
