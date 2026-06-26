CREATE TABLE IF NOT EXISTS cms_locks (
    lock_key VARCHAR(190) PRIMARY KEY,
    owner VARCHAR(190) NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
