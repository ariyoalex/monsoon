<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class Session
{
    private const SESSION_NAME = 'monsoon_session';
    private const SESSION_LIFETIME = 86400;

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => self::SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::refresh();
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function getId(): string
    {
        return session_id();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    private static function refresh(): void
    {
        $lastRegen = self::get('_last_regenerated', 0);
        if (time() - $lastRegen > 1800) {
            self::regenerate();
            self::set('_last_regenerated', time());
        }
    }
}
