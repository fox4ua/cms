-- v9: split module filesystem into cms/system and cms/modules.
-- Compatible with older MySQL/MariaDB: uses INFORMATION_SCHEMA + PREPARE.

SET @schema := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cms_modules ADD COLUMN source_type ENUM(''system'',''modules'') NOT NULL DEFAULT ''modules'' AFTER installed_version',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'cms_modules' AND COLUMN_NAME = 'source_type'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE cms_modules ADD COLUMN source_path VARCHAR(255) NULL AFTER source_type',
        'SELECT 1'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'cms_modules' AND COLUMN_NAME = 'source_path'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE cms_modules
SET source_type='system', source_path=CONCAT('cms/system/', machine_name)
WHERE machine_name IN ('Kernel','Dashboard','Auth','ModuleManager','RouteManager','SystemHealth','AuditLog','Maintenance','Menu','Settings');

UPDATE cms_modules
SET source_type='modules', source_path=CONCAT('cms/modules/', machine_name)
WHERE source_path IS NULL OR source_path = '';
