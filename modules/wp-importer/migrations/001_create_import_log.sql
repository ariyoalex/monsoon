-- Migration 001: Import log and redirects tables for WordPress Importer
-- Run order: after core migrations

CREATE TABLE IF NOT EXISTS `import_log` (
    `id` VARCHAR(36) NOT NULL,
    `summary` LONGTEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_import_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `redirects` (
    `id` VARCHAR(36) NOT NULL,
    `from_path` VARCHAR(500) NOT NULL,
    `to_path` VARCHAR(500) NOT NULL,
    `code` SMALLINT NOT NULL DEFAULT 301,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_redirects_from` (`from_path`),
    KEY `idx_redirects_to` (`to_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `import_items` (
    `id` VARCHAR(36) NOT NULL,
    `import_log_id` VARCHAR(36) NOT NULL,
    `item_type` ENUM('author','category','tag','post','page','media','redirect') NOT NULL,
    `wp_id` VARCHAR(100),
    `monsoon_id` VARCHAR(36),
    `status` ENUM('created','skipped','failed') NOT NULL DEFAULT 'created',
    `error_message` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_import_items_log` (`import_log_id`),
    KEY `idx_import_items_type` (`item_type`),
    KEY `idx_import_items_wp` (`wp_id`),
    CONSTRAINT `fk_import_items_log` FOREIGN KEY (`import_log_id`) REFERENCES `import_log`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;