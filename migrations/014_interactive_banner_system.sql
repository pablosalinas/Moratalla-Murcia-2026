-- 014_interactive_banner_system.sql
-- Implementación del sistema de banners interactivos

CREATE TABLE IF NOT EXISTS `banners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `image_path` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `link` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Añadir configuración por defecto para la velocidad del banner (5 segundos)
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('banner_speed', '5000');
