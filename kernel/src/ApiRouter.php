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
    }
}
