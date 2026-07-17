<?php

declare(strict_types=1);

namespace Monsoon\Modules\WpImporter;

use Monsoon\Kernel\Router;
use Monsoon\Kernel\Response;
use Monsoon\Kernel\CsrfMiddleware;

final class WpImporterModule
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function registerRoutes(Router $router): void
    {
        $router->addRoute('POST', '/api/v1/import/preview', function () {
            $file = $_FILES['wxr_file'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                return Response::error(422, 'Valid WXR file required.');
            }

            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                return Response::error(500, 'Failed to read uploaded file.');
            }

            $parser = new \Monsoon\Modules\WpImporter\Src\WxrParser();
            try {
                $parsed = $parser->parse($content);
            } catch (\Throwable $e) {
                return Response::error(422, 'Invalid WXR file: ' . $e->getMessage());
            }

            $shortcodes = $this->scanShortcodes($parsed['posts'], $parsed['pages']);
            $warnings = [];
            if (count($parsed['media']) > 100) $warnings[] = 'Large media library (' . count($parsed['media']) . ' items)';
            if ((count($parsed['posts']) + count($parsed['pages'])) > 500) $warnings[] = 'Large content volume (' . (count($parsed['posts']) + count($parsed['pages'])) . ' items)';

            return Response::json([
                'data' => [
                    'authors' => count($parsed['authors']),
                    'categories' => count($parsed['categories']),
                    'tags' => count($parsed['tags']),
                    'posts' => count($parsed['posts']),
                    'pages' => count($parsed['pages']),
                    'media' => count($parsed['media']),
                    'shortcodes_found' => array_unique($shortcodes),
                    'warnings' => $warnings,
                ]
            ]);
        });

        $router->addRoute('POST', '/api/v1/import/upload', function () {
            $file = $_FILES['wxr_file'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                return Response::error(422, 'Valid WXR file required.');
            }

            $options = [
                'preserve_dates' => ($_POST['preserve_dates'] ?? '1') === '1',
                'create_redirects' => ($_POST['create_redirects'] ?? '1') === '1',
                'skip_existing' => ($_POST['skip_existing'] ?? '0') === '1',
                'download_media' => ($_POST['download_media'] ?? '1') === '1',
            ];

            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                return Response::error(500, 'Failed to read file.');
            }

            $importLogId = \Monsoon\Kernel\Uuid::v4();
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare('INSERT INTO import_log (id, summary, created_at) VALUES (?, ?, ?)');
            $summary = 'WP Import - ' . date('Y-m-d H:i:s');
            $stmt->bind_param('sss', $importLogId, $summary, $now);
            $stmt->execute();
            $stmt->close();

            $service = new \Monsoon\Modules\WpImporter\Src\ImportService($this->db);
            $service->setImportLogId($importLogId);

            // Parse the XML first
            $content = file_get_contents($file['tmp_name']);
            $parser = new \Monsoon\Modules\WpImporter\Src\WxrParser();
            $parsed = $parser->parse($content);

            $result = $service->import($parsed, $options);

            $stmt = $this->db->prepare('UPDATE import_log SET summary = ? WHERE id = ?');
            $finalSummary = sprintf(
                'WP Import: %d authors, %d cats, %d tags, %d posts, %d pages, %d media',
                $result['stats']['authors']['created'] ?? 0,
                $result['stats']['categories']['created'] ?? 0,
                $result['stats']['tags']['created'] ?? 0,
                $result['stats']['posts']['created'] ?? 0,
                $result['stats']['pages']['created'] ?? 0,
                $result['stats']['media']['created'] ?? 0
            );
            $stmt->bind_param('ss', $finalSummary, $importLogId);
            $stmt->execute();
            $stmt->close();

            return Response::json(['data' => ['success' => true, 'stats' => $result['stats'], 'mappings' => $result['mappings']]]);
        });

        $router->addRoute('GET', '/api/v1/import/log/{id}', function (array $params) {
            $stmt = $this->db->prepare('SELECT * FROM import_log WHERE id = ?');
            $stmt->bind_param('s', $params['id']);
            $stmt->execute();
            $log = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$log) return Response::error(404, 'Import log not found');

            $stmt = $this->db->prepare('SELECT * FROM import_items WHERE import_log_id = ? ORDER BY created_at');
            $stmt->bind_param('s', $params['id']);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return Response::json(['data' => array_merge($log, ['items' => $items])]);
        });

        $router->addRoute('GET', '/api/v1/import/list', function () {
            $stmt = $this->db->prepare('SELECT * FROM import_log ORDER BY created_at DESC LIMIT 50');
            $stmt->execute();
            $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return Response::json(['data' => $logs]);
        });

        $router->addRoute('DELETE', '/api/v1/import/{id}', function (array $params) {
            $stmt = $this->db->prepare('DELETE FROM import_log WHERE id = ?');
            $stmt->bind_param('s', $params['id']);
            $stmt->execute();
            $deleted = $stmt->affected_rows > 0;
            $stmt->close();
            return Response::json(['data' => ['deleted' => $deleted]]);
        });

        $router->addRoute('POST', '/api/v1/redirects', function () {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $from = $data['from_path'] ?? '';
            $to = $data['to_path'] ?? '';
            if ($from === '' || $to === '') {
                return Response::error(422, 'from_path and to_path required');
            }
            $id = \Monsoon\Kernel\Uuid::v4();
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare('INSERT INTO redirects (id, from_path, to_path, code, created_at) VALUES (?, ?, ?, 301, ?) ON DUPLICATE KEY UPDATE to_path = VALUES(to_path), code = VALUES(code)');
            $stmt->bind_param('ssss', $id, $from, $to, $now);
            $stmt->execute();
            $stmt->close();
            return Response::json(['data' => ['id' => $id, 'from_path' => $from, 'to_path' => $to, 'code' => 301]], 201);
        });

        $router->addRoute('GET', '/api/v1/redirects', function () {
            $stmt = $this->db->prepare('SELECT * FROM redirects ORDER BY created_at DESC');
            $stmt->execute();
            $redirects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return Response::json(['data' => $redirects]);
        });

        $router->addRoute('DELETE', '/api/v1/redirects/{id}', function (array $params) {
            $stmt = $this->db->prepare('DELETE FROM redirects WHERE id = ?');
            $stmt->bind_param('s', $params['id']);
            $stmt->execute();
            $deleted = $stmt->affected_rows > 0;
            $stmt->close();
            return Response::json(['data' => ['deleted' => $deleted]]);
        });
    }

    private function scanShortcodes(array $posts, array $pages): array
    {
        $shortcodes = [];
        foreach (array_merge($posts, $pages) as $item) {
            preg_match_all('/\[\[?([a-zA-Z0-9_-]+)/', $item['content'], $m);
            $shortcodes = array_merge($shortcodes, $m[1] ?? []);
        }
        return array_unique($shortcodes);
    }

    private function logEvent(string $importLogId, array $event): void
    {
        $id = \Monsoon\Kernel\Uuid::v4();
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare('INSERT INTO import_items (id, import_log_id, item_type, wp_id, monsoon_id, status, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssssss', $id, $importLogId, $event['type'], $event['wp_id'] ?? '', $event['monsoon_id'] ?? '', $event['status'] ?? 'created', $event['error'] ?? '', $now);
        $stmt->execute();
        $stmt->close();
    }
}