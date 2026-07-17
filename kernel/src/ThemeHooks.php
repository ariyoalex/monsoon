<?php
declare(strict_types=1);
namespace Monsoon\Kernel;

/**
 * Theme hooks allow modules to hook into theme lifecycle events.
 * Modules register callbacks for specific hooks, and the theme calls them at the right time.
 */
final class ThemeHooks
{
    private static ?self $instance = null;
    private array $hooks = [];

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(string $hook, callable $callback, int $priority = 10): void
    {
        $this->hooks[$hook][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
        usort($this->hooks[$hook], fn($a, $b) => $a['priority'] <=> $b['priority']);
    }

    public function unregister(string $hook, callable $callback): void
    {
        if (!isset($this->hooks[$hook])) {
            return;
        }
        $this->hooks[$hook] = array_filter(
            $this->hooks[$hook],
            fn($entry) => $entry['callback'] !== $callback
        );
    }

    public function apply(string $hook, mixed $data = null): mixed
    {
        if (!isset($this->hooks[$hook])) {
            return $data;
        }

        foreach ($this->hooks[$hook] as $entry) {
            $result = call_user_func($entry['callback'], $data);
            if ($result !== null) {
                $data = $result;
            }
        }

        return $data;
    }

    public function doAction(string $hook): void
    {
        if (!isset($this->hooks[$hook])) {
            return;
        }

        foreach ($this->hooks[$hook] as $entry) {
            call_user_func($entry['callback']);
        }
    }

    public function hasHooks(string $hook): bool
    {
        return !empty($this->hooks[$hook]);
    }

    public function getRegisteredHooks(): array
    {
        return $this->hooks;
    }
}
