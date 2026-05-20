CREATE TABLE IF NOT EXISTS `news_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `event_date` DATE DEFAULT NULL,
    `is_active_home` TINYINT(1) DEFAULT 0,
    `category_id` INT DEFAULT NULL,
    `is_active_category` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
