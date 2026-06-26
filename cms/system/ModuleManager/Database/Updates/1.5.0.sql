-- ModuleManager 1.5.0: operation log and hardened locks.
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
SET @sql := (SELECT IF(IS_NULLABLE='YES', 'ALTER TABLE `cms_locks` MODIFY `lock_token` CHAR(64) NOT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='cms_locks' AND COLUMN_NAME='lock_token' LIMIT 1);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
