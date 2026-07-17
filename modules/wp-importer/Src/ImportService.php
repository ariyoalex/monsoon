<?php

declare(strict_types=1);

namespace Monsoon\Modules\WpImporter\Src;

use Monsoon\Kernel\Uuid;
use Monsoon\Kernel\Database;
use Monsoon\Kernel\ContentService;
use Monsoon\Kernel\TaxonomyService;
use Monsoon\Kernel\MediaService;
use Monsoon\Kernel\Auth;

final class ImportService
{
    private \mysqli $db;
    private WxrParser $parser;
    private ContentService $contentService;
    private TaxonomyService $taxonomyService;
    private MediaService $mediaService;
    private Auth $auth;
    private string $importLogId = '';
    private array $stats = [
        'authors' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
        'categories' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
        'tags' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
        'posts' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
        'pages' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
        'media' => ['created' => 0, 'skipped' => 0, 'errors' => 0],
        'shortcodes_found' => [],
        'redirects_created' => 0,
    ];
    private array $authorMap = [];
    private array $categoryMap = [];
    private array $tagMap = [];
    private array $postIdMap = [];
    private array $shortcodesFound = [];
    private string $uploadDir = '/var/www/html/public/uploads/';

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->parser = new WxrParser();
        $this->contentService = new ContentService($db);
        $this->taxonomyService = new TaxonomyService($db);
        $this->mediaService = new MediaService($db);
        $this->auth = Auth::getInstance();
        $this->auth->setDatabase($db);
        if (!is_dir($this->uploadDir)) mkdir($this->uploadDir, 0755, true);
    }

    public function setImportLogId(string $id): void
    {
        $this->importLogId = $id;
    }

    public function import(array $parsed, array $options = []): array
    {
        $this->importAuthors($parsed['authors']);
        $this->importTaxonomies($parsed['categories'], $parsed['tags']);
        $this->importMedia($parsed['media'], $options);
        $this->importContent($parsed['posts'], 'post', $options);
        $this->importContent($parsed['pages'], 'page', $options);
        $this->createRedirectsForItems($parsed['posts'], $parsed['pages'], $options);

        $this->logImport($parsed, $options);

        return [
            'success' => true,
            'stats' => $this->stats,
            'mappings' => [
                'authors' => $this->authorMap,
                'categories' => $this->categoryMap,
                'tags' => $this->tagMap,
                'posts' => $this->postIdMap,
            ],
        ];
    }

    private function importAuthors(array $authors): void
    {
        foreach ($authors as $wpId => $author) {
            if ($author['email'] === '') continue;
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $author['email']);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $this->authorMap[$wpId] = $existing['id'];
                $this->stats['authors']['skipped']++;
                continue;
            }

            $newId = Uuid::v4();
            $passwordHash = password_hash('wp_import_' . bin2hex(random_bytes(16)), PASSWORD_ARGON2ID);
            $stmt = $this->db->prepare('INSERT INTO users (id, email, password_hash, role_id, status, created_at) VALUES (?, ?, ?, (SELECT id FROM roles WHERE name = "author"), "active", NOW())');
            $stmt->bind_param('sss', $newId, $author['email'], $passwordHash);
            $stmt->execute();
            $stmt->close();

            $this->authorMap[$wpId] = $newId;
            $this->stats['authors']['created']++;
        }
    }

    private function importTaxonomies(array $categories, array $tags): void
    {
        foreach ($categories as $wpId => $cat) {
            $stmt = $this->db->prepare('SELECT id FROM taxonomies WHERE slug = ? LIMIT 1');
            $stmt->bind_param('s', $cat['slug']);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $this->categoryMap[$wpId] = $existing['id'];
                $this->stats['categories']['skipped']++;
                continue;
            }

            $newId = Uuid::v4();
            $stmt = $this->db->prepare('INSERT INTO taxonomies (id, name, slug, type, created_at) VALUES (?, ?, ?, "category", NOW())');
            $stmt->bind_param('sss', $newId, $cat['name'], $cat['slug']);
            $stmt->execute();
            $stmt->close();

            $this->categoryMap[$wpId] = $newId;
            $this->stats['categories']['created']++;
        }

        foreach ($tags as $wpId => $tag) {
            $stmt = $this->db->prepare('SELECT id FROM taxonomies WHERE slug = ? LIMIT 1');
            $stmt->bind_param('s', $tag['slug']);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $this->tagMap[$wpId] = $existing['id'];
                $this->stats['tags']['skipped']++;
                continue;
            }

            $newId = Uuid::v4();
            $stmt = $this->db->prepare('INSERT INTO taxonomies (id, name, slug, type, created_at) VALUES (?, ?, ?, "post_tag", NOW())');
            $stmt->bind_param('sss', $newId, $tag['name'], $tag['slug']);
            $stmt->execute();
            $stmt->close();

            $this->tagMap[$wpId] = $newId;
            $this->stats['tags']['created']++;
        }
    }

    private function importMedia(array $mediaItems, array $options): void
    {
        foreach ($mediaItems as $media) {
            $wpId = $media['wp_id'];
            $url = $media['attachment_url'] ?? $media['guid'] ?? '';

            if ($url === '') {
                $this->stats['media']['errors']++;
                continue;
            }

            $stmt = $this->db->prepare('SELECT id FROM media WHERE file_path = ? LIMIT 1');
            $stmt->bind_param('s', $url);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing) {
                $this->postIdMap[$wpId] = $existing['id'];
                $this->stats['media']['skipped']++;
                continue;
            }

            $newId = Uuid::v4();
            $fileName = basename($url);
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $mime = $this->mimeFromExt($ext);

            $stmt = $this->db->prepare('INSERT INTO media (id, file_path, mime_type, uploader_id, alt_text, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $altText = $media['title'] ?? $fileName;
            $uploaderId = $this->authorMap[$media['author_login'] ?? ''] ?? (array_values($this->authorMap)[0] ?? null);
            $stmt->bind_param('sssss', $newId, $url, $mime, $uploaderId, $altText);
            $stmt->execute();
            $stmt->close();

            $this->postIdMap[$wpId] = $newId;
            $this->stats['media']['created']++;
        }
    }

    private function importContent(array $items, string $type, array $options): void
    {
        foreach ($items as $item) {
            $wpId = $item['wp_id'];
            $slug = $item['slug'] ?: $this->generateSlug($item['title']);
            $status = $this->mapStatus($item['status']);

            $stmt = $this->db->prepare('SELECT id FROM content_items WHERE slug = ? AND type = ? LIMIT 1');
            $stmt->bind_param('ss', $slug, $type);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing && ($options['skip_existing'] ?? false)) {
                $this->postIdMap[$wpId] = $existing['id'];
                $this->stats[$type . 's']['skipped']++;
                continue;
            }

            $authorId = $this->authorMap[$item['author_login'] ?? ''] ?? (array_values($this->authorMap)[0] ?? null);
            $newId = Uuid::v4();
            $now = date('Y-m-d H:i:s');
            $blocks = $this->convertWpContentToBlocks($item['content'], $item['title'], $type);

            $shortcodes = $this->detectShortcodes($item['content']);
            if (!empty($shortcodes)) {
                $this->shortcodesFound = array_merge($this->shortcodesFound, $shortcodes);
            }

            $createdAt = $item['date'] ?: $now;
            $publishedAt = $status === 'published' ? ($item['date'] ?: $now) : null;
            $blocksJson = json_encode($blocks);

            $stmt = $this->db->prepare('INSERT INTO content_items (id, type, title, slug, status, body, author_id, created_at, updated_at, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssssssss', $newId, $type, $item['title'], $slug, $status, $blocksJson, $authorId, $createdAt, $now, $publishedAt);
            $stmt->execute();
            $stmt->close();

            $this->createRevision($newId, $blocksJson, $authorId);
            $this->assignTaxonomies($newId, $item['categories'], $item['tags']);

            if ($options['create_redirects'] ?? true) {
                $this->createRedirect($item, $slug, $type);
            }

            $this->postIdMap[$wpId] = $newId;
            $this->stats[$type . 's']['created']++;
        }
    }

    private function mapStatus(string $wpStatus): string
    {
        return match (strtolower($wpStatus)) {
            'publish' => 'published',
            'draft' => 'draft',
            'pending' => 'draft',
            'future' => 'draft',
            'private' => 'draft',
            'trash' => 'archived',
            default => 'draft',
        };
    }

    private function generateSlug(string $title): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        return $slug ?: Uuid::v4();
    }

    private function convertWpContentToBlocks(string $content, string $title, string $type): array
    {
        $blocks = [];
        if ($type === 'post' || $type === 'page') {
            $blocks[] = ['id' => Uuid::v4(), 'type' => 'heading', 'data' => ['text' => $title, 'level' => 1]];
        }

        $cleanContent = $this->stripShortcodes($content);
        $paragraphs = preg_split('/\n\s*\n/', $cleanContent);
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') continue;
            if (preg_match('/^<h([2-6])>(.*?)<\/h\1>$/i', $p, $m)) {
                $blocks[] = ['id' => Uuid::v4(), 'type' => 'heading', 'data' => ['text' => strip_tags($m[2]), 'level' => (int)$m[1]]];
            } elseif (preg_match('/^<ul>(.*?)<\/ul>$/is', $p)) {
                $blocks[] = ['id' => Uuid::v4(), 'type' => 'list', 'data' => ['items' => [$p], 'ordered' => false]];
            } elseif (preg_match('/^<ol>(.*?)<\/ol>$/is', $p)) {
                $blocks[] = ['id' => Uuid::v4(), 'type' => 'list', 'data' => ['items' => [$p], 'ordered' => true]];
            } elseif (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $p, $m)) {
                $blocks[] = ['id' => Uuid::v4(), 'type' => 'image', 'data' => ['url' => $m[1], 'alt' => '', 'caption' => '']];
            } else {
                $blocks[] = ['id' => Uuid::v4(), 'type' => 'paragraph', 'data' => ['text' => strip_tags($p)]];
            }
        }

        if (empty($blocks)) {
            $blocks[] = ['id' => Uuid::v4(), 'type' => 'paragraph', 'data' => ['text' => '']];
        }
        return $blocks;
    }

    private function stripShortcodes(string $content): string
    {
        return preg_replace('/\[\[?[^\]]+\]?\]/', '', $content);
    }

    private function detectShortcodes(string $content): array
    {
        preg_match_all('/\[\[?([a-z0-9_-]+)/i', $content, $matches);
        $shortcodes = array_unique($matches[1] ?? []);
        return array_map(fn($s) => ['shortcode' => $s], $shortcodes);
    }

    private function createRevision(string $contentId, string $blocksJson, ?string $authorId): void
    {
        $revId = Uuid::v4();
        $snapshot = json_decode($blocksJson, true);
        $snapshotJson = json_encode(['blocks' => $snapshot, 'title' => '', 'excerpt' => '']);

        $stmt = $this->db->prepare('INSERT INTO content_revisions (id, content_id, snapshot, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('sss', $revId, $contentId, $snapshotJson);
        $stmt->execute();
        $stmt->close();
    }

    private function assignTaxonomies(string $contentId, array $catSlugs, array $tagSlugs): void
    {
        foreach ($catSlugs as $slug) {
            if (isset($this->categoryMap[$slug])) {
                $this->db->query("INSERT IGNORE INTO content_taxonomy (content_id, taxonomy_id) VALUES ('$contentId', '{$this->categoryMap[$slug]}')");
            }
        }
        foreach ($tagSlugs as $slug) {
            if (isset($this->tagMap[$slug])) {
                $this->db->query("INSERT IGNORE INTO content_taxonomy (content_id, taxonomy_id) VALUES ('$contentId', '{$this->tagMap[$slug]}')");
            }
        }
    }

    private function createRedirect(array $item, string $newSlug, string $type): void
    {
        $oldPath = '';
        if (isset($item['link']) && preg_match('#https?://[^/]+(/.*)#', $item['link'], $m)) {
            $oldPath = $m[1];
        } elseif (isset($item['guid']) && preg_match('#https?://[^/]+(/.*)#', $item['guid'], $m)) {
            $oldPath = $m[1];
        }
        if ($oldPath && $oldPath !== '/' . $newSlug) {
            $id = Uuid::v4();
            $from = $oldPath;
            $to = '/' . $newSlug;
            $code = 301;

            $stmt = $this->db->prepare('INSERT INTO redirects (id, from_path, to_path, code, created_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE to_path = VALUES(to_path), code = VALUES(code)');
            $stmt->bind_param('sssi', $id, $from, $to, $code);
            $stmt->execute();
            $stmt->close();

            $this->stats['redirects_created']++;
        }
    }

    private function createRedirectsForItems(array $posts, array $pages, array $options): void
    {
        if (!($options['create_redirects'] ?? true)) return;
        foreach (array_merge($posts, $pages) as $item) {
            $newSlug = $item['slug'] ?? '';
            if ($newSlug) {
                $this->createRedirect($item, $newSlug, '');
            }
        }
    }

    private function mimeFromExt(string $ext): string
    {
        $map = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf', 'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'mp4' => 'video/mp4', 'mov' => 'video/quicktime', 'mp3' => 'audio/mpeg',
        ];
        return $map[strtolower($ext)] ?? 'application/octet-stream';
    }

    private function logImport(array $parsed, array $options): void
    {
        $id = Uuid::v4();
        $now = date('Y-m-d H:i:s');
        $summary = json_encode([
            'stats' => $this->stats,
            'options' => $options,
            'source_base_url' => $parsed['base_url'],
        ]);

        $stmt = $this->db->prepare('INSERT INTO import_log (id, summary, created_at) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $id, $summary, $now);
        $stmt->execute();
        $stmt->close();
    }

    public function getImportLogs(): array
    {
        $result = $this->db->query('SELECT id, summary, created_at FROM import_log ORDER BY created_at DESC LIMIT 50');
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }

    public function rollbackLastImport(): bool
    {
        $stmt = $this->db->query('SELECT id FROM import_log ORDER BY created_at DESC LIMIT 1');
        $row = $stmt->fetch_assoc();
        if (!$row) return false;
        $logId = $row['id'];

        $this->db->query("DELETE FROM content_items WHERE id IN (SELECT monsoon_id FROM import_items WHERE import_log_id = '$logId' AND monsoon_id IS NOT NULL)");
        $this->db->query("DELETE FROM media WHERE id IN (SELECT monsoon_id FROM import_items WHERE import_log_id = '$logId' AND monsoon_id IS NOT NULL)");
        $this->db->query("DELETE FROM import_items WHERE import_log_id = '$logId'");
        $this->db->query("DELETE FROM import_log WHERE id = '$logId'");

        return true;
    }
}