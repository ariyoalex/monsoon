<?php
declare(strict_types=1);
namespace Monsoon\Kernel;

final class MenuService
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $result = $this->db->query('SELECT * FROM menus ORDER BY name ASC');
        $menus = [];
        while ($row = $result->fetch_assoc()) {
            $row['items'] = json_decode($row['items'] ?? '[]', true);
            $menus[] = $row;
        }
        return $menus;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM menus WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $row['items'] = json_decode($row['items'] ?? '[]', true);
        }
        return $row ?: null;
    }

    public function getByLocation(string $location): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM menus WHERE location = ? LIMIT 1');
        $stmt->bind_param('s', $location);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $row['items'] = json_decode($row['items'] ?? '[]', true);
        }
        return $row ?: null;
    }

    public function create(array $data): array
    {
        $id = Uuid::v4();
        $name = $data['name'] ?? '';
        $location = $data['location'] ?? null;
        $items = json_encode($data['items'] ?? []);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO menus (id, name, location, items, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssss', $id, $name, $location, $items, $now, $now);
        $stmt->execute();
        $stmt->close();

        return $this->findById($id);
    }

    public function update(string $id, array $data): ?array
    {
        $fields = [];
        $params = [];
        $types = '';

        if (isset($data['name'])) {
            $fields[] = 'name = ?';
            $params[] = $data['name'];
            $types .= 's';
        }
        if (array_key_exists('location', $data)) {
            $fields[] = 'location = ?';
            $params[] = $data['location'];
            $types .= 's';
        }
        if (isset($data['items'])) {
            $fields[] = 'items = ?';
            $params[] = json_encode($data['items']);
            $types .= 's';
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $fields[] = 'updated_at = ?';
        $params[] = date('Y-m-d H:i:s');
        $types .= 's';
        $params[] = $id;
        $types .= 's';

        $sql = 'UPDATE menus SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM menus WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        return $deleted;
    }
}
