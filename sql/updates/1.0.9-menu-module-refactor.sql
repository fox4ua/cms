-- AdminMenu -> Menu refactor.
-- Creates universal menus/menu_items tables and migrates existing admin_menu_items to admin_sidebar.

CREATE TABLE IF NOT EXISTS menus (
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

CREATE TABLE IF NOT EXISTS menu_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_key VARCHAR(100) NOT NULL,
    item_key VARCHAR(190) NOT NULL,
    parent_key VARCHAR(190) NULL,
    title VARCHAR(190) NOT NULL,
    url VARCHAR(255) NOT NULL DEFAULT '#',
    icon VARCHAR(100) NULL,
    module VARCHAR(100) NULL,
    permission VARCHAR(190) NULL,
    target VARCHAR(30) NULL,
    weight INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    UNIQUE KEY uq_menu_item (menu_key, item_key),
    INDEX idx_menu_parent (menu_key, parent_key),
    INDEX idx_menu_module (module),
    INDEX idx_menu_active (is_active, weight)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO menus (menu_key, title, description, area, module, is_system, is_active, created_at)
SELECT 'admin_sidebar','Боковое меню админки','Основное навигационное меню административной панели','admin','Menu',1,1,NOW()
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE menu_key='admin_sidebar');

INSERT INTO menus (menu_key, title, description, area, module, is_system, is_active, created_at)
SELECT 'admin_topbar','Верхнее меню админки','Верхнее меню административной панели','admin','Menu',1,1,NOW()
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE menu_key='admin_topbar');

INSERT INTO menus (menu_key, title, description, area, module, is_system, is_active, created_at)
SELECT 'frontend_main','Главное меню сайта','Основное публичное меню сайта','frontend','Menu',0,1,NOW()
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE menu_key='frontend_main');

INSERT INTO menus (menu_key, title, description, area, module, is_system, is_active, created_at)
SELECT 'frontend_footer','Нижнее меню сайта','Публичное меню в подвале сайта','frontend','Menu',0,1,NOW()
WHERE NOT EXISTS (SELECT 1 FROM menus WHERE menu_key='frontend_footer');


SET @has_admin_menu_items := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_menu_items'
);

SET @sql := IF(@has_admin_menu_items > 0,
"INSERT INTO menu_items (menu_key, item_key, parent_key, title, url, icon, module, permission, target, weight, is_active, is_system, created_at, updated_at)
SELECT
    'admin_sidebar',
    COALESCE(NULLIF(menu_key, ''), CONCAT('legacy_', id)),
    COALESCE(parent_key, ''),
    title,
    url,
    icon,
    CASE WHEN module = 'AdminMenu' THEN 'Menu' ELSE module END,
    permission,
    '',
    weight,
    is_active,
    is_system,
    created_at,
    updated_at
FROM admin_menu_items
ON DUPLICATE KEY UPDATE
    parent_key = VALUES(parent_key),
    title = VALUES(title),
    url = VALUES(url),
    icon = VALUES(icon),
    module = VALUES(module),
    permission = VALUES(permission),
    weight = VALUES(weight),
    is_active = VALUES(is_active),
    is_system = VALUES(is_system),
    updated_at = NOW()",
"SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE cms_modules
SET machine_name='Menu', name='Menu', description='Универсальное управление меню CMS', source_path='cms/system/Menu', available_version='1.1.0', installed_version='1.1.0', version='1.1.0'
WHERE machine_name='AdminMenu';

UPDATE cms_routes
SET module='Menu', route_key=REPLACE(route_key, 'AdminMenu:', 'Menu:'), controller=REPLACE(controller, '\\AdminMenu\\', '\\Menu\\')
WHERE module='AdminMenu' OR controller LIKE '%\\AdminMenu\\%';

UPDATE menu_items SET module='Menu' WHERE module='AdminMenu';
