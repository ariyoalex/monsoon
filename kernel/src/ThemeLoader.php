<?php
declare(strict_types=1);
namespace Monsoon\Kernel;

final class ThemeLoader
{
    private string $themesDir;
    private ?\mysqli $db;
    private ?array $activeTheme = null;

    public function __construct(string $themesDir, ?\mysqli $db = null)
    {
        $this->themesDir = rtrim($themesDir, '/');
        $this->db = $db;
    }

    public function getAvailableThemes(): array
    {
        $themes = [];
        $dirs = glob($this->themesDir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $manifest = $this->loadManifest(basename($dir));
            if ($manifest !== null) {
                $themes[] = $manifest;
            }
        }
        return $themes;
    }

    public function getActiveTheme(): ?array
    {
        if ($this->activeTheme !== null) {
            return $this->activeTheme;
        }
        $name = $this->getActiveThemeName();
        $this->activeTheme = $this->loadManifest($name);
        return $this->activeTheme;
    }

    public function getActiveThemeName(): string
    {
        if ($this->db === null) {
            return 'starter';
        }
        $stmt = $this->db->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $key = 'theme_active';
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['setting_value'] ?? 'starter';
    }

    public function setActiveTheme(string $themeName): void
    {
        if ($this->db === null) {
            return;
        }
        $stmt = $this->db->prepare('UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?');
        $key = 'theme_active';
        $stmt->bind_param('ss', $themeName, $key);
        $stmt->execute();
        $stmt->close();
        $this->activeTheme = null;
    }

    public function getThemeSettings(): array
    {
        if ($this->db === null) {
            return [];
        }
        $result = $this->db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'site_%' OR setting_key LIKE 'theme_%' OR setting_key LIKE '%_color' OR setting_key LIKE 'font_%'");
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public function updateThemeSetting(string $key, string $value): void
    {
        if ($this->db === null) {
            return;
        }
        $stmt = $this->db->prepare('UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?');
        $stmt->bind_param('ss', $value, $key);
        $stmt->execute();
        $stmt->close();
    }

    public function loadManifest(string $themeName): ?array
    {
        $path = $this->themesDir . '/' . $themeName . '/theme.json';
        if (!is_file($path)) {
            return null;
        }
        $json = file_get_contents($path);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }
        $data['_path'] = $this->themesDir . '/' . $themeName;
        $data['_name'] = $themeName;
        return $data;
    }

    public function getTemplatePath(string $template): ?string
    {
        $theme = $this->getActiveTheme();
        if ($theme === null) {
            return null;
        }
        $templates = $theme['templates'] ?? [];
        $fileName = $templates[$template] ?? ($template . '.php');
        $path = $theme['_path'] . '/' . $fileName;
        return is_file($path) ? $path : null;
    }

    public function renderTemplate(string $template, array $data = []): void
    {
        $path = $this->getTemplatePath($template);
        if ($path === null) {
            $path = $this->getTemplatePath('index');
        }
        if ($path === null) {
            echo '<p>Template not found.</p>';
            return;
        }
        extract($data);
        include $path;
    }
}
