CREATE TABLE IF NOT EXISTS seo_meta (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    content_id VARCHAR(36) NOT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    og_title VARCHAR(255) NULL,
    og_description TEXT NULL,
    og_image VARCHAR(500) NULL,
    canonical_url VARCHAR(500) NULL,
    schema_type VARCHAR(50) NULL DEFAULT 'WebPage',
    noindex TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_seo_content (content_id),
    FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
