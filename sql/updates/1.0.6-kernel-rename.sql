-- v10: rename system module Core to Kernel after cms/system structure change.
-- Compatible with MySQL/MariaDB. Safe to run multiple times.

UPDATE cms_modules
SET machine_name = 'Kernel',
    name = 'Kernel',
    description = 'Минимальное ядро CMS: admin shell, layout, guard, menu service, cache, hooks',
    source_type = 'system',
    source_path = 'cms/system/Kernel',
    is_system = 1,
    is_installed = 1,
    is_enabled = 1,
    updated_at = NOW()
WHERE machine_name = 'Core';

UPDATE cms_modules
SET dependencies = REPLACE(REPLACE(dependencies, '"Core"', '"Kernel"'), '{"Core":', '{"Kernel":')
WHERE dependencies LIKE '%Core%';

UPDATE cms_routes
SET controller = REPLACE(controller, '\\Modules\\Core\\', '\\Modules\\Kernel\\')
WHERE controller LIKE '%\\Modules\\Core\\%';

UPDATE auth_extension_hooks
SET handler_class = REPLACE(handler_class, '\\Modules\\Core\\', '\\Modules\\Kernel\\')
WHERE handler_class LIKE '%\\Modules\\Core\\%';

UPDATE cms_module_updates
SET module = 'Kernel'
WHERE module = 'Core';

UPDATE cms_modules
SET source_path = CONCAT('cms/system/', machine_name), source_type = 'system'
WHERE machine_name IN ('Kernel','Dashboard','Auth','ModuleManager','RouteManager','SystemHealth','AuditLog','Maintenance','Menu','Settings');
