-- Migración 003: Reconstrucción total de la base de datos
-- Fecha: 2026-05-07
-- Descripción: Limpia el esquema anterior y recrea todo desde cero para asegurar compatibilidad.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `page_images`;
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `migrations`;

SET FOREIGN_KEY_CHECKS = 1;

-- Crear tabla de migraciones para el sistema automático
CREATE TABLE `migrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de Categorías
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL UNIQUE,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de Páginas
CREATE TABLE `pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL UNIQUE,
    `content` LONGTEXT,
    `original_file` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de Imágenes
CREATE TABLE `page_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `page_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `caption` TEXT DEFAULT NULL,
    `is_cover` TINYINT(1) DEFAULT 0,
    FOREIGN KEY (`page_id`) REFERENCES `pages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de Usuarios
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'editor') DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de Configuración
CREATE TABLE `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(191) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERTAR DATOS INICIALES --

-- Usuario admin (admin / admin123)
INSERT INTO `users` (`username`, `password`, `role`) 
VALUES ('admin', '$2y$10$WkG.C4oB3n0O.z8kL4A2QOTM8R7A6SHTgT./QcOzT3Y5T.S/oR6J6', 'admin');

-- Configuración básica
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES 
('site_name', 'Moratalla Murcia 2026'),
('ticker_text', 'Bienvenido al nuevo portal de Moratalla — Patrimonio Histórico Digital'),
('ticker_speed', '30');

-- Estructura de Menú Inicial
INSERT INTO `categories` (`id`, `name`, `slug`, `sort_order`) VALUES 
(1, 'Patrimonio', 'patrimonio', 1),
(2, 'Historia', 'historia', 2),
(3, 'Cultura', 'cultura', 3);

-- Páginas de ejemplo
INSERT INTO `pages` (`category_id`, `title`, `slug`, `content`) VALUES 
(1, 'Introducción al Patrimonio', 'introduccion-patrimonio', '<p>Bienvenido a la sección de patrimonio de Moratalla.</p>'),
(2, 'Breve Historia', 'breve-historia', '<p>Moratalla tiene una historia milenaria que comienza en la prehistoria...</p>');
