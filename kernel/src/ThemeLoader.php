<?php
declare(strict_types=1);
namespace Monsoon\Kernel;

final class ThemeLoader
{
    private string $themesDir;
    private ?array $activeTheme = null;

    public function __construct(string $themesDir)
    {
        $this->themesDir = rtrim($themesDir, '/');
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
        return 'starter';
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
