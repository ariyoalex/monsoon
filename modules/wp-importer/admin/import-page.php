<?php

declare(strict_types=1);

namespace Monsoon\Modules\WpImporter;

function renderImportPage(): string
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>WordPress Import - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
.drop-zone { border: 2px dashed #dee2e6; border-radius: 8px; padding: 3rem; text-align: center; transition: border-color 0.2s; }
.drop-zone:hover, .drop-zone.active { border-color: #1034A6; background: #f0f4ff; }
.drop-zone input { display: none; }
.import-step { display: none; }
.import-step.active { display: block; }
.summary-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; }
.progress-ring { width: 60px; height: 60px; }
.shortcode-badge { font-size: 0.7rem; background: #fff3cd; color: #856404; padding: 2px 6px; border-radius: 4px; margin: 2px; display: inline-block; }
.log-entry { font-family: monospace; font-size: 0.85rem; padding: 4px 8px; border-bottom: 1px solid #eee; }
.log-entry.error { color: #dc3545; }
.log-entry.success { color: #198754; }
.log-entry.warning { color: #ffc107; }
</style>
</head>
<body>
<div class="d-flex">
<div class="sidebar" style="width: 250px;">
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage/dashboard" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" aria-label="Content">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/menus" aria-label="Menus">Menus</a>
<a href="/manage/widgets" aria-label="Widgets">Widgets</a>
<a href="/manage/customize" aria-label="Customize">Customize</a>
<a href="/manage/themes" aria-label="Themes">Themes</a>
<a href="/manage/seo" aria-label="SEO">SEO</a>
<a href="/manage/forms" aria-label="Forms">Forms</a>
<a href="/manage/security" aria-label="Security">Security</a>
<a href="/manage/import" class="active" aria-label="WordPress Import" aria-current="page">WordPress Import</a>
<a href="/manage/backup" aria-label="Backup">Backup</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">WordPress Import</h1>
<span class="badge bg-success">Module Active</span>
</div>

<div class="card shadow-sm">
<div class="card-header bg-white"><h2 class="h5 mb-0">Import from WordPress Export (WXR)</h2></div>
<div class="card-body">

<div class="import-step active" id="step-upload">
<div class="drop-zone" id="dropZone">
<div class="mb-3"><svg width="48" height="48" class="text-muted" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-1 1.5L18.5 9H13V3.5zM6 20V4h5v5h9v11H6z"/></svg></div>
<h3 class="h5 mb-2">Drop your WordPress WXR file here</h3>
<p class="text-muted mb-3">Or click to browse. Supports .xml and .wxr files.</p>
<input type="file" id="wxrFile" accept=".xml,.wxr" aria-label="WXR file">
<button class="btn btn-primary mt-3" id="btnPreview" style="background-color:#1034A6;border-color:#1034A6;" disabled>Preview Import</button>
</div>
<div class="text-center text-muted small mt-3">Export from WordPress: Tools → Export → All content</div>
</div>

<div class="import-step" id="step-preview">
<div class="d-flex justify-content-between align-items-center mb-3">
<h2 class="h5 mb-0">Import Preview</h2>
<button class="btn btn-outline-secondary btn-sm" id="btnBackUpload">Change File</button>
</div>
<div class="row g-3 mb-4" id="previewStats">
<div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-primary" id="stat-authors">0</div><div class="small text-muted">Authors</div></div></div>
<div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-primary" id="stat-cats">0</div><div class="small text-muted">Categories</div></div></div>
<div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-primary" id="stat-tags">0</div><div class="small text-muted">Tags</div></div></div>
<div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-primary" id="stat-posts">0</div><div class="small text-muted">Posts</div></div></div>
<div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-primary" id="stat-pages">0</div><div class="small text-muted">Pages</div></div></div>
<div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-primary" id="stat-media">0</div><div class="small text-muted">Media</div></div></div>
</div>
<div class="row g-3 mb-4" id="previewWarnings"></div>
<div class="d-flex gap-2">
<button class="btn btn-outline-secondary" id="btnBackUpload2">Back</button>
<button class="btn btn-primary ms-auto" id="btnStartImport" style="background-color:#1034A6;border-color:#1034A6;">Start Import</button>
</div>
</div>

<div class="import-step" id="step-import">
<div class="mb-3">
<h2 class="h5 mb-1" id="importTitle">Importing...</h2>
<div class="progress" style="height: 8px;" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
<div class="progress-bar bg-primary" id="importProgress" style="width: 0%"></div>
</div>
</div>
<div class="row g-3 mb-3">
<div class="col-md-3"><div class="summary-card p-3 text-center"><div class="h4 text-success" id="imp-authors">0</div><div class="small text-muted">Authors</div></div></div>
<div class="col-md-3"><div class="summary-card p-3 text-center"><div class="h4 text-success" id="imp-cats">0</div><div class="small text-muted">Categories</div></div></div>
<div class="col-md-3"><div class="summary-card p-3 text-center"><div class="h4 text-success" id="imp-tags">0</div><div class="small text-muted">Tags</div></div></div>
<div class="col-md-3"><div class="summary-card p-3 text-center"><div class="h4 text-success" id="imp-posts">0</div><div class="small text-muted">Posts</div></div></div>
</div>
<div class="row g-3 mb-3">
<div class="col-md-3"><div class="summary-card p-3 text-center"><div class="h4 text-success" id="imp-pages">0</div><div class="small text-muted">Pages</div></div></div>
<div class="col-md-3"><div class="summary-card p-3 text-center"><div class="h4 text-success" id="imp-media">0</div><div class="small text-muted">Media</div></div></div>
<div class="col-md-3"><div class="summary-card p-3 text-center"><div class="h4 text-warning" id="imp-shortcodes">0</div><div class="small text-muted">Shortcodes Found</div></div></div>
<div class="col-md-3"><div class="summary-card p-3 text-center"><div class="h4 text-info" id="imp-redirects">0</div><div class="small text-muted">Redirects</div></div></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-3">
<h3 class="h6 mb-0">Live Log</h3>
<div class="form-check form-switch">
<input class="form-check-input" type="checkbox" id="autoScroll" checked>
<label class="form-check-label small" for="autoScroll">Auto-scroll</label>
</div>
</div>
<div class="card" style="max-height: 300px; overflow-y: auto;">
<div class="card-body p-2" id="importLog"></div>
</div>
</div>

<div class="import-step" id="step-complete">
<div class="text-center py-5">
<svg class="text-success mb-3" width="64" height="64" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
<h2 class="h4 mb-2">Import Complete</h2>
<p class="text-muted mb-4">Your WordPress content has been imported into Monsoon CMS.</p>
<div class="row g-3 justify-content-center mb-4" id="finalStats"></div>
<div class="d-flex gap-2 justify-content-center">
<a href="/manage/content" class="btn btn-primary" style="background-color:#1034A6;border-color:#1034A6;">View Content</a>
<a href="/manage/import" class="btn btn-outline-secondary">Import Another</a>
</div>
</div>
</div>

</div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container" style="z-index:11;" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
(function() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('wxrFile');
    const btnPreview = document.getElementById('btnPreview');
    const btnStartImport = document.getElementById('btnStartImport');
    const steps = document.querySelectorAll('.import-step');
    let wxrFile = null;
    let importOptions = {};

    function showStep(id) {
        steps.forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
    }

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('active'); });
    dropZone.addEventListener('dragleave', e => { e.preventDefault(); dropZone.classList.remove('active'); });
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('active');
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', e => e.target.files.length && handleFile(e.target.files[0]));

    function handleFile(file) {
        if (!file.name.match(/\.(xml|wxr)$/i)) { Monsoon.toast('Please select a .xml or .wxr file', 'danger'); return; }
        wxrFile = file;
        btnPreview.disabled = false;
        dropZone.querySelector('h3').textContent = file.name;
        dropZone.querySelector('p').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
    }

    btnPreview.addEventListener('click', previewImport);
    document.getElementById('btnBackUpload').addEventListener('click', () => showStep('step-upload'));
    document.getElementById('btnBackUpload2').addEventListener('click', () => showStep('step-upload'));

    async function previewImport() {
        Monsoon.setLoading(btnPreview, true);
        const form = new FormData();
        form.append('wxr_file', wxrFile);
        try {
            const res = await fetch('/api/v1/import/preview', { method: 'POST', body: form });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error?.message || 'Preview failed');
            const d = json.data;
            document.getElementById('stat-authors').textContent = d.authors;
            document.getElementById('stat-cats').textContent = d.categories;
            document.getElementById('stat-tags').textContent = d.tags;
            document.getElementById('stat-posts').textContent = d.posts;
            document.getElementById('stat-pages').textContent = d.pages;
            document.getElementById('stat-media').textContent = d.media;

            const warns = document.getElementById('previewWarnings');
            warns.innerHTML = '';
            if (d.media > 100) warns.innerHTML += '<div class="col-12"><div class="alert alert-warning"><strong>Large media library</strong> (' + d.media + ' items). Consider enabling "Download media" option.</div></div>';
            if (d.posts + d.pages > 500) warns.innerHTML += '<div class="col-12"><div class="alert alert-warning"><strong>Large content volume</strong> (' + (d.posts + d.pages) + ' items). Import may take several minutes.</div></div>';

            importOptions = {
                preserve_dates: document.getElementById('optPreserveDates')?.checked ?? true,
                create_redirects: document.getElementById('optRedirects')?.checked ?? true,
                skip_existing: document.getElementById('optSkipExisting')?.checked ?? false,
                download_media: document.getElementById('optDownloadMedia')?.checked ?? true,
            };
            showStep('step-preview');
        } catch (e) { Monsoon.toast(e.message, 'danger'); } finally { Monsoon.setLoading(btnPreview, false); }
    }

    btnStartImport.addEventListener('click', startImport);

    async function startImport() {
        Monsoon.setLoading(btnStartImport, true);
        showStep('step-import');
        document.getElementById('importLog').innerHTML = '';

        const form = new FormData();
        form.append('wxr_file', wxrFile);
        Object.entries(importOptions).forEach(([k,v]) => form.append(k, v));

        try {
            const res = await fetch('/api/v1/import/upload', { method: 'POST', body: form });
            const reader = res.body?.getReader();
            if (!reader) throw new Error('No response stream');

            const decoder = new TextDecoder();
            let buffer = '';
            while (true) {
                const {done, value} = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, {stream: true});
                const lines = buffer.split('\n');
                buffer = lines.pop();
                lines.forEach(line => {
                    if (line.startsWith('data: ')) {
                        try {
                            const evt = JSON.parse(line.slice(6));
                            handleImportEvent(evt);
                        } catch {}
                    }
                });
            }
            const final = JSON.parse(buffer);
            if (final.data?.success) {
                showComplete(final.data);
            } else {
                throw new Error(final.error?.message || 'Import failed');
            }
        } catch (e) {
            logImport('ERROR: ' + e.message, 'error');
            Monsoon.toast('Import failed: ' + e.message, 'danger');
        } finally {
            Monsoon.setLoading(btnStartImport, false);
        }
    }

    function handleImportEvent(evt) {
        if (evt.type === 'progress') {
            const pct = evt.data.percent;
            document.getElementById('importProgress').style.width = pct + '%';
            document.getElementById('importProgress').setAttribute('aria-valuenow', pct);
            document.getElementById('imp-authors').textContent = evt.data.stats.authors?.created || 0;
            document.getElementById('imp-cats').textContent = evt.data.stats.categories?.created || 0;
            document.getElementById('imp-tags').textContent = evt.data.stats.tags?.created || 0;
            document.getElementById('imp-posts').textContent = evt.data.stats.posts?.created || 0;
            document.getElementById('imp-pages').textContent = evt.data.stats.pages?.created || 0;
            document.getElementById('imp-media').textContent = evt.data.stats.media?.created || 0;
            document.getElementById('imp-shortcodes').textContent = (evt.data.stats.shortcodes_found || []).length;
            document.getElementById('imp-redirects').textContent = evt.data.stats.redirects_created || 0;
        } else if (evt.type === 'log') {
            logImport(evt.data.message, evt.data.level);
        }
    }

    function logImport(msg, level = 'info') {
        const log = document.getElementById('importLog');
        const entry = document.createElement('div');
        entry.className = 'log-entry ' + level;
        entry.textContent = '[' + new Date().toLocaleTimeString() + '] ' + msg;
        log.appendChild(entry);
        if (document.getElementById('autoScroll').checked) log.scrollTop = log.scrollHeight;
    }

    function showComplete(data) {
        showStep('step-complete');
        const stats = data.stats;
        const final = document.getElementById('finalStats');
        final.innerHTML = `
            <div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-success">${stats.authors?.created || 0}</div><div class="small text-muted">Authors</div></div></div>
            <div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-success">${stats.categories?.created || 0}</div><div class="small text-muted">Categories</div></div></div>
            <div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-success">${stats.tags?.created || 0}</div><div class="small text-muted">Tags</div></div></div>
            <div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-success">${stats.posts?.created || 0}</div><div class="small text-muted">Posts</div></div></div>
            <div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-success">${stats.pages?.created || 0}</div><div class="small text-muted">Pages</div></div></div>
            <div class="col-md-2"><div class="summary-card p-3 text-center"><div class="h4 text-success">${stats.media?.created || 0}</div><div class="small text-muted">Media</div></div></div>
        `;
    }
})();
</script>
</body>
</html>
HTML;
}