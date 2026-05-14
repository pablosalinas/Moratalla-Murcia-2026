-- Migración 024: Sincronización de tabla page_images (Parte 1)
-- Generada: 2026-05-14 13:13:08

SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM `page_images`;

REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('1', '2', 'assets/images/p2/img_69fd07506e9e3_Jesus1p.jpg', NULL, '1');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('2', '2', 'assets/images/p2/img_69fd07507180d_Jesus2p.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('3', '3', 'assets/images/p3/img_69fd075078f30_coche_canip.jpg', NULL, '1');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('4', '3', 'assets/images/p3/img_69fd07507b1f1_coche_jesusp.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('5', '3', 'assets/images/p3/img_69fd07507c9ee_coche_rojop.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('6', '3', 'assets/images/p3/img_69fd07507d97d_coche_blancop.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('7', '28', 'assets/images/p28/img_69fd0750f2512_moratalla_club_futbol_2008-2009.jpg', NULL, '1');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('8', '31', 'assets/images/p31/img_69fd075118ba1_IMGP3086.jpg', NULL, '1');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('9', '31', 'assets/images/p31/img_69fd07511dd41_IMGP3090.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('10', '31', 'assets/images/p31/img_69fd0751208d0_IMGP3091.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('11', '31', 'assets/images/p31/img_69fd075122bf5_IMGP3092.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('12', '31', 'assets/images/p31/img_69fd075123c63_IMGP3093.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('13', '31', 'assets/images/p31/img_69fd075124b2a_IMGP3095.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('14', '31', 'assets/images/p31/img_69fd0751257e5_IMGP3096.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('15', '31', 'assets/images/p31/img_69fd0751267d6_IMGP3097.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('16', '31', 'assets/images/p31/img_69fd075127941_IMGP3099.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('17', '31', 'assets/images/p31/img_69fd0751288e9_IMGP3100.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('18', '31', 'assets/images/p31/img_69fd07512978b_IMGP3101.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('19', '31', 'assets/images/p31/img_69fd07512a809_IMGP3102.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('20', '31', 'assets/images/p31/img_69fd07512b9fd_IMGP3103.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('21', '31', 'assets/images/p31/img_69fd07512cb08_IMGP3105.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('22', '31', 'assets/images/p31/img_69fd07512d886_IMGP3106.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('23', '31', 'assets/images/p31/img_69fd07512e8b3_IMGP3108.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('24', '31', 'assets/images/p31/img_69fd07512fa8c_IMGP3109.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('25', '31', 'assets/images/p31/img_69fd075130a6c_IMGP3110.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('26', '31', 'assets/images/p31/img_69fd0751317b0_IMGP3112.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('27', '31', 'assets/images/p31/img_69fd0751328a1_IMGP3114.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('28', '31', 'assets/images/p31/img_69fd075134e93_IMGP3115.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('29', '31', 'assets/images/p31/img_69fd075135f44_IMGP3116.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('30', '31', 'assets/images/p31/img_69fd0751371f0_IMGP3117.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('31', '31', 'assets/images/p31/img_69fd075138533_IMGP3119.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('32', '31', 'assets/images/p31/img_69fd075139230_IMGP3121.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('33', '31', 'assets/images/p31/img_69fd07513a030_IMGP3122.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('34', '31', 'assets/images/p31/img_69fd07513b16a_IMGP3123.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('35', '31', 'assets/images/p31/img_69fd07513d37e_IMGP3124.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('36', '31', 'assets/images/p31/img_69fd07513dffe_IMGP3126.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('37', '31', 'assets/images/p31/img_69fd0751407fa_IMGP3128.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('38', '31', 'assets/images/p31/img_69fd075142619_IMGP3129.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('39', '31', 'assets/images/p31/img_69fd0751436a0_IMGP3130.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('40', '31', 'assets/images/p31/img_69fd0751448ae_IMGP3131.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('41', '31', 'assets/images/p31/img_69fd0751467cb_IMGP3132.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('42', '31', 'assets/images/p31/img_69fd0751479bc_IMGP3133.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('43', '31', 'assets/images/p31/img_69fd075149c99_IMGP3134.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('44', '31', 'assets/images/p31/img_69fd07514a8ef_IMGP3135.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('45', '31', 'assets/images/p31/img_69fd07514b9f7_IMGP3136.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('46', '31', 'assets/images/p31/img_69fd07514ca93_IMGP3138.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('47', '31', 'assets/images/p31/img_69fd07514e991_IMGP3142.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('48', '31', 'assets/images/p31/img_69fd07514f996_IMGP3143.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('49', '31', 'assets/images/p31/img_69fd075150ae3_IMGP3145.jpg', NULL, '0');
REPLACE INTO `page_images` (`id`, `page_id`, `image_path`, `caption`, `is_cover`) VALUES ('50', '31', 'assets/images/p31/img_69fd075151a20_IMGP3147.jpg', NULL, '0');

SET FOREIGN_KEY_CHECKS = 1;
