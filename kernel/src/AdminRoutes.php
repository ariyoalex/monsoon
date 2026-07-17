<?php

declare(strict_types=1);

namespace Monsoon\Kernel;

final class AdminRoutes
{
    private static ?AssetService $assetService = null;

    public static function register(Router $router, array $config, ?AssetService $assetService = null): void
    {
        self::$assetService = $assetService;
        $router->addRoute('GET', '/', function () {
            return Response::html(self::renderLandingPage());
        });

        $router->addRoute('GET', '/manage/install', function () use ($config) {
            $db = Database::getInstance()->getConnection();
            $installer = new Installer($db, $config);

            if ($installer->isInstalled()) {
                return Response::redirect('/manage/login');
            }

            $result = $installer->run();
            return Response::html(self::renderInstallPage($result));
        });

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

                header('Location: /manage/dashboard');
                return [
                    'status' => 302,
                    'headers' => ['Location' => '/manage/dashboard'],
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
            return Response::redirect('/manage/dashboard');
        });

        $router->addRoute('GET', '/manage/dashboard', function () {
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

        $router->addRoute('GET', '/manage/content/{id}/preview', function (array $params) {
            $db = Database::getInstance()->getConnection();
            $service = new ContentService($db);
            $item = $service->findById($params['id']);
            if ($item === null) {
                return Response::redirect('/manage/content');
            }
            return Response::html(self::renderPreviewPage($item));
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

        $router->addRoute('GET', '/manage/customize', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderCustomizerPage());
        });

        $router->addRoute('GET', '/manage/customize/preview', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderCustomizerPreview());
        });

