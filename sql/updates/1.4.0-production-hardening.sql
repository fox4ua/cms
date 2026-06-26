-- CMS 1.4.0 production hardening. Safe to run repeatedly on MySQL/MariaDB.

CREATE TABLE IF NOT EXISTS cms_schema_updates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL,
    status ENUM('success','error') NOT NULL DEFAULT 'success',
    checksum CHAR(64) NULL,
    applied_at DATETIME NOT NULL,
    UNIQUE KEY uq_schema_version (version),
    INDEX idx_schema_status_applied (status, applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS module_operation_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    operation_id CHAR(32) NOT NULL UNIQUE,
    module VARCHAR(100) NOT NULL,
    operation VARCHAR(40) NOT NULL,
    requested_by CHAR(36) NULL,
    owner_ip VARCHAR(45) NULL,
    from_version VARCHAR(50) NULL,
    to_version VARCHAR(50) NULL,
    status ENUM('running','success','error') NOT NULL DEFAULT 'running',
    message TEXT NULL,
    error_class VARCHAR(255) NULL,
    error_hash CHAR(64) NULL,
    context_json JSON NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    duration_ms INT UNSIGNED NULL,
    INDEX idx_module_operation (module, operation),
    INDEX idx_status_started (status, started_at),
    INDEX idx_requested_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `cms_locks` ADD COLUMN `lock_token` CHAR(64) NULL AFTER `lock_key`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='cms_locks' AND COLUMN_NAME='lock_token');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `cms_locks` ADD COLUMN `operation` VARCHAR(190) NULL AFTER `owner`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='cms_locks' AND COLUMN_NAME='operation');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `cms_locks` ADD COLUMN `updated_at` DATETIME NULL AFTER `created_at`', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='cms_locks' AND COLUMN_NAME='updated_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE cms_locks SET lock_token = SHA2(CONCAT(lock_key, created_at, RAND()), 256) WHERE lock_token IS NULL OR lock_token='';
