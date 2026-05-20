CREATE TABLE IF NOT EXISTS `news_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `news_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`news_id`) REFERENCES `news_events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
