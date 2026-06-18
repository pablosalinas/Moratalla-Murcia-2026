-- Migración 087: Añadir campo email a la tabla restaurantes
-- Fecha: 2026-06-18

ALTER TABLE `restaurantes` ADD COLUMN `email` VARCHAR(255) DEFAULT NULL AFTER `telefono2`;
