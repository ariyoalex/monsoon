<?php

declare(strict_types=1);

namespace Monsoon\Modules\SecurityHardening;

use Monsoon\Kernel\Uuid;

final class SecurityService
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    // --- Rate Limiting ---

    public function recordLoginAttempt(string $ip, string $email, bool $success): void
    {
        $id = Uuid::v4();
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            'INSERT INTO login_attempts (id, ip_address, email, success, attempted_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssis', $id, $ip, $email, $success, $now);
        $stmt->execute();
        $stmt->close();
    }

    public function isRateLimited(string $ip, int $maxAttempts = 5, int $windowSeconds = 900): bool
    {
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND success = 0 AND attempted_at > ?'
        );
        $stmt->bind_param('ss', $ip, $cutoff);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0) >= $maxAttempts;
    }

    public function getFailedAttempts(string $ip, int $windowSeconds = 900): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - $windowSeconds);
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM login_attempts WHERE ip_address = ? AND success = 0 AND attempted_at > ?'
        );
        $stmt->bind_param('ss', $ip, $cutoff);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    }

    // --- 2FA ---

    public function generate2faSecret(string $userId): array
    {
        $secret = strtoupper(bin2hex(random_bytes(20)));
        $id = Uuid::v4();
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO user_2fa (id, user_id, secret, enabled, created_at) VALUES (?, ?, ?, 0, ?) ON DUPLICATE KEY UPDATE secret = VALUES(secret), enabled = 0'
        );
        $stmt->bind_param('ssss', $id, $userId, $secret, $now);
        $stmt->execute();
        $stmt->close();

        return ['secret' => $secret, 'otpauth_url' => 'otpauth://totp/Monsoon:' . $userId . '?secret=' . $secret . '&issuer=Monsoon'];
    }

    public function verify2faCode(string $userId, string $code): bool
    {
        $stmt = $this->db->prepare('SELECT secret, enabled FROM user_2fa WHERE user_id = ? AND enabled = 1 LIMIT 1');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return false;

        // Simple TOTP verification (RFC 6238)
        $time = floor(time() / 30);
        for ($offset = -1; $offset <= 1; $offset++) {
            $counter = $time + $offset;
            $hash = hash_hmac('sha1', pack('N*', 0, $counter), $row['secret']);
            $otp = substr($hash, -6);
            if (hash_equals($otp, $code)) {
                return true;
            }
        }
        return false;
    }

    public function enable2fa(string $userId): void
    {
        $stmt = $this->db->prepare('UPDATE user_2fa SET enabled = 1 WHERE user_id = ?');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function disable2fa(string $userId): void
    {
        $stmt = $this->db->prepare('UPDATE user_2fa SET enabled = 0 WHERE user_id = ?');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $stmt->close();
    }

    public function is2faEnabled(string $userId): bool
    {
        $stmt = $this->db->prepare('SELECT enabled FROM user_2fa WHERE user_id = ? AND enabled = 1 LIMIT 1');
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($row);
    }

    // --- Audit Log ---

    public function log(string $action, ?string $userId = null, ?string $entityType = null, ?string $entityId = null, ?array $details = null): void
    {
        $id = Uuid::v4();
        $detailsJson = $details ? json_encode($details) : null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO audit_log (id, user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssssss', $id, $userId, $action, $entityType, $entityId, $detailsJson, $ip, $ua, $now);
        $stmt->execute();
        $stmt->close();
    }

    public function getAuditLog(int $limit = 100, int $offset = 0, ?string $action = null, ?string $entityType = null): array
    {
        $where = '1=1';
        $types = '';
        $params = [];

        if ($action) {
            $where .= ' AND action = ?';
            $types .= 's';
            $params[] = $action;
        }
        if ($entityType) {
            $where .= ' AND entity_type = ?';
            $types .= 's';
            $params[] = $entityType;
        }

        $sql = "SELECT al.*, u.email as user_email FROM audit_log al LEFT JOIN users u ON al.user_id = u.id WHERE $where ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $row['details'] = json_decode($row['details'] ?? '{}', true);
            $logs[] = $row;
        }
        $stmt->close();
        return $logs;
    }

    // --- File Integrity ---

    public function scanFiles(string $directory, array $extensions = ['php', 'js', 'css', 'json']): array
    {
        $results = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!in_array($file->getExtension(), $extensions, true)) continue;
            $relativePath = str_replace($directory . '/', '', $file->getPathname());
            if (str_starts_with($relativePath, 'vendor/') || str_starts_with($relativePath, 'node_modules/')) continue;

            $results[] = [
                'path' => $relativePath,
                'checksum' => hash_file('sha256', $file->getPathname()),
                'size' => $file->getSize(),
            ];
        }

        return $results;
    }

    public function storeChecksums(array $files): void
    {
        $stmt = $this->db->prepare('DELETE FROM file_integrity');
        $stmt->execute();
        $stmt->close();

        foreach ($files as $file) {
            $id = Uuid::v4();
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare(
                'INSERT INTO file_integrity (id, file_path, checksum, file_size, checked_at) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssis', $id, $file['path'], $file['checksum'], $file['size'], $now);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function checkIntegrity(string $directory): array
    {
        $stored = [];
        $result = $this->db->query('SELECT file_path, checksum, file_size FROM file_integrity');
        while ($row = $result->fetch_assoc()) {
            $stored[$row['file_path']] = $row;
        }

        $current = [];
        foreach ($this->scanFiles($directory) as $file) {
            $current[$file['path']] = $file;
        }

        $modified = [];
        $added = [];
        $deleted = [];

        foreach ($current as $path => $file) {
            if (!isset($stored[$path])) {
                $added[] = $path;
            } elseif ($stored[$path]['checksum'] !== $file['checksum']) {
                $modified[] = $path;
            }
        }

        foreach ($stored as $path => $file) {
            if (!isset($current[$path])) {
                $deleted[] = $path;
            }
        }

        return ['modified' => $modified, 'added' => $added, 'deleted' => $deleted, 'clean' => empty($modified) && empty($added) && empty($deleted)];
    }
}
