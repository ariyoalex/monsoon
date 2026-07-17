<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class Kernel
{
    private array $config;
    private Router $router;
    private ModuleLoader $moduleLoader;
    private PermissionGate $permissionGate;
    private MiddlewarePipeline $middlewarePipeline;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->router = new Router($config);

        $this->middlewarePipeline = new MiddlewarePipeline();
        $this->middlewarePipeline->pipe(new CsrfMiddleware());
        $this->middlewarePipeline->pipe(new AuthMiddleware());
        $this->router->setMiddleware($this->middlewarePipeline);

        $this->permissionGate = PermissionGate::getInstance();
        $this->permissionGate->registerDefaults();

        $this->moduleLoader = new ModuleLoader(
            dirname(__DIR__, 2) . '/modules',
            $this->router
        );

        AdminRoutes::register($this->router, $this->config);
    }

    public function handle(): void
    {
        $db = null;

        $dbHost = $this->config['DB_HOST'] ?? '';
        if ($dbHost !== '') {
            try {
                $db = Database::getInstance();
                $db->connect($this->config);
                $auth = Auth::getInstance();
                $auth->setDatabase($db->getConnection());
            } catch (\Throwable $e) {
                $db = null;
            }
        }

        if ($db !== null) {
            ApiRouter::register($this->router, $db->getConnection());
            $this->moduleLoader->loadModules($db->getConnection());
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $response = $this->router->dispatch($method, $uri);
        $response->send();
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getModuleLoader(): ModuleLoader
    {
        return $this->moduleLoader;
    }

    public function getPermissionGate(): PermissionGate
    {
        return $this->permissionGate;
    }

    public function getMiddlewarePipeline(): MiddlewarePipeline
    {
        return $this->middlewarePipeline;
    }
}
