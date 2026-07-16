<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class ContentService
{
    private \mysqli $db;

    private const VALID_STATUSES = ['draft', 'review', 'approved', 'published', 'archived'];
    private const VALID_TYPES = ['page', 'post'];

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function create(array $data): array
    {
        $id = Uuid::v4();
        $type = in_array($data['type'] ?? '', self::VALID_TYPES, true) ? $data['type'] : 'page';
        $title = $data['title'] ?? '';
        $status = in_array($data['status'] ?? '', self::VALID_STATUSES, true) ? $data['status'] : 'draft';
        $authorId = $data['author_id'] ?? '';
        $body = $data['body'] ?? '';
        $now = date('Y-m-d H:i:s');

        $slug = isset($data['slug']) && $data['slug'] !== ''
            ? SlugGenerator::generateUnique($data['slug'], $this->db)
            : SlugGenerator::generateUnique($title, $this->db);

        $publishedAt = null;
        if ($status === 'published') {
            $publishedAt = $data['published_at'] ?? $now;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO content_items (id, type, title, slug, body, status, author_id, created_at, updated_at, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('ssssssssss', $id, $type, $title, $slug, $body, $status, $authorId, $now, $now, $publishedAt);
        $stmt->execute();
        $stmt->close();

        $this->createRevision($id);

        return $this->findById($id);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM content_items WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM content_items WHERE slug = ? LIMIT 1');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item ?: null;
    }

    public function findAll(array $params = []): array
    {
        $conditions = [];
        $types = '';
        $values = [];

        if (!empty($params['type'])) {
            $conditions[] = 'type = ?';
            $types .= 's';
            $values[] = $params['type'];
        }

        if (!empty($params['status'])) {
            $conditions[] = 'status = ?';
            $types .= 's';
            $values[] = $params['status'];
        }

        if (!empty($params['author_id'])) {
            $conditions[] = 'author_id = ?';
            $types .= 's';
            $values[] = $params['author_id'];
        }

        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $orderBy = 'created_at DESC';
        if (!empty($params['order_by'])) {
            $orderBy = $params['order_by'];
        }

        $limit = '';
        if (!empty($params['limit'])) {
            $limitVal = (int)$params['limit'];
            $limit = "LIMIT {$limitVal}";
        }

        $sql = "SELECT * FROM content_items {$where} ORDER BY {$orderBy} {$limit}";

        $stmt = $this->db->prepare($sql);

        if (!empty($values)) {
            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $items;
    }

    public function update(string $id, array $data): ?array
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            return null;
        }

        $fields = [];
        $types = '';
        $values = [];

        if (isset($data['title'])) {
            $fields[] = 'title = ?';
            $types .= 's';
            $values[] = $data['title'];
        }

        if (isset($data['slug']) && $data['slug'] !== '') {
            $slug = SlugGenerator::generateUnique($data['slug'], $this->db);
            $fields[] = 'slug = ?';
            $types .= 's';
            $values[] = $slug;
        }

        if (isset($data['body'])) {
            $fields[] = 'body = ?';
            $types .= 's';
            $values[] = $data['body'];
        }

        if (isset($data['status'])) {
            $status = in_array($data['status'], self::VALID_STATUSES, true) ? $data['status'] : $existing['status'];
            $fields[] = 'status = ?';
            $types .= 's';
            $values[] = $status;

            if ($status === 'published' && $existing['published_at'] === null) {
                $fields[] = 'published_at = ?';
                $types .= 's';
                $values[] = date('Y-m-d H:i:s');
            }
        }

        if (isset($data['type'])) {
            $type = in_array($data['type'], self::VALID_TYPES, true) ? $data['type'] : $existing['type'];
            $fields[] = 'type = ?';
            $types .= 's';
            $values[] = $type;
        }

        if (empty($fields)) {
            return $existing;
        }

        $now = date('Y-m-d H:i:s');
        $fields[] = 'updated_at = ?';
        $types .= 's';
        $values[] = $now;

        $values[] = $id;
        $types .= 's';

        $sql = 'UPDATE content_items SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();

        $this->createRevision($id);

        return $this->findById($id);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM content_items WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    public function createRevision(string $contentId): void
    {
        $content = $this->findById($contentId);
        if ($content === null) {
            return;
        }

        $id = Uuid::v4();
        $snapshot = json_encode($content);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO content_revisions (id, content_id, snapshot, created_at) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $id, $contentId, $snapshot, $now);
        $stmt->execute();
        $stmt->close();
    }

    public function getRevisions(string $contentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM content_revisions WHERE content_id = ? ORDER BY created_at DESC'
        );
        $stmt->bind_param('s', $contentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $revisions = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $revisions;
    }

    public function restoreRevision(string $revisionId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM content_revisions WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $revisionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $revision = $result->fetch_assoc();
        $stmt->close();

        if ($revision === null) {
            return null;
        }

        $snapshot = json_decode($revision['snapshot'], true);
        if (!is_array($snapshot)) {
            return null;
        }

        return $this->update($snapshot['id'], $snapshot);
    }
}