SET @nullable := (SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='cms_locks' AND COLUMN_NAME='lock_token' LIMIT 1);
SET @sql := IF(@nullable='YES', 'ALTER TABLE `cms_locks` MODIFY `lock_token` CHAR(64) NOT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO cms_modules (machine_name,name,description,version,available_version,installed_version,source_type,source_path,install_status,is_installed,is_enabled,is_system,menu_order,dependencies,installed_at,updated_at)
VALUES
('Installer','Installer','Одноразовый защищённый веб-установщик CMS','1.0.0','1.0.0','1.0.0','system','cms/system/Installer','installed',1,1,1,5,'{"Kernel":"1.2.0"}',NOW(),NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),version=VALUES(version),available_version=VALUES(available_version),installed_version=VALUES(installed_version),source_type='system',source_path=VALUES(source_path),is_system=1,updated_at=NOW();

UPDATE cms_modules SET version='1.2.0',available_version='1.2.0',installed_version='1.2.0',description='Минимальное ядро CMS: bootstrap, admin shell, contracts, security and infrastructure services',updated_at=NOW() WHERE machine_name='Kernel';
UPDATE cms_modules SET version='1.5.0',available_version='1.5.0',installed_version='1.5.0',description='Установка, обновление, включение, отключение и аудит операций модулей CMS',updated_at=NOW() WHERE machine_name='ModuleManager';
UPDATE cms_modules SET version='1.1.0',available_version='1.1.0',installed_version='1.1.0',description='Проверка liveness, readiness и production-настроек CMS',updated_at=NOW() WHERE machine_name='SystemHealth';
UPDATE cms_modules SET version='1.1.0',available_version='1.1.0',installed_version='1.1.0',description='Системные настройки CMS, включая HTTP security headers',updated_at=NOW() WHERE machine_name='Settings';
UPDATE cms_modules SET version='1.1.0',available_version='1.1.0',installed_version='1.1.0',description='Оркестрация модульных операций обслуживания CMS',updated_at=NOW() WHERE machine_name='Maintenance';
UPDATE cms_modules SET version='1.2.0',available_version='1.2.0',installed_version='1.2.0',description='Универсальное управление всеми меню CMS',dependencies='{"Kernel":"1.2.0"}',updated_at=NOW() WHERE machine_name='Menu';
UPDATE cms_modules SET dependencies='{"Kernel":"1.2.0"}',updated_at=NOW() WHERE machine_name IN ('Auth','Dashboard','AuditLog');
UPDATE cms_modules SET dependencies='{"Kernel":"1.2.0","ModuleManager":"1.3.0"}',updated_at=NOW() WHERE machine_name='RouteManager';
UPDATE cms_modules SET dependencies='{"Kernel":"1.2.0","Settings":"1.1.0"}',updated_at=NOW() WHERE machine_name='SystemHealth';

INSERT INTO cms_routes (module,route_key,http_method,path,controller,action,is_admin,is_active,is_system,sort_order,created_at,updated_at) VALUES
('ModuleManager','ModuleManager:GET:/admin/modules/operations','GET','/admin/modules/operations','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','operations',1,1,1,311,NOW(),NOW()),
('ModuleManager','ModuleManager:GET:/admin/modules/locks','GET','/admin/modules/locks','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','locks',1,1,1,312,NOW(),NOW()),
('ModuleManager','ModuleManager:POST:/admin/modules/locks/force/(:segment)','POST','/admin/modules/locks/force/(:segment)','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','forceUnlock/$1',1,1,1,313,NOW(),NOW()),
('Maintenance','Maintenance:GET:/admin/system/maintenance','GET','/admin/system/maintenance','\\Modules\\Maintenance\\Controllers\\MaintenanceController','index',1,1,1,314,NOW(),NOW())
ON DUPLICATE KEY UPDATE module=VALUES(module),controller=VALUES(controller),action=VALUES(action),is_active=1,is_system=1,updated_at=NOW();

INSERT INTO menu_items (menu_key,item_key,parent_key,title,link_type,url,icon,module,permission,target,weight,is_active,is_system,created_at,updated_at) VALUES
('admin_sidebar','module_manager.operations','system','Операции модулей','url','/admin/modules/operations','clock-history','ModuleManager','','',11,1,1,NOW(),NOW()),
('admin_sidebar','module_manager.locks','system','Блокировки','url','/admin/modules/locks','lock','ModuleManager','','',12,1,1,NOW(),NOW()),
('admin_sidebar','maintenance.manage','system','Обслуживание','url','/admin/system/maintenance','tools','Maintenance','','',34,1,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title),url=VALUES(url),icon=VALUES(icon),module=VALUES(module),weight=VALUES(weight),is_active=1,deleted_at=NULL,updated_at=NOW();

INSERT INTO system_settings (setting_group,setting_key,setting_label,setting_value,field_type,field_options,description,is_public,is_system,sort_order,is_required,min_value,max_value,validation_rule,is_secret,created_at,updated_at) VALUES
('security','security_csp','Content Security Policy','default-src ''self''; img-src ''self'' data:; style-src ''self'' ''unsafe-inline''; script-src ''self''; font-src ''self'' data:; connect-src ''self''; frame-ancestors ''self''; base-uri ''self''; form-action ''self''','textarea',NULL,'Основная CSP политика. Обязательно должна содержать default-src.',0,1,10,1,NULL,NULL,'csp',0,NOW(),NOW()),
('security','security_csp_report_only','CSP Report-Only','0','checkbox',NULL,'Не блокировать нарушения CSP.',0,1,20,0,NULL,NULL,NULL,0,NOW(),NOW()),
('security','security_hsts_enabled','HSTS','1','checkbox',NULL,'Отправлять HSTS только при HTTPS.',0,1,30,0,NULL,NULL,NULL,0,NOW(),NOW()),
('security','security_hsts_max_age','HSTS max-age','31536000','number',NULL,'Срок HSTS в секундах.',0,1,40,0,300,63072000,NULL,0,NOW(),NOW()),
('security','security_hsts_include_subdomains','HSTS includeSubDomains','1','checkbox',NULL,'Применять HSTS к поддоменам.',0,1,50,0,NULL,NULL,NULL,0,NOW(),NOW()),
('security','security_hsts_preload','HSTS preload','0','checkbox',NULL,'Добавлять preload только после проверки домена.',0,1,60,0,NULL,NULL,NULL,0,NOW(),NOW()),
('security','security_frame_options','X-Frame-Options','SAMEORIGIN','select','{"DENY":"DENY","SAMEORIGIN":"SAMEORIGIN"}','Защита от clickjacking.',0,1,70,1,NULL,NULL,'header',0,NOW(),NOW()),
('security','security_referrer_policy','Referrer-Policy','strict-origin-when-cross-origin','text',NULL,'Политика Referer.',0,1,80,1,NULL,NULL,'header',0,NOW(),NOW()),
('security','security_permissions_policy','Permissions-Policy','camera=(), microphone=(), geolocation=()','textarea',NULL,'Разрешения браузерных API.',0,1,90,1,NULL,NULL,'header',0,NOW(),NOW())
ON DUPLICATE KEY UPDATE setting_label=VALUES(setting_label),field_type=VALUES(field_type),field_options=VALUES(field_options),description=VALUES(description),is_system=1,sort_order=VALUES(sort_order),is_required=VALUES(is_required),min_value=VALUES(min_value),max_value=VALUES(max_value),validation_rule=VALUES(validation_rule),updated_at=NOW();

INSERT INTO cms_schema_updates (version,status,checksum,applied_at)
VALUES ('1.4.0','success',SHA2('1.4.0-production-hardening',256),NOW())
ON DUPLICATE KEY UPDATE status='success',checksum=VALUES(checksum),applied_at=NOW();
