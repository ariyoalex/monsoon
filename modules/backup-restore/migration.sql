CREATE TABLE IF NOT EXISTS backups (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('full', 'database', 'files') NOT NULL DEFAULT 'full',
    status ENUM('pending', 'running', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    file_path VARCHAR(500) NULL,
    file_size BIGINT NULL,
    db_tables_count INT NULL,
    db_rows_count INT NULL,
    notes TEXT NULL,
    created_by VARCHAR(36) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_backups_status (status),
    INDEX idx_backups_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
