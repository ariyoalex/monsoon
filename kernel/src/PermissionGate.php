<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

use RuntimeException;

final class PermissionGate
{
    private static ?self $instance = null;
    private array $registeredCapabilities = [];
    private array $moduleScopes = [];

    private function __construct() {}

    private function __clone() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function registerCapability(string $capability, string $description = ''): void
    {
        $this->registeredCapabilities[$capability] = $description;
    }

    public function getRegisteredCapabilities(): array
    {
        return $this->registeredCapabilities;
    }

    public function hasCapability(string $capability): bool
    {
        return isset($this->registeredCapabilities[$capability]);
    }

    public function declareModuleScope(string $moduleSlug, array $capabilities): void
    {
        $this->moduleScopes[$moduleSlug] = $capabilities;
    }

    public function getModuleScope(string $moduleSlug): array
    {
        return $this->moduleScopes[$moduleSlug] ?? [];
    }

    public function check(string $moduleSlug, string $capability): bool
    {
        $scope = $this->getModuleScope($moduleSlug);

        return in_array($capability, $scope, true);
    }

    public function assert(string $moduleSlug, string $capability): void
    {
        if (!$this->check($moduleSlug, $capability)) {
            throw new RuntimeException(
                "Module '$moduleSlug' attempted action requiring '$capability' "
                . 'which is not in its declared capability scope.'
            );
        }
    }

    public function registerDefaults(): void
    {
        $defaults = [
            'content.read' => 'Read content items',
            'content.write' => 'Create and update content items',
            'content.delete' => 'Delete content items',
            'media.read' => 'Read media files',
            'media.write' => 'Upload and update media files',
            'media.delete' => 'Delete media files',
            'user.read' => 'Read user data',
            'user.write' => 'Create and update users',
            'user.delete' => 'Delete users',
            'settings.read' => 'Read system settings',
            'settings.write' => 'Update system settings',
            'mail.send' => 'Send email',
            'files.read' => 'Read files from the filesystem',
            'files.write' => 'Write files to the filesystem',
            'database.dump' => 'Export database dump',
            'auth.read' => 'Read authentication configuration',
            'auth.write' => 'Modify authentication configuration',
        ];

        foreach ($defaults as $cap => $desc) {
            $this->registerCapability($cap, $desc);
        }
    }
}
