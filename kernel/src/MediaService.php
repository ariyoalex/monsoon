<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class MediaService
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM media WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item ?: null;
    }

    public function findAll(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare('SELECT * FROM media ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('ii', $perPage, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $countResult = $this->db->query('SELECT COUNT(*) AS total FROM media');
        $total = (int)($countResult->fetch_assoc()['total'] ?? 0);

        return ['items' => $items, 'total' => $total];
    }

    public function delete(string $id): bool
    {
        $item = $this->findById($id);
        if ($item === null) {
            return false;
        }

        $filePath = dirname(__DIR__, 2) . '/public' . $item['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $stmt = $this->db->prepare('DELETE FROM media WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected > 0;
    }
}
