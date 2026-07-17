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
    private PageCache $pageCache;
    private AssetService $assetService;

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

        $cacheDir = $config['CACHE_DIR'] ?? dirname(__DIR__, 2) . '/cache/page';
        $cacheTtl = (int)($config['CACHE_TTL'] ?? 3600);
        $this->pageCache = new PageCache($cacheDir, $cacheTtl);

        $assetCacheDir = $config['ASSET_CACHE_DIR'] ?? dirname(__DIR__, 2) . '/cache/asset';
        $assetUrlBase = $config['ASSET_URL_BASE'] ?? '/';
        $assetVersion = $config['ASSET_VERSION'] ?? '1.0.0';
        $this->assetService = new AssetService(
            dirname(__DIR__, 2) . '/public',
            $assetCacheDir,
            $assetUrlBase,
            $assetVersion
        );

        AdminRoutes::register($this->router, $this->config, $this->assetService);
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
            ApiRouter::register($this->router, $db->getConnection(), $this->pageCache);
            $this->moduleLoader->loadModules($db->getConnection());
            $this->pageCache->setDatabase($db->getConnection());
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$header] = $value;
            }
        }

        if ($this->pageCache->shouldCache($method, $uri, $headers, $_COOKIE)) {
            $cacheKey = $this->pageCache->generateKey($method, $uri, $headers);
            $cached = $this->pageCache->get($cacheKey);
            
            if ($cached !== null) {
                $response = new Response(200, [
                    'Content-Type' => 'text/html; charset=utf-8',
                    'X-Cache' => 'HIT',
                ], $cached);
                $response->send();
                return;
            }
        }

        ob_start();
        
        $response = $this->router->dispatch($method, $uri);
        
        if ($this->pageCache->shouldCache($method, $uri, $headers, $_COOKIE)) {
            $body = ob_get_clean();
            $cacheKey = $this->pageCache->generateKey($method, $uri, $headers);
            $this->pageCache->set($cacheKey, $body);
            $response = new Response($response->status(), array_merge(
                $response->headers(),
                ['X-Cache' => 'MISS']
            ), $body);
            $response->send();
            return;
        }
        
        ob_end_flush();
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

    public function getPageCache(): PageCache
    {
        return $this->pageCache;
    }

    public function getAssetService(): AssetService
    {
        return $this->assetService;
    }
}
