<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class Request
{
    private ?string $cachedUri = null;

    public function path(): string
    {
        if ($this->cachedUri === null) {
            $this->cachedUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        }
        return $this->cachedUri;
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    public function isApi(): bool
    {
        return str_starts_with($this->path(), '/api/');
    }

    public function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json') || $this->isApi();
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->isApi()) {
            $data = $this->json();
            return $data[$key] ?? $default;
        }
        return $_POST[$key] ?? $default;
    }

    public function json(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || $raw === '') {
            return [];
        }

        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');

        if ($header === null) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
