<?php

declare(strict_types=1);

namespace Monsoon\Tests;

use Monsoon\Kernel\Config;
use Monsoon\Kernel\ErrorHandler;
use Monsoon\Kernel\Kernel;
use Monsoon\Kernel\Router;
use PHPUnit\Framework\TestCase;

final class KernelTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = Config::load(__DIR__ . '/..');
    }

    public function test_config_loads_with_defaults(): void
    {
        $this->assertSame('development', $this->config['APP_ENV']);
        $this->assertSame('localhost', $this->config['DB_HOST']);
    }

    public function test_kernel_instantiates(): void
    {
        $kernel = new Kernel($this->config);
        $this->assertInstanceOf(Kernel::class, $kernel);
        $this->assertSame($this->config, $kernel->getConfig());
    }

    public function test_kernel_has_router(): void
    {
        $kernel = new Kernel($this->config);
        $router = $kernel->getRouter();
        $this->assertInstanceOf(Router::class, $router);
    }

    public function test_router_returns_404_for_unknown_route(): void
    {
        $router = new Router($this->config);
        $response = $router->dispatch('GET', '/nonexistent');

        $this->assertIsArray($response);
        $this->assertSame(404, $response['status']);
    }

    public function test_router_matches_registered_route(): void
    {
        $router = new Router($this->config);
        $router->addRoute('GET', '/test', fn () => [
            'status' => 200,
            'body' => 'ok',
        ]);

        $response = $router->dispatch('GET', '/test');
        $this->assertSame(200, $response['status']);
        $this->assertSame('ok', $response['body']);
    }

    public function test_router_extracts_slug_parameter(): void
    {
        $router = new Router($this->config);
        $router->addRoute('GET', '/{slug}', fn (array $params) => [
            'status' => 200,
            'body' => $params['slug'] ?? '',
        ]);

        $response = $router->dispatch('GET', '/hello-world');
        $this->assertSame('hello-world', $response['body']);
    }

    public function test_router_cleans_uri(): void
    {
        $router = new Router($this->config);
        $router->addRoute('GET', '/page', fn () => [
            'status' => 200,
            'body' => 'page',
        ]);

        $response = $router->dispatch('GET', '/page/');
        $this->assertSame(200, $response['status']);
    }

    public function test_error_handler_registers(): void
    {
        ErrorHandler::register();
        $this->assertTrue(true);
    }
}
