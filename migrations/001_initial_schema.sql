-- Migración 001: Esquema inicial completo
-- Fecha: 2026-05-04
-- Descripción: Crea todas las tablas base del proyecto Moratalla-Murcia-2026

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL UNIQUE,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL UNIQUE,
    `content` LONGTEXT,
    `original_file` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `page_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `caption` TEXT DEFAULT NULL,
    `is_cover` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'editor') DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(191) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuario admin por defecto (pass: admin123)
INSERT IGNORE INTO `users` (`username`, `password`, `role`) 
VALUES ('admin', '$2y$10$WkG.C4oB3n0O.z8kL4A2QOTM8R7A6SHTgT./QcOzT3Y5T.S/oR6J6', 'admin');

-- Configuración por defecto
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) 
VALUES ('ticker_text', 'Bienvenido a moratalla-murcia.com — Patrimonio Histórico Digital');

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) 
VALUES ('ticker_speed', '30');
