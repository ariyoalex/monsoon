<?php

declare(strict_types=1);

namespace Monsoon\Modules\BackupRestore;

use Monsoon\Kernel\Uuid;

final class BackupService
{
    private \mysqli $db;
    private string $backupDir;
    private string $webRoot;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->webRoot = '/var/www/html/';
        $this->backupDir = $this->webRoot . 'modules/backup-restore/backups/';
        $this->ensureBackupDir();
    }

    private function ensureBackupDir(): void
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function getAll(): array
    {
        $result = $this->db->query('SELECT * FROM backups ORDER BY created_at DESC');
        $backups = [];
        while ($row = $result->fetch_assoc()) {
            $backups[] = $row;
        }
        return $backups;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM backups WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function createBackup(string $name, string $type = 'full', string $notes = ''): array
    {
        $id = Uuid::v4();
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO backups (id, name, type, status, notes, created_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $pending = 'pending';
        $stmt->bind_param('ssssss', $id, $name, $type, $pending, $notes, $now);
        $stmt->execute();
        $stmt->close();

        $backupDir = $this->backupDir . $id . '/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $this->updateStatus($id, 'running');

        try {
            $result = match ($type) {
                'database' => $this->performDatabaseDump($backupDir, $id),
                'files' => $this->performFileArchive($backupDir, $id),
                default => $this->performFullBackupWithId($backupDir, $id),
            };
            return $result;
        } catch (\Throwable $e) {
            $this->updateStatus($id, 'failed');
            throw $e;
        }
    }

    public function deleteBackup(string $id): bool
    {
        $backup = $this->findById($id);
        if (!$backup) {
            return false;
        }

        if (!empty($backup['file_path']) && file_exists($backup['file_path'])) {
            $dir = dirname($backup['file_path']);
            $this->removeDirectory($dir);
        }

        $stmt = $this->db->prepare('DELETE FROM backups WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        return $deleted;
    }

    public function performDatabaseDump(string $backupDir, string $backupId): array
    {
        $tablesResult = $this->db->query('SHOW TABLES');
        if (!$tablesResult) {
            throw new \RuntimeException('Failed to list database tables.');
        }

        $tables = [];
        while ($row = $tablesResult->fetch_row()) {
            $tables[] = $row[0];
        }

        $sqlFile = $backupDir . $backupId . '_database.sql';
        $handle = fopen($sqlFile, 'w');
        if (!$handle) {
            throw new \RuntimeException('Failed to create SQL dump file.');
        }

        fwrite($handle, "-- Monsoon CMS Database Dump\n");
        fwrite($handle, "-- Backup ID: " . $backupId . "\n");
        fwrite($handle, "-- Date: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "-- Server: " . $this->db->server_info . "\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

        $totalRows = 0;
        foreach ($tables as $tableName) {
            $escapedTable = $this->db->real_escape_string($tableName);
            $tableResult = $this->db->query("SELECT * FROM `{$escapedTable}`");
            if (!$tableResult) {
                continue;
            }

            $numFields = $tableResult->field_count;
            $fieldMeta = $tableResult->fetch_fields();

            fwrite($handle, "DROP TABLE IF EXISTS `{$escapedTable}`;\n");

            $createResult = $this->db->query("SHOW CREATE TABLE `{$escapedTable}`");
            if ($createRow = $createResult->fetch_row()) {
                fwrite($handle, $createRow[1] . ";\n\n");
            }

            $rowCount = $tableResult->num_rows;
            if ($rowCount === 0) {
                continue;
            }

            $insertBatch = [];
            $batchSize = 100;
            $rowCountWritten = 0;

            while ($row = $tableResult->fetch_assoc()) {
                $values = [];
                foreach ($fieldMeta as $field) {
                    $fieldName = $field->name;
                    $value = $row[$fieldName] ?? null;
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $escaped = $this->db->real_escape_string((string) $value);
                        $values[] = "'" . $escaped . "'";
                    }
                }

                $fieldNames = array_map(fn($f) => '`' . $f->name . '`', $fieldMeta);
                $insertBatch[] = '(' . implode(', ', $values) . ')';

                if (count($insertBatch) >= $batchSize) {
                    fwrite($handle, "INSERT INTO `{$escapedTable}` (" . implode(', ', $fieldNames) . ") VALUES\n");
                    fwrite($handle, implode(",\n", $insertBatch) . ";\n\n");
                    $insertBatch = [];
                    $rowCountWritten += $batchSize;
                }
            }

            if (!empty($insertBatch)) {
                fwrite($handle, "INSERT INTO `{$escapedTable}` (" . implode(', ', $fieldNames) . ") VALUES\n");
                fwrite($handle, implode(",\n", $insertBatch) . ";\n\n");
            }

            $totalRows += $rowCount;
            $tableResult->free();
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($handle);

        $fileSize = filesize($sqlFile);

        $stmt = $this->db->prepare(
            'UPDATE backups SET file_path = ?, file_size = ?, db_tables_count = ?, db_rows_count = ?, status = ?, completed_at = NOW() WHERE id = ?'
        );
        $tablesCount = count($tables);
        $completed = 'completed';
        $stmt->bind_param('siisss', $sqlFile, $fileSize, $tablesCount, $totalRows, $completed, $backupId);
        $stmt->execute();
        $stmt->close();

        return [
            'id' => $backupId,
            'type' => 'database',
            'status' => 'completed',
            'file_path' => $sqlFile,
            'file_size' => $fileSize,
            'tables_count' => count($tables),
            'rows_count' => $totalRows,
        ];
    }

    public function performFileArchive(string $backupDir, string $backupId): string
    {
        $zipPath = $backupDir . $backupId . '_files.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create ZIP archive.');
        }

        $projectRoot = $this->webRoot;
        $skipDirs = ['vendor', '.ddev', '.git', 'node_modules', 'backups'];
        $skipExtensions = ['md'];

        $this->addDirectoryToZip($zip, $projectRoot, $projectRoot, $skipDirs, $skipExtensions);
        $zip->close();

        $fileSize = filesize($zipPath);

        $stmt = $this->db->prepare(
            'UPDATE backups SET file_path = ?, file_size = ?, status = ?, completed_at = NOW() WHERE id = ?'
        );
        $completed = 'completed';
        $stmt->bind_param('sis', $zipPath, $fileSize, $completed, $backupId);
        $stmt->execute();
        $stmt->close();

        return $zipPath;
    }

    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $baseDir, array $skipDirs, array $skipExtensions): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $dir . '/' . $item;
            $relativePath = ltrim(substr($fullPath, strlen($baseDir)), '/');

            if (is_dir($fullPath)) {
                if (in_array($item, $skipDirs, true)) {
                    continue;
                }
                if (str_starts_with($relativePath, 'modules/backup-restore/backups')) {
                    continue;
                }
                $this->addDirectoryToZip($zip, $fullPath, $baseDir, $skipDirs, $skipExtensions);
            } else {
                $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                if (in_array($extension, $skipExtensions, true)) {
                    continue;
                }
                if (basename($fullPath) === '.env') {
                    continue;
                }
                if (str_starts_with($relativePath, 'modules/backup-restore/backups')) {
                    continue;
                }
                $zip->addFile($fullPath, $relativePath);
            }
        }
    }

    public function performFullBackup(string $name, string $notes, ?string $createdBy): array
    {
        $id = Uuid::v4();
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO backups (id, name, type, status, notes, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $type = 'full';
        $pending = 'pending';
        $stmt->bind_param('sssssss', $id, $name, $type, $pending, $notes, $createdBy, $now);
        $stmt->execute();
        $stmt->close();

        $this->updateStatus($id, 'running');

        $backupDir = $this->backupDir . $id . '/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        try {
            $result = $this->performFullBackupWithId($backupDir, $id);
            return $result;
        } catch (\Throwable $e) {
            $this->updateStatus($id, 'failed');
            throw $e;
        }
    }

    private function performFullBackupWithId(string $backupDir, string $id): array
    {
        $dbResult = $this->performDatabaseDump($backupDir, $id);

        $zipPath = $backupDir . $id . '_files.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create ZIP archive.');
        }

        $projectRoot = $this->webRoot;
        $skipDirs = ['vendor', '.ddev', '.git', 'node_modules', 'backups'];
        $skipExtensions = ['md'];
        $this->addDirectoryToZip($zip, $projectRoot, $projectRoot, $skipDirs, $skipExtensions);
        $zip->close();

        $fileSize = filesize($zipPath);
        $totalSize = ($dbResult['file_size'] ?? 0) + $fileSize;

        $stmt = $this->db->prepare(
            'UPDATE backups SET file_size = ?, type = ?, status = ?, completed_at = NOW() WHERE id = ?'
        );
        $fullType = 'full';
        $completed = 'completed';
        $stmt->bind_param('isss', $totalSize, $fullType, $completed, $id);
        $stmt->execute();
        $stmt->close();

        return [
            'id' => $id,
            'type' => 'full',
            'status' => 'completed',
            'db_file' => $dbResult['file_path'],
            'db_size' => $dbResult['file_size'],
            'files_path' => $zipPath,
            'files_size' => $fileSize,
            'total_size' => $totalSize,
            'tables_count' => $dbResult['tables_count'],
            'rows_count' => $dbResult['rows_count'],
        ];
    }

    public function restoreFromBackup(string $id): array
    {
        $backup = $this->findById($id);
        if (!$backup) {
            throw new \RuntimeException('Backup not found.');
        }

        if ($backup['status'] !== 'completed') {
            throw new \RuntimeException('Cannot restore from a backup that is not completed.');
        }

        $sqlFilePath = null;
        if (!empty($backup['file_path']) && str_ends_with($backup['file_path'], '.sql')) {
            $sqlFilePath = $backup['file_path'];
        } elseif ($backup['type'] === 'full' && !empty($backup['file_path'])) {
            $dir = dirname($backup['file_path']);
            $files = glob($dir . '/*.sql');
            if (!empty($files)) {
                $sqlFilePath = $files[0];
            }
        }

        if (!$sqlFilePath || !file_exists($sqlFilePath)) {
            throw new \RuntimeException('SQL dump file not found for this backup.');
        }

        $sql = file_get_contents($sqlFilePath);
        if ($sql === false) {
            throw new \RuntimeException('Failed to read SQL dump file.');
        }

        $this->db->begin_transaction();
        try {
            $this->db->multi_query($sql);
            while ($this->db->next_result()) {
                if ($this->db->errno !== 0) {
                    throw new \RuntimeException('SQL restore error: ' . $this->db->error);
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }

        return [
            'id' => $id,
            'name' => $backup['name'],
            'status' => 'restored',
            'message' => 'Database restored successfully from backup.',
        ];
    }

    public function getBackupStats(): array
    {
        $result = $this->db->query('SELECT COUNT(*) as total, COALESCE(SUM(file_size), 0) as total_size FROM backups WHERE status = \'completed\'');
        $row = $result->fetch_assoc();

        $latestResult = $this->db->query('SELECT * FROM backups ORDER BY created_at DESC LIMIT 1');
        $latest = $latestResult->fetch_assoc();

        return [
            'total_backups' => (int) ($row['total'] ?? 0),
            'total_size' => (int) ($row['total_size'] ?? 0),
            'latest_backup' => $latest,
        ];
    }

    private function updateStatus(string $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE backups SET status = ? WHERE id = ?');
        $stmt->bind_param('ss', $status, $id);
        $stmt->execute();
        $stmt->close();
    }

    private function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}
