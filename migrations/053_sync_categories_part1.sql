-- Migración 053: Sincronización de tabla categories (Parte 1)
-- Generada: 2026-05-14 12:02:50

SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `categories`;

REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('1', NULL, 'Asociaciones', 'asociaciones', '2');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('2', '142', 'Automovilismo', 'automovilismo-73', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('3', '2', '2004', '2004-51', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('4', '2', '2005', '2005-48', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('5', '2', '2006', '2006-39', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('7', '142', 'Ciclista', 'ciclista-36', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('8', '7', '2004', '2004-73', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('9', '7', '2005', '2005-42', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('10', '7', '2006', '2006-33', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('11', '142', 'Futbol', 'futbol-25', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('12', '11', 'Temporada03 04', 'temporada03-04-52', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('13', '11', 'Temporada04 05', 'temporada04-05-80', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('14', '11', 'Temporada05 06', 'temporada05-06-83', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('15', '11', 'Temporada06 07', 'temporada06-07-40', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('16', '11', 'Temporada07 08', 'temporada07-08-44', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('17', '11', 'Temporada08 09', 'temporada08-09-93', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('18', '11', 'Temporada09 10', 'temporada09-10-64', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('19', '11', 'Temporada10 11', 'temporada10-11-37', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('28', '142', 'Baloncesto', 'baloncesto-96', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('29', '28', '2005', '2005-88', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('30', '28', '2006', '2006-92', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('33', NULL, 'Colaboraciones', 'colaboraciones', '99');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('34', NULL, 'Consultas', 'consultas', '99');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('35', NULL, 'Contacto', 'contacto', '99');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('36', NULL, 'Corporacion', 'corporacion', '10');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('37', NULL, 'Festival banda', 'festival-banda', '99');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('39', NULL, 'Fiestas', 'fiestas', '4');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('40', '39', 'SanAnton', 'sananton-59', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('44', '39', 'Ssta', 'ssta-96-966', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('45', '39', 'StoXto', 'stoxto-90', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('46', '45', 'Stoxto2004', 'stoxto2004-78', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('49', '46', 'Web damas', 'web-damas-89', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('52', '45', 'Stoxto2005', 'stoxto2005-12', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('55', '45', 'Stoxto2006', 'stoxto2006-95', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('59', '45', 'Stoxto2007', 'stoxto2007-93', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('66', '45', 'Stoxto2008', 'stoxto2008-99', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('80', NULL, 'Gastronomia', 'gastronomia', '7');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('81', NULL, 'Lugares', 'lugares', '6');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('83', NULL, 'Noticias', 'noticias', '5');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('84', '83', '2006', '2006-82', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('88', '83', '2007', '2007-71', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('91', '83', '2008', '2008-26', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('92', '83', '2009', '2009-29', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('97', '83', '2010', '2010-74', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('100', '83', 'Apenas10', 'apenas10-77', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('106', NULL, 'Publicidad', 'publicidad', '9');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('107', '106', 'Albury', 'albury-42', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('109', '106', 'Alojamientos', 'alojamientos-22', '0');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('113', NULL, 'Servicios', 'servicios', '8');
REPLACE INTO `categories` (`id`, `parent_id`, `name`, `slug`, `sort_order`) VALUES ('116', NULL, 'Situacion', 'situacion', '99');

SET FOREIGN_KEY_CHECKS = 1;
