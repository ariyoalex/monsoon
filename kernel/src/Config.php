<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class Config
{
    private static array $defaults = [
        'DB_HOST' => 'localhost',
        'DB_NAME' => 'monsoon',
        'DB_USER' => 'root',
        'DB_PASS' => '',
        'APP_ENV' => 'development',
        'APP_URL' => 'http://localhost:8080',
    ];

    private function __construct()
    {
    }

    public static function load(string $rootPath): array
    {
        $config = self::$defaults;

        $envPath = $rootPath . '/.env';

        if (is_file($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);

                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }

                    $parts = explode('=', $line, 2);

                    if (count($parts) === 2) {
                        $key = trim($parts[0]);
                        $value = trim($parts[1]);

                        $value = trim($value, '"\'');
                        $config[$key] = $value;
                    }
                }
            }
        }

        return $config;
    }
}
