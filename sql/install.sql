-- Clean installation SQL for CMS v14 production hardening.
-- This file is for a new empty database only.
-- Upgrades from older builds must use sql/updates/*.sql.

CREATE TABLE users (
    id CHAR(36) PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','blocked') NOT NULL DEFAULT 'active',
    password_changed_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    selector CHAR(24) NOT NULL UNIQUE,
    token_hash VARCHAR(255) NOT NULL,
    type ENUM('refresh','remember') NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent_hash CHAR(64) NULL,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_user_type (user_id, type),
    INDEX idx_expires (expires_at),
    CONSTRAINT fk_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE active_sessions (
    id CHAR(32) PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    session_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent_hash CHAR(64) NOT NULL,
    created_at DATETIME NOT NULL,
    last_activity_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_login_failed (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    blocked_until DATETIME NULL,
    last_attempt_at DATETIME NOT NULL,
    UNIQUE KEY uniq_email_ip (email, ip_address),
    INDEX idx_blocked_until (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_action_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id CHAR(36) NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_admin_created (admin_id, created_at),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suspicious_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NULL,
    event VARCHAR(100) NOT NULL,
    context_json JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_event_created (event, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_ip_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_value VARCHAR(64) NOT NULL,
    rule_type ENUM('allow','deny') NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uniq_ip_rule (ip_value, rule_type),
    INDEX idx_type_active (rule_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_password_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_user_created (user_id, created_at),
    CONSTRAINT fk_password_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_security_flags (
    user_id CHAR(36) PRIMARY KEY,
    force_password_change TINYINT(1) NOT NULL DEFAULT 0,
    password_changed_at DATETIME NULL,
    two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
    two_factor_secret VARCHAR(255) NULL,
    login_allowed_from DATETIME NULL,
    login_allowed_until DATETIME NULL,
    allowed_ip_list TEXT NULL,
    denied_ip_list TEXT NULL,
    updated_at DATETIME NULL,
    CONSTRAINT fk_security_flags_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE auth_extension_hooks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    hook_name VARCHAR(100) NOT NULL,
    handler_class VARCHAR(255) NOT NULL,
    handler_method VARCHAR(100) NOT NULL DEFAULT 'handle',
    priority INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uniq_auth_hook_handler (module, hook_name, handler_class, handler_method),
    INDEX idx_hook_active (hook_name, is_active, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cms_schema_updates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(50) NOT NULL,
    status ENUM('success','error') NOT NULL DEFAULT 'success',
    checksum CHAR(64) NULL,
    applied_at DATETIME NOT NULL,
    UNIQUE KEY uq_schema_version (version),
    INDEX idx_schema_status_applied (status, applied_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cms_modules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    machine_name VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(255) NULL,
    version VARCHAR(50) NOT NULL DEFAULT '1.0.0',
    available_version VARCHAR(50) NOT NULL DEFAULT '1.0.0',
    installed_version VARCHAR(50) NOT NULL DEFAULT '1.0.0',
    source_type ENUM('system','modules') NOT NULL DEFAULT 'modules',
    source_path VARCHAR(255) NULL,
    install_status ENUM('discovered','installing','installed','updating','error') NOT NULL DEFAULT 'installed',
    is_installed TINYINT(1) NOT NULL DEFAULT 1,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    menu_order INT NOT NULL DEFAULT 100,
    dependencies TEXT NULL,
    last_error TEXT NULL,
    installed_at DATETIME NULL,
    updated_at DATETIME NULL,
    INDEX idx_installed_enabled (is_installed, is_enabled),
    INDEX idx_status (install_status),
    INDEX idx_order (menu_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cms_module_updates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    from_version VARCHAR(50) NOT NULL DEFAULT '0.0.0',
    to_version VARCHAR(50) NOT NULL,
    sql_file VARCHAR(255) NULL,
    status ENUM('success','error') NOT NULL,
    error TEXT NULL,
    executed_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_module_version (module, to_version),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cms_locks (
    lock_key VARCHAR(190) PRIMARY KEY,
    lock_token CHAR(64) NOT NULL,
    owner VARCHAR(190) NULL,
    operation VARCHAR(190) NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_expires_at (expires_at),
    INDEX idx_owner (owner)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE module_operation_logs (
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

CREATE TABLE menus (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_key VARCHAR(100) NOT NULL,
    title VARCHAR(190) NOT NULL,
    description VARCHAR(255) NULL,
    area VARCHAR(100) NOT NULL DEFAULT 'frontend',
    module VARCHAR(100) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_menus_key (menu_key),
    INDEX idx_menus_area (area),
    INDEX idx_menus_active (is_active),
    INDEX idx_menus_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_key VARCHAR(100) NOT NULL,
    item_key VARCHAR(190) NOT NULL,
    parent_key VARCHAR(190) NULL,
    title VARCHAR(190) NOT NULL,
    link_type ENUM('url','route','entity','separator','heading') NOT NULL DEFAULT 'url',
    url VARCHAR(255) NOT NULL DEFAULT '#',
    route_name VARCHAR(190) NULL,
    entity_type VARCHAR(100) NULL,
    entity_id VARCHAR(100) NULL,
    langcode VARCHAR(12) NULL,
    icon VARCHAR(100) NULL,
    module VARCHAR(100) NULL,
    permission VARCHAR(190) NULL,
    target VARCHAR(30) NULL,
    weight INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_menu_item (menu_key, item_key),
    INDEX idx_menu_parent (menu_key, parent_key),
    INDEX idx_menu_module (module),
    INDEX idx_menu_type (link_type),
    INDEX idx_menu_lang (langcode),
    INDEX idx_menu_deleted (deleted_at),
    INDEX idx_menu_active (is_active, weight),
    CONSTRAINT fk_menu_items_menu FOREIGN KEY (menu_key) REFERENCES menus(menu_key) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cms_routes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL,
    route_key VARCHAR(190) NOT NULL UNIQUE,
    http_method VARCHAR(20) NOT NULL DEFAULT 'GET',
    path VARCHAR(255) NOT NULL,
    controller VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL DEFAULT 'index',
    is_admin TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uniq_method_path (http_method, path),
    INDEX idx_module_active (module, is_active),
    INDEX idx_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE system_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_group VARCHAR(100) NOT NULL,
    setting_key VARCHAR(150) NOT NULL UNIQUE,
    setting_label VARCHAR(190) NOT NULL,
    setting_value TEXT NULL,
    field_type ENUM('text','textarea','number','checkbox','select','json') NOT NULL DEFAULT 'text',
    field_options TEXT NULL,
    description VARCHAR(255) NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 100,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    min_value INT NULL,
    max_value INT NULL,
    validation_rule VARCHAR(255) NULL,
    is_secret TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_group_sort (setting_group, sort_order),
    INDEX idx_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Administrator is created by the protected web installer; no default credentials are shipped.

INSERT INTO auth_settings (setting_key, setting_value, updated_at) VALUES
('max_failed_attempts','5',NOW()),
('block_base_seconds','60',NOW()),
('block_multiplier','20',NOW()),
('session_idle_ttl','1800',NOW()),
('session_absolute_ttl','43200',NOW()),
('remember_enabled','1',NOW()),
('remember_me_expiry','7776000',NOW()),
('remember_rotate_after','604800',NOW()),
('remember_max_tokens','5',NOW()),
('password_min_length','14',NOW()),
('password_require_upper','1',NOW()),
('password_require_lower','1',NOW()),
('password_require_digit','1',NOW()),
('password_require_special','1',NOW()),
('password_history_count','5',NOW()),
('password_expires_days','180',NOW()),
('captcha_enabled','0',NOW()),
('captcha_after_attempts','3',NOW()),
('captcha_skip_internal_ip','1',NOW()),
('two_factor_mode','off',NOW()),
('two_factor_skip_internal_ip','1',NOW()),
('admin_ip_allowlist_enabled','0',NOW()),
('internal_ip_ranges','10.0.0.0/8\n172.16.0.0/12\n192.168.0.0/16\n127.0.0.1/32\n::1/128',NOW());

INSERT INTO cms_schema_updates (version,status,checksum,applied_at)
VALUES ('1.4.0','success',SHA2('clean-install-1.4.0',256),NOW());

INSERT INTO cms_modules (machine_name, name, description, version, available_version, installed_version, source_type, source_path, install_status, is_installed, is_enabled, is_system, menu_order, dependencies, installed_at) VALUES
('Kernel','Kernel','Минимальное ядро CMS: bootstrap, admin shell, contracts, security and infrastructure services','1.2.0','1.2.0','1.2.0','system','cms/system/Kernel','installed',1,1,1,10,'{}',NOW()),
('Dashboard','Dashboard','Главная панель администрирования и виджеты CMS','1.0.0','1.0.0','1.0.0','system','cms/system/Dashboard','installed',1,1,1,12,'{"Kernel":"1.2.0"}',NOW()),
('Auth','Auth','Авторизация, сессии, настройки безопасности и управление входом','1.0.0','1.0.0','1.0.0','system','cms/system/Auth','installed',1,1,1,20,'{"Kernel":"1.2.0"}',NOW()),
('ModuleManager','Module Manager','Установка, обновление, включение, отключение и аудит операций модулей CMS','1.5.0','1.5.0','1.5.0','system','cms/system/ModuleManager','installed',1,1,1,30,'{"Kernel":"1.2.0"}',NOW()),
('RouteManager','Route Manager','Управление маршрутами CMS из базы данных','1.1.0','1.1.0','1.1.0','system','cms/system/RouteManager','installed',1,1,1,32,'{"Kernel":"1.2.0","ModuleManager":"1.3.0"}',NOW()),
('SystemHealth','System Health','Проверка liveness, readiness и production-настроек CMS','1.1.0','1.1.0','1.1.0','system','cms/system/SystemHealth','installed',1,1,1,34,'{"Kernel":"1.2.0","Settings":"1.1.0"}',NOW()),
('AuditLog','Audit Log','Просмотр журнала действий и подозрительных событий','1.0.0','1.0.0','1.0.0','system','cms/system/AuditLog','installed',1,1,1,36,'{"Kernel":"1.2.0"}',NOW()),
('Maintenance','Maintenance','Оркестрация модульных операций обслуживания CMS','1.1.0','1.1.0','1.1.0','system','cms/system/Maintenance','installed',1,1,1,38,'{"Kernel":"1.2.0"}',NOW()),
('Menu','Menu','Универсальное управление меню CMS','1.2.0','1.2.0','1.2.0','system','cms/system/Menu','installed',1,1,1,40,'{"Kernel":"1.2.0"}',NOW()),
('Settings','Settings','Системные настройки CMS, включая HTTP security headers','1.1.0','1.1.0','1.1.0','system','cms/system/Settings','installed',1,1,1,50,'{"Kernel":"1.2.0"}',NOW()),
('Installer','Installer','Одноразовый защищённый веб-установщик CMS','1.0.0','1.0.0','1.0.0','system','cms/system/Installer','installed',1,1,1,5,'{"Kernel":"1.2.0"}',NOW());

INSERT INTO menus (menu_key, title, description, area, module, is_system, is_active, created_at) VALUES
('admin_sidebar','Боковое меню админки','Основное навигационное меню административной панели','admin','Menu',1,1,NOW()),
('admin_topbar','Верхнее меню админки','Верхнее меню административной панели','admin','Menu',1,1,NOW()),
('frontend_main','Главное меню сайта','Основное публичное меню сайта','frontend','Menu',0,1,NOW()),
('frontend_footer','Нижнее меню сайта','Публичное меню в подвале сайта','frontend','Menu',0,1,NOW());

INSERT INTO menu_items (menu_key, item_key, parent_key, title, link_type, url, icon, module, permission, target, weight, is_active, is_system, created_at) VALUES
('admin_sidebar','dashboard','', 'Панель','url','/admin','speedometer','Dashboard','','',10,1,1,NOW()),
('admin_sidebar','system','', 'Система','heading','#','boxes','','','',20,1,1,NOW()),
('admin_sidebar','security','', 'Безопасность','heading','#','shield-lock','','','',30,1,1,NOW()),
('admin_sidebar','configuration','', 'Конфигурация','heading','#','gear','','','',40,1,1,NOW()),
('admin_sidebar','module_manager.modules','system','Модули','url','/admin/modules','boxes','ModuleManager','','',10,1,1,NOW()),
('admin_sidebar','module_manager.operations','system','Операции модулей','url','/admin/modules/operations','clock-history','ModuleManager','','',11,1,1,NOW()),
('admin_sidebar','module_manager.locks','system','Блокировки','url','/admin/modules/locks','lock','ModuleManager','','',12,1,1,NOW()),
('admin_sidebar','route_manager.routes','system','Маршруты','url','/admin/routes','signpost','RouteManager','','',20,1,1,NOW()),
('admin_sidebar','menu.manage','system','Меню','url','/admin/menu','list','Menu','','',30,1,1,NOW()),
('admin_sidebar','system_health.health','system','Состояние системы','url','/admin/system/health','activity','SystemHealth','','',40,1,1,NOW()),
('admin_sidebar','maintenance.manage','system','Обслуживание','url','/admin/system/maintenance','tools','Maintenance','','',41,1,1,NOW()),
('admin_sidebar','auth.settings','security','Безопасность входа','url','/admin/auth/settings','shield-lock','Auth','','',10,1,1,NOW()),
('admin_sidebar','auth.ip_rules','security','IP правила','url','/admin/auth/ip-rules','globe','Auth','','',20,1,1,NOW()),
('admin_sidebar','auth.sessions','security','Активные сессии','url','/admin/auth/sessions','shield','Auth','','',30,1,1,NOW()),
('admin_sidebar','auth.extensions','security','Расширения Auth','url','/admin/auth/extensions','plug','Auth','','',40,1,1,NOW()),
('admin_sidebar','audit_log.audit','security','Audit log','url','/admin/security/audit','list','AuditLog','','',50,1,1,NOW()),
('admin_sidebar','audit_log.suspicious','security','Suspicious logs','url','/admin/security/suspicious','warning','AuditLog','','',60,1,1,NOW()),
('admin_sidebar','auth.password','security','Сменить пароль','url','/admin/auth/password','key','Auth','','',70,1,1,NOW()),
('admin_sidebar','settings.cms','configuration','Настройки CMS','url','/admin/settings','gear','Settings','','',10,1,1,NOW());

INSERT INTO cms_routes (module, route_key, http_method, path, controller, action, is_admin, is_active, is_system, sort_order, created_at) VALUES
('Dashboard','Dashboard:GET:/admin','GET','/admin','\\Modules\\Dashboard\\Controllers\\DashboardController','index',1,1,1,21,NOW()),
('Dashboard','Dashboard:GET:/admin/dashboard','GET','/admin/dashboard','\\Modules\\Dashboard\\Controllers\\DashboardController','index',1,1,1,22,NOW()),
('AuditLog','AuditLog:GET:/admin/security/audit','GET','/admin/security/audit','\\Modules\\AuditLog\\Controllers\\AuditLogController','audit',1,1,1,23,NOW()),
('AuditLog','AuditLog:GET:/admin/security/suspicious','GET','/admin/security/suspicious','\\Modules\\AuditLog\\Controllers\\AuditLogController','suspicious',1,1,1,24,NOW()),
('Maintenance','Maintenance:GET:/admin/system/maintenance','GET','/admin/system/maintenance','\\Modules\\Maintenance\\Controllers\\MaintenanceController','index',1,1,1,25,NOW()),
('Maintenance','Maintenance:POST:/admin/system/cleanup','POST','/admin/system/cleanup','\\Modules\\Maintenance\\Controllers\\MaintenanceController','cleanup',1,1,1,25,NOW()),
('Auth','Auth:GET:/login','GET','/login','\\Modules\\Auth\\Controllers\\AuthController','login',0,1,1,71,NOW()),
('Auth','Auth:POST:/login','POST','/login','\\Modules\\Auth\\Controllers\\AuthController','attemptLogin',0,1,1,72,NOW()),
('Auth','Auth:POST:/logout','POST','/logout','\\Modules\\Auth\\Controllers\\AuthController','logout',0,1,1,73,NOW()),
('Auth','Auth:GET:/admin/auth/settings','GET','/admin/auth/settings','\\Modules\\Auth\\Controllers\\AuthSettingsController','index',1,1,1,74,NOW()),
('Auth','Auth:POST:/admin/auth/settings','POST','/admin/auth/settings','\\Modules\\Auth\\Controllers\\AuthSettingsController','save',1,1,1,75,NOW()),
('Auth','Auth:GET:/admin/auth/ip-rules','GET','/admin/auth/ip-rules','\\Modules\\Auth\\Controllers\\IpRulesController','index',1,1,1,76,NOW()),
('Auth','Auth:POST:/admin/auth/ip-rules','POST','/admin/auth/ip-rules','\\Modules\\Auth\\Controllers\\IpRulesController','add',1,1,1,77,NOW()),
('Auth','Auth:POST:/admin/auth/ip-rules/delete/(:num)','POST','/admin/auth/ip-rules/delete/(:num)','\\Modules\\Auth\\Controllers\\IpRulesController','delete/$1',1,1,1,78,NOW()),
('Auth','Auth:GET:/admin/auth/password','GET','/admin/auth/password','\\Modules\\Auth\\Controllers\\PasswordController','change',1,1,1,79,NOW()),
('Auth','Auth:POST:/admin/auth/password','POST','/admin/auth/password','\\Modules\\Auth\\Controllers\\PasswordController','save',1,1,1,80,NOW()),
('Auth','Auth:GET:/admin/auth/extensions','GET','/admin/auth/extensions','\\Modules\\Auth\\Controllers\\AuthExtensionsController','index',1,1,1,81,NOW()),
('Auth','Auth:POST:/admin/auth/extensions/sync','POST','/admin/auth/extensions/sync','\\Modules\\Auth\\Controllers\\AuthExtensionsController','sync',1,1,1,82,NOW()),
('Auth','Auth:POST:/admin/auth/extensions/toggle/(:num)','POST','/admin/auth/extensions/toggle/(:num)','\\Modules\\Auth\\Controllers\\AuthExtensionsController','toggle/$1',1,1,1,83,NOW()),
('Auth','Auth:GET:/admin/auth/sessions','GET','/admin/auth/sessions','\\Modules\\Auth\\Controllers\\SessionsController','index',1,1,1,84,NOW()),
('Auth','Auth:POST:/admin/auth/sessions/revoke/(:segment)','POST','/admin/auth/sessions/revoke/(:segment)','\\Modules\\Auth\\Controllers\\SessionsController','revoke/$1',1,1,1,85,NOW()),
('Auth','Auth:POST:/admin/auth/sessions/revoke-others','POST','/admin/auth/sessions/revoke-others','\\Modules\\Auth\\Controllers\\SessionsController','revokeOthers',1,1,1,86,NOW()),
('ModuleManager','ModuleManager:GET:/admin/modules','GET','/admin/modules','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','index',1,1,1,31,NOW()),
('ModuleManager','ModuleManager:GET:/admin/modules/operations','GET','/admin/modules/operations','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','operations',1,1,1,311,NOW()),
('ModuleManager','ModuleManager:GET:/admin/modules/locks','GET','/admin/modules/locks','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','locks',1,1,1,312,NOW()),
('ModuleManager','ModuleManager:POST:/admin/modules/locks/force/(:segment)','POST','/admin/modules/locks/force/(:segment)','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','forceUnlock/$1',1,1,1,313,NOW()),
('ModuleManager','ModuleManager:GET:/admin/modules/preflight/(:num)/(:segment)','GET','/admin/modules/preflight/(:num)/(:segment)','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','preflight/$1/$2',1,1,1,32,NOW()),
('ModuleManager','ModuleManager:POST:/admin/modules/sync','POST','/admin/modules/sync','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','sync',1,1,1,33,NOW()),
('ModuleManager','ModuleManager:POST:/admin/modules/install/(:num)','POST','/admin/modules/install/(:num)','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','install/$1',1,1,1,34,NOW()),
('ModuleManager','ModuleManager:POST:/admin/modules/update/(:num)','POST','/admin/modules/update/(:num)','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','update/$1',1,1,1,35,NOW()),
('ModuleManager','ModuleManager:POST:/admin/modules/enable/(:num)','POST','/admin/modules/enable/(:num)','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','enable/$1',1,1,1,36,NOW()),
('ModuleManager','ModuleManager:POST:/admin/modules/disable/(:num)','POST','/admin/modules/disable/(:num)','\\Modules\\ModuleManager\\Controllers\\ModuleManagerController','disable/$1',1,1,1,37,NOW()),
('RouteManager','RouteManager:GET:/admin/routes','GET','/admin/routes','\\Modules\\RouteManager\\Controllers\\RouteManagerController','index',1,1,1,41,NOW()),
('RouteManager','RouteManager:GET:/admin/routes/create','GET','/admin/routes/create','\\Modules\\RouteManager\\Controllers\\RouteManagerController','create',1,1,1,42,NOW()),
('RouteManager','RouteManager:GET:/admin/routes/edit/(:num)','GET','/admin/routes/edit/(:num)','\\Modules\\RouteManager\\Controllers\\RouteManagerController','edit/$1',1,1,1,43,NOW()),
('RouteManager','RouteManager:POST:/admin/routes/save','POST','/admin/routes/save','\\Modules\\RouteManager\\Controllers\\RouteManagerController','save',1,1,1,44,NOW()),
('RouteManager','RouteManager:POST:/admin/routes/save/(:num)','POST','/admin/routes/save/(:num)','\\Modules\\RouteManager\\Controllers\\RouteManagerController','save/$1',1,1,1,45,NOW()),
('RouteManager','RouteManager:POST:/admin/routes/sync','POST','/admin/routes/sync','\\Modules\\RouteManager\\Controllers\\RouteManagerController','sync',1,1,1,46,NOW()),
('RouteManager','RouteManager:POST:/admin/routes/toggle/(:num)','POST','/admin/routes/toggle/(:num)','\\Modules\\RouteManager\\Controllers\\RouteManagerController','toggle/$1',1,1,1,47,NOW()),
('RouteManager','RouteManager:POST:/admin/routes/delete/(:num)','POST','/admin/routes/delete/(:num)','\\Modules\\RouteManager\\Controllers\\RouteManagerController','delete/$1',1,1,1,48,NOW()),
('SystemHealth','SystemHealth:GET:/admin/system/health','GET','/admin/system/health','\\Modules\\SystemHealth\\Controllers\\SystemHealthController','index',1,1,1,46,NOW()),
('Menu','Menu:GET:/admin/menu','GET','/admin/menu','\\Modules\\Menu\\Controllers\\MenuController','index',1,1,1,51,NOW()),
('Menu','Menu:GET:/admin/menu/create','GET','/admin/menu/create','\\Modules\\Menu\\Controllers\\MenuController','createMenu',1,1,1,52,NOW()),
('Menu','Menu:GET:/admin/menu/edit/(:num)','GET','/admin/menu/edit/(:num)','\\Modules\\Menu\\Controllers\\MenuController','editMenu/$1',1,1,1,53,NOW()),
('Menu','Menu:POST:/admin/menu/save','POST','/admin/menu/save','\\Modules\\Menu\\Controllers\\MenuController','saveMenu',1,1,1,54,NOW()),
('Menu','Menu:POST:/admin/menu/save/(:num)','POST','/admin/menu/save/(:num)','\\Modules\\Menu\\Controllers\\MenuController','saveMenu/$1',1,1,1,55,NOW()),
('Menu','Menu:GET:/admin/menu/items/create','GET','/admin/menu/items/create','\\Modules\\Menu\\Controllers\\MenuController','createItem',1,1,1,56,NOW()),
('Menu','Menu:GET:/admin/menu/items/edit/(:num)','GET','/admin/menu/items/edit/(:num)','\\Modules\\Menu\\Controllers\\MenuController','editItem/$1',1,1,1,57,NOW()),
('Menu','Menu:POST:/admin/menu/items/save','POST','/admin/menu/items/save','\\Modules\\Menu\\Controllers\\MenuController','saveItem',1,1,1,58,NOW()),
('Menu','Menu:POST:/admin/menu/items/save/(:num)','POST','/admin/menu/items/save/(:num)','\\Modules\\Menu\\Controllers\\MenuController','saveItem/$1',1,1,1,59,NOW()),
('Menu','Menu:POST:/admin/menu/items/toggle/(:num)','POST','/admin/menu/items/toggle/(:num)','\\Modules\\Menu\\Controllers\\MenuController','toggleItem/$1',1,1,1,60,NOW()),
('Menu','Menu:POST:/admin/menu/items/delete/(:num)','POST','/admin/menu/items/delete/(:num)','\\Modules\\Menu\\Controllers\\MenuController','deleteItem/$1',1,1,1,61,NOW()),
('Settings','Settings:GET:/admin/settings','GET','/admin/settings','\\Modules\\Settings\\Controllers\\SettingsController','index',1,1,1,61,NOW()),
('Settings','Settings:GET:/admin/settings/(:segment)','GET','/admin/settings/(:segment)','\\Modules\\Settings\\Controllers\\SettingsController','index/$1',1,1,1,62,NOW()),
('Settings','Settings:POST:/admin/settings/(:segment)','POST','/admin/settings/(:segment)','\\Modules\\Settings\\Controllers\\SettingsController','save/$1',1,1,1,63,NOW());

INSERT INTO system_settings (setting_group, setting_key, setting_label, setting_value, field_type, field_options, description, is_public, is_system, sort_order, is_required, min_value, max_value, validation_rule, is_secret, created_at) VALUES
('site','site_name','Название сайта','My CMS','text',NULL,'Отображается в админке и публичной части.',1,1,10,1,NULL,NULL,NULL,0,NOW()),
('site','site_description','Описание сайта','','textarea',NULL,'Краткое описание проекта.',1,1,20,0,NULL,NULL,NULL,0,NOW()),
('site','admin_email','Email администратора','admin@example.com','text',NULL,'Основной email для системных уведомлений.',0,1,30,1,NULL,NULL,'email',0,NOW()),
('site','maintenance_mode','Режим обслуживания','0','checkbox',NULL,'При включении публичная часть может быть закрыта.',0,1,40,0,NULL,NULL,NULL,0,NOW()),
('localization','default_language','Язык по умолчанию','ru','select','{"ru":"Русский","uk":"Українська","en":"English"}','Базовый язык интерфейса.',1,1,10,1,NULL,NULL,NULL,0,NOW()),
('localization','timezone','Часовой пояс','Europe/Kyiv','text',NULL,'Например Europe/Kyiv.',0,1,20,1,NULL,NULL,'timezone',0,NOW()),
('localization','date_format','Формат даты','d.m.Y H:i','text',NULL,'PHP date format.',0,1,30,0,NULL,NULL,NULL,0,NOW()),
('mail','mail_from_email','Email отправителя','noreply@example.com','text',NULL,'Адрес отправителя системной почты.',0,1,10,1,NULL,NULL,'email',0,NOW()),
('mail','mail_from_name','Имя отправителя','CMS','text',NULL,'Имя отправителя системной почты.',0,1,20,0,NULL,NULL,NULL,0,NOW()),
('mail','mail_enabled','Отправка почты','0','checkbox',NULL,'Глобальный переключатель отправки почты.',0,1,30,0,NULL,NULL,NULL,0,NOW()),
('files','upload_max_mb','Максимальный размер файла, MB','20','number',NULL,'Ограничение загрузки через CMS.',0,1,10,1,1,2048,NULL,0,NOW()),
('files','allowed_extensions','Разрешённые расширения','jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip','textarea',NULL,'Список через запятую.',0,1,20,0,NULL,NULL,NULL,0,NOW()),
('files','default_storage','Хранилище по умолчанию','public','select','{"public":"Public","private":"Private"}','Подготовка под File Manager.',0,1,30,0,NULL,NULL,NULL,0,NOW()),
('logging','audit_log_enabled','Журнал действий','1','checkbox',NULL,'Глобальное включение audit log.',0,1,10,0,NULL,NULL,NULL,0,NOW()),
('logging','audit_log_retention_days','Хранить audit log, дней','365','number',NULL,'0 — не удалять автоматически.',0,1,20,0,0,3650,NULL,0,NOW()),
('system','cache_settings_ttl','TTL кэша настроек, секунд','3600','number',NULL,'Используется SettingService.',0,1,10,0,60,86400,NULL,0,NOW()),
('security','security_csp','Content Security Policy','default-src ''self''; img-src ''self'' data:; style-src ''self'' ''unsafe-inline''; script-src ''self''; font-src ''self'' data:; connect-src ''self''; frame-ancestors ''self''; base-uri ''self''; form-action ''self''','textarea',NULL,'Основная CSP политика. Обязательно должна содержать default-src.',0,1,10,1,NULL,NULL,'csp',0,NOW()),
('security','security_csp_report_only','CSP Report-Only','0','checkbox',NULL,'Не блокировать нарушения CSP, только отправлять отчёты браузера.',0,1,20,0,NULL,NULL,NULL,0,NOW()),
('security','security_hsts_enabled','HSTS','1','checkbox',NULL,'Отправлять Strict-Transport-Security только при HTTPS.',0,1,30,0,NULL,NULL,NULL,0,NOW()),
('security','security_hsts_max_age','HSTS max-age','31536000','number',NULL,'Срок HSTS в секундах.',0,1,40,0,300,63072000,NULL,0,NOW()),
('security','security_hsts_include_subdomains','HSTS includeSubDomains','1','checkbox',NULL,'Применять HSTS к поддоменам.',0,1,50,0,NULL,NULL,NULL,0,NOW()),
('security','security_hsts_preload','HSTS preload','0','checkbox',NULL,'Добавлять preload. Включайте только после проверки домена.',0,1,60,0,NULL,NULL,NULL,0,NOW()),
('security','security_frame_options','X-Frame-Options','SAMEORIGIN','select','{"DENY":"DENY","SAMEORIGIN":"SAMEORIGIN"}','Защита от clickjacking.',0,1,70,1,NULL,NULL,'header',0,NOW()),
('security','security_referrer_policy','Referrer-Policy','strict-origin-when-cross-origin','text',NULL,'Политика Referer.',0,1,80,1,NULL,NULL,'header',0,NOW()),
('security','security_permissions_policy','Permissions-Policy','camera=(), microphone=(), geolocation=()','textarea',NULL,'Разрешения браузерных API.',0,1,90,1,NULL,NULL,'header',0,NOW());
