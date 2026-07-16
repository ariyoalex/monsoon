<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class AdminRoutes
{
    public static function register(Router $router, array $config): void
    {
        $router->addRoute('GET', '/manage/login', function () {
            return [
                'status' => 200,
                'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                'body' => self::renderLoginPage(),
            ];
        });

        $router->addRoute('POST', '/manage/login', function () use ($config) {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($email === '' || $password === '') {
                return [
                    'status' => 400,
                    'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                    'body' => self::renderLoginPage('Email and password are required.'),
                ];
            }

            try {
                $db = Database::getInstance()->getConnection();
                $auth = Auth::getInstance();
                $auth->setDatabase($db);

                $result = $auth->login($email, $password);

                if (!$result['success']) {
                    return [
                        'status' => 401,
                        'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                        'body' => self::renderLoginPage($result['error'] ?? 'Login failed.'),
                    ];
                }

                header('Location: /manage');
                return [
                    'status' => 302,
                    'headers' => ['Location' => '/manage'],
                    'body' => '',
                ];
            } catch (\Throwable $e) {
                return [
                    'status' => 500,
                    'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                    'body' => self::renderLoginPage('An internal error occurred.'),
                ];
            }
        });

        $router->addRoute('GET', '/manage/logout', function () {
            Auth::getInstance()->logout();
            header('Location: /manage/login');
            return [
                'status' => 302,
                'headers' => ['Location' => '/manage/login'],
                'body' => '',
            ];
        });

        $router->addRoute('GET', '/manage', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }

            $user = $auth->getCurrentUser();
            return Response::html(self::renderDashboard($user));
        });

        $router->addRoute('GET', '/manage/content', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderContentList());
        });

        $router->addRoute('GET', '/manage/content/new', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderContentEditor());
        });

        $router->addRoute('GET', '/manage/content/{id}/edit', function (array $params) {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            $db = Database::getInstance()->getConnection();
            $service = new ContentService($db);
            $item = $service->findById($params['id']);
            if ($item === null) {
                return Response::redirect('/manage/content');
            }
            return Response::html(self::renderContentEditor($item));
        });

        $router->addRoute('GET', '/manage/media', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderMediaPage());
        });

        $router->addRoute('GET', '/manage/users', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderUsersPage());
        });

        $router->addRoute('GET', '/manage/settings', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderSettingsPage());
        });

        $router->addRoute('GET', '/manage/roles', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderRolesPage());
        });
    }

    private static function renderLoginPage(string $error = ''): string
    {
        $errorHtml = '';
        if ($error !== '') {
            $errorHtml = '<div class="alert alert-danger mb-4">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $csrfField = CsrfMiddleware::field();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; display: flex; align-items: center; min-height: 100vh; }
.login-card { max-width: 420px; width: 100%; }
.card { border-color: #E1E5EC; }
.card-header { background: #1034A6; color: #fff; }
.btn-primary { background-color: #1034A6; border-color: #1034A6; }
</style>
</head>
<body>
<div class="container">
<div class="row justify-content-center">
<div class="col login-card">
<div class="card shadow-sm">
<div class="card-header">
<h1 class="h4 mb-0">Monsoon CMS</h1>
</div>
<div class="card-body">
{$errorHtml}
<form method="post" action="/manage/login">
{$csrfField}
<div class="mb-3">
<label for="email" class="form-label">Email</label>
<input type="email" class="form-control" id="email" name="email" required autocomplete="email">
</div>
<div class="mb-3">
<label for="password" class="form-label">Password</label>
<input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
</div>
<button type="submit" class="btn btn-primary w-100" aria-label="Log in">Log in</button>
</form>
</div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
</body>
</html>
HTML;
    }

    private static function renderDashboard(?array $user): string
    {
        $userEmail = htmlspecialchars($user['email'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; }
.sidebar { background: #1A1A1A; min-height: 100vh; color: #fff; }
.sidebar a { color: #ccc; text-decoration: none; display: block; padding: 0.5rem 1rem; border-radius: 0.25rem; }
.sidebar a:hover { background: #333; color: #fff; }
.sidebar .brand { padding: 1rem; font-weight: 700; color: #fff; border-bottom: 1px solid #333; }
.content { padding: 2rem; }
.card { border-color: #E1E5EC; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
        <nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage" aria-label="Dashboard" aria-current="page">Dashboard</a>
<a href="/manage/content" aria-label="Content">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3">Dashboard</h1>
<span class="text-muted">{$userEmail}</span>
</div>
<div class="row">
<div class="col-md-4 mb-3">
<div class="card shadow-sm">
<div class="card-body">
<h2 class="h6 text-muted">Content</h2>
<p class="display-6 mb-0">0</p>
</div>
</div>
</div>
<div class="col-md-4 mb-3">
<div class="card shadow-sm">
<div class="card-body">
<h2 class="h6 text-muted">Media</h2>
<p class="display-6 mb-0">0</p>
</div>
</div>
</div>
<div class="col-md-4 mb-3">
<div class="card shadow-sm">
<div class="card-body">
<h2 class="h6 text-muted">Users</h2>
<p class="display-6 mb-0">1</p>
</div>
</div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
</body>
</html>
HTML;
    }

    private static function renderContentList(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Content - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; }
.sidebar { background: #1A1A1A; min-height: 100vh; color: #fff; }
.sidebar a { color: #ccc; text-decoration: none; display: block; padding: 0.5rem 1rem; border-radius: 0.25rem; }
.sidebar a:hover, .sidebar a.active { background: #333; color: #fff; }
.sidebar .brand { padding: 1rem; font-weight: 700; color: #fff; border-bottom: 1px solid #333; }
.content { padding: 2rem; }
.card { border-color: #E1E5EC; }
.table th { font-weight: 600; color: #555555; }
.status-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 1rem; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
        <nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" class="active" aria-label="Content" aria-current="page">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">Content</h1>
<a href="/manage/content/new" class="btn btn-primary" style="background-color:#1034A6;border-color:#1034A6;" aria-label="Create new page">New Page</a>
</div>
<div class="card shadow-sm">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr>
<th>Title</th>
<th>Type</th>
<th>Status</th>
<th>Author</th>
<th>Date</th>
<th></th>
</tr>
</thead>
<tbody id="content-list">
<tr><td colspan="6" class="text-center text-muted py-4">Loading...</td></tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:11" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadContent();
});

function loadContent() {
    fetch('/api/v1/content')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('content-list');
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No content yet. Create your first page.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map(item => {
                const statusClass = item.status === 'published' ? 'success' : (item.status === 'draft' ? 'secondary' : 'warning');
                const date = new Date(item.created_at).toLocaleDateString();
                return '<tr>' +
                    '<td><a href="/manage/content/' + item.id + '/edit" class="text-decoration-none" style="color:#1034A6;">' + Monsoon.escapeHtml(item.title || 'Untitled') + '</a></td>' +
                    '<td>' + Monsoon.escapeHtml(item.type) + '</td>' +
                    '<td><span class="status-badge bg-' + statusClass + ' text-white">' + Monsoon.escapeHtml(item.status) + '</span></td>' +
                    '<td class="text-muted">' + Monsoon.escapeHtml(item.author_id) + '</td>' +
                    '<td class="text-muted">' + date + '</td>' +
                    '<td><a href="/manage/content/' + item.id + '/edit" class="btn btn-sm btn-outline-secondary">Edit</a></td>' +
                '</tr>';
            }).join('');
        })
        .catch(() => {
            document.getElementById('content-list').innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load content.</td></tr>';
        });
}
</script>
</body>
</html>
HTML;
    }

    private static function renderContentEditor(?array $item = null): string
    {
        $isEdit = $item !== null;
        $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($item['slug'] ?? '', ENT_QUOTES, 'UTF-8');
        $status = $item['status'] ?? 'draft';
        $type = $item['type'] ?? 'page';
        $id = $item['id'] ?? '';
        $pageTitle = $isEdit ? 'Edit Content' : 'New Content';
        $draftSelected = $status === 'draft' ? ' selected' : '';
        $reviewSelected = $status === 'review' ? ' selected' : '';
        $publishedSelected = $status === 'published' ? ' selected' : '';
        $archivedSelected = $status === 'archived' ? ' selected' : '';
        $pageSelected = $type === 'page' ? ' selected' : '';
        $postSelected = $type === 'post' ? ' selected' : '';
        $bodyJson = $item['body'] ?? '[]';
        if ($bodyJson === '' || $bodyJson === null) {
            $bodyJson = '[]';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$pageTitle} - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; }
.sidebar { background: #1A1A1A; min-height: 100vh; color: #fff; }
.sidebar a { color: #ccc; text-decoration: none; display: block; padding: 0.5rem 1rem; border-radius: 0.25rem; }
.sidebar a:hover, .sidebar a.active { background: #333; color: #fff; }
.sidebar .brand { padding: 1rem; font-weight: 700; color: #fff; border-bottom: 1px solid #333; }
.content { padding: 2rem; }
.card { border-color: #E1E5EC; }
.btn-primary { background-color: #1034A6; border-color: #1034A6; }
.form-control:focus { border-color: #1034A6; box-shadow: 0 0 0 0.2rem rgba(16, 52, 166, 0.25); }
.toast-container { position: fixed; bottom: 1rem; right: 1rem; z-index: 9999; }
.toast { min-width: 280px; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
        <nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" class="active" aria-label="Content" aria-current="page">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">{$pageTitle}</h1>
<div class="d-flex align-items-center gap-3">
<span id="save-status"></span>
<a href="/manage/content" class="btn btn-outline-secondary" aria-label="Back to content listing">Back to Content</a>
</div>
</div>
<form id="content-form">
<input type="hidden" id="content-id" value="{$id}">
<div class="row">
<div class="col-lg-8">
<div class="card shadow-sm mb-4">
<div class="card-body">
<div class="mb-3">
<label for="title" class="form-label fw-semibold">Title</label>
<input type="text" class="form-control form-control-lg" id="title" name="title" value="{$title}" placeholder="Enter title" required>
</div>
<div class="mb-3">
<label for="slug" class="form-label fw-semibold">Slug</label>
<div class="input-group">
<span class="input-group-text">/</span>
<input type="text" class="form-control" id="slug" name="slug" value="{$slug}" placeholder="auto-generated-from-title">
</div>
</div>
<div class="mb-3">
<label class="form-label fw-semibold">Content</label>
<div id="block-editor-canvas"></div>
</div>
</div>
</div>
</div>
<div class="col-lg-4">
<div class="card shadow-sm mb-4">
<div class="card-header bg-white"><h2 class="h6 mb-0">Publish</h2></div>
<div class="card-body">
<div class="mb-3">
<label for="status" class="form-label fw-semibold">Status</label>
<select class="form-select" id="status" name="status">
<option value="draft"{$draftSelected}>Draft</option>
<option value="review"{$reviewSelected}>Review</option>
<option value="published"{$publishedSelected}>Published</option>
<option value="archived"{$archivedSelected}>Archived</option>
</select>
</div>
<div class="mb-3">
<label for="type" class="form-label fw-semibold">Type</label>
<select class="form-select" id="type" name="type">
<option value="page"{$pageSelected}>Page</option>
<option value="post"{$postSelected}>Post</option>
</select>
</div>
<div class="d-grid">
<button type="submit" class="btn btn-primary" id="save-btn" aria-label="Save content">Save</button>
</div>
</div>
</div>
</div>
</div>
</form>
</div>
</div>
<div class="toast-container" id="toast-container" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script src="/block-registry.js"></script>
<script src="/block-editor.js"></script>
<script src="/block-toolbar.js"></script>
<script>
const INITIAL_BLOCKS = {$bodyJson};
document.addEventListener('DOMContentLoaded', function() {
    BlockEditor.init('block-editor-canvas');
    BlockToolbar.init();

    if (Array.isArray(INITIAL_BLOCKS) && INITIAL_BLOCKS.length > 0) {
        BlockEditor.setBlocks(INITIAL_BLOCKS);
    } else {
        BlockEditor.addBlock('paragraph');
    }
});

document.getElementById('content-form').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!Monsoon.validateForm(this)) return;
    saveContent();
});

document.getElementById('title').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.dataset.manual) {
        slugField.value = this.value.toLowerCase()
            .replace(/[^a-z0-9\\s\\-_]/g, '')
            .replace(/[\\s\\-_]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.manual = 'true';
});

function saveContent() {
    const id = document.getElementById('content-id').value;
    const isEdit = id !== '';
    const btn = document.getElementById('save-btn');

    const data = {
        title: document.getElementById('title').value,
        slug: document.getElementById('slug').value,
        content: BlockEditor.getJSON(),
        body: BlockEditor.getJSON(),
        status: document.getElementById('status').value,
        type: document.getElementById('type').value,
    };

    Monsoon.setLoading(btn, true);

    const url = isEdit ? '/api/v1/content/' + id : '/api/v1/content';
    const method = isEdit ? 'PUT' : 'POST';

    Monsoon.api(url, { method, body: data })
        .then(json => {
            Monsoon.toast('Content saved successfully.', 'success');
            if (!isEdit && json.data && json.data.id) {
                window.location.href = '/manage/content/' + json.data.id + '/edit';
            }
        })
        .catch(() => {})
        .finally(() => Monsoon.setLoading(btn, false));
}
</script>
</body>
</html>
HTML;
    }

    private static function renderMediaPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Media - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; }
.sidebar { background: #1A1A1A; min-height: 100vh; color: #fff; }
.sidebar a { color: #ccc; text-decoration: none; display: block; padding: 0.5rem 1rem; border-radius: 0.25rem; }
.sidebar a:hover, .sidebar a.active { background: #333; color: #fff; }
.sidebar .brand { padding: 1rem; font-weight: 700; color: #fff; border-bottom: 1px solid #333; }
.content { padding: 2rem; }
.card { border-color: #E1E5EC; }
.upload-zone { border: 2px dashed #E1E5EC; border-radius: 0.5rem; padding: 2rem; text-align: center; cursor: pointer; transition: border-color 0.2s; }
.upload-zone:hover { border-color: #1034A6; }
.media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem; }
.media-card { position: relative; border: 1px solid #E1E5EC; border-radius: 0.5rem; overflow: hidden; background: #fff; }
.media-card .thumb { width: 100%; height: 140px; object-fit: cover; display: block; background: #F4F6FA; }
.media-card .thumb-placeholder { width: 100%; height: 140px; display: flex; align-items: center; justify-content: center; background: #F4F6FA; color: #555555; font-size: 2rem; }
.media-card .info { padding: 0.5rem; }
.media-card .info .name { font-size: 0.8rem; font-weight: 600; color: #1A1A1A; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.media-card .info .meta { font-size: 0.7rem; color: #555555; }
.media-card .delete-btn { position: absolute; top: 0.25rem; right: 0.25rem; background: #D33F3F; color: #fff; border: none; border-radius: 50%; width: 24px; height: 24px; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; }
.media-card:hover .delete-btn { opacity: 1; }
.btn-primary { background-color: #1034A6; border-color: #1034A6; }
.toast-container { position: fixed; bottom: 1rem; right: 1rem; z-index: 9999; }
.toast { min-width: 280px; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
        <nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" aria-label="Content">Content</a>
<a href="/manage/media" class="active" aria-label="Media" aria-current="page">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<h1 class="h3 mb-4">Media Library</h1>
<div class="card shadow-sm mb-4">
<div class="card-body">
<div class="upload-zone" id="upload-zone">
<p class="mb-1 fw-semibold">Drop files here or click to upload</p>
<p class="text-muted small mb-0">Alt text is required for all uploads</p>
<input type="file" id="file-input" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx" style="display:none;">
</div>
<div id="upload-progress" class="mt-3" style="display:none;">
<div class="mb-2">
<label for="alt-text" class="form-label fw-semibold">Alt Text</label>
<input type="text" class="form-control" id="alt-text" placeholder="Describe the file" maxlength="500">
</div>
<div class="d-flex align-items-center gap-2">
<div class="progress flex-grow-1" style="height: 6px;">
<div class="progress-bar" id="upload-bar" style="width: 0%"></div>
</div>
<button type="button" class="btn btn-primary btn-sm" id="upload-btn">Upload</button>
<button type="button" class="btn btn-outline-secondary btn-sm" id="upload-cancel">Cancel</button>
</div>
</div>
</div>
</div>
<div id="media-grid" class="media-grid mb-4"></div>
<div id="media-empty" class="card shadow-sm" style="display:none;">
<div class="card-body">
<p class="text-muted text-center py-4">No media uploaded yet.</p>
</div>
</div>
<div id="media-loading" class="text-center py-4 text-muted">Loading media...</div>
</div>
</div>
<div class="toast-container" id="toast-container" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadMedia();
    initUpload();
});

function initUpload() {
    const zone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('file-input');
    const progress = document.getElementById('upload-progress');
    const uploadBtn = document.getElementById('upload-btn');
    const cancelBtn = document.getElementById('upload-cancel');

    let pendingFiles = [];

    zone.addEventListener('click', () => fileInput.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor = '#1034A6'; });
    zone.addEventListener('dragleave', () => { zone.style.borderColor = '#E1E5EC'; });
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.style.borderColor = '#E1E5EC';
        if (e.dataTransfer.files.length > 0) {
            pendingFiles = Array.from(e.dataTransfer.files);
            progress.style.display = 'block';
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            pendingFiles = Array.from(fileInput.files);
            progress.style.display = 'block';
        }
    });

    cancelBtn.addEventListener('click', () => {
        pendingFiles = [];
        progress.style.display = 'none';
        fileInput.value = '';
        document.getElementById('alt-text').value = '';
    });

    uploadBtn.addEventListener('click', () => {
        const altText = document.getElementById('alt-text').value.trim();
        if (altText === '') {
            Monsoon.toast('Alt text is required for all uploads.', 'danger');
            return;
        }
        if (pendingFiles.length === 0) {
            Monsoon.toast('No files selected.', 'warning');
            return;
        }
        uploadFiles(pendingFiles, altText);
    });
}

function uploadFiles(files, altText) {
    const bar = document.getElementById('upload-bar');
    const uploadBtn = document.getElementById('upload-btn');
    let uploaded = 0;
    const total = files.length;

    uploadBtn.disabled = true;
    uploadBtn.textContent = 'Uploading...';

    function uploadNext() {
        if (uploaded >= total) {
            bar.style.width = '100%';
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload';
            document.getElementById('upload-progress').style.display = 'none';
            document.getElementById('file-input').value = '';
            document.getElementById('alt-text').value = '';
            loadMedia();
            return;
        }

        const file = files[uploaded];
        const formData = new FormData();
        formData.append('file', file);
        formData.append('alt_text', altText);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/api/v1/media', true);
        xhr.onload = function() {
            uploaded++;
            bar.style.width = Math.round((uploaded / total) * 100) + '%';
            if (xhr.status === 201) {
                Monsoon.toast(file.name + ' uploaded.', 'success');
            } else {
                let msg = 'Upload failed.';
                try {
                    const resp = JSON.parse(xhr.responseText);
                    msg = resp.error?.message || msg;
                } catch(e) {}
                Monsoon.toast(file.name + ': ' + msg, 'danger');
            }
            uploadNext();
        };
        xhr.onerror = function() {
            uploaded++;
            bar.style.width = Math.round((uploaded / total) * 100) + '%';
            Monsoon.toast(file.name + ': Network error.', 'danger');
            uploadNext();
        };
        xhr.send(formData);
    }

    uploadNext();
}

function loadMedia() {
    fetch('/api/v1/media')
        .then(r => r.json())
        .then(data => {
            const grid = document.getElementById('media-grid');
            const empty = document.getElementById('media-empty');
            const loading = document.getElementById('media-loading');
            loading.style.display = 'none';

            if (!data.data || data.data.length === 0) {
                grid.innerHTML = '';
                empty.style.display = 'block';
                return;
            }

            empty.style.display = 'none';
            grid.innerHTML = data.data.map(item => {
                const isImage = item.mime_type && item.mime_type.startsWith('image/');
                const isVideo = item.mime_type && item.mime_type.startsWith('video/');
                const isAudio = item.mime_type && item.mime_type.startsWith('audio/');
                const isPdf = item.mime_type === 'application/pdf';

                let thumb = '';
                if (isImage) {
                    thumb = '<img class="thumb" src="' + Monsoon.escapeHtml(item.file_path) + '" alt="' + Monsoon.escapeHtml(item.alt_text) + '">';
                } else if (isVideo) {
                    thumb = '<div class="thumb-placeholder"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg></div>';
                } else if (isAudio) {
                    thumb = '<div class="thumb-placeholder"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div>';
                } else if (isPdf) {
                    thumb = '<div class="thumb-placeholder"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>';
                } else {
                    thumb = '<div class="thumb-placeholder"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>';
                }

                const size = item.file_size > 1024 * 1024
                    ? (item.file_size / (1024 * 1024)).toFixed(1) + ' MB'
                    : (item.file_size / 1024).toFixed(1) + ' KB';

                const date = new Date(item.created_at).toLocaleDateString();

                return '<div class="media-card">' +
                    '<button class="delete-btn" onclick="deleteMedia(\\'' + item.id + '\\')" title="Delete" aria-label="Delete ' + Monsoon.escapeHtml(item.file_name) + '">&times;</button>' +
                    thumb +
                    '<div class="info">' +
                    '<div class="name" title="' + Monsoon.escapeHtml(item.file_name) + '">' + Monsoon.escapeHtml(item.file_name) + '</div>' +
                    '<div class="meta">' + size + ' &middot; ' + date + '</div>' +
                    '</div></div>';
            }).join('');
        })
        .catch(() => {
            document.getElementById('media-loading').innerHTML = 'Failed to load media.';
        });
}

function deleteMedia(id) {
    if (!Monsoon.confirm('Delete this file? This cannot be undone.')) return;

    fetch('/api/v1/media/' + id, { method: 'DELETE' })
        .then(r => {
            if (r.ok) {
                Monsoon.toast('File deleted.', 'success');
                loadMedia();
            } else {
                Monsoon.toast('Failed to delete file.', 'danger');
            }
        })
        .catch(() => Monsoon.toast('Network error.', 'danger'));
}
</script>
</body>
</html>
HTML;
    }

    private static function renderUsersPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; }
.sidebar { background: #1A1A1A; min-height: 100vh; color: #fff; }
.sidebar a { color: #ccc; text-decoration: none; display: block; padding: 0.5rem 1rem; border-radius: 0.25rem; }
.sidebar a:hover, .sidebar a.active { background: #333; color: #fff; }
.sidebar .brand { padding: 1rem; font-weight: 700; color: #fff; border-bottom: 1px solid #333; }
.content { padding: 2rem; }
.card { border-color: #E1E5EC; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" aria-label="Content">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" class="active" aria-label="Users" aria-current="page">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<h1 class="h3 mb-4">Users</h1>
<div class="card shadow-sm">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr><th>Email</th><th>Role</th><th>Status</th><th>Joined</th></tr>
</thead>
<tbody id="users-list">
<tr><td colspan="4" class="text-center text-muted py-4">Loading...</td></tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('/api/v1/users')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('users-list');
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No users found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map(u => {
                const date = new Date(u.created_at).toLocaleDateString();
                return '<tr><td>' + Monsoon.escapeHtml(u.email) + '</td><td>' + Monsoon.escapeHtml(u.role_id) + '</td><td>' + Monsoon.escapeHtml(u.status) + '</td><td class="text-muted">' + date + '</td></tr>';
            }).join('');
        })
        .catch(() => {
            document.getElementById('users-list').innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load users.</td></tr>';
        });
});
</script>
</body>
</html>
HTML;
    }

    private static function renderSettingsPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; }
.sidebar { background: #1A1A1A; min-height: 100vh; color: #fff; }
.sidebar a { color: #ccc; text-decoration: none; display: block; padding: 0.5rem 1rem; border-radius: 0.25rem; }
.sidebar a:hover, .sidebar a.active { background: #333; color: #fff; }
.sidebar .brand { padding: 1rem; font-weight: 700; color: #fff; border-bottom: 1px solid #333; }
.content { padding: 2rem; }
.card { border-color: #E1E5EC; }
.btn-primary { background-color: #1034A6; border-color: #1034A6; }
.form-control:focus { border-color: #1034A6; box-shadow: 0 0 0 0.2rem rgba(16, 52, 166, 0.25); }
.toast-container { position: fixed; bottom: 1rem; right: 1rem; z-index: 9999; }
.toast { min-width: 280px; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" aria-label="Content">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" class="active" aria-label="Settings" aria-current="page">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<h1 class="h3 mb-4">Settings</h1>
<div class="card shadow-sm">
<div class="card-body">
<div id="settings-loading" class="text-center text-muted py-4">Loading settings...</div>
<form id="settings-form" style="display:none;">
<div class="row">
<div class="col-lg-6">
<div class="mb-3">
<label for="site_name" class="form-label fw-semibold">Site Name</label>
<input type="text" class="form-control" id="site_name" name="site_name" placeholder="My Site">
</div>
<div class="mb-3">
<label for="site_description" class="form-label fw-semibold">Site Description</label>
<textarea class="form-control" id="site_description" name="site_description" rows="3" placeholder="A brief description of your site"></textarea>
</div>
<div class="mb-3">
<label for="site_url" class="form-label fw-semibold">Site URL</label>
<input type="url" class="form-control" id="site_url" name="site_url" placeholder="https://example.com">
</div>
<div class="mb-3">
<label for="locale" class="form-label fw-semibold">Locale</label>
<select class="form-select" id="locale" name="locale">
<option value="en">English</option>
<option value="es">Spanish</option>
<option value="fr">French</option>
<option value="de">German</option>
<option value="pt">Portuguese</option>
</select>
</div>
</div>
</div>
<div class="d-grid mt-3" style="max-width: 200px;">
<button type="submit" class="btn btn-primary" id="save-btn" aria-label="Save settings">Save Settings</button>
</div>
</form>
</div>
</div>
</div>
</div>
<div class="toast-container" id="toast-container" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadSettings();
});

document.getElementById('settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings();
});

function loadSettings() {
    fetch('/api/v1/settings')
        .then(r => r.json())
        .then(data => {
            const settings = {};
            if (data.data) {
                data.data.forEach(function(s) {
                    settings[s.setting_key] = s;
                });
            }
            document.getElementById('site_name').value = settings.site_name?.setting_value || '';
            document.getElementById('site_description').value = settings.site_description?.setting_value || '';
            document.getElementById('site_url').value = settings.site_url?.setting_value || '';
            document.getElementById('locale').value = settings.locale?.setting_value || 'en';
            document.getElementById('settings-loading').style.display = 'none';
            document.getElementById('settings-form').style.display = 'block';
        })
        .catch(function() {
            document.getElementById('settings-loading').innerHTML = 'Failed to load settings.';
        });
}

function saveSettings() {
    var btn = document.getElementById('save-btn');
    Monsoon.setLoading(btn, true);

    var fields = [
        { key: 'site_name', value: document.getElementById('site_name').value },
        { key: 'site_description', value: document.getElementById('site_description').value },
        { key: 'site_url', value: document.getElementById('site_url').value },
        { key: 'locale', value: document.getElementById('locale').value },
    ];

    var promises = fields.map(function(f) {
        return fetch('/api/v1/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ setting_key: f.key, setting_value: f.value }),
        }).then(function(r) {
            if (r.status === 201) return r.json();
            return r.json().then(function(j) { throw new Error(j.error?.message || 'Save failed'); });
        });
    });

    Promise.all(promises)
        .then(function() {
            Monsoon.toast('Settings saved successfully.', 'success');
        })
        .catch(function(err) {
            Monsoon.toast(err.message || 'Failed to save settings.', 'danger');
        })
        .finally(function() {
            Monsoon.setLoading(btn, false, 'Save Settings');
        });
}
</script>
</body>
</html>
HTML;
    }

    private static function renderRolesPage(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Roles - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #F4F6FA; }
.sidebar { background: #1A1A1A; min-height: 100vh; color: #fff; }
.sidebar a { color: #ccc; text-decoration: none; display: block; padding: 0.5rem 1rem; border-radius: 0.25rem; }
.sidebar a:hover, .sidebar a.active { background: #333; color: #fff; }
.sidebar .brand { padding: 1rem; font-weight: 700; color: #fff; border-bottom: 1px solid #333; }
.content { padding: 2rem; }
.card { border-color: #E1E5EC; }
.btn-primary { background-color: #1034A6; border-color: #1034A6; }
.form-control:focus, .form-select:focus { border-color: #1034A6; box-shadow: 0 0 0 0.2rem rgba(16, 52, 166, 0.25); }
.toast-container { position: fixed; bottom: 1rem; right: 1rem; z-index: 9999; }
.toast { min-width: 280px; }
.cap-badge { font-size: 0.7rem; padding: 0.15rem 0.4rem; margin: 0.1rem; display: inline-block; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" aria-label="Content">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" class="active" aria-label="Roles" aria-current="page">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">Roles</h1>
<button class="btn btn-primary" id="new-role-btn" style="background-color:#1034A6;border-color:#1034A6;" aria-label="Create new role">New Role</button>
</div>
<div class="card shadow-sm">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr><th>Name</th><th>Capabilities</th><th>Created</th><th></th></tr>
</thead>
<tbody id="roles-list">
<tr><td colspan="4" class="text-center text-muted py-4">Loading...</td></tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<div class="modal fade" id="role-modal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title h5" id="role-modal-title">Edit Role</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="mb-3">
<label for="role-name" class="form-label fw-semibold">Role Name</label>
<input type="text" class="form-control" id="role-name" placeholder="e.g. editor">
</div>
<div class="mb-3">
<label class="form-label fw-semibold">Capabilities</label>
<div id="capabilities-list" class="p-3" style="background:#F4F6FA;border-radius:0.25rem;"></div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-primary" id="save-role-btn" style="background-color:#1034A6;border-color:#1034A6;" aria-label="Save role">Save</button>
</div>
</div>
</div>
</div>

<div class="toast-container" id="toast-container" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
var ALL_CAPABILITIES = [
    'content.read', 'content.write', 'content.delete',
    'media.read', 'media.write', 'media.delete',
    'user.read', 'user.write', 'user.delete',
    'settings.read', 'settings.write',
    'mail.send', 'files.read', 'files.write',
    'database.dump', 'auth.read', 'auth.write'
];

var CAP_DESCRIPTIONS = {
    'content.read': 'Read content items',
    'content.write': 'Create and update content items',
    'content.delete': 'Delete content items',
    'media.read': 'Read media files',
    'media.write': 'Upload and update media files',
    'media.delete': 'Delete media files',
    'user.read': 'Read user data',
    'user.write': 'Create and update users',
    'user.delete': 'Delete users',
    'settings.read': 'Read system settings',
    'settings.write': 'Update system settings',
    'mail.send': 'Send email',
    'files.read': 'Read files from the filesystem',
    'files.write': 'Write files to the filesystem',
    'database.dump': 'Export database dump',
    'auth.read': 'Read authentication configuration',
    'auth.write': 'Modify authentication configuration'
};

var editingRoleId = null;
var modal = null;

document.addEventListener('DOMContentLoaded', function() {
    loadRoles();
    modal = new bootstrap.Modal(document.getElementById('role-modal'));
    document.getElementById('new-role-btn').addEventListener('click', function() {
        editingRoleId = null;
        document.getElementById('role-modal-title').textContent = 'New Role';
        document.getElementById('role-name').value = '';
        renderCapabilities([]);
        modal.show();
    });
    document.getElementById('save-role-btn').addEventListener('click', saveRole);
});

function loadRoles() {
    fetch('/api/v1/roles')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tbody = document.getElementById('roles-list');
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No roles yet. Create your first role.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map(function(role) {
                var caps = (role.capabilities || []).map(function(c) {
                    return '<span class="cap-badge bg-primary text-white">' + Monsoon.escapeHtml(c) + '</span>';
                }).join(' ');
                var date = new Date(role.created_at).toLocaleDateString();
                return '<tr>' +
                    '<td class="fw-semibold">' + Monsoon.escapeHtml(role.name) + '</td>' +
                    '<td>' + caps + '</td>' +
                    '<td class="text-muted">' + date + '</td>' +
                    '<td><button class="btn btn-sm btn-outline-secondary" onclick="editRole(\\'' + role.id + '\\', \\'' + Monsoon.escapeHtml(role.name) + '\\', ' + JSON.stringify(role.capabilities) + ')">Edit</button></td>' +
                '</tr>';
            }).join('');
        })
        .catch(function() {
            document.getElementById('roles-list').innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load roles.</td></tr>';
        });
}

function editRole(id, name, capabilities) {
    editingRoleId = id;
    document.getElementById('role-modal-title').textContent = 'Edit Role';
    document.getElementById('role-name').value = name;
    renderCapabilities(capabilities);
    modal.show();
}

function renderCapabilities(selected) {
    var container = document.getElementById('capabilities-list');
    container.innerHTML = ALL_CAPABILITIES.map(function(cap) {
        var checked = selected.indexOf(cap) !== -1 ? ' checked' : '';
        var desc = CAP_DESCRIPTIONS[cap] || cap;
        return '<div class="form-check">' +
            '<input class="form-check-input cap-check" type="checkbox" value="' + cap + '"' + checked + ' id="cap-' + cap.replace(/\\./g, '-') + '">' +
            '<label class="form-check-label" for="cap-' + cap.replace(/\\./g, '-') + '">' + Monsoon.escapeHtml(cap) + ' <span class="text-muted small">(' + Monsoon.escapeHtml(desc) + ')</span></label>' +
        '</div>';
    }).join('');
}

function saveRole() {
    var name = document.getElementById('role-name').value.trim();
    if (name === '') {
        Monsoon.toast('Role name is required.', 'danger');
        return;
    }

    var capabilities = [];
    document.querySelectorAll('.cap-check:checked').forEach(function(cb) {
        capabilities.push(cb.value);
    });

    var btn = document.getElementById('save-role-btn');
    Monsoon.setLoading(btn, true);

    var isEdit = editingRoleId !== null;
    var url = isEdit ? '/api/v1/roles/' + editingRoleId : '/api/v1/roles';
    var method = isEdit ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: name, capabilities: capabilities }),
    })
    .then(function(r) {
        return r.json().then(function(json) { return { ok: r.ok, json: json }; });
    })
    .then(function(result) {
        if (result.ok) {
            Monsoon.toast(isEdit ? 'Role updated.' : 'Role created.', 'success');
            modal.hide();
            loadRoles();
        } else {
            Monsoon.toast(result.json.error?.message || 'Failed to save role.', 'danger');
        }
    })
    .catch(function() {
        Monsoon.toast('Network error.', 'danger');
    })
    .finally(function() {
        Monsoon.setLoading(btn, false);
    });
}
</script>
</body>
</html>
HTML;
    }
}
