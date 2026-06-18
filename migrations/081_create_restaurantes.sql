-- Migración 081: Sistema de Bares y Restaurantes
-- Fecha: 2026-06-18
-- Descripción: Crea las tablas para gestionar bares y restaurantes de Moratalla

CREATE TABLE IF NOT EXISTS `restaurantes` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `nombre`        VARCHAR(255) NOT NULL,
    `calle`         VARCHAR(255) DEFAULT NULL,
    `poblacion`     VARCHAR(100) DEFAULT NULL,
    `es_pedania`    TINYINT(1) DEFAULT 0,
    `municipio`     VARCHAR(100) DEFAULT 'Moratalla',
    `provincia`     VARCHAR(100) DEFAULT 'Murcia',
    `codigo_postal` VARCHAR(10) DEFAULT NULL,
    `telefono1`     VARCHAR(30) DEFAULT NULL,
    `telefono2`     VARCHAR(30) DEFAULT NULL,
    `web`           VARCHAR(500) DEFAULT NULL,
    `facebook`      VARCHAR(500) DEFAULT NULL,
    `tripadvisor`   VARCHAR(500) DEFAULT NULL,
    `gmap_url`      VARCHAR(500) DEFAULT NULL,
    `descripcion`   TEXT DEFAULT NULL,
    `is_visible`    TINYINT(1) DEFAULT 1,
    `sort_order`    INT DEFAULT 0,
    `views`         INT DEFAULT 0,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `restaurante_images` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `restaurante_id` INT NOT NULL,
    `image_path`  VARCHAR(500) NOT NULL,
    `caption`     TEXT DEFAULT NULL,
    `is_cover`    TINYINT(1) DEFAULT 0,
    `is_visible`  TINYINT(1) DEFAULT 1,
    `sort_order`  INT DEFAULT 0,
    `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`restaurante_id`) REFERENCES `restaurantes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
