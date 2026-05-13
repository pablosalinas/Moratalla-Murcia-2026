-- Migración 020: Sincronización de tabla settings
-- Generada: 2026-05-13 21:37:26

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `settings`;

REPLACE INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES ('1', 'site_name', 'Moratalla Murcia 2026');
REPLACE INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES ('2', 'ticker_text', 'Bienvenido a moratalla-murcia.com ??? Patrimonio Hist??rico Digital');
REPLACE INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES ('3', 'ticker_speed', '70');
REPLACE INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES ('6', 'banner_speed', '1000');

SET FOREIGN_KEY_CHECKS = 1;
