-- v8 Kernel split: Dashboard, AuditLog and Maintenance are standalone modules.
-- Run once on databases upgraded from v7.x.

UPDATE cms_modules
SET description = 'Минимальное ядро CMS: admin shell, layout, guard, menu service, cache, hooks',
    version = '1.1.0', available_version = '1.1.0', installed_version = '1.1.0', updated_at = NOW()
WHERE machine_name = 'Kernel';

INSERT INTO cms_modules (machine_name, name, description, version, available_version, installed_version, install_status, is_installed, is_enabled, is_system, menu_order, dependencies, installed_at)
VALUES
('Dashboard','Dashboard','Главная панель администрирования и виджеты CMS','1.0.0','1.0.0','1.0.0','installed',1,1,1,12,'{"Kernel":"1.0.0"}',NOW()),
('AuditLog','Audit Log','Просмотр журнала действий и подозрительных событий','1.0.0','1.0.0','1.0.0','installed',1,1,1,36,'{"Kernel":"1.0.0"}',NOW()),
('Maintenance','Maintenance','Регламентная очистка и сервисное обслуживание CMS','1.0.0','1.0.0','1.0.0','installed',1,1,1,38,'{"Kernel":"1.0.0"}',NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    version = VALUES(version),
    available_version = VALUES(available_version),
    installed_version = VALUES(installed_version),
    is_installed = 1,
    is_enabled = 1,
    is_system = 1,
    updated_at = NOW();

UPDATE admin_menu_items
SET module = 'Dashboard', updated_at = NOW()
WHERE menu_key = 'dashboard' OR url = '/admin';

UPDATE admin_menu_items
SET menu_key = 'audit_log.audit', module = 'AuditLog', updated_at = NOW()
WHERE menu_key = 'core.audit' OR url = '/admin/security/audit';

UPDATE admin_menu_items
SET menu_key = 'audit_log.suspicious', module = 'AuditLog', updated_at = NOW()
WHERE menu_key = 'core.suspicious' OR url = '/admin/security/suspicious';

DELETE FROM cms_routes
WHERE path IN ('/admin','/admin/dashboard','/admin/security/audit','/admin/security/suspicious','/admin/system/cleanup');

INSERT INTO cms_routes (module, route_key, http_method, path, controller, action, is_admin, is_active, is_system, sort_order, created_at) VALUES
('Dashboard','Dashboard:GET:/admin','GET','/admin','\\Modules\\Dashboard\\Controllers\\DashboardController','index',1,1,1,21,NOW()),
('Dashboard','Dashboard:GET:/admin/dashboard','GET','/admin/dashboard','\\Modules\\Dashboard\\Controllers\\DashboardController','index',1,1,1,22,NOW()),
('AuditLog','AuditLog:GET:/admin/security/audit','GET','/admin/security/audit','\\Modules\\AuditLog\\Controllers\\AuditLogController','audit',1,1,1,23,NOW()),
('AuditLog','AuditLog:GET:/admin/security/suspicious','GET','/admin/security/suspicious','\\Modules\\AuditLog\\Controllers\\AuditLogController','suspicious',1,1,1,24,NOW()),
('Maintenance','Maintenance:POST:/admin/system/cleanup','POST','/admin/system/cleanup','\\Modules\\Maintenance\\Controllers\\MaintenanceController','cleanup',1,1,1,25,NOW());
