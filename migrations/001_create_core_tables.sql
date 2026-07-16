CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id VARCHAR(36) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email),
    INDEX idx_users_role (role_id),
    INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    capabilities JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_items (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    type VARCHAR(50) NOT NULL DEFAULT 'page',
    title VARCHAR(255) NOT NULL DEFAULT '',
    slug VARCHAR(255) NOT NULL,
    body LONGTEXT,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    author_id VARCHAR(36) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    INDEX idx_content_type (type),
    INDEX idx_content_status (status),
    INDEX idx_content_author (author_id),
    INDEX idx_content_slug (slug),
    INDEX idx_content_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_revisions (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    content_id VARCHAR(36) NOT NULL,
    snapshot JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_revisions_content (content_id),
    FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS taxonomies (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'category',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_taxonomy_slug (slug),
    INDEX idx_taxonomy_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_taxonomy (
    content_id VARCHAR(36) NOT NULL,
    taxonomy_id VARCHAR(36) NOT NULL,
    PRIMARY KEY (content_id, taxonomy_id),
    FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
    FOREIGN KEY (taxonomy_id) REFERENCES taxonomies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL DEFAULT 0,
    alt_text VARCHAR(500) NOT NULL DEFAULT '',
    uploader_id VARCHAR(36) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_media_uploader (uploader_id),
    INDEX idx_media_mime (mime_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modules (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    version VARCHAR(50) NOT NULL DEFAULT '0.0.0',
    manifest JSON NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'inactive',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    scope VARCHAR(100) NOT NULL DEFAULT 'global',
    setting_key VARCHAR(100) NOT NULL,
    setting_value LONGTEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_settings_scope_key (scope, setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
