-- migrations/068_add_category_visibility.sql
-- Añadimos la columna `is_visible` a la tabla `categories` si no existe

SET @dbname = DATABASE();
SET @tablename = 'categories';
SET @columnname = 'is_visible';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE categories ADD COLUMN is_visible TINYINT(1) DEFAULT 1 AFTER sort_order"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
