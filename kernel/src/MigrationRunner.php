<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class MigrationRunner
{
    private string $migrationsDir;
    private \mysqli $db;

    public function __construct(string $migrationsDir, \mysqli $db)
    {
        $this->migrationsDir = rtrim($migrationsDir, '/');
        $this->db = $db;
    }

    public function run(): array
    {
        $this->ensureTrackingTable();
        $applied = $this->getAppliedMigrations();
        $result = ['applied' => [], 'failed' => []];

        $files = glob($this->migrationsDir . '/[0-9]*.sql');
        sort($files);

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }

            try {
                $this->db->begin_transaction();

                if ($this->db->multi_query($sql) === false) {
                    throw new \RuntimeException($this->db->error);
                }

                while ($this->db->more_results()) {
                    $this->db->next_result();
                }

                $this->recordMigration($name);
                $this->db->commit();
                $result['applied'][] = $name;
            } catch (\Throwable $e) {
                $this->db->rollback();
                $result['failed'][] = ['migration' => $name, 'error' => $e->getMessage()];
            }
        }

        return $result;
    }

    private function ensureTrackingTable(): void
    {
        $this->db->query(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function getAppliedMigrations(): array
    {
        $result = $this->db->query('SELECT migration FROM schema_migrations ORDER BY id ASC');
        $migrations = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $migrations[] = $row['migration'];
            }
        }
        return $migrations;
    }

    private function recordMigration(string $name): void
    {
        $stmt = $this->db->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->close();
    }
}
