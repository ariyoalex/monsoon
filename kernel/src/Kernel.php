<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class Kernel
{
    private array $config;
    private Router $router;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->router = new Router($config);
    }

    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $response = $this->router->dispatch($method, $uri);

        $this->sendResponse($response);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    private function sendResponse(array $response): void
    {
        $status = $response['status'] ?? 200;
        $headers = $response['headers'] ?? [];
        $body = $response['body'] ?? '';

        http_response_code($status);

        foreach ($headers as $name => $value) {
            header("$name: $value");
        }

        if (!isset($headers['Content-Type'])) {
            header('Content-Type: text/html; charset=utf-8');
        }

        echo $body;
    }
}
