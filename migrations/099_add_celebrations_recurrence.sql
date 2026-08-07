ALTER TABLE `celebrations` 
ADD COLUMN `event_date` DATE NULL AFTER `end_date`,
ADD COLUMN `recurrence` VARCHAR(50) NOT NULL DEFAULT 'none' AFTER `event_date`,
ADD COLUMN `easter_offset` INT NULL AFTER `recurrence`;
