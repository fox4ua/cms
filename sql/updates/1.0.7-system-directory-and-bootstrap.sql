-- v11: rename cms/core directory concept to cms/system and register production bootstrap metadata.
-- Safe to run multiple times.

SET @schema := DATABASE();

-- If source_type still uses ENUM('core','modules'), convert it to ENUM('system','modules').
SET @coltype := (
    SELECT COLUMN_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema AND TABLE_NAME = 'cms_modules' AND COLUMN_NAME = 'source_type'
    LIMIT 1
);

SET @sql := IF(
    @coltype LIKE '%core%',
    'ALTER TABLE cms_modules MODIFY source_type ENUM(''system'',''modules'') NOT NULL DEFAULT ''modules''',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE cms_modules
SET source_type = 'system',
    source_path = REPLACE(source_path, 'cms/core/', 'cms/system/'),
    updated_at = NOW()
WHERE source_path LIKE 'cms/core/%'
   OR machine_name IN ('Kernel','Dashboard','Auth','ModuleManager','RouteManager','SystemHealth','AuditLog','Maintenance','Menu','Settings');

UPDATE cms_modules
SET source_path = CONCAT('cms/system/', machine_name),
    source_type = 'system',
    updated_at = NOW()
WHERE machine_name IN ('Kernel','Dashboard','Auth','ModuleManager','RouteManager','SystemHealth','AuditLog','Maintenance','Menu','Settings');
