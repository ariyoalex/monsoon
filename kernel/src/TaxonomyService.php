<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class TaxonomyService
{
    private \mysqli $db;

    private const VALID_TYPES = ['category', 'tag'];

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function create(string $name, string $type = 'category'): array
    {
        $id = Uuid::v4();
        $type = in_array($type, self::VALID_TYPES, true) ? $type : 'category';
        $slug = SlugGenerator::generate($name);
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare(
            'INSERT INTO taxonomies (id, name, slug, type, created_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $id, $name, $slug, $type, $now);
        $stmt->execute();
        $stmt->close();

        return $this->findById($id);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM taxonomies WHERE id = ? LIMIT 1');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM taxonomies WHERE slug = ? LIMIT 1');
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item ?: null;
    }

    public function findAll(string $type = ''): array
    {
        if ($type !== '') {
            $stmt = $this->db->prepare('SELECT * FROM taxonomies WHERE type = ? ORDER BY name ASC');
            $stmt->bind_param('s', $type);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM taxonomies ORDER BY type ASC, name ASC');
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $items;
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM taxonomies WHERE id = ?');
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    public function attachToContent(string $contentId, string $taxonomyId): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO content_taxonomy (content_id, taxonomy_id) VALUES (?, ?)'
        );
        $stmt->bind_param('ss', $contentId, $taxonomyId);
        $stmt->execute();
        $stmt->close();
    }

    public function detachFromContent(string $contentId, string $taxonomyId): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM content_taxonomy WHERE content_id = ? AND taxonomy_id = ?'
        );
        $stmt->bind_param('ss', $contentId, $taxonomyId);
        $stmt->execute();
        $stmt->close();
    }

    public function getContentTaxonomies(string $contentId): array
    {
        $stmt = $this->db->prepare(
            'SELECT t.* FROM taxonomies t INNER JOIN content_taxonomy ct ON t.id = ct.taxonomy_id WHERE ct.content_id = ?'
        );
        $stmt->bind_param('s', $contentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $items;
    }
}
