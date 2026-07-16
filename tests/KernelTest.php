<?php

declare(strict_types=1);

namespace Monsoon\Tests;

use Monsoon\Kernel\Config;
use Monsoon\Kernel\ErrorHandler;
use Monsoon\Kernel\Kernel;
use Monsoon\Kernel\Router;
use Monsoon\Kernel\Uuid;
use Monsoon\Kernel\ModuleManifest;
use Monsoon\Kernel\PermissionGate;
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
        $this->assertInstanceOf(Router::class, $kernel->getRouter());
    }

    public function test_kernel_has_permission_gate(): void
    {
        $kernel = new Kernel($this->config);
        $gate = $kernel->getPermissionGate();
        $this->assertInstanceOf(PermissionGate::class, $gate);
        $this->assertTrue($gate->hasCapability('content.read'));
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

    public function test_uuid_generates_valid_v4(): void
    {
        $uuid = Uuid::v4();
        $this->assertTrue(Uuid::isValid($uuid));
        $this->assertSame(36, strlen($uuid));
    }

    public function test_uuid_is_unique(): void
    {
        $uuids = [];
        for ($i = 0; $i < 100; $i++) {
            $uuids[] = Uuid::v4();
        }
        $this->assertSame(100, count(array_unique($uuids)));
    }

    public function test_uuid_validates_correctly(): void
    {
        $this->assertTrue(Uuid::isValid('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertFalse(Uuid::isValid('not-a-uuid'));
        $this->assertFalse(Uuid::isValid(''));
    }

    public function test_module_manifest_from_json(): void
    {
        $json = json_encode([
            'slug' => 'test-module',
            'version' => '1.0.0',
            'capabilities_required' => ['content.read'],
            'admin_routes' => ['/manage/test-module'],
            'public_routes' => [],
            'migrations' => ['001_create_table.sql'],
        ]);

        $manifest = ModuleManifest::fromJson($json);
        $this->assertSame('test-module', $manifest->slug);
        $this->assertSame('1.0.0', $manifest->version);
        $this->assertContains('content.read', $manifest->capabilitiesRequired);
    }

    public function test_permission_gate_registers_defaults(): void
    {
        $gate = PermissionGate::getInstance();
        $gate->registerDefaults();

        $this->assertTrue($gate->hasCapability('content.read'));
        $this->assertTrue($gate->hasCapability('mail.send'));
        $this->assertFalse($gate->hasCapability('nonexistent.capability'));
    }

    public function test_permission_gate_checks_module_scope(): void
    {
        $gate = PermissionGate::getInstance();
        $gate->declareModuleScope('forms', ['content.read', 'mail.send']);

        $this->assertTrue($gate->check('forms', 'content.read'));
        $this->assertTrue($gate->check('forms', 'mail.send'));
        $this->assertFalse($gate->check('forms', 'content.write'));
    }

    public function test_permission_gate_assert_passes(): void
    {
        $gate = PermissionGate::getInstance();
        $gate->declareModuleScope('seo', ['settings.read']);

        $gate->assert('seo', 'settings.read');
        $this->assertTrue(true);
    }

    public function test_permission_gate_assert_fails(): void
    {
        $this->expectException(\RuntimeException::class);

        $gate = PermissionGate::getInstance();
        $gate->declareModuleScope('bad-module', ['content.read']);
        $gate->assert('bad-module', 'settings.write');
    }

    public function test_error_handler_registers(): void
    {
        ErrorHandler::register();
        $this->assertTrue(true);
    }
}
