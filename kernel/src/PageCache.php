<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class PageCache
{
    private string $cacheDir;
    private int $ttl;
    private \mysqli $db;
    private string $prefix = 'monsoon_cache_';

    public function __construct(string $cacheDir, int $ttl = 3600)
    {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        $this->ttl = $ttl;
        
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function setDatabase(\mysqli $db): void
    {
        $this->db = $db;
    }

    public function get(string $key): ?string
    {
        $file = $this->cacheDir . $this->prefix . md5($key) . '.cache';
        
        if (!is_file($file)) {
            return null;
        }
        
        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }
        
        $cached = @unserialize($data);
        if ($cached === false || !isset($cached['expires'], $cached['content'])) {
            @unlink($file);
            return null;
        }
        
        if (time() > $cached['expires']) {
            @unlink($file);
            return null;
        }
        
        return $cached['content'];
    }

    public function set(string $key, string $content): bool
    {
        $file = $this->cacheDir . $this->prefix . md5($key) . '.cache';
        
        $data = serialize([
            'expires' => time() + $this->ttl,
            'content' => $content,
        ]);
        
        $tempFile = $file . '.tmp';
        if (file_put_contents($tempFile, $data) === false) {
            return false;
        }
        
        if (!rename($tempFile, $file)) {
            @unlink($tempFile);
            return false;
        }
        
        return true;
    }

    public function delete(string $key): bool
    {
        $file = $this->cacheDir . $this->prefix . md5($key) . '.cache';
        return @unlink($file);
    }

    public function purgeAll(): int
    {
        $deleted = 0;
        $files = glob($this->cacheDir . $this->prefix . '*.cache');
        foreach ($files as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function purgeByPattern(string $pattern): int
    {
        $deleted = 0;
        $files = glob($this->cacheDir . $this->prefix . '*' . $pattern . '*.cache');
        foreach ($files as $file) {
            if (@unlink($file)) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function generateKey(string $method, string $uri, array $headers = []): string
    {
        $normalizedUri = $this->normalizeUri($uri);
        $acceptLanguage = $headers['accept-language'] ?? '';
        
        return $method . '|' . $normalizedUri . '|' . $acceptLanguage;
    }

    private function normalizeUri(string $uri): string
    {
        $parsed = parse_url($uri);
        $path = $parsed['path'] ?? '/';
        $query = $parsed['query'] ?? '';
        
        parse_str($query, $params);
        ksort($params);
        $sortedQuery = http_build_query($params);
        
        return $path . ($sortedQuery ? '?' . $sortedQuery : '');
    }

    public function shouldCache(string $method, string $uri, array $headers = [], array $cookies = []): bool
    {
        if ($method !== 'GET' && $method !== 'HEAD') {
            return false;
        }
        
        if (isset($cookies['monsoon_session'])) {
            return false;
        }
        
        if (isset($headers['authorization'])) {
            return false;
        }
        
        if (isset($headers['cache-control']) && strpos($headers['cache-control'], 'no-cache') !== false) {
            return false;
        }
        
        $parsed = parse_url($uri);
        $path = $parsed['path'] ?? '/';
        
        $skipPaths = [
            '/manage/',
            '/api/',
            '/login',
            '/logout',
            '/install',
        ];
        
        foreach ($skipPaths as $skip) {
            if (strpos($path, $skip) === 0) {
                return false;
            }
        }
        
        return true;
    }

    public function onContentChange(string $type = 'content'): void
    {
        $this->purgeByPattern($type);
    }

    public function getStats(): array
    {
        $files = glob($this->cacheDir . $this->prefix . '*.cache');
        $totalSize = 0;
        $count = count($files);
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
        }
        
        return [
            'entries' => $count,
            'size_bytes' => $totalSize,
            'size_human' => $this->formatBytes($totalSize),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}