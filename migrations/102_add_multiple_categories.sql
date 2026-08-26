ALTER TABLE `pages` ADD COLUMN `category_id_2` INT DEFAULT NULL AFTER `category_id`;
ALTER TABLE `pages` ADD COLUMN `category_id_3` INT DEFAULT NULL AFTER `category_id_2`;
ALTER TABLE `pages` ADD CONSTRAINT `fk_pages_cat2` FOREIGN KEY (`category_id_2`) REFERENCES `categories`(`id`) ON DELETE SET NULL;
ALTER TABLE `pages` ADD CONSTRAINT `fk_pages_cat3` FOREIGN KEY (`category_id_3`) REFERENCES `categories`(`id`) ON DELETE SET NULL;

ALTER TABLE `news_events` ADD COLUMN `category_id_2` INT DEFAULT NULL AFTER `category_id`;
ALTER TABLE `news_events` ADD COLUMN `category_id_3` INT DEFAULT NULL AFTER `category_id_2`;
ALTER TABLE `news_events` ADD CONSTRAINT `fk_news_cat2` FOREIGN KEY (`category_id_2`) REFERENCES `categories`(`id`) ON DELETE SET NULL;
ALTER TABLE `news_events` ADD CONSTRAINT `fk_news_cat3` FOREIGN KEY (`category_id_3`) REFERENCES `categories`(`id`) ON DELETE SET NULL;
