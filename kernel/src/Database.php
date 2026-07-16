<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

use mysqli;
use RuntimeException;

/**
 * All queries must use prepared statements only.
 */
final class Database
{
    private static ?self $instance = null;
    private ?mysqli $connection = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function connect(array $config): void
    {
        $host = $config['DB_HOST'] ?? 'localhost';
        $user = $config['DB_USER'] ?? 'root';
        $pass = $config['DB_PASS'] ?? '';
        $name = $config['DB_NAME'] ?? 'monsoon';

        $this->connection = new mysqli($host, $user, $pass, $name);

        if ($this->connection->connect_error) {
            throw new RuntimeException(
                'Database connection failed: ' . $this->connection->connect_error
            );
        }

        $this->connection->set_charset('utf8mb4');
    }

    public function getConnection(): mysqli
    {
        if ($this->connection === null) {
            throw new RuntimeException('Database not connected. Call connect() first.');
        }

        return $this->connection;
    }

    public function close(): void
    {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }

        self::$instance = null;
    }
}
