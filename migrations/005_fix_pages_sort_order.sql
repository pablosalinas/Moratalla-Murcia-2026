-- Migración 005: Añadir columna sort_order a la tabla pages
-- Fecha: 2026-05-08

ALTER TABLE `pages` ADD COLUMN `sort_order` INT DEFAULT 0 AFTER `original_file`;
