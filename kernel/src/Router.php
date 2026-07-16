<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

use RuntimeException;

final class Router
{
    private array $config;
    private array $routes = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function addRoute(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $method = strtoupper($method);
        $uri = $this->cleanUri($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $regex = $this->patternToRegex($route['pattern']);

            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                return call_user_func($route['handler'], $params);
            }
        }

        return [
            'status' => 404,
            'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
            'body' => '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>404 - Not Found</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
<div class="card shadow-sm">
<div class="card-header bg-warning text-dark">
<h1 class="h4 mb-0">404 - Page Not Found</h1>
</div>
<div class="card-body">
<p class="mb-0">The page you are looking for could not be found.</p>
</div>
</div>
</div>
</body>
</html>',
        ];
    }

    private function cleanUri(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH);

        if ($uri === false || $uri === null) {
            $uri = '/';
        }

        $uri = '/' . trim($uri, '/');
        $uri = $uri === '' ? '/' : $uri;

        return $uri;
    }

    private function patternToRegex(string $pattern): string
    {
        $regex = preg_replace('/\{slug\}/', '([a-z0-9-]+)', $pattern);
        $regex = preg_replace('/\{id\}/', '([a-f0-9-]{36})', $regex);

        return '/^' . str_replace('/', '\/', $regex) . '$/';
    }
}
