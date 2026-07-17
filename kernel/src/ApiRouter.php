<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class ApiRouter
{
    public static function register(Router $router, \mysqli $db): void
    {
        $contentService = new ContentService($db);
        $taxonomyService = new TaxonomyService($db);

        // ==================== CONTENT ====================

        $router->addRoute('GET', '/api/v1/content', function () use ($contentService, $db) {
            $page = max((int)($_GET['page'] ?? 1), 1);
            $perPage = min(max((int)($_GET['per_page'] ?? 20), 1), 100);
            $offset = ($page - 1) * $perPage;

            $params = [
                'type' => $_GET['type'] ?? '',
                'status' => $_GET['status'] ?? '',
                'author_id' => $_GET['author_id'] ?? '',
                'limit' => $perPage,
                'offset' => $offset,
                'order_by' => $_GET['order_by'] ?? 'created_at DESC',
            ];

            $params = array_filter($params, fn ($v) => $v !== '');

            $items = $contentService->findAll($params);

            $countSql = 'SELECT COUNT(*) AS total FROM content_items';
            $countResult = $db->query($countSql);
            $total = (int)($countResult->fetch_assoc()['total'] ?? 0);

            return Response::json([
                'data' => $items,
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                ],
            ]);
        });

        $router->addRoute('GET', '/api/v1/content/{id}', function (array $params) use ($contentService) {
            $item = $contentService->findById($params['id']);
            if ($item === null) {
                return Response::error(404, 'Content item not found.');
            }
            return Response::json(['data' => $item]);
        });

        $router->addRoute('POST', '/api/v1/content', function () use ($contentService) {
            $data = (new Request())->json();

            if (empty($data['title'])) {
                return Response::error(422, 'Title is required.');
            }

            if (empty($data['author_id'])) {
                $auth = Auth::getInstance();
                $userId = $auth->getUserId();
                if ($userId === null) {
                    return Response::error(401, 'Authentication required.');
                }
                $data['author_id'] = $userId;
            }

            $item = $contentService->create($data);
            return Response::json(['data' => $item], 201);
        });

        $router->addRoute('PUT', '/api/v1/content/{id}', function (array $params) use ($contentService) {
            $data = (new Request())->json();
            $item = $contentService->update($params['id'], $data);

            if ($item === null) {
                return Response::error(404, 'Content item not found.');
            }

            return Response::json(['data' => $item]);
        });

        $router->addRoute('DELETE', '/api/v1/content/{id}', function (array $params) use ($contentService) {
            $deleted = $contentService->delete($params['id']);

            if (!$deleted) {
                return Response::error(404, 'Content item not found.');
            }

            return Response::empty(204);
        });

        $router->addRoute('GET', '/api/v1/content/{id}/revisions', function (array $params) use ($contentService) {
            $revisions = $contentService->getRevisions($params['id']);
            return Response::json(['data' => $revisions]);
        });

        $router->addRoute('POST', '/api/v1/revisions/{id}/restore', function (array $params) use ($contentService) {
            $item = $contentService->restoreRevision($params['id']);
            if ($item === null) {
                return Response::error(404, 'Revision not found.');
            }
            return Response::json(['data' => $item]);
        });

        // ==================== TAXONOMIES ====================

        $router->addRoute('GET', '/api/v1/taxonomies', function () use ($taxonomyService) {
            $type = $_GET['type'] ?? '';
            $items = $taxonomyService->findAll($type);
            return Response::json(['data' => $items]);
        });

        $router->addRoute('GET', '/api/v1/taxonomies/{id}', function (array $params) use ($taxonomyService) {
            $item = $taxonomyService->findById($params['id']);
            if ($item === null) {
                return Response::error(404, 'Taxonomy not found.');
            }
            return Response::json(['data' => $item]);
        });

        $router->addRoute('POST', '/api/v1/taxonomies', function () use ($taxonomyService) {
            $data = (new Request())->json();
            $name = $data['name'] ?? '';
            $type = $data['type'] ?? 'category';

            if ($name === '') {
                return Response::error(422, 'Name is required.');
            }

            $item = $taxonomyService->create($name, $type);
            return Response::json(['data' => $item], 201);
        });

        $router->addRoute('DELETE', '/api/v1/taxonomies/{id}', function (array $params) use ($taxonomyService) {
            $deleted = $taxonomyService->delete($params['id']);
            if (!$deleted) {
                return Response::error(404, 'Taxonomy not found.');
            }
            return Response::empty(204);
        });

        $router->addRoute('POST', '/api/v1/content/{id}/taxonomies', function (array $params) use ($taxonomyService) {
            $data = (new Request())->json();
            $taxonomyId = $data['taxonomy_id'] ?? '';

            if ($taxonomyId === '') {
                return Response::error(422, 'taxonomy_id is required.');
            }

            $taxonomyService->attachToContent($params['id'], $taxonomyId);
            return Response::empty(204);
        });

        $router->addRoute('DELETE', '/api/v1/content/{id}/taxonomies/{taxonomyId}', function (array $params) use ($taxonomyService) {
            $taxonomyService->detachFromContent($params['id'], $params['taxonomyId']);
            return Response::empty(204);
        });

        $router->addRoute('GET', '/api/v1/content/{id}/taxonomies', function (array $params) use ($taxonomyService) {
            $items = $taxonomyService->getContentTaxonomies($params['id']);
            return Response::json(['data' => $items]);
        });

        // ==================== USERS ====================

        $router->addRoute('GET', '/api/v1/users', function () use ($db) {
            $stmt = $db->prepare('SELECT id, email, role_id, status, created_at FROM users ORDER BY created_at DESC');
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return Response::json(['data' => $users]);
        });

        $router->addRoute('GET', '/api/v1/users/me', function () {
            $auth = Auth::getInstance();
            $user = $auth->getCurrentUser();
            if ($user === null) {
                return Response::error(401, 'Authentication required.');
            }
            unset($user['password_hash']);
            return Response::json(['data' => $user]);
        });

        $router->addRoute('GET', '/api/v1/users/{id}', function (array $params) use ($db) {
            $stmt = $db->prepare('SELECT id, email, role_id, status, created_at FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('s', $params['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user === null) {
                return Response::error(404, 'User not found.');
            }

            return Response::json(['data' => $user]);
        });

        // ==================== AUTH ====================

        $router->addRoute('POST', '/api/v1/auth/login', function () use ($db) {
            $data = (new Request())->json();
            $email = $data['email'] ?? '';
            $password = $data['password'] ?? '';

            if ($email === '' || $password === '') {
                return Response::error(422, 'Email and password are required.');
            }

            $auth = Auth::getInstance();
            $auth->setDatabase($db);
            $result = $auth->login($email, $password);

            if (!$result['success']) {
                return Response::error(401, $result['error'] ?? 'Login failed.');
            }

            $user = $result['user'];
            unset($user['password_hash']);

            return Response::json(['data' => $user, 'message' => 'Login successful.']);
        });

        $router->addRoute('POST', '/api/v1/auth/logout', function () {
            Auth::getInstance()->logout();
            return Response::json(['message' => 'Logged out.']);
        });

        // ==================== SETTINGS ====================

        $router->addRoute('GET', '/api/v1/settings', function () use ($db) {
            $stmt = $db->prepare('SELECT * FROM settings ORDER BY scope ASC, setting_key ASC');
            $stmt->execute();
            $result = $stmt->get_result();
            $settings = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return Response::json(['data' => $settings]);
        });

        $router->addRoute('PUT', '/api/v1/settings/{id}', function (array $params) use ($db) {
            $data = (new Request())->json();
            $value = $data['setting_value'] ?? null;

            if ($value === null) {
                return Response::error(422, 'setting_value is required.');
            }

            $stmt = $db->prepare('UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE id = ?');
            $stmt->bind_param('ss', $value, $params['id']);
            $stmt->execute();
            $stmt->close();

            return Response::json(['message' => 'Setting updated.']);
        });

        $router->addRoute('POST', '/api/v1/settings', function () use ($db) {
            $data = (new Request())->json();
            $scope = $data['scope'] ?? 'global';
            $key = $data['setting_key'] ?? '';
            $value = $data['setting_value'] ?? '';

            if ($key === '') {
                return Response::error(422, 'setting_key is required.');
            }

            $id = Uuid::v4();
            $now = date('Y-m-d H:i:s');

            $stmt = $db->prepare('INSERT INTO settings (id, scope, setting_key, setting_value, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
            $stmt->bind_param('ssssss', $id, $scope, $key, $value, $now, $now);
            $stmt->execute();
            $stmt->close();

            return Response::json(['data' => ['id' => $id, 'scope' => $scope, 'setting_key' => $key, 'setting_value' => $value]], 201);
        });

        $router->addRoute('PUT', '/api/v1/settings', function () use ($db) {
            $data = (new Request())->json();
            $updated = 0;

            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    continue;
                }
                $stmt = $db->prepare('INSERT INTO settings (id, scope, setting_key, setting_value, created_at, updated_at) VALUES (?, \'global\', ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
                $fakeId = Uuid::v4();
                $stmt->bind_param('sss', $fakeId, $key, $value);
                $stmt->execute();
                $updated += $stmt->affected_rows > 0 ? 1 : 0;
                $stmt->close();
            }

            return Response::json(['data' => ['updated' => $updated]]);
        });

        // ==================== MEDIA ====================

        $router->addRoute('POST', '/api/v1/media', function () use ($db) {
            $auth = Auth::getInstance();
            $userId = $auth->getUserId();
            if ($userId === null) {
                return Response::error(401, 'Authentication required.');
            }

            $altText = $_POST['alt_text'] ?? '';
            if ($altText === '') {
                return Response::error(422, 'Alt text is required for all uploads.');
            }

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                $errorCode = $_FILES['file']['error'] ?? -1;
                return Response::error(422, 'File upload failed. Error code: ' . $errorCode);
            }

            $file = $_FILES['file'];
            $originalName = $file['name'];
            $mimeType = $file['type'];
            $fileSize = $file['size'];
            $tmpPath = $file['tmp_name'];

            if ($fileSize > 10 * 1024 * 1024) {
                return Response::error(422, 'File size must be under 10MB.');
            }

            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                'video/mp4', 'video/webm',
                'audio/mpeg', 'audio/wav',
                'application/pdf',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
            if (!in_array($mimeType, $allowedMimes, true)) {
                return Response::error(422, 'File type not allowed.');
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'mp3', 'wav', 'pdf', 'doc', 'docx'];
            if (!in_array($ext, $allowedExts, true)) {
                return Response::error(422, 'File extension not allowed.');
            }

            $id = Uuid::v4();
            $safeName = $id . '.' . $ext;
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $destPath = $uploadDir . '/' . $safeName;

            if (strpos($originalName, '..') !== false || strpos($originalName, '/') !== false) {
                return Response::error(422, 'Invalid file name.');
            }

            if (!move_uploaded_file($tmpPath, $destPath)) {
                return Response::error(500, 'Failed to save uploaded file.');
            }

            $filePath = '/uploads/' . $safeName;
            $now = date('Y-m-d H:i:s');

            $stmt = $db->prepare(
                'INSERT INTO media (id, file_path, file_name, mime_type, file_size, alt_text, uploader_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('ssssisss', $id, $filePath, $originalName, $mimeType, $fileSize, $altText, $userId, $now);
            $stmt->execute();
            $stmt->close();

            return Response::json([
                'data' => [
                    'id' => $id,
                    'file_path' => $filePath,
                    'file_name' => $originalName,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'alt_text' => $altText,
                ],
            ], 201);
        });

        $router->addRoute('GET', '/api/v1/media', function () use ($db) {
            $page = max((int)($_GET['page'] ?? 1), 1);
            $perPage = min(max((int)($_GET['per_page'] ?? 20), 1), 100);
            $offset = ($page - 1) * $perPage;

            $stmt = $db->prepare('SELECT * FROM media ORDER BY created_at DESC LIMIT ? OFFSET ?');
            $stmt->bind_param('ii', $perPage, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $countResult = $db->query('SELECT COUNT(*) AS total FROM media');
            $total = (int)($countResult->fetch_assoc()['total'] ?? 0);

            return Response::json([
                'data' => $items,
                'meta' => ['total' => $total, 'page' => $page, 'per_page' => $perPage],
            ]);
        });

        $router->addRoute('DELETE', '/api/v1/media/{id}', function (array $params) use ($db) {
            $stmt = $db->prepare('SELECT file_path FROM media WHERE id = ? LIMIT 1');
            $stmt->bind_param('s', $params['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $media = $result->fetch_assoc();
            $stmt->close();

            if ($media === null) {
                return Response::error(404, 'Media not found.');
            }

            $filePath = dirname(__DIR__, 2) . '/public' . $media['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $stmt = $db->prepare('DELETE FROM media WHERE id = ?');
            $stmt->bind_param('s', $params['id']);
            $stmt->execute();
            $stmt->close();

            return Response::empty(204);
        });

        // ==================== ROLES ====================

        $router->addRoute('GET', '/api/v1/roles', function () use ($db) {
            $stmt = $db->prepare('SELECT * FROM roles ORDER BY name ASC');
            $stmt->execute();
            $result = $stmt->get_result();
            $roles = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($roles as &$role) {
                $role['capabilities'] = json_decode($role['capabilities'], true) ?? [];
            }

            return Response::json(['data' => $roles]);
        });

        $router->addRoute('POST', '/api/v1/roles', function () use ($db) {
            $data = (new Request())->json();
            $name = $data['name'] ?? '';
            $capabilities = $data['capabilities'] ?? [];

            if ($name === '') {
                return Response::error(422, 'Role name is required.');
            }

            $id = Uuid::v4();
            $capabilitiesJson = json_encode($capabilities);
            $now = date('Y-m-d H:i:s');

            $stmt = $db->prepare('INSERT INTO roles (id, name, capabilities, created_at, updated_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $id, $name, $capabilitiesJson, $now, $now);
            $stmt->execute();
            $stmt->close();

            return Response::json(['data' => ['id' => $id, 'name' => $name, 'capabilities' => $capabilities]], 201);
        });

        $router->addRoute('PUT', '/api/v1/roles/{id}', function (array $params) use ($db) {
            $data = (new Request())->json();
            $capabilities = $data['capabilities'] ?? null;
            $name = $data['name'] ?? null;

            $fields = [];
            $types = '';
            $values = [];

            if ($name !== null) {
                $fields[] = 'name = ?';
                $types .= 's';
                $values[] = $name;
            }

            if ($capabilities !== null) {
                $capabilitiesJson = json_encode($capabilities);
                $fields[] = 'capabilities = ?';
                $types .= 's';
                $values[] = $capabilitiesJson;
            }

            if (empty($fields)) {
                return Response::error(422, 'No fields to update.');
            }

            $now = date('Y-m-d H:i:s');
            $fields[] = 'updated_at = ?';
            $types .= 's';
            $values[] = $now;

            $values[] = $params['id'];
            $types .= 's';

            $sql = 'UPDATE roles SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $db->prepare($sql);
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $stmt->close();

            return Response::json(['message' => 'Role updated.']);
        });

        $router->addRoute('DELETE', '/api/v1/roles/{id}', function (array $params) use ($db) {
            $stmt = $db->prepare('DELETE FROM roles WHERE id = ?');
            $stmt->bind_param('s', $params['id']);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected === 0) {
                return Response::error(404, 'Role not found.');
            }

            return Response::empty(204);
        });

        // ==================== THEMES ====================

        $router->addRoute('POST', '/api/v1/themes/{name}/activate', function (array $params) use ($db) {
            $themeLoader = new ThemeLoader(dirname(__DIR__, 2) . '/themes', $db);
            $themeLoader->setActiveTheme($params['name']);
            ThemeHooks::getInstance()->doAction('theme:settings:saved');
            return Response::json(['data' => ['theme' => $params['name'], 'message' => 'Theme activated']]);
        });

        // ==================== MENUS ====================

        $menuService = new MenuService($db);

        $router->addRoute('GET', '/api/v1/menus', function () use ($menuService) {
            $menus = $menuService->getAll();
            return Response::json(['data' => $menus]);
        });

        $router->addRoute('POST', '/api/v1/menus', function () use ($menuService) {
            $data = (new Request())->json();
            if (empty($data['name'])) {
                return Response::error(422, 'Menu name is required.');
            }
            $menu = $menuService->create($data);
            return Response::json(['data' => $menu], 201);
        });

        $router->addRoute('PUT', '/api/v1/menus/{id}', function (array $params) use ($menuService) {
            $data = (new Request())->json();
            $menu = $menuService->update($params['id'], $data);
            if ($menu === null) {
                return Response::error(404, 'Menu not found.');
            }
            return Response::json(['data' => $menu]);
        });

        $router->addRoute('DELETE', '/api/v1/menus/{id}', function (array $params) use ($menuService) {
            $deleted = $menuService->delete($params['id']);
            if (!$deleted) {
                return Response::error(404, 'Menu not found.');
            }
            return Response::empty(204);
        });

        // ==================== WIDGETS ====================

        $widgetService = new WidgetService($db);

        $router->addRoute('GET', '/api/v1/widget-areas', function () use ($widgetService) {
            $theme = $_GET['theme'] ?? 'starter';
            $areas = $widgetService->getAreas($theme);
            return Response::json(['data' => $areas]);
        });

        $router->addRoute('POST', '/api/v1/widget-areas', function () use ($widgetService) {
            $data = (new Request())->json();
            if (empty($data['name']) || empty($data['slug'])) {
                return Response::error(422, 'Name and slug are required.');
            }
            $area = $widgetService->createArea($data);
            return Response::json(['data' => $area], 201);
        });

        $router->addRoute('GET', '/api/v1/widget-areas/{id}/widgets', function (array $params) use ($widgetService) {
            $widgets = $widgetService->getWidgets($params['id']);
            return Response::json(['data' => $widgets]);
        });

        $router->addRoute('POST', '/api/v1/widgets', function () use ($widgetService) {
            $data = (new Request())->json();
            if (empty($data['area_id']) || empty($data['type'])) {
                return Response::error(422, 'area_id and type are required.');
            }
            $widget = $widgetService->createWidget($data);
            return Response::json(['data' => $widget], 201);
        });

        $router->addRoute('PUT', '/api/v1/widgets/{id}', function (array $params) use ($widgetService) {
            $data = (new Request())->json();
            $widget = $widgetService->updateWidget($params['id'], $data);
            if ($widget === null) {
                return Response::error(404, 'Widget not found.');
            }
            return Response::json(['data' => $widget]);
        });

        $router->addRoute('DELETE', '/api/v1/widgets/{id}', function (array $params) use ($widgetService) {
            $deleted = $widgetService->deleteWidget($params['id']);
            if (!$deleted) {
                return Response::error(404, 'Widget not found.');
            }
            return Response::empty(204);
        });
    }
}
