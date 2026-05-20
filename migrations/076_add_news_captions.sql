ALTER TABLE news_images ADD COLUMN caption VARCHAR(255) NULL AFTER image_path;
ALTER TABLE news_events ADD COLUMN image_caption VARCHAR(255) NULL AFTER image_path;
