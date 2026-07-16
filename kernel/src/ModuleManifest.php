<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

use RuntimeException;

final class ModuleManifest
{
    public readonly string $slug;
    public readonly string $version;
    public readonly array $capabilitiesRequired;
    public readonly array $adminRoutes;
    public readonly array $publicRoutes;
    public readonly array $migrations;

    public function __construct(array $data)
    {
        $this->slug = $data['slug'] ?? '';
        $this->version = $data['version'] ?? '0.0.0';
        $this->capabilitiesRequired = $data['capabilities_required'] ?? [];
        $this->adminRoutes = $data['admin_routes'] ?? [];
        $this->publicRoutes = $data['public_routes'] ?? [];
        $this->migrations = $data['migrations'] ?? [];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid module manifest JSON');
        }

        return new self($data);
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("Manifest file not found or unreadable: $path");
        }

        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("Failed to read manifest file: $path");
        }

        return self::fromJson($json);
    }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'version' => $this->version,
            'capabilities_required' => $this->capabilitiesRequired,
            'admin_routes' => $this->adminRoutes,
            'public_routes' => $this->publicRoutes,
            'migrations' => $this->migrations,
        ];
    }
}
