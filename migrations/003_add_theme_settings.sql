INSERT IGNORE INTO settings (id, scope, setting_key, setting_value, created_at, updated_at) VALUES
(UUID(), 'global', 'theme_active', 'starter', NOW(), NOW()),
(UUID(), 'global', 'site_name', 'Monsoon CMS', NOW(), NOW()),
(UUID(), 'global', 'site_tagline', 'The CMS that gets out of your way.', NOW(), NOW()),
(UUID(), 'global', 'primary_color', '#1034A6', NOW(), NOW()),
(UUID(), 'global', 'sidebar_color', '#1A1A1A', NOW(), NOW()),
(UUID(), 'global', 'background_color', '#F4F6FA', NOW(), NOW()),
(UUID(), 'global', 'font_body', 'Graphik', NOW(), NOW()),
(UUID(), 'global', 'font_heading', 'Means', NOW(), NOW());
