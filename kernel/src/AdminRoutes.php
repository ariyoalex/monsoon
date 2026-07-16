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
<button type="submit" class="btn btn-primary w-100">Log in</button>
</form>
</div>
</div>
</div>
</div>
</div>
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
<nav class="mt-3 px-2">
<a href="/manage">Dashboard</a>
<a href="/manage/content">Content</a>
<a href="/manage/media">Media</a>
<a href="/manage/users">Users</a>
<a href="/manage/settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger">Log out</a>
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
<nav class="mt-3 px-2">
<a href="/manage">Dashboard</a>
<a href="/manage/content" class="active">Content</a>
<a href="/manage/media">Media</a>
<a href="/manage/users">Users</a>
<a href="/manage/settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">Content</h1>
<a href="/manage/content/new" class="btn btn-primary" style="background-color:#1034A6;border-color:#1034A6;">New Page</a>
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
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:11"></div>
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
                    '<td><a href="/manage/content/' + item.id + '/edit" class="text-decoration-none" style="color:#1034A6;">' + escapeHtml(item.title || 'Untitled') + '</a></td>' +
                    '<td>' + escapeHtml(item.type) + '</td>' +
                    '<td><span class="status-badge bg-' + statusClass + ' text-white">' + escapeHtml(item.status) + '</span></td>' +
                    '<td class="text-muted">' + escapeHtml(item.author_id) + '</td>' +
                    '<td class="text-muted">' + date + '</td>' +
                    '<td><a href="/manage/content/' + item.id + '/edit" class="btn btn-sm btn-outline-secondary">Edit</a></td>' +
                '</tr>';
            }).join('');
        })
        .catch(() => {
            document.getElementById('content-list').innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load content.</td></tr>';
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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
        $body = htmlspecialchars($item['body'] ?? '', ENT_QUOTES, 'UTF-8');
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
<nav class="mt-3 px-2">
<a href="/manage">Dashboard</a>
<a href="/manage/content" class="active">Content</a>
<a href="/manage/media">Media</a>
<a href="/manage/users">Users</a>
<a href="/manage/settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">{$pageTitle}</h1>
<a href="/manage/content" class="btn btn-outline-secondary">Back to Content</a>
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
<label for="body" class="form-label fw-semibold">Body</label>
<textarea class="form-control" id="body" name="body" rows="15" placeholder="Write your content here...">{$body}</textarea>
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
<button type="submit" class="btn btn-primary" id="save-btn">Save</button>
</div>
</div>
</div>
</div>
</div>
</form>
</div>
</div>
<div class="toast-container" id="toast-container"></div>
<script>
document.getElementById('content-form').addEventListener('submit', function(e) {
    e.preventDefault();
    saveContent();
});

// Auto-generate slug from title
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
        body: document.getElementById('body').value,
        status: document.getElementById('status').value,
        type: document.getElementById('type').value,
    };

    btn.disabled = true;
    btn.textContent = 'Saving...';

    const url = isEdit ? '/api/v1/content/' + id : '/api/v1/content';
    const method = isEdit ? 'PUT' : 'POST';

    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
    })
    .then(r => r.json().then(json => ({ ok: r.ok, json })))
    .then(({ ok, json }) => {
        if (ok) {
            showToast('Content saved successfully.', 'success');
            if (!isEdit && json.data && json.data.id) {
                window.location.href = '/manage/content/' + json.data.id + '/edit';
            }
        } else {
            showToast(json.error?.message || 'Failed to save.', 'danger');
        }
    })
    .catch(() => showToast('Network error.', 'danger'))
    .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Save';
    });
}

function showToast(message, type) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast align-items-center text-bg-' + type + ' border-0 show';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
.upload-zone { border: 2px dashed #E1E5EC; border-radius: 0.5rem; padding: 3rem; text-align: center; cursor: pointer; transition: border-color 0.2s; }
.upload-zone:hover { border-color: #1034A6; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2">
<a href="/manage">Dashboard</a>
<a href="/manage/content">Content</a>
<a href="/manage/media" class="active">Media</a>
<a href="/manage/users">Users</a>
<a href="/manage/settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger">Log out</a>
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
</div>
</div>
<div class="card shadow-sm">
<div class="card-body">
<p class="text-muted text-center py-4">Media library will be populated once uploads are implemented.</p>
</div>
</div>
</div>
</div>
<script>
document.getElementById('upload-zone').addEventListener('click', () => document.getElementById('file-input').click());
document.getElementById('upload-zone').addEventListener('dragover', e => { e.preventDefault(); e.currentTarget.style.borderColor = '#1034A6'; });
document.getElementById('upload-zone').addEventListener('dragleave', e => { e.currentTarget.style.borderColor = '#E1E5EC'; });
document.getElementById('upload-zone').addEventListener('drop', e => { e.preventDefault(); e.currentTarget.style.borderColor = '#E1E5EC'; });
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
<nav class="mt-3 px-2">
<a href="/manage">Dashboard</a>
<a href="/manage/content">Content</a>
<a href="/manage/media">Media</a>
<a href="/manage/users" class="active">Users</a>
<a href="/manage/settings">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger">Log out</a>
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
                return '<tr><td>' + escapeHtml(u.email) + '</td><td>' + escapeHtml(u.role_id) + '</td><td>' + escapeHtml(u.status) + '</td><td class="text-muted">' + date + '</td></tr>';
            }).join('');
        })
        .catch(() => {
            document.getElementById('users-list').innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load users.</td></tr>';
        });
});
function escapeHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
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
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2">
<a href="/manage">Dashboard</a>
<a href="/manage/content">Content</a>
<a href="/manage/media">Media</a>
<a href="/manage/users">Users</a>
<a href="/manage/settings" class="active">Settings</a>
<a href="/manage/logout" class="mt-4 text-danger">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<h1 class="h3 mb-4">Settings</h1>
<div class="card shadow-sm">
<div class="card-body">
<p class="text-muted text-center py-4">Site settings will be available here once the settings module is implemented.</p>
</div>
</div>
</div>
</div>
</body>
</html>
HTML;
    }
}
