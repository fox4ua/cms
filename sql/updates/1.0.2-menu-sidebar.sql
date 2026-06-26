-- Admin menu hierarchy/sidebar upgrade. MySQL/MariaDB compatible, no ALTER ... IF NOT EXISTS.
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `admin_menu_items` ADD COLUMN `menu_key` VARCHAR(190) NULL AFTER `id`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_menu_items' AND COLUMN_NAME = 'menu_key');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `admin_menu_items` ADD COLUMN `parent_key` VARCHAR(190) NULL AFTER `menu_key`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_menu_items' AND COLUMN_NAME = 'parent_key');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `admin_menu_items` ADD INDEX `idx_menu_key` (`menu_key`)', 'SELECT 1') FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_menu_items' AND INDEX_NAME = 'idx_menu_key');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `admin_menu_items` ADD INDEX `idx_parent_key` (`parent_key`)', 'SELECT 1') FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_menu_items' AND INDEX_NAME = 'idx_parent_key');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE admin_menu_items SET menu_key = CONCAT(module, ':', url) WHERE menu_key IS NULL OR menu_key = '';

INSERT INTO admin_menu_items (menu_key, parent_key, parent_id, title, url, icon, module, permission, weight, is_active, is_system, created_at)
SELECT 'system','',NULL,'Система','#','boxes','','',20,1,1,NOW()
WHERE NOT EXISTS (SELECT 1 FROM admin_menu_items WHERE menu_key='system');

INSERT INTO admin_menu_items (menu_key, parent_key, parent_id, title, url, icon, module, permission, weight, is_active, is_system, created_at)
SELECT 'security','',NULL,'Безопасность','#','shield-lock','','',30,1,1,NOW()
WHERE NOT EXISTS (SELECT 1 FROM admin_menu_items WHERE menu_key='security');

INSERT INTO admin_menu_items (menu_key, parent_key, parent_id, title, url, icon, module, permission, weight, is_active, is_system, created_at)
SELECT 'configuration','',NULL,'Конфигурация','#','gear','','',40,1,1,NOW()
WHERE NOT EXISTS (SELECT 1 FROM admin_menu_items WHERE menu_key='configuration');

UPDATE admin_menu_items SET parent_key='system', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='system') AS p), weight=10 WHERE url='/admin/modules';
UPDATE admin_menu_items SET parent_key='system', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='system') AS p), weight=20 WHERE url='/admin/routes';
UPDATE admin_menu_items SET parent_key='system', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='system') AS p), weight=30 WHERE url='/admin/menu';
UPDATE admin_menu_items SET parent_key='system', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='system') AS p), weight=40 WHERE url='/admin/system/health';
UPDATE admin_menu_items SET parent_key='security', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='security') AS p), weight=10 WHERE url='/admin/auth/settings';
UPDATE admin_menu_items SET parent_key='security', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='security') AS p), weight=20 WHERE url='/admin/auth/ip-rules';
UPDATE admin_menu_items SET parent_key='security', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='security') AS p), weight=30 WHERE url='/admin/auth/sessions';
UPDATE admin_menu_items SET parent_key='security', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='security') AS p), weight=40 WHERE url='/admin/auth/extensions';
UPDATE admin_menu_items SET parent_key='security', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='security') AS p), weight=50 WHERE url='/admin/security/audit';
UPDATE admin_menu_items SET parent_key='security', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='security') AS p), weight=60 WHERE url='/admin/security/suspicious';
UPDATE admin_menu_items SET parent_key='security', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='security') AS p), weight=70 WHERE url='/admin/auth/password';
UPDATE admin_menu_items SET parent_key='configuration', parent_id=(SELECT id FROM (SELECT id FROM admin_menu_items WHERE menu_key='configuration') AS p), weight=10 WHERE url='/admin/settings';
