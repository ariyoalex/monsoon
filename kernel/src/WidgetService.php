<?php
declare(strict_types=1);
namespace Monsoon\Kernel;

final class WidgetService
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function getAreas(string $theme = 'starter'): array
    {
        $stmt = $this->db->prepare('SELECT * FROM widget_areas WHERE theme = ? ORDER BY name ASC');
        $stmt->bind_param('s', $theme);
        $stmt->execute();
        $result = $stmt->get_result();
        $areas = [];
        while ($row = $result->fetch_assoc()) {
            $areas[] = $row;
        }
        $stmt->close();
        return $areas;
    }

    public function getWidgets(string $areaId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM widgets WHERE area_id = ? ORDER BY `order` ASC');
        $stmt->bind_param('s', $areaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $widgets = [];
        while ($row = $result->fetch_assoc()) {
            $row['settings'] = json_decode($row['settings'] ?? '{}', true);
            $widgets[] = $row;
        }
        $stmt->close();
        return $widgets;
    }

    public function createArea(array $data): array
    {
        $id = Uuid::v4();
        $name = $data['name'] ?? '';
        $slug = $data['slug'] ?? '';
        $theme = $data['theme'] ?? 'starter';
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO widget_areas (id, name, slug, theme, created_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $id, $name, $slug, $theme, $now);
        $stmt->execute();
        $stmt->close();

        return ['id' => $id, 'name' => $name, 'slug' => $slug, 'theme' => $theme];
    }

    public function createWidget(array $data): array
    {
        $id = Uuid::v4();
        $areaId = $data['area_id'] ?? '';
        $type = $data['type'] ?? '';
        $title = $data['title'] ?? null;
        $settings = json_encode($data['settings'] ?? []);
        $order = $data['order'] ?? 0;
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO widgets (id, area_id, type, title, settings, `order`, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssis', $id, $areaId, $type, $title, $settings, $order, $now, $now);
        $stmt->execute();
        $stmt->close();

        return $this->findWidget($id);
    }

    public function updateWidget(string $id, array $data): ?array
    {
        $fields = [];
        $params = [];
        $types = '';

        if (isset($data['title'])) {
            $fields[] = 'title = ?';
            $params[] = $data['title'];
            $types .= 's';
        }
        if (isset($data['settings'])) {
            $fields[] = 'settings = ?';
            $params[] = json_encode($data['settings']);
            $types .= 's';
        }
        if (isset($data['order'])) {
            $fields[] = '`order` = ?';
            $params[] = (int)$data['order'];
            $types .= 'i';
        }

        if (empty($fields)) {
            return $this->findWidget($id);
        }

        $fields[] = 'updated_at = ?';
        $params[] = date('Y-m-d H:i:s');
        $types .= 's';
        $params[] = $id;
        $types .= 's';

        $sql = 'UPDATE widgets SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        return $this->findWidget($id);
    }

    public function deleteWidget(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM widgets WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();
        return $deleted;
    }

    private function findWidget(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM widgets WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $row['settings'] = json_decode($row['settings'] ?? '{}', true);
        }
        return $row ?: null;
    }
}
