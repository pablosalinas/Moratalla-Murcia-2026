ALTER TABLE `visit_logs` 
ADD COLUMN `country` VARCHAR(100) NULL AFTER `referrer`,
ADD COLUMN `city` VARCHAR(100) NULL AFTER `country`;
