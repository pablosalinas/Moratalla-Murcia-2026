ALTER TABLE `news_events` ADD COLUMN `image_author` VARCHAR(255) NULL AFTER `image_caption`;
ALTER TABLE `news_images` ADD COLUMN `author` VARCHAR(255) NULL AFTER `caption`;
ALTER TABLE `page_images` ADD COLUMN `author` VARCHAR(255) NULL AFTER `caption`;