        $router->addRoute('GET', '/manage/menus', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderMenusPage());
        });

        $router->addRoute('GET', '/manage/widgets', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderWidgetsPage());
        });

        $router->addRoute('GET', '/manage/themes', function () {
            $auth = Auth::getInstance();
            if (!$auth->isAuthenticated()) {
                return Response::redirect('/manage/login');
            }
            return Response::html(self::renderThemesPage());
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
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
body { display: flex; align-items: center; min-height: 100vh; }
.login-card { max-width: 420px; width: 100%; }
.card-header { background: #1034A6; color: #fff; }
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
HTML;
    }

    private static function renderDashboard(?array $user): string
    {
        $userEmail = htmlspecialchars($user['email'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');


        $sidebar = self::renderSidebar('dashboard');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
</head>
<body>
<div class="d-flex">
{$sidebar}
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

        $sidebar = self::renderSidebar('content');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Content - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
.table th { font-weight: 600; color: #555555; }
.status-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 1rem; }
</style>
</head>
<body>
<div class="d-flex">
{$sidebar}
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


        $sidebar = self::renderSidebar('content');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$pageTitle} - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
</head>
<body>
<div class="d-flex">
{$sidebar}
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
<script src="/block-api.js"></script>
<script src="/block-manifest.js"></script>
<script src="/block-editor.js"></script>
<script src="/block-toolbar.js"></script>
<script src="/block-revisions.js"></script>
<script src="/block-preview.js"></script>
<script>
const INITIAL_BLOCKS = {$bodyJson};
document.addEventListener('DOMContentLoaded', function() {
    BlockEditor.init('block-editor-canvas');
    BlockToolbar.init();
    BlockRevisions.init('{$id}');
    BlockPreview.init();

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

        $sidebar = self::renderSidebar('media');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Media - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
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
</style>
</head>
<body>
<div class="d-flex">
{$sidebar}
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

        $sidebar = self::renderSidebar('users');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Users - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
</head>
<body>
<div class="d-flex">
{$sidebar}
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

        $sidebar = self::renderSidebar('settings');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
</head>
<body>
<div class="d-flex">
{$sidebar}
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

        $sidebar = self::renderSidebar('roles');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Roles - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
.cap-badge { font-size: 0.7rem; padding: 0.15rem 0.4rem; margin: 0.1rem; display: inline-block; }
</style>
</head>
<body>
<div class="d-flex">
{$sidebar}
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

    private static function renderInstallPage(array $result): string
    {
        $migrations = $result['migrations'] ?? [];
        $roles = $result['roles'] ?? [];
        $admin = $result['admin'] ?? null;

        $migrationList = '';
        $applied = $migrations['applied'] ?? [];
        foreach ($applied as $m) {
            $name = htmlspecialchars((string)$m, ENT_QUOTES, 'UTF-8');
            $migrationList .= "<li>{$name}</li>";
        }
        if (empty($applied)) {
            $migrationList = '<li>Database already up to date</li>';
        }

        $roleList = '';
        foreach ($roles as $r) {
            $name = htmlspecialchars($r['name'] ?? '', ENT_QUOTES, 'UTF-8');
            $roleList .= "<li>{$name}</li>";
        }
        if (empty($roles)) {
            $roleList = '<li>Roles already exist</li>';
        }

        $adminSection = '';
        if ($admin !== null) {
            $adminEmail = htmlspecialchars($admin['email'] ?? 'admin@monsoon.local', ENT_QUOTES, 'UTF-8');
            $adminSection = <<<HTML
<div class="mb-4">
<h3 class="h5">Admin User Created</h3>
<ul class="list-unstyled">
<li><strong>Email:</strong> {$adminEmail}</li>
<li><strong>Password:</strong> admin123</li>
</ul>
</div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation Complete - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
body { background: #F4F6FA; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
.install-card { max-width: 560px; width: 100%; }
.card { border-color: #E1E5EC; }
.card-header { background: #1034A6; color: #fff; }
.btn-primary { background-color: #1034A6; border-color: #1034A6; }
</style>
</head>
<body>
<div class="container">
<div class="row justify-content-center">
<div class="col install-card">
<div class="card shadow-sm">
<div class="card-header">
<h1 class="h4 mb-0">Monsoon CMS</h1>
</div>
<div class="card-body">
<h2 class="h3 mb-4">Installation Complete</h2>
<div class="mb-4">
<h3 class="h5">Migrations Run</h3>
<ul class="list-unstyled">{$migrationList}</ul>
</div>
<div class="mb-4">
<h3 class="h5">Roles Created</h3>
<ul class="list-unstyled">{$roleList}</ul>
</div>
{$adminSection}
<a href="/manage/login" class="btn btn-primary w-100" aria-label="Go to Login">Go to Login</a>
</div>
</div>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
HTML;
    }

    private static function renderPreviewPage(array $item): string
    {
        $title = htmlspecialchars($item['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8');
        $status = $item['status'] ?? 'draft';
        $statusClass = $status === 'published' ? 'success' : ($status === 'draft' ? 'secondary' : 'warning');
        $statusLabel = htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8');
        $id = htmlspecialchars($item['id'] ?? '', ENT_QUOTES, 'UTF-8');
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
<title>{$title} - Preview - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
body { background: #fff; font-family: 'Graphik', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
.preview-header { background: #F4F6FA; border-bottom: 1px solid #E1E5EC; padding: 1rem 2rem; }
.preview-header h1 { font-family: 'Means', Georgia, serif; margin: 0; }
.preview-container { max-width: 800px; margin: 2rem auto; padding: 0 2rem; }
.preview-block { margin-bottom: 1.5rem; }
.preview-block h1, .preview-block h2, .preview-block h3, .preview-block h4, .preview-block h5, .preview-block h6 { font-family: 'Means', Georgia, serif; }
.preview-block p { font-size: 1.1rem; line-height: 1.7; }
.preview-block img { max-width: 100%; height: auto; border-radius: 0.5rem; }
.preview-block blockquote { border-left: 4px solid #1034A6; padding-left: 1rem; margin-left: 0; color: #555555; font-style: italic; }
.preview-block blockquote footer { font-style: normal; font-size: 0.9rem; color: #777; margin-top: 0.5rem; }
.preview-block hr { border: none; border-top: 1px solid #E1E5EC; margin: 2rem 0; }
.preview-block figure { margin: 0; }
.preview-block figcaption { font-size: 0.9rem; color: #555555; margin-top: 0.5rem; text-align: center; }
.preview-block .btn { display: inline-block; padding: 0.5rem 1.5rem; border-radius: 0.25rem; text-decoration: none; font-weight: 600; }
.preview-block .btn-primary { background: #1034A6; color: #fff; }
.preview-block .btn-secondary { background: #6c757d; color: #fff; }
.status-badge { font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 1rem; }
.back-link { color: #1034A6; text-decoration: none; }
.back-link:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="preview-header d-flex justify-content-between align-items-center">
<a href="/manage/content/{$id}/edit" class="back-link" aria-label="Back to Editor">&larr; Back to Editor</a>
<span class="status-badge bg-{$statusClass} text-white">{$statusLabel}</span>
</div>
<div class="preview-container">
<h1>{$title}</h1>
<div id="preview-content"></div>
</div>
<script>
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

const blocks = {$bodyJson};
const container = document.getElementById('preview-content');
blocks.forEach(block => {
    const div = document.createElement('div');
    div.className = 'preview-block block-type-' + block.type;
    switch(block.type) {
        case 'heading':
            const level = block.data.level || 2;
            div.innerHTML = '<h' + level + '>' + escapeHtml(block.data.text || '') + '</h' + level + '>';
            break;
        case 'paragraph':
            div.innerHTML = '<p>' + (block.data.text || '') + '</p>';
            break;
        case 'image':
            div.innerHTML = '<figure><img src="' + escapeHtml(block.data.src || '') + '" alt="' + escapeHtml(block.data.alt || '') + '">' + (block.data.caption ? '<figcaption>' + escapeHtml(block.data.caption) + '</figcaption>' : '') + '</figure>';
            break;
        case 'list':
            const tag = block.data.ordered ? 'ol' : 'ul';
            div.innerHTML = '<' + tag + '>' + (block.data.items || []).map(item => '<li>' + escapeHtml(item) + '</li>').join('') + '</' + tag + '>';
            break;
        case 'quote':
            div.innerHTML = '<blockquote><p>' + escapeHtml(block.data.text || '') + '</p>' + (block.data.attribution ? '<footer>' + escapeHtml(block.data.attribution) + '</footer>' : '') + '</blockquote>';
            break;
        case 'separator':
            div.innerHTML = '<hr>';
            break;
        case 'button':
            div.innerHTML = '<a href="' + escapeHtml(block.data.url || '#') + '" class="btn btn-' + escapeHtml(block.data.style || 'primary') + '">' + escapeHtml(block.data.text || '') + '</a>';
            break;
        default:
            div.innerHTML = '<p>[Unknown block: ' + block.type + ']</p>';
    }
    container.appendChild(div);
});
</script>
</body>
</html>
HTML;
    }

    private static function getCssBundle(): string
    {
        if (self::$assetService === null) {
            return '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">';
        }
        return self::$assetService->getStyleHtml();
    }

    private static function getJsBundle(bool $inFooter = true): string
    {
        if (self::$assetService === null) {
            return '';
        }
        return self::$assetService->getScriptHtml($inFooter);
    }

    private static function renderLandingPage(): string
    {
        $logoSvg = file_get_contents(__DIR__ . '/../../public/images/logos/isolated-layout.svg');
        $cssBundle = self::getCssBundle();
        $jsBundle = self::getJsBundle(false);
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Monsoon CMS — Open Source Content Management</title>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
{$cssBundle}
</head>
<body class="landing-page">
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark landing-nav">
<div class="container">
<a class="navbar-brand fw-bold" href="/"><svg class="landing-logo" viewBox="0 0 300 300">{$logoSvg}</svg><span class="ms-2">Monsoon</span></a>
<div class="navbar-nav ms-auto">
<a class="nav-link" href="#features">Features</a>
<a class="nav-link" href="#modules">Modules</a>
<a class="nav-link" href="#performance">Performance</a>
<a href="/manage/login" class="btn btn-outline-light btn-sm ms-3">Log In</a>
</div>
</div>
</nav>

<!-- Hero -->
<section class="hero-section">
<div class="container text-center">
<h1 class="hero-title">The CMS that gets out of your way.</h1>
<p class="hero-subtitle">Monsoon is an open source, modular CMS built for developers and content creators who want speed, flexibility, and full control — without the bloat.</p>
<div class="hero-cta">
<a href="/manage/install" class="btn btn-primary btn-lg me-3">Get Started</a>
<a href="#features" class="btn btn-outline-light btn-lg">Learn More</a>
</div>
</div>
</section>

<!-- Features -->
<section id="features" class="py-5">
<div class="container">
<h2 class="section-title text-center mb-5">Built Different</h2>
<div class="row g-4">
<div class="col-md-4">
<div class="feature-card">
<div class="feature-icon">&#9889;</div>
<h3>Blazing Fast</h3>
<p>PHP 8.4, MySQLi with prepared statements, minimal overhead. Loads under 2 seconds on shared hosting.</p>
</div>
</div>
<div class="col-md-4">
<div class="feature-card">
<div class="feature-icon">&#129513;</div>
<h3>Fully Modular</h3>
<p>Every feature is a module. Install only what you need. Modules register routes, permissions, and blocks through a simple manifest.</p>
</div>
</div>
<div class="col-md-4">
<div class="feature-card">
<div class="feature-icon">&#128274;</div>
<h3>Permission-Gated</h3>
<p>18 granular capabilities out of the box. Every action checks permissions — API, admin, and modules alike.</p>
</div>
</div>
<div class="col-md-4">
<div class="feature-card">
<div class="feature-icon">&#9999;&#65039;</div>
<h3>Block Editor</h3>
<p>Drag-to-reorder blocks, slash commands, auto-save, undo/redo. Build pages visually without writing code.</p>
</div>
</div>
<div class="col-md-4">
<div class="feature-card">
<div class="feature-icon">&#127912;</div>
<h3>Theme Engine</h3>
<p>Template hierarchy, asset enqueue system, customizer. Themes can override any template without touching core.</p>
</div>
</div>
<div class="col-md-4">
<div class="feature-card">
<div class="feature-icon">&#128268;</div>
<h3>Block Editor</h3>
<p>Drag-to-reorder blocks, slash commands, auto-save, undo/redo. Build pages visually without writing code.</p>
</div>
</div>
</div>
</div>
</section>

<!-- Performance -->
<section id="performance" class="py-5 bg-light">
<div class="container">
<h2 class="section-title text-center mb-5">Performance by Default</h2>
<div class="row g-4 text-center">
<div class="col-md-3">
<div class="stat-number">0</div>
<div class="stat-label">jQuery Dependencies</div>
</div>
<div class="col-md-3">
<div class="stat-number">100%</div>
<div class="stat-label">Vanilla JavaScript</div>
</div>
<div class="col-md-3">
<div class="stat-number">100%</div>
<div class="stat-label">Vanilla JavaScript</div>
</div>
<div class="col-md-3">
<div class="stat-number">100%</div>
<div class="stat-label">Vanilla JavaScript</div>
</div>
</div>
</div>
</section>

<!-- Modules -->
<section id="modules" class="py-5">
<div class="container">
<h2 class="section-title text-center mb-5">Module Architecture</h2>
<div class="row align-items-center">
<div class="col-lg-6">
<pre class="module-code"><code>{
  "name": "monsoon/comments",
  "version": "1.0.0",
  "permissions": [
    "comments.read",
    "comments.moderate"
  ],
  "routes": {
    "/api/v1/comments": "CommentsApiController"
  },
  "blocks": {
    "comment-form": "CommentFormBlock"
  }
}</code></pre>
</div>
<div class="col-lg-6">
<h3>One manifest. Full control.</h3>
<p class="lead">Each module declares its permissions, routes, and blocks in a simple JSON manifest. Monsoon handles the rest — registration, gating, and lifecycle.</</div>
</div>
</section>

<!-- CTA -->
<section class="py-5 bg-primary text-white">
<div class="container text-center">
<h2>Ready to build?</h2>
<p class="lead mb-4">Monsoon is free and open source. Start building your next project today.</</p>
<a href="/manage/install" class="btn btn-light btn-lg">Install Monsoon</a>
</div>
</section>

<!-- Footer -->
<footer class="py-4 bg-dark text-white">
<div class="container text-center">
<p class="mb-0">&copy; 2026 Monsoon CMS. Open source under the GPL v3.</p>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
{$jsBundle}
</body>
</html>
HTML;
    }

    /**
     * Render the canonical admin sidebar navigation.
    "comments.read",
    "comments.moderate"
  ],
  "routes": {
    "/api/v1/comments": "CommentsApiController"
  },
  "blocks": {
    "comment-form": "CommentFormBlock"
  }
}</code></pre>
</div>
<div class="col-lg-6">
<h3>One manifest. Full control.</h3>
<p class="lead">Each module declares its permissions, routes, and blocks in a simple JSON manifest. Monsoon handles the rest — registration, gating, and lifecycle.</p>
<ul class="module-features">
<li>Self-contained with own database migrations</li>
<li>Registers permissions automatically</li>
<li>Provides custom blocks for the editor</li>
<li>Can hook into any system event</li>
</ul>
</div>
</div>
</div>
</section>

<!-- CTA -->
<section class="py-5 bg-primary text-white">
<div class="container text-center">
<h2>Ready to build?</h2>
<p class="lead mb-4">Monsoon is free and open source. Start building your next project today.</p>
<a href="/manage/install" class="btn btn-light btn-lg">Install Monsoon</a>
</div>
</section>

<!-- Footer -->
<footer class="py-4 bg-dark text-white">
<div class="container text-center">
<p class="mb-0">&copy; 2026 Monsoon CMS. Open source under the GPL v3.</p>
</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
{$jsBundle}
</body>
</html>
HTML;
    }

    /**
     * Render the canonical admin sidebar navigation.
     * Every admin page must use this so the nav stays complete and consistent.
     * @param string $active One of: dashboard, content, media, users, roles, settings, menus, widgets, customize, themes, seo, forms, security, backup
     */
    private static function renderSidebar(string $active): string
    {
        $items = [
            'dashboard' => 'Dashboard',
            'content'  => 'Content',
            'media'    => 'Media',
            'users'    => 'Users',
            'roles'    => 'Roles',
            'settings' => 'Settings',
            'menus'    => 'Menus',
            'widgets'  => 'Widgets',
            'customize'=> 'Customize',
            'themes'   => 'Themes',
            'seo'      => 'SEO',
            'forms'    => 'Forms',
            'security' => 'Security',
            'backup'   => 'Backup',
        ];

        $links = '';
        foreach ($items as $key => $label) {
            $isActive = $key === $active;
            $attrs = $isActive
                ? ' class="active" aria-label="' . $label . '" aria-current="page"'
                : ' aria-label="' . $label . '"';
            $links .= '<a href="/manage/' . $key . '"' . $attrs . '>' . $label . '</a>' . "\n";
        }

        $logoSvg = file_get_contents(__DIR__ . '/../../public/images/logos/default-monochrome-white.svg');

        return <<<HTML
<div class="sidebar" style="width: 250px;">
<div class="brand"><a href="/manage/dashboard" aria-label="Monsoon CMS Home">{$logoSvg}</a></div>
<nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
{$links}<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
HTML;
    }

    private static function renderCustomizerPage(): string
    {

        $sidebar = self::renderSidebar('customize');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customize - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
.customizer-layout { display: flex; height: calc(100vh - 56px); }
.customizer-controls { width: 350px; overflow-y: auto; border-right: 1px solid #dee2e6; background: #fff; }
.customizer-controls .accordion-body { padding: 1rem; }
.customizer-preview { flex: 1; background: #f0f0f0; }
.customizer-preview iframe { width: 100%; height: 100%; border: none; }
.color-picker-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.color-picker-row label { flex: 1; font-size: 0.9rem; }
.color-picker-row input[type="color"] { width: 40px; height: 32px; border: 1px solid #dee2e6; border-radius: 4px; cursor: pointer; padding: 2px; }
</style>
</head>
<body>
<div class="d-flex">
{$sidebar}
<div class="content flex-grow-1 p-0">
<div class="d-flex justify-content-between align-items-center mb-0 px-3 py-2 border-bottom bg-white">
<div class="d-flex align-items-center gap-3">
<a href="/manage/dashboard" class="text-decoration-none" style="color:#1034A6;font-size:0.9rem;">&larr; Back to Dashboard</a>
<h1 class="h5 mb-0">Customize Your Site</h1>
</div>
<button type="button" class="btn btn-primary btn-sm" id="save-publish-btn" onclick="MonsoonCustomizer.save()" aria-label="Save and publish settings">Save &amp; Publish</button>
</div>
<div class="customizer-layout">
<div class="customizer-controls">
<div class="accordion" id="customizer-accordion">
<div class="accordion-item">
<h2 class="accordion-header" id="heading-identity">
<button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-identity" aria-expanded="true" aria-controls="collapse-identity">
<strong>Site Identity</strong>
</button>
</h2>
<div id="collapse-identity" class="accordion-collapse collapse show" data-bs-parent="#customizer-accordion">
<div class="accordion-body">
<div class="mb-3">
<label for="site-title" class="form-label fw-semibold">Site Title</label>
<input type="text" class="form-control" id="site-title" placeholder="My Site">
</div>
<div class="mb-3">
<label for="site-tagline" class="form-label fw-semibold">Tagline</label>
<input type="text" class="form-control" id="site-tagline" placeholder="A short description">
</div>
<div class="mb-0">
<label class="form-label fw-semibold">Site Icon</label>
<div class="d-flex align-items-center gap-2">
<div id="site-icon-preview" style="width:40px;height:40px;border:1px solid #dee2e6;border-radius:4px;display:flex;align-items:center;justify-content:center;background:#F4F6FA;font-size:1.2rem;">&#9679;</div>
<button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('site-icon-input').click()">Choose File</button>
<input type="file" id="site-icon-input" accept="image/*" style="display:none;">
</div>
</div>
</div>
</div>
</div>
<div class="accordion-item">
<h2 class="accordion-header" id="heading-colors">
<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-colors" aria-expanded="false" aria-controls="collapse-colors">
<strong>Colors</strong>
</button>
</h2>
<div id="collapse-colors" class="accordion-collapse collapse" data-bs-parent="#customizer-accordion">
<div class="accordion-body">
<div class="color-picker-row">
<label>Primary Color</label>
<input type="color" id="primary-color" value="#1034A6" data-setting="primaryColor">
</div>
<div class="color-picker-row">
<label>Sidebar Color</label>
<input type="color" id="sidebar-color" value="#1A1A1A" data-setting="sidebarColor">
</div>
<div class="color-picker-row">
<label>Background Color</label>
<input type="color" id="background-color" value="#ffffff" data-setting="backgroundColor">
</div>
</div>
</div>
</div>
<div class="accordion-item">
<h2 class="accordion-header" id="heading-typography">
<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-typography" aria-expanded="false" aria-controls="collapse-typography">
<strong>Typography</strong>
</button>
</h2>
<div id="collapse-typography" class="accordion-collapse collapse" data-bs-parent="#customizer-accordion">
<div class="accordion-body">
<div class="mb-3">
<label for="body-font" class="form-label fw-semibold">Body Font</label>
<select class="form-select" id="body-font" data-setting="bodyFont">
<option value="Graphik">Graphik</option>
<option value="system-ui">System</option>
<option value="Georgia, serif">Georgia</option>
</select>
</div>
<div class="mb-0">
<label for="heading-font" class="form-label fw-semibold">Heading Font</label>
<select class="form-select" id="heading-font" data-setting="headingFont">
<option value="Means">Means</option>
<option value="Georgia, serif">Georgia</option>
<option value="system-ui">System</option>
</select>
</div>
</div>
</div>
</div>
<div class="accordion-item">
<h2 class="accordion-header" id="heading-menus">
<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-menus" aria-expanded="false" aria-controls="collapse-menus">
<strong>Menus</strong>
</button>
</h2>
<div id="collapse-menus" class="accordion-collapse collapse" data-bs-parent="#customizer-accordion">
<div class="accordion-body">
<p class="text-muted small mb-3">Assign menu locations for your theme.</p>
<div class="mb-3">
<label for="menu-primary" class="form-label fw-semibold">Primary Menu</label>
<select class="form-select" id="menu-primary" data-setting="menuPrimary">
<option value="">None</option>
</select>
</div>
<div class="mb-0">
<label for="menu-footer" class="form-label fw-semibold">Footer Menu</label>
<select class="form-select" id="menu-footer" data-setting="menuFooter">
<option value="">None</option>
</select>
</div>
</div>
</div>
</div>
<div class="accordion-item">
<h2 class="accordion-header" id="heading-widgets">
<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-widgets" aria-expanded="false" aria-controls="collapse-widgets">
<strong>Widgets</strong>
</button>
</h2>
<div id="collapse-widgets" class="accordion-collapse collapse" data-bs-parent="#customizer-accordion">
<div class="accordion-body">
<p class="text-muted small mb-3">Manage widget areas for sidebars and footers.</p>
<a href="/manage/settings" class="btn btn-outline-secondary btn-sm w-100">Widget Settings &rarr;</a>
</div>
</div>
</div>
</div>
</div>
<div class="customizer-preview">
<iframe id="customize-preview" src="/manage/customize/preview" title="Live Preview"></iframe>
</div>
</div>
</div>
</div>
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:11" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script src="/customizer.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    MonsoonCustomizer.init();
});
</script>
</body>
</html>
HTML;
    }

    private static function renderCustomizerPreview(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Preview - Customize</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
:root {
    --color-primary: #1034A6;
    --color-sidebar: #1A1A1A;
    --color-bg: #ffffff;
    --font-body: 'Graphik', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-heading: 'Means', Georgia, serif;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: var(--font-body); background: var(--color-bg); color: #1A1A1A; }
h1, h2, h3, h4, h5, h6 { font-family: var(--font-heading); }
.site-header { background: var(--color-sidebar); color: #fff; padding: 1rem 0; }
.site-header .container { display: flex; align-items: center; justify-content: space-between; max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }
.site-header .site-title { font-size: 1.25rem; font-weight: 700; color: #fff; text-decoration: none; font-family: var(--font-heading); }
.site-header .site-tagline { font-size: 0.8rem; color: #ccc; margin-left: 1rem; }
.site-header nav a { color: #ccc; text-decoration: none; margin-left: 1.5rem; font-size: 0.9rem; }
.site-header nav a:hover { color: #fff; }
.site-main { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem; display: flex; gap: 2rem; }
.site-main .main-content { flex: 1; min-width: 0; }
.site-main .sidebar-area { width: 300px; flex-shrink: 0; }
.site-footer { background: #222; color: #aaa; padding: 2rem 0; margin-top: 3rem; }
.site-footer .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; display: flex; justify-content: space-between; align-items: center; }
.site-footer a { color: #ccc; text-decoration: none; margin-left: 1.5rem; }
.sample-post h2 { font-family: var(--font-heading); font-size: 1.75rem; margin-bottom: 0.5rem; color: #1A1A1A; }
.sample-post .meta { font-size: 0.85rem; color: #777; margin-bottom: 1rem; }
.sample-post p { font-size: 1.05rem; line-height: 1.75; margin-bottom: 1rem; color: #333; }
.sample-post blockquote { border-left: 4px solid var(--color-primary); padding-left: 1rem; margin: 1.5rem 0; color: #555; font-style: italic; }
.widget { background: #F4F6FA; border: 1px solid #E1E5EC; border-radius: 0.5rem; padding: 1.25rem; margin-bottom: 1.5rem; }
.widget h3 { font-family: var(--font-heading); font-size: 1.1rem; margin-bottom: 0.75rem; color: #1A1A1A; }
.widget ul { list-style: none; padding: 0; }
.widget ul li { padding: 0.4rem 0; border-bottom: 1px solid #E1E5EC; }
.widget ul li:last-child { border-bottom: none; }
.widget ul li a { color: #1034A6; text-decoration: none; font-size: 0.9rem; }
.widget ul li a:hover { text-decoration: underline; }
.btn-primary { background: var(--color-primary); border-color: var(--color-primary); }
</style>
</head>
<body>
<header class="site-header">
<div class="container">
<a href="#" class="site-title" id="preview-site-title">My Site</a>
<span class="site-tagline" id="preview-site-tagline">A short description of your site</span>
<nav id="preview-nav">
<a href="#">Home</a>
<a href="#">Blog</a>
<a href="#">About</a>
<a href="#">Contact</a>
</nav>
</div>
</header>
<main class="site-main">
<div class="main-content">
<article class="sample-post">
<h2>Welcome to Your New Site</h2>
<div class="meta">Published July 17, 2026</div>
<p>This is a sample page to preview your theme customizations. As you change colors, fonts, and other settings in the customizer panel, this preview updates in real time.</p>
<blockquote>The design of your site should reflect your brand and personality. Use the customizer to make it your own.</blockquote>
<p>Monsoon CMS gives you full control over every aspect of your site's appearance. Change the primary color, switch fonts, or adjust the sidebar background to match your vision.</p>
<p>Try changing the settings on the left to see how they affect this preview. All changes are applied instantly via CSS custom properties.</p>
</article>
</div>
<div class="sidebar-area">
<div class="widget">
<h3>Recent Posts</h3>
<ul>
<li><a href="#">Getting Started with Monsoon</a></li>
<li><a href="#">Building Your First Theme</a></li>
<li><a href="#">Block Editor Tips</a></li>
<li><a href="#">Module Development Guide</a></li>
</ul>
</div>
<div class="widget">
<h3>Categories</h3>
<ul>
<li><a href="#">Tutorials</a></li>
<li><a href="#">News</a></li>
<li><a href="#">Development</a></li>
</ul>
</div>
</div>
</main>
<footer class="site-footer">
<div class="container">
<span>&copy; 2026 <span id="preview-footer-name">My Site</span>. All rights reserved.</span>
<div>
<a href="#">Privacy</a>
<a href="#">Terms</a>
<a href="#">RSS</a>
</div>
</div>
</footer>
<script>
(function() {
    window.parent.postMessage({ type: 'customize-ready' }, '*');

    window.addEventListener('message', function(e) {
        if (!e.data || e.data.type !== 'customize-update') return;
        var s = e.data.settings;
        var root = document.documentElement;

        if (s.primaryColor) root.style.setProperty('--color-primary', s.primaryColor);
        if (s.sidebarColor) root.style.setProperty('--color-sidebar', s.sidebarColor);
        if (s.backgroundColor) root.style.setProperty('--color-bg', s.backgroundColor);
        if (s.bodyFont) root.style.setProperty('--font-body', s.bodyFont === 'Graphik' ? "'Graphik', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" : s.bodyFont);
        if (s.headingFont) root.style.setProperty('--font-heading', s.headingFont === 'Means' ? "'Means', Georgia, serif" : s.headingFont);

        if (s.site_name !== undefined) {
            var title = document.getElementById('preview-site-title');
            if (title) title.textContent = s.site_name || 'My Site';
            var footerName = document.getElementById('preview-footer-name');
            if (footerName) footerName.textContent = s.site_name || 'My Site';
        }
        if (s.site_tagline !== undefined) {
            var tagline = document.getElementById('preview-site-tagline');
            if (tagline) tagline.textContent = s.site_tagline || '';
        }

        var header = document.querySelector('.site-header');
        if (header && s.sidebarColor) header.style.background = s.sidebarColor;
        var footer = document.querySelector('.site-footer');
        if (footer && s.sidebarColor) footer.style.background = s.sidebarColor;
    });
})();
</script>
</body>
</html>
HTML;
    }

    private static function renderThemesPage(): string
    {
        $db = Database::getInstance()->getConnection();
        $themeLoader = new ThemeLoader(dirname(__DIR__, 2) . '/themes', $db);
        $availableThemes = $themeLoader->getAvailableThemes();
        $activeThemeName = $themeLoader->getActiveThemeName();

        $themeCards = '';
        foreach ($availableThemes as $theme) {
            $name = htmlspecialchars($theme['name'] ?? $theme['_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $slug = htmlspecialchars($theme['_name'] ?? '', ENT_QUOTES, 'UTF-8');
            $version = htmlspecialchars($theme['version'] ?? '', ENT_QUOTES, 'UTF-8');
            $description = htmlspecialchars($theme['description'] ?? '', ENT_QUOTES, 'UTF-8');
            $author = htmlspecialchars($theme['author'] ?? '', ENT_QUOTES, 'UTF-8');
            $isActive = $theme['_name'] === $activeThemeName;
            $activeClass = $isActive ? ' border-success' : '';
            $badge = $isActive ? '<span class="badge bg-success">Active</span>' : '';
            $supports = $theme['supports'] ?? [];
            $supportsList = '';
            if (!empty($supports['blockTypes'])) {
                foreach ($supports['blockTypes'] as $bt) {
                    $btEsc = htmlspecialchars($bt, ENT_QUOTES, 'UTF-8');
                    $supportsList .= "<span class=\"badge bg-light text-dark me-1\">{$btEsc}</span>";
                }
            }

            $actions = '';
            if ($isActive) {
                $actions = '<a href="/manage/customize" class="btn btn-sm btn-outline-primary">Customize</a>';
            } else {
                $actions = '<button class="btn btn-sm btn-primary activate-theme-btn" data-theme="' . $slug . '" style="background-color:#1034A6;border-color:#1034A6;">Activate</button>';
            }

            $supportsBlock = $supportsList !== '' ? '<div class="mb-3">' . $supportsList . '</div>' : '';

            $themeCards .= <<<HTML
<div class="col-md-4 mb-4">
<div class="card shadow-sm h-100{$activeClass}" id="theme-card-{$slug}">
<div class="card-body d-flex flex-column">
<div class="d-flex justify-content-between align-items-start mb-2">
<h3 class="h6 mb-0 fw-semibold">{$name}</h3>
{$badge}
</div>
<p class="text-muted small mb-2">v{$version} &middot; {$author}</p>
<p class="small mb-3">{$description}</p>
{$supportsBlock}
<div class="mt-auto">{$actions}</div>
</div>
</div>
</div>
HTML;
        }

        if (empty($availableThemes)) {
            $themeCards = '<div class="col-12"><p class="text-muted text-center py-4">No themes found.</p></div>';
        }


        $sidebar = self::renderSidebar('themes');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Themes - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
</head>
<body>
<div class="d-flex">
{$sidebar}
<div class="content flex-grow-1">
<h1 class="h3 mb-4">Themes</h1>
<div class="row" id="themes-grid">
{$themeCards}
</div>
</div>
</div>
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:11" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.activate-theme-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var themeName = this.dataset.theme;
            if (!Monsoon.confirm('Activate this theme?')) return;
            var btnEl = this;
            Monsoon.setLoading(btnEl, true);
            fetch('/api/v1/themes/' + encodeURIComponent(themeName) + '/activate', { method: 'POST' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.data && data.data.theme) {
                        Monsoon.toast('Theme activated.', 'success');
                        window.location.reload();
                    } else {
                        Monsoon.toast(data.error?.message || 'Failed to activate theme.', 'danger');
                    }
                })
                .catch(function() {
                    Monsoon.toast('Network error.', 'danger');
                })
                .finally(function() {
                    Monsoon.setLoading(btnEl, false);
                });
        });
    });
});
</script>
</body>
</html>
HTML;
    }

    private static function renderMenusPage(): string
    {

        $sidebar = self::renderSidebar('menus');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Menus - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
.menu-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: #fff; border: 1px solid #E1E5EC; border-radius: 0.25rem; margin-bottom: 0.25rem; cursor: move; }
.menu-item:hover { border-color: #1034A6; }
.menu-item .drag-handle { color: #aaa; cursor: grab; }
.menu-item .item-label { flex: 1; font-size: 0.9rem; }
.menu-item .remove-btn { background: none; border: none; color: #D33F3F; cursor: pointer; font-size: 1.1rem; padding: 0; line-height: 1; }
.menu-item.sortable-ghost { opacity: 0.4; }
</style>
</head>
<body>
<div class="d-flex">
{$sidebar}
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">Menus</h1>
<button class="btn btn-primary" id="create-menu-btn" style="background-color:#1034A6;border-color:#1034A6;" aria-label="Create new menu">New Menu</button>
</div>
<div class="card shadow-sm">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr><th>Name</th><th>Location</th><th>Items</th><th></th></tr>
</thead>
<tbody id="menus-list">
<tr><td colspan="4" class="text-center text-muted py-4">Loading...</td></tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>

<div class="modal fade" id="menu-modal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title h5" id="menu-modal-title">Create Menu</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="mb-3">
<label for="menu-name" class="form-label fw-semibold">Menu Name</label>
<input type="text" class="form-control" id="menu-name" placeholder="e.g. Main Navigation" required>
</div>
<div class="mb-3">
<label for="menu-location" class="form-label fw-semibold">Location</label>
<select class="form-select" id="menu-location">
<option value="">None</option>
<option value="primary">Primary</option>
<option value="footer">Footer</option>
<option value="social">Social</option>
</select>
</div>
<hr>
<div class="d-flex justify-content-between align-items-center mb-2">
<label class="form-label fw-semibold mb-0">Menu Items</label>
<button type="button" class="btn btn-sm btn-outline-primary" id="add-menu-item-btn">Add Item</button>
</div>
<div id="menu-items-list"></div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-primary" id="save-menu-btn" style="background-color:#1034A6;border-color:#1034A6;" aria-label="Save menu">Save</button>
</div>
</div>
</div>
</div>

<div class="modal fade" id="menu-item-modal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title h5" id="menu-item-modal-title">Add Menu Item</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="mb-3">
<label for="item-label" class="form-label fw-semibold">Label</label>
<input type="text" class="form-control" id="item-label" placeholder="e.g. About Us" required>
</div>
<div class="mb-3">
<label for="item-url" class="form-label fw-semibold">URL</label>
<input type="text" class="form-control" id="item-url" placeholder="e.g. /about or https://example.com">
</div>
<div class="mb-3">
<label for="item-type" class="form-label fw-semibold">Type</label>
<select class="form-select" id="item-type">
<option value="custom">Custom URL</option>
<option value="page">Content Page</option>
</select>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-primary" id="save-menu-item-btn" style="background-color:#1034A6;border-color:#1034A6;">Add</button>
</div>
</div>
</div>
</div>

<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:11" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
var editingMenuId = null;
var menuModal = null;
var menuItemModal = null;
var currentMenuItems = [];

document.addEventListener('DOMContentLoaded', function() {
    menuModal = new bootstrap.Modal(document.getElementById('menu-modal'));
    menuItemModal = new bootstrap.Modal(document.getElementById('menu-item-modal'));
    loadMenus();

    document.getElementById('create-menu-btn').addEventListener('click', function() {
        editingMenuId = null;
        currentMenuItems = [];
        document.getElementById('menu-modal-title').textContent = 'Create Menu';
        document.getElementById('menu-name').value = '';
        document.getElementById('menu-location').value = '';
        renderMenuItems();
        menuModal.show();
    });

    document.getElementById('save-menu-btn').addEventListener('click', saveMenu);
    document.getElementById('add-menu-item-btn').addEventListener('click', function() {
        document.getElementById('item-label').value = '';
        document.getElementById('item-url').value = '';
        document.getElementById('item-type').value = 'custom';
        menuItemModal.show();
    });
    document.getElementById('save-menu-item-btn').addEventListener('click', addMenuItem);
});

function loadMenus() {
    fetch('/api/v1/menus')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tbody = document.getElementById('menus-list');
            if (!data.data || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No menus yet. Create your first menu.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map(function(menu) {
                var itemCount = (menu.items || []).length;
                var location = menu.location || '<span class="text-muted">None</span>';
                return '<tr>' +
                    '<td class="fw-semibold">' + Monsoon.escapeHtml(menu.name) + '</td>' +
                    '<td>' + location + '</td>' +
                    '<td>' + itemCount + ' items</td>' +
                    '<td>' +
                        '<button class="btn btn-sm btn-outline-secondary me-1" onclick="editMenu(\\'' + menu.id + '\\')">Edit</button>' +
                        '<button class="btn btn-sm btn-outline-danger" onclick="deleteMenu(\\'' + menu.id + '\\')">Delete</button>' +
                    '</td></tr>';
            }).join('');
        })
        .catch(function() {
            document.getElementById('menus-list').innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load menus.</td></tr>';
        });
}

function editMenu(id) {
    fetch('/api/v1/menus')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var menu = (data.data || []).find(function(m) { return m.id === id; });
            if (!menu) return;
            editingMenuId = id;
            currentMenuItems = menu.items || [];
            document.getElementById('menu-modal-title').textContent = 'Edit Menu';
            document.getElementById('menu-name').value = menu.name;
            document.getElementById('menu-location').value = menu.location || '';
            renderMenuItems();
            menuModal.show();
        });
}

function renderMenuItems() {
    var container = document.getElementById('menu-items-list');
    if (currentMenuItems.length === 0) {
        container.innerHTML = '<p class="text-muted small">No items yet. Click "Add Item" to add menu items.</p>';
        return;
    }
    container.innerHTML = currentMenuItems.map(function(item, index) {
        return '<div class="menu-item" draggable="true" data-index="' + index + '">' +
            '<span class="drag-handle" aria-hidden="true">&#9776;</span>' +
            '<span class="item-label">' + Monsoon.escapeHtml(item.label || '') + ' <span class="text-muted small">(' + Monsoon.escapeHtml(item.url || '') + ')</span></span>' +
            '<button type="button" class="remove-btn" onclick="removeMenuItem(' + index + ')" aria-label="Remove item">&times;</button>' +
        '</div>';
    }).join('');

    container.querySelectorAll('.menu-item').forEach(function(el) {
        el.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', el.dataset.index);
            el.style.opacity = '0.5';
        });
        el.addEventListener('dragend', function() { el.style.opacity = '1'; });
        el.addEventListener('dragover', function(e) { e.preventDefault(); });
        el.addEventListener('drop', function(e) {
            e.preventDefault();
            var fromIndex = parseInt(e.dataTransfer.getData('text/plain'));
            var toIndex = parseInt(el.dataset.index);
            if (fromIndex !== toIndex) {
                var item = currentMenuItems.splice(fromIndex, 1)[0];
                currentMenuItems.splice(toIndex, 0, item);
                renderMenuItems();
            }
        });
    });
}

function addMenuItem() {
    var label = document.getElementById('item-label').value.trim();
    var url = document.getElementById('item-url').value.trim();
    var type = document.getElementById('item-type').value;
    if (label === '') {
        Monsoon.toast('Label is required.', 'danger');
        return;
    }
    currentMenuItems.push({ label: label, url: url, type: type });
    renderMenuItems();
    menuItemModal.hide();
}

function removeMenuItem(index) {
    currentMenuItems.splice(index, 1);
    renderMenuItems();
}

function saveMenu() {
    var name = document.getElementById('menu-name').value.trim();
    if (name === '') {
        Monsoon.toast('Menu name is required.', 'danger');
        return;
    }
    var location = document.getElementById('menu-location').value || null;
    var btn = document.getElementById('save-menu-btn');
    Monsoon.setLoading(btn, true);

    var isEdit = editingMenuId !== null;
    var url = isEdit ? '/api/v1/menus/' + editingMenuId : '/api/v1/menus';
    var method = isEdit ? 'PUT' : 'POST';

    Monsoon.api(url, { method: method, body: { name: name, location: location, items: currentMenuItems } })
        .then(function() {
            Monsoon.toast(isEdit ? 'Menu updated.' : 'Menu created.', 'success');
            menuModal.hide();
            loadMenus();
        })
        .catch(function() {})
        .finally(function() { Monsoon.setLoading(btn, false); });
}

function deleteMenu(id) {
    if (!Monsoon.confirm('Delete this menu?')) return;
    fetch('/api/v1/menus/' + id, { method: 'DELETE' })
        .then(function(r) {
            if (r.ok) {
                Monsoon.toast('Menu deleted.', 'success');
                loadMenus();
            } else {
                Monsoon.toast('Failed to delete menu.', 'danger');
            }
        })
        .catch(function() { Monsoon.toast('Network error.', 'danger'); });
}
</script>
</body>
</html>
HTML;
    }

    private static function renderWidgetsPage(): string
    {

        $sidebar = self::renderSidebar('widgets');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Widgets - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
.widget-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #fff; border: 1px solid #E1E5EC; border-radius: 0.25rem; margin-bottom: 0.5rem; cursor: move; }
.widget-item:hover { border-color: #1034A6; }
.widget-item .drag-handle { color: #aaa; cursor: grab; }
.widget-item .widget-info { flex: 1; }
.widget-item .widget-info .widget-title { font-weight: 600; font-size: 0.9rem; }
.widget-item .widget-info .widget-type { font-size: 0.75rem; color: #555555; }
.widget-item .widget-actions { display: flex; gap: 0.25rem; }
.widget-item.sortable-ghost { opacity: 0.4; }
.area-section { border: 1px solid #E1E5EC; border-radius: 0.5rem; margin-bottom: 1rem; overflow: hidden; }
.area-header { background: #F4F6FA; padding: 0.75rem 1rem; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
.area-body { padding: 0.75rem; }
</style>
</head>
<body>
<div class="d-flex">
{$sidebar}
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">Widgets</h1>
<button class="btn btn-primary" id="add-area-btn" style="background-color:#1034A6;border-color:#1034A6;" aria-label="Add widget area">New Area</button>
</div>
<div id="areas-container">
<p class="text-center text-muted py-4">Loading...</p>
</div>
</div>
</div>

<div class="modal fade" id="area-modal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title h5">New Widget Area</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="mb-3">
<label for="area-name" class="form-label fw-semibold">Area Name</label>
<input type="text" class="form-control" id="area-name" placeholder="e.g. Sidebar" required>
</div>
<div class="mb-3">
<label for="area-slug" class="form-label fw-semibold">Slug</label>
<input type="text" class="form-control" id="area-slug" placeholder="e.g. sidebar">
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-primary" id="save-area-btn" style="background-color:#1034A6;border-color:#1034A6;">Create</button>
</div>
</div>
</div>
</div>

<div class="modal fade" id="widget-type-modal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title h5">Add Widget</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<p class="text-muted mb-3">Select a widget type:</p>
<div class="d-grid gap-2">
<button type="button" class="btn btn-outline-primary text-start" onclick="selectWidgetType('text')">Text / HTML</button>
<button type="button" class="btn btn-outline-primary text-start" onclick="selectWidgetType('recent_posts')">Recent Posts</button>
<button type="button" class="btn btn-outline-primary text-start" onclick="selectWidgetType('custom_html')">Custom HTML</button>
</div>
</div>
</div>
</div>
</div>

<div class="modal fade" id="widget-edit-modal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title h5" id="widget-edit-title">Edit Widget</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="mb-3">
<label for="widget-title-input" class="form-label fw-semibold">Title</label>
<input type="text" class="form-control" id="widget-title-input" placeholder="Widget title">
</div>
<div id="widget-settings-fields"></div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-primary" id="save-widget-btn" style="background-color:#1034A6;border-color:#1034A6;">Save</button>
</div>
</div>
</div>
</div>

<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:11" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
var areaModal = null;
var widgetTypeModal = null;
var widgetEditModal = null;
var addingToAreaId = null;
var editingWidgetId = null;
var currentWidgetType = null;

document.addEventListener('DOMContentLoaded', function() {
    areaModal = new bootstrap.Modal(document.getElementById('area-modal'));
    widgetTypeModal = new bootstrap.Modal(document.getElementById('widget-type-modal'));
    widgetEditModal = new bootstrap.Modal(document.getElementById('widget-edit-modal'));
    loadAreas();

    document.getElementById('add-area-btn').addEventListener('click', function() {
        document.getElementById('area-name').value = '';
        document.getElementById('area-slug').value = '';
        areaModal.show();
    });
    document.getElementById('save-area-btn').addEventListener('click', saveArea);
    document.getElementById('save-widget-btn').addEventListener('click', saveWidget);
});

function loadAreas() {
    fetch('/api/v1/widget-areas')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var container = document.getElementById('areas-container');
            if (!data.data || data.data.length === 0) {
                container.innerHTML = '<div class="card shadow-sm"><div class="card-body text-center text-muted py-4">No widget areas yet. Create your first area.</div></div>';
                return;
            }
            container.innerHTML = data.data.map(function(area) {
                return '<div class="area-section" id="area-' + area.id + '">' +
                    '<div class="area-header">' +
                        '<span>' + Monsoon.escapeHtml(area.name) + ' <small class="text-muted">(' + Monsoon.escapeHtml(area.slug) + ')</small></span>' +
                        '<button class="btn btn-sm btn-outline-primary" onclick="openAddWidget(\\'' + area.id + '\\')">Add Widget</button>' +
                    '</div>' +
                    '<div class="area-body" id="widgets-' + area.id + '">' +
                        '<p class="text-muted small mb-0">Loading widgets...</p>' +
                    '</div>' +
                '</div>';
            }).join('');
            data.data.forEach(function(area) { loadWidgets(area.id); });
        })
        .catch(function() {
            document.getElementById('areas-container').innerHTML = '<p class="text-center text-danger py-4">Failed to load widget areas.</p>';
        });
}

function loadWidgets(areaId) {
    fetch('/api/v1/widget-areas/' + areaId + '/widgets')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var container = document.getElementById('widgets-' + areaId);
            if (!data.data || data.data.length === 0) {
                container.innerHTML = '<p class="text-muted small mb-0">No widgets in this area.</p>';
                return;
            }
            container.innerHTML = data.data.map(function(w) {
                var settings = w.settings || {};
                var extra = '';
                if (w.type === 'text' && settings.content) {
                    extra = '<div class="text-muted small mt-1">' + Monsoon.escapeHtml(settings.content.substring(0, 80)) + (settings.content.length > 80 ? '...' : '') + '</div>';
                } else if (w.type === 'recent_posts') {
                    extra = '<div class="text-muted small mt-1">Show ' + Monsoon.escapeHtml(String(settings.count || 5)) + ' posts</div>';
                } else if (w.type === 'custom_html' && settings.html) {
                    extra = '<div class="text-muted small mt-1">' + Monsoon.escapeHtml(settings.html.substring(0, 80)) + (settings.html.length > 80 ? '...' : '') + '</div>';
                }
                return '<div class="widget-item" draggable="true" data-id="' + w.id + '" data-area="' + areaId + '">' +
                    '<span class="drag-handle" aria-hidden="true">&#9776;</span>' +
                    '<div class="widget-info">' +
                        '<div class="widget-title">' + Monsoon.escapeHtml(w.title || w.type) + '</div>' +
                        '<div class="widget-type">' + Monsoon.escapeHtml(w.type) + '</div>' +
                        extra +
                    '</div>' +
                    '<div class="widget-actions">' +
                        '<button class="btn btn-sm btn-outline-secondary" onclick="editWidget(\\'' + w.id + '\\', \\'' + areaId + '\\')">Edit</button>' +
                        '<button class="btn btn-sm btn-outline-danger" onclick="deleteWidget(\\'' + w.id + '\\', \\'' + areaId + '\\')">&#215;</button>' +
                    '</div>' +
                '</div>';
            }).join('');

            container.querySelectorAll('.widget-item').forEach(function(el) {
                el.addEventListener('dragstart', function(e) {
                    e.dataTransfer.setData('text/plain', el.dataset.id);
                    el.style.opacity = '0.5';
                });
                el.addEventListener('dragend', function() { el.style.opacity = '1'; });
                el.addEventListener('dragover', function(e) { e.preventDefault(); });
                el.addEventListener('drop', function(e) {
                    e.preventDefault();
                    var draggedId = e.dataTransfer.getData('text/plain');
                    if (draggedId !== el.dataset.id) {
                        var items = container.querySelectorAll('.widget-item');
                        var ids = Array.from(items).map(function(i) { return i.dataset.id; });
                        var fromIdx = ids.indexOf(draggedId);
                        var toIdx = ids.indexOf(el.dataset.id);
                        if (fromIdx !== -1 && toIdx !== -1) {
                            Monsoon.api('/api/v1/widgets/' + draggedId, { method: 'PUT', body: { order: toIdx } })
                                .then(function() { loadWidgets(areaId); });
                        }
                    }
                });
            });
        });
}

function saveArea() {
    var name = document.getElementById('area-name').value.trim();
    var slug = document.getElementById('area-slug').value.trim();
    if (name === '' || slug === '') {
        Monsoon.toast('Name and slug are required.', 'danger');
        return;
    }
    var btn = document.getElementById('save-area-btn');
    Monsoon.setLoading(btn, true);
    Monsoon.api('/api/v1/widget-areas', { method: 'POST', body: { name: name, slug: slug } })
        .then(function() {
            Monsoon.toast('Area created.', 'success');
            areaModal.hide();
            loadAreas();
        })
        .catch(function() {})
        .finally(function() { Monsoon.setLoading(btn, false); });
}

function openAddWidget(areaId) {
    addingToAreaId = areaId;
    widgetTypeModal.show();
}

function selectWidgetType(type) {
    widgetTypeModal.hide();
    editingWidgetId = null;
    currentWidgetType = type;
    document.getElementById('widget-edit-title').textContent = 'Add ' + type.replace(/_/g, ' ');
    document.getElementById('widget-title-input').value = '';
    renderWidgetFields(type, {});
    widgetEditModal.show();
}

function renderWidgetFields(type, settings) {
    var container = document.getElementById('widget-settings-fields');
    if (type === 'text') {
        container.innerHTML = '<div class="mb-3"><label for="widget-content" class="form-label fw-semibold">Content</label><textarea class="form-control" id="widget-content" rows="4" placeholder="Enter text content">' + Monsoon.escapeHtml(settings.content || '') + '</textarea></div>';
    } else if (type === 'recent_posts') {
        container.innerHTML = '<div class="mb-3"><label for="widget-count" class="form-label fw-semibold">Number of posts</label><input type="number" class="form-control" id="widget-count" value="' + Monsoon.escapeHtml(String(settings.count || 5)) + '" min="1" max="20"></div>';
    } else if (type === 'custom_html') {
        container.innerHTML = '<div class="mb-3"><label for="widget-html" class="form-label fw-semibold">HTML Code</label><textarea class="form-control" id="widget-html" rows="6" placeholder="Enter HTML">' + Monsoon.escapeHtml(settings.html || '') + '</textarea></div>';
    } else {
        container.innerHTML = '<p class="text-muted">No settings for this widget type.</p>';
    }
}

function editWidget(widgetId, areaId) {
    fetch('/api/v1/widget-areas/' + areaId + '/widgets')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var widget = (data.data || []).find(function(w) { return w.id === widgetId; });
            if (!widget) return;
            editingWidgetId = widgetId;
            addingToAreaId = areaId;
            currentWidgetType = widget.type;
            document.getElementById('widget-edit-title').textContent = 'Edit ' + widget.type.replace(/_/g, ' ');
            document.getElementById('widget-title-input').value = widget.title || '';
            renderWidgetFields(widget.type, widget.settings || {});
            widgetEditModal.show();
        });
}

function saveWidget() {
    var title = document.getElementById('widget-title-input').value.trim();
    var settings = {};
    var contentEl = document.getElementById('widget-content');
    var countEl = document.getElementById('widget-count');
    var htmlEl = document.getElementById('widget-html');

    if (contentEl) settings.content = contentEl.value;
    if (countEl) settings.count = parseInt(countEl.value) || 5;
    if (htmlEl) settings.html = htmlEl.value;

    var btn = document.getElementById('save-widget-btn');
    Monsoon.setLoading(btn, true);

    var isEdit = editingWidgetId !== null;
    var url = isEdit ? '/api/v1/widgets/' + editingWidgetId : '/api/v1/widgets';
    var method = isEdit ? 'PUT' : 'POST';
    var body = isEdit ? { title: title, settings: settings } : { area_id: addingToAreaId, type: currentWidgetType, title: title, settings: settings, order: 0 };

    Monsoon.api(url, { method: method, body: body })
        .then(function() {
            Monsoon.toast(isEdit ? 'Widget updated.' : 'Widget added.', 'success');
            widgetEditModal.hide();
            loadWidgets(addingToAreaId);
        })
        .catch(function() {})
        .finally(function() { Monsoon.setLoading(btn, false); });
}

function deleteWidget(widgetId, areaId) {
    if (!Monsoon.confirm('Delete this widget?')) return;
    fetch('/api/v1/widgets/' + widgetId, { method: 'DELETE' })
        .then(function(r) {
            if (r.ok) {
                Monsoon.toast('Widget deleted.', 'success');
                loadWidgets(areaId);
            } else {
                Monsoon.toast('Failed to delete widget.', 'danger');
            }
        })
        .catch(function() { Monsoon.toast('Network error.', 'danger'); });
}
</script>
</body>
</html>
HTML;
    }
}
