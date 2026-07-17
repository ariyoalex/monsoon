<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

use RuntimeException;

final class Installer
{
    private \mysqli $db;
    private array $config;

    public function __construct(\mysqli $db, array $config)
    {
        $this->db = $db;
        $this->config = $config;
    }

    public function isInstalled(): bool
    {
        try {
            $result = $this->db->query('SELECT COUNT(*) AS count FROM users');
            if ($result === false) {
                return false;
            }
            $row = $result->fetch_assoc();
            return (int)($row['count'] ?? 0) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function run(): array
    {
        $output = ['migrations' => [], 'roles' => [], 'admin' => null];

        $runner = new MigrationRunner(
            dirname(__DIR__, 2) . '/migrations',
            $this->db
        );
        $output['migrations'] = $runner->run();

        $output['roles'] = $this->seedRoles();

        if (!$this->isInstalled()) {
            $output['admin'] = $this->createAdminUser();
        }

        return $output;
    }

    private function seedRoles(): array
    {
        $roles = [
            [
                'name' => 'administrator',
                'capabilities' => [
                    'content.read', 'content.write', 'content.delete',
                    'media.read', 'media.write', 'media.delete',
                    'user.read', 'user.write', 'user.delete',
                    'settings.read', 'settings.write',
                    'mail.send', 'files.read', 'files.write',
                    'database.dump', 'auth.read', 'auth.write',
                ],
            ],
            [
                'name' => 'editor',
                'capabilities' => [
                    'content.read', 'content.write', 'content.delete',
                    'media.read', 'media.write', 'media.delete',
                    'settings.read',
                ],
            ],
            [
                'name' => 'author',
                'capabilities' => [
                    'content.read', 'content.write',
                    'media.read', 'media.write',
                ],
            ],
            [
                'name' => 'contributor',
                'capabilities' => [
                    'content.read', 'content.write',
                    'media.read',
                ],
            ],
            [
                'name' => 'subscriber',
                'capabilities' => [
                    'content.read',
                ],
            ],
        ];

        $created = [];

        foreach ($roles as $role) {
            $stmt = $this->db->prepare(
                'SELECT id FROM roles WHERE name = ? LIMIT 1'
            );
            $stmt->bind_param('s', $role['name']);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $created[] = ['name' => $role['name'], 'id' => $existing['id'], 'new' => false];
                continue;
            }

            $id = Uuid::v4();
            $capabilitiesJson = json_encode($role['capabilities']);
            $now = date('Y-m-d H:i:s');

            $insert = $this->db->prepare(
                'INSERT INTO roles (id, name, capabilities, created_at, updated_at) VALUES (?, ?, ?, ?, ?)'
            );
            $insert->bind_param('sssss', $id, $role['name'], $capabilitiesJson, $now, $now);
            $insert->execute();
            $insert->close();

            $created[] = ['name' => $role['name'], 'id' => $id, 'new' => true];
        }

        return $created;
    }

    private function createAdminUser(): array
    {
        $stmt = $this->db->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
        $roleName = 'administrator';
        $stmt->bind_param('s', $roleName);
        $stmt->execute();
        $result = $stmt->get_result();
        $adminRole = $result->fetch_assoc();
        $stmt->close();

        if (!$adminRole) {
            throw new RuntimeException('Administrator role not found. Run seedRoles first.');
        }

        $email = $this->config['ADMIN_EMAIL'] ?? 'admin@monsoon.local';
        $password = $this->config['ADMIN_PASSWORD'] ?? 'admin123';

        $auth = Auth::getInstance();
        $auth->setDatabase($this->db);

        return $auth->createUser($email, $password, $adminRole['id'], 'active');
    }
}
