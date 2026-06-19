-- Migración 090: Añadir sort_order a news_events (sin usar IF NOT EXISTS para MySQL antiguos)
-- Fallará silenciosamente si ya existe (como en local), pero funcionará en producción.

ALTER TABLE `news_events` ADD COLUMN `sort_order` INT DEFAULT 0 AFTER `is_active_category`;
