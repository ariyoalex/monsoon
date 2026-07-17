<?php

declare(strict_types=1);

namespace Monsoon\Modules\SeoBasics;

use Monsoon\Kernel\Uuid;

final class SeoService
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function getMetaForContent(string $contentId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM seo_meta WHERE content_id = ? LIMIT 1');
        $stmt->bind_param('s', $contentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: [];
    }

    public function saveMeta(string $contentId, array $data): array
    {
        $existing = $this->getMetaForContent($contentId);

        if (!empty($existing)) {
            $fields = [];
            $params = [];
            $types = '';

            foreach (['meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'canonical_url', 'schema_type'] as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field] !== '' ? $data[$field] : null;
                    $types .= 's';
                }
            }

            if (array_key_exists('noindex', $data)) {
                $fields[] = 'noindex = ?';
                $params[] = (int)$data['noindex'];
                $types .= 'i';
            }

            if (!empty($fields)) {
                $fields[] = 'updated_at = NOW()';
                $params[] = $contentId;
                $types .= 's';
                $sql = 'UPDATE seo_meta SET ' . implode(', ', $fields) . ' WHERE content_id = ?';
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $id = Uuid::v4();
            $metaTitle = $data['meta_title'] ?? null;
            $metaDesc = $data['meta_description'] ?? null;
            $ogTitle = $data['og_title'] ?? null;
            $ogDesc = $data['og_description'] ?? null;
            $ogImage = $data['og_image'] ?? null;
            $canonical = $data['canonical_url'] ?? null;
            $schemaType = $data['schema_type'] ?? 'WebPage';
            $noindex = (int)($data['noindex'] ?? 0);

            $stmt = $this->db->prepare(
                'INSERT INTO seo_meta (id, content_id, meta_title, meta_description, og_title, og_description, og_image, canonical_url, schema_type, noindex, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
            );
            $stmt->bind_param('sssssssssi', $id, $contentId, $metaTitle, $metaDesc, $ogTitle, $ogDesc, $ogImage, $canonical, $schemaType, $noindex);
            $stmt->execute();
            $stmt->close();
        }

        return $this->getMetaForContent($contentId);
    }

    public function generateSitemap(): string
    {
        $result = $this->db->query(
            "SELECT slug, title, updated_at FROM content_items WHERE status = 'published' ORDER BY updated_at DESC"
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        while ($row = $result->fetch_assoc()) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>/" . htmlspecialchars($row['slug']) . "</loc>\n";
            $xml .= "    <lastmod>" . htmlspecialchars($row['updated_at']) . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>";

        return $xml;
    }

    public function generateRobotsTxt(): string
    {
        return "User-agent: *\nAllow: /\nDisallow: /manage/\nDisallow: /api/\n\nSitemap: /sitemap.xml\n";
    }

    public function generateSchema(array $content, array $seoMeta): string
    {
        $schemaType = $seoMeta['schema_type'] ?? 'WebPage';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $schemaType,
            'name' => $seoMeta['meta_title'] ?? $content['title'] ?? '',
            'description' => $seoMeta['meta_description'] ?? '',
            'url' => $seoMeta['canonical_url'] ?? '/',
        ];

        if ($schemaType === 'Article' || $schemaType === 'BlogPosting') {
            $schema['datePublished'] = $content['created_at'] ?? '';
            $schema['dateModified'] = $content['updated_at'] ?? '';
            if (!empty($seoMeta['og_image'])) {
                $schema['image'] = $seoMeta['og_image'];
            }
        }

        return '<script type="application/ld+json">'
            . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>';
    }
}
