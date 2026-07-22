-- Creación de la Base de Datos moratalla-murcia-2026
CREATE DATABASE IF NOT EXISTS `moratalla-murcia-2026` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `moratalla-murcia-2026`;

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(191) NOT NULL UNIQUE,
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

-- Insertar el primer usuario administrador (pass: admin123)
-- hash bcrypt: $2y$10$WkG.C4oB3n0O.z8kL4A2QOTM8R7A6SHTgT./QcOzT3Y5T.S/oR6J6
INSERT IGNORE INTO `users` (`username`, `password`, `role`) VALUES ('admin', '$2y$10$WkG.C4oB3n0O.z8kL4A2QOTM8R7A6SHTgT./QcOzT3Y5T.S/oR6J6', 'admin');

CREATE TABLE IF NOT EXISTS `visit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT,
    `browser` VARCHAR(100),
    `os` VARCHAR(100),
    `page_url` VARCHAR(255),
    `referrer` VARCHAR(255),
    `is_new_session` TINYINT(1) DEFAULT 0,
    `visit_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `celebrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `html_content` TEXT,
    `css_content` TEXT,
    `js_content` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

