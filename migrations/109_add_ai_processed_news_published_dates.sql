-- Estructura de tabla ai_processed_news con columnas de fecha real y delimitacion de busqueda de noticias
CREATE TABLE IF NOT EXISTS `ai_processed_news` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `url_hash` CHAR(64) NOT NULL,
    `normalized_title` VARCHAR(255) NOT NULL,
    `source_name` VARCHAR(100) DEFAULT '',
    `status` ENUM('new','available','published','discarded') NOT NULL DEFAULT 'new',
    `news_event_id` INT NULL,
    `title_original` VARCHAR(255) NOT NULL,
    `url_original` TEXT NULL,
    `published_title` VARCHAR(255) NULL,
    `snippet_summary` TEXT NULL,
    `remote_image_url` TEXT NULL,
    `published_date` VARCHAR(100) NULL,
    `published_date_iso` DATE NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_url_hash` (`url_hash`),
    INDEX `idx_norm_title` (`normalized_title`),
    INDEX `idx_status` (`status`),
    INDEX `idx_pub_date_iso` (`published_date_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('news_search_date_from', ''),
('news_search_date_to', '')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
