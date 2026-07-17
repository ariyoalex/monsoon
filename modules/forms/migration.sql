CREATE TABLE IF NOT EXISTS forms (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    fields JSON NULL,
    settings JSON NULL DEFAULT NULL,
    success_message TEXT NULL,
    redirect_url VARCHAR(500) NULL,
    notification_email VARCHAR(255) NULL,
    honeypot_enabled TINYINT(1) NOT NULL DEFAULT 1,
    time_limit_seconds INT NOT NULL DEFAULT 5,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS form_submissions (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    form_id VARCHAR(36) NOT NULL,
    data JSON NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_submission_form (form_id),
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
