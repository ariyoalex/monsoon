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
<div class="brand"><a href="/manage/dashboard" aria-label="Monsoon CMS Home"><svg data-v-423bf9ae="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 471 90" class="iconLeft"><g data-v-423bf9ae="" id="43331a93-31fb-48d5-be89-af4c978a04d6" fill="white" transform="matrix(6.08519290965367,0,0,6.08519290965367,105.19774537180933,-8.853955239594285)"><path d="M11.31 3.92L8.90 3.92L6.36 12.21L3.81 3.92L1.33 3.92L1.33 13.58L2.87 13.58L2.87 8.83C2.87 7.73 2.87 6.43 2.84 5.39C3.11 6.41 3.51 7.73 3.86 8.81L5.43 13.58L7.21 13.58L8.76 8.82C9.13 7.73 9.53 6.40 9.81 5.38C9.79 6.40 9.79 7.73 9.79 8.83L9.79 13.58L11.31 13.58ZM16.49 5.95C13.99 5.95 12.73 7.69 12.73 9.86C12.73 12.03 13.99 13.78 16.49 13.78C18.98 13.78 20.26 12.03 20.26 9.86C20.26 7.69 18.98 5.95 16.49 5.95ZM16.49 12.66C15.13 12.66 14.35 11.54 14.35 9.86C14.35 8.18 15.13 7.07 16.49 7.07C17.84 7.07 18.62 8.18 18.62 9.86C18.62 11.54 17.84 12.66 16.49 12.66ZM28.18 8.08C28.04 6.78 27.13 5.95 25.54 5.95C24.79 5.95 24.25 6.08 23.66 6.52L23.16 7.38L23.16 6.15L21.62 6.15L21.62 13.58L23.16 13.58L23.16 10.25C23.16 7.92 24.02 7.08 25.09 7.08C26.14 7.08 26.59 7.67 26.66 8.51C26.68 8.89 26.68 9.21 26.68 9.56L26.68 13.58L28.22 13.58L28.22 9.39C28.22 8.79 28.21 8.46 28.18 8.08ZM32.49 5.95C30.84 5.95 29.75 6.79 29.75 8.18C29.75 9.48 30.76 10.05 32.12 10.40C33.38 10.74 33.88 11.09 33.88 11.73C33.88 12.38 33.31 12.68 32.59 12.68C31.82 12.68 31.19 12.31 31.01 11.35L29.46 11.35C29.67 13.03 30.88 13.78 32.54 13.78C34.16 13.78 35.43 13.06 35.43 11.62C35.43 10.30 34.58 9.66 32.96 9.24C31.85 8.95 31.30 8.67 31.30 7.99C31.30 7.43 31.65 7.03 32.38 7.03C33.14 7.03 33.60 7.49 33.75 8.27L35.32 8.27C35.15 6.92 34.20 5.95 32.49 5.95ZM39.97 5.95C37.46 5.95 36.20 7.69 36.20 9.86C36.20 12.03 37.46 13.78 39.97 13.78C42.46 13.78 43.74 12.03 43.74 9.86C43.74 7.69 42.46 5.95 39.97 5.95ZM39.97 12.66C38.61 12.66 37.83 11.54 37.83 9.86C37.83 8.18 38.61 7.07 39.97 7.07C41.31 7.07 42.10 8.18 42.10 9.86C42.10 11.54 41.31 12.66 39.97 12.66ZM48.30 5.95C45.79 5.95 44.53 7.69 44.53 9.86C44.53 12.03 45.79 13.78 48.30 13.78C50.79 13.78 52.07 12.03 52.07 9.86C52.07 7.69 50.79 5.95 48.30 5.95ZM48.30 12.66C46.94 12.66 46.16 11.54 46.16 9.86C46.16 8.18 46.94 7.07 48.30 7.07C49.64 7.07 50.43 8.18 50.43 9.86C50.43 11.54 49.64 12.66 48.30 12.66ZM59.99 8.08C59.85 6.78 58.94 5.95 57.34 5.95C56.60 5.95 56.06 6.08 55.47 6.52L54.96 7.38L54.96 6.15L53.42 6.15L53.42 13.58L54.96 13.58L54.96 10.25C54.96 7.92 55.83 7.08 56.90 7.08C57.95 7.08 58.39 7.67 58.46 8.51C58.49 8.89 58.49 9.21 58.49 9.56L58.49 13.58L60.03 13.58L60.03 9.39C60.03 8.79 60.02 8.46 59.99 8.08Z"></path></g><g data-v-423bf9ae="" id="b862067f-3685-40bd-94d4-6f36c77ca779" transform="matrix(1.3432835820895523,0,0,1.3432835820895523,-19.794774069714904,-22.83582089552239)" stroke="none" fill="white"><path d="M71.162 17c-7.18 0-13 5.82-13 13s5.82 13 13 13 13-5.82 13-13-5.82-13-13-13m2.117 21.825a9 9 0 0 1-2.019.234 9.06 9.06 0 0 1 0-18.118c.695 0 1.369.086 2.019.234-4.029.919-7.039 4.517-7.039 8.825s3.01 7.906 7.039 8.825M28.45 58c-7.18 0-13 5.82-13 13s5.82 13 13 13 13-5.82 13-13-5.82-13-13-13m4.627 20.837a9 9 0 0 1-4.529 1.222 9.06 9.06 0 0 1 0-18.118c1.652 0 3.196.449 4.529 1.222-2.705 1.567-4.529 4.486-4.529 7.837s1.824 6.27 4.529 7.837M71.45 58c-7.18 0-13 5.82-13 13s5.82 13 13 13 13-5.82 13-13-5.82-13-13-13m0 22.059a9.06 9.06 0 1 1 0-18.118 9.06 9.06 0 0 1 0 18.118"></path></g></svg></a></div>
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