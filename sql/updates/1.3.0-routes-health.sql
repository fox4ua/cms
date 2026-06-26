-- RouteManager and SystemHealth upgrade
CREATE TABLE IF NOT EXISTS cms_routes (
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
