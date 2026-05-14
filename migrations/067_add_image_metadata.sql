-- migrations/067_add_image_metadata.sql
-- Añadimos las columnas de orden y visibilidad a page_images si no existen
-- El campo `caption` ya existe desde el schema inicial.

SET @dbname = DATABASE();
SET @tablename = 'page_images';
SET @columnname1 = 'sort_order';
SET @columnname2 = 'is_visible';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname1)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE page_images ADD COLUMN sort_order INT DEFAULT 0 AFTER caption"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement2 = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname2)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE page_images ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER sort_order"
));
PREPARE alterIfNotExists2 FROM @preparedStatement2;
EXECUTE alterIfNotExists2;
DEALLOCATE PREPARE alterIfNotExists2;
