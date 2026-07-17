<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

use Throwable;

final class ModuleLoader
{
    private string $modulesDir;
    private Router $router;
    private array $modules = [];

    public function __construct(string $modulesDir, Router $router)
    {
        $this->modulesDir = rtrim($modulesDir, '/');
        $this->router = $router;
    }

    public function discover(): array
    {
        $this->modules = [];
        $items = scandir($this->modulesDir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $modulePath = $this->modulesDir . '/' . $item;

            if (!is_dir($modulePath)) {
                continue;
            }

            $manifestPath = $modulePath . '/manifest.json';

            if (!is_file($manifestPath)) {
                continue;
            }

            try {
                $manifest = ModuleManifest::fromFile($manifestPath);
                $this->modules[$manifest->slug] = [
                    'path' => $modulePath,
                    'manifest' => $manifest,
                    'active' => false,
                ];
            } catch (Throwable $e) {
                continue;
            }
        }

        return $this->modules;
    }

    public function activate(string $slug): bool
    {
        if (!isset($this->modules[$slug])) {
            return false;
        }

        $module = &$this->modules[$slug];
        $manifest = $module['manifest'];

        foreach ($manifest->adminRoutes as $route) {
            $this->router->addRoute('GET', $route, function () use ($slug) {
                return [
                    'status' => 200,
                    'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                    'body' => "Module: $slug admin route",
                ];
            });
        }

        foreach ($manifest->publicRoutes as $route) {
            $this->router->addRoute('GET', $route, function () use ($slug) {
                return [
                    'status' => 200,
                    'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                    'body' => "Module: $slug public route",
                ];
            });
        }

        $module['active'] = true;

        return true;
    }

    public function deactivate(string $slug): bool
    {
        if (!isset($this->modules[$slug])) {
            return false;
        }

        $this->modules[$slug]['active'] = false;

        return true;
    }

    public function getModules(): array
    {
        return $this->modules;
    }

    public function getActiveModules(): array
    {
        return array_filter($this->modules, fn ($m) => $m['active']);
    }

    public function getManifest(string $slug): ?ModuleManifest
    {
        if (!isset($this->modules[$slug])) {
            return null;
        }

        return $this->modules[$slug]['manifest'];
    }

    public function loadModules(\mysqli $db): void
    {
        $this->discover();

        foreach ($this->modules as $slug => &$module) {
            $modulePath = $module['path'];

            $moduleClasses = [
                'seo-basics' => ['SeoModule', 'SeoBasics'],
                'forms' => ['FormsModule', 'Forms'],
                'security-hardening' => ['SecurityModule', 'SecurityHardening'],
                'backup-restore' => ['BackupModule', 'BackupRestore'],
            ];

            if (isset($moduleClasses[$slug])) {
                [$className, $nsPart] = $moduleClasses[$slug];
                $classFile = $modulePath . '/' . $className . '.php';

                if (is_file($classFile)) {
                    try {
                        require_once $classFile;
                        $fqcn = "Monsoon\\Modules\\{$nsPart}\\{$className}";
                        $instance = new $fqcn($db);
                        $instance->registerRoutes($this->router);
                        $module['active'] = true;
                    } catch (Throwable $e) {
                        $module['active'] = false;
                    }
                }
            }

            $manifest = $module['manifest'];
            foreach ($manifest->adminRoutes as $route) {
                $adminFile = $modulePath . '/admin/' . basename($route) . '-page.php';
                if (!is_file($adminFile)) {
                    $adminFile = $modulePath . '/admin/' . basename($route) . '.php';
                }
                $adminPath = $adminFile;
                $this->router->addRoute('GET', $route, function () use ($adminPath, $slug) {
                    if (is_file($adminPath)) {
                        ob_start();
                        require $adminPath;
                        $output = ob_get_clean();

                        $funcName = null;
                        if (str_contains(basename($adminPath), 'forms')) {
                            $funcName = 'renderFormsPage';
                        } elseif (str_contains(basename($adminPath), 'backup')) {
                            $funcName = 'renderBackupPage';
                        }

                        if ($funcName && function_exists($funcName)) {
                            $output = $funcName();
                        }

                        return Response::html($output);
                    }
                    return Response::html("<h1>Module: $slug</h1>");
                });
            }
        }
    }
}
