<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

use RuntimeException;

final class Auth
{
    private static ?self $instance = null;
    private ?\mysqli $db = null;
    private ?array $currentUser = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setDatabase(\mysqli $db): void
    {
        $this->db = $db;
    }

    public function login(string $email, string $password): array
    {
        if ($this->db === null) {
            throw new RuntimeException('Database not set on Auth service');
        }

        $stmt = $this->db->prepare(
            'SELECT id, email, password_hash, role_id, status FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Account is not active'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid email or password'];
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            $newHash = password_hash($password, PASSWORD_ARGON2ID);
            $updateStmt = $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $updateStmt->bind_param('ss', $newHash, $user['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }

        Session::start();
        Session::set('user_id', $user['id']);
        Session::set('user_email', $user['email']);
        Session::set('user_role_id', $user['role_id']);
        Session::regenerate();

        $this->currentUser = $user;

        return ['success' => true, 'user' => $user];
    }

    public function logout(): void
    {
        Session::start();
        Session::destroy();
        $this->currentUser = null;
    }

    public function isAuthenticated(): bool
    {
        if ($this->currentUser !== null) {
            return true;
        }

        Session::start();
        $userId = Session::get('user_id');

        if ($userId === null) {
            return false;
        }

        if ($this->db === null) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT id, email, role_id, status FROM users WHERE id = ? AND status = ? LIMIT 1'
        );
        $status = 'active';
        $stmt->bind_param('ss', $userId, $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $this->currentUser = $user;
            return true;
        }

        Session::destroy();
        return false;
    }

    public function getCurrentUser(): ?array
    {
        if ($this->currentUser === null) {
            $this->isAuthenticated();
        }
        return $this->currentUser;
    }

    public function getUserId(): ?string
    {
        $user = $this->getCurrentUser();
        return $user['id'] ?? null;
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function createUser(string $email, string $password, string $roleId, string $status = 'active'): array
    {
        if ($this->db === null) {
            throw new RuntimeException('Database not set on Auth service');
        }

        $id = Uuid::v4();
        $passwordHash = $this->hashPassword($password);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO users (id, email, password_hash, role_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssss', $id, $email, $passwordHash, $roleId, $status, $now, $now);
        $stmt->execute();
        $stmt->close();

        return ['id' => $id, 'email' => $email, 'role_id' => $roleId, 'status' => $status];
    }
}
