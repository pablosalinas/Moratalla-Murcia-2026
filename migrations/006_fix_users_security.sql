-- Migración 006: Añadir columnas de seguridad para login
-- Fecha: 2026-05-08

ALTER TABLE `users` 
ADD COLUMN `failed_attempts` INT DEFAULT 0 AFTER `role`,
ADD COLUMN `locked_until` DATETIME NULL DEFAULT NULL AFTER `failed_attempts`;
