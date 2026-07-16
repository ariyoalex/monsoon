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
}
