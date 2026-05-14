-- Migración 039: Sincronización de tabla settings (Parte 1)
-- Generada: 2026-05-14 13:13:08

SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `settings`;

REPLACE INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES ('1', 'site_name', 'Moratalla Murcia 2026');
REPLACE INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES ('2', 'ticker_text', 'Bienvenido a moratalla-murcia.com — Patrimonio Histórico Digital (local)');
REPLACE INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES ('3', 'ticker_speed', '70');
REPLACE INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES ('6', 'banner_speed', '5000');

SET FOREIGN_KEY_CHECKS = 1;
