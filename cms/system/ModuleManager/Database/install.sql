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
