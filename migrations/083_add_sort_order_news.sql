-- Migración 083: Añadir sort_order a news_events para permitir ordenación manual
-- Fecha: 2026-06-18

ALTER TABLE `news_events` ADD COLUMN IF NOT EXISTS `sort_order` INT DEFAULT 0 AFTER `is_active_category`;
