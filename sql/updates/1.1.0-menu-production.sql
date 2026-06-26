-- Menu production hardening + SystemMaintenance -> Maintenance.
-- Compatible MySQL/MariaDB migration using INFORMATION_SCHEMA + PREPARE.

UPDATE cms_modules
SET machine_name='Maintenance', name='Maintenance', source_path='cms/system/Maintenance', updated_at=NOW()
WHERE machine_name='SystemMaintenance';

UPDATE cms_routes
SET module='Maintenance', route_key=REPLACE(route_key,'SystemMaintenance:','Maintenance:'), controller=REPLACE(controller,'\\SystemMaintenance\\','\\Maintenance\\'), updated_at=NOW()
WHERE module='SystemMaintenance' OR controller LIKE '%\\SystemMaintenance\\%';

UPDATE menu_items SET module='Maintenance', updated_at=NOW() WHERE module='SystemMaintenance';

SET @sql := (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `link_type` ENUM('url','route','entity','separator','heading') NOT NULL DEFAULT 'url' AFTER `title`", 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND COLUMN_NAME='link_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `menu_items` ADD COLUMN `route_name` VARCHAR(190) NULL AFTER `url`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND COLUMN_NAME='route_name');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `menu_items` ADD COLUMN `entity_type` VARCHAR(100) NULL AFTER `route_name`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND COLUMN_NAME='entity_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `menu_items` ADD COLUMN `entity_id` VARCHAR(100) NULL AFTER `entity_type`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND COLUMN_NAME='entity_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `menu_items` ADD COLUMN `langcode` VARCHAR(12) NULL AFTER `entity_id`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND COLUMN_NAME='langcode');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `menu_items` ADD COLUMN `deleted_at` DATETIME NULL AFTER `updated_at`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND COLUMN_NAME='deleted_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE menu_items SET link_type='heading' WHERE url='#' AND (parent_key='' OR parent_key IS NULL) AND link_type='url';
UPDATE menu_items SET link_type='url' WHERE (link_type IS NULL OR link_type='') AND url <> '#';

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `menu_items` ADD INDEX `idx_menu_type` (`link_type`)', 'SELECT 1') FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND INDEX_NAME='idx_menu_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `menu_items` ADD INDEX `idx_menu_lang` (`langcode`)', 'SELECT 1') FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND INDEX_NAME='idx_menu_lang');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `menu_items` ADD INDEX `idx_menu_deleted` (`deleted_at`)', 'SELECT 1') FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menu_items' AND INDEX_NAME='idx_menu_deleted');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
