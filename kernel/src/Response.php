<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class Response
{
    private int $status;
    private array $headers;
    private string $body;

    public function __construct(int $status = 200, array $headers = [], string $body = '')
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html; charset=utf-8'], $body);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        return new self($status, ['Content-Type' => 'application/json; charset=utf-8'], $body);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self($status, ['Location' => $url]);
    }

    public static function empty(int $status = 204): self
    {
        return new self($status);
    }

    public static function error(int $status, string $message): self
    {
        return self::json(['error' => ['code' => $status, 'message' => $message]], $status);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if ($this->body !== '') {
            echo $this->body;
        }
    }

    public function status(): int
    {
        return $this->status;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers;
        $headers[$name] = $value;
        return new self($this->status, $headers, $this->body);
    }

    public function withStatus(int $status): self
    {
        return new self($status, $this->headers, $this->body);
    }
}
