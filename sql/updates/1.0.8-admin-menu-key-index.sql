-- Fix admin_menu_items uniqueness for hierarchical/group menu items.
-- Old uq_url_module(url,module) breaks group rows that share url='#' and module=''.

-- Ensure menu_key exists.
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `admin_menu_items` ADD COLUMN `menu_key` VARCHAR(190) NULL AFTER `id`',
    'SELECT 1')
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'admin_menu_items'
  AND COLUMN_NAME = 'menu_key');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Fill empty menu_key values with deterministic unique legacy keys.
UPDATE admin_menu_items
SET menu_key = CONCAT('legacy_', id)
WHERE menu_key IS NULL OR menu_key = '';

-- Drop wrong unique index if it exists.
SET @sql := (SELECT IF(COUNT(*) > 0,
    'ALTER TABLE `admin_menu_items` DROP INDEX `uq_url_module`',
    'SELECT 1')
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'admin_menu_items'
  AND INDEX_NAME = 'uq_url_module');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- If menu_key still has duplicates for any reason, make them unique before adding unique index.
UPDATE admin_menu_items ami
JOIN (
    SELECT menu_key
    FROM admin_menu_items
    GROUP BY menu_key
    HAVING COUNT(*) > 1
) d ON d.menu_key = ami.menu_key
SET ami.menu_key = CONCAT(ami.menu_key, '_', ami.id);

-- Add correct unique index by menu_key if missing.
SET @sql := (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `admin_menu_items` ADD UNIQUE KEY `uq_menu_key` (`menu_key`)',
    'SELECT 1')
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'admin_menu_items'
  AND INDEX_NAME = 'uq_menu_key');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Make menu_key NOT NULL after values are guaranteed.
SET @sql := (SELECT IF(IS_NULLABLE = 'YES',
    'ALTER TABLE `admin_menu_items` MODIFY `menu_key` VARCHAR(190) NOT NULL',
    'SELECT 1')
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'admin_menu_items'
  AND COLUMN_NAME = 'menu_key');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
