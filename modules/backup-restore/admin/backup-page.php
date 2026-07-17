<?php

declare(strict_types=1);

namespace Monsoon\Modules\BackupRestore;

function renderBackupPage(): string
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Backup &amp; Restore - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
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
<a href="/manage/backup" class="active" aria-label="Backup" aria-current="page">Backup</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">Backup &amp; Restore</h1>
<button class="btn btn-primary" id="create-backup-btn" style="background-color:#1034A6;border-color:#1034A6;" aria-label="Create new backup">Create Backup</button>
</div>

<div class="row g-3 mb-4">
<div class="col-md-4">
<div class="card shadow-sm text-center">
<div class="card-body">
<div class="text-muted small text-uppercase fw-semibold mb-1">Total Backups</div>
<div class="h3 mb-0" id="stat-total">0</div>
</div>
</div>
</div>
<div class="col-md-4">
<div class="card shadow-sm text-center">
<div class="card-body">
<div class="text-muted small text-uppercase fw-semibold mb-1">Total Size</div>
<div class="h3 mb-0" id="stat-size">0 B</div>
</div>
</div>
</div>
<div class="col-md-4">
<div class="card shadow-sm text-center">
<div class="card-body">
<div class="text-muted small text-uppercase fw-semibold mb-1">Latest Backup</div>
<div class="h6 mb-0" id="stat-latest">None</div>
</div>
</div>
</div>
</div>

<div id="backup-form-card" class="card shadow-sm mb-4" style="display:none;">
<div class="card-header bg-white d-flex justify-content-between align-items-center">
<h2 class="h5 mb-0">Create New Backup</h2>
<button type="button" class="btn-close" id="cancel-backup-btn" aria-label="Close"></button>
</div>
<div class="card-body">
<div class="row g-3">
<div class="col-md-4">
<label for="backup-name" class="form-label fw-semibold">Backup Name</label>
<input type="text" class="form-control form-control-sm" id="backup-name" placeholder="e.g. Pre-update backup">
</div>
<div class="col-md-3">
<label for="backup-type" class="form-label fw-semibold">Type</label>
<select class="form-select form-select-sm" id="backup-type">
<option value="full">Full Backup</option>
<option value="database">Database Only</option>
<option value="files">Files Only</option>
</select>
</div>
<div class="col-md-5">
<label for="backup-notes" class="form-label fw-semibold">Notes (optional)</label>
<input type="text" class="form-control form-control-sm" id="backup-notes" placeholder="Reason for backup">
</div>
</div>
<div class="mt-3">
<button type="button" class="btn btn-primary btn-sm" id="start-backup-btn" style="background-color:#1034A6;border-color:#1034A6;">Start Backup</button>
</div>
</div>
</div>

<div class="card shadow-sm">
<div class="card-header bg-white d-flex justify-content-between align-items-center">
<h2 class="h6 mb-0">Backups</h2>
<button type="button" class="btn btn-sm btn-outline-secondary" id="refresh-backups-btn" aria-label="Refresh list">Refresh</button>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead class="table-light">
<tr>
<th>Name</th>
<th>Type</th>
<th>Status</th>
<th>Size</th>
<th>Tables</th>
<th>Rows</th>
<th>Created</th>
<th class="text-end">Actions</th>
</tr>
</thead>
<tbody id="backups-tbody">
<tr><td colspan="8" class="text-center text-muted py-4" id="loading-row">
<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading backups...
</td></tr>
</tbody>
</table>
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
    var refreshTimer = null;

    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        loadBackups();

        document.getElementById('create-backup-btn').addEventListener('click', function() {
            document.getElementById('backup-form-card').style.display = 'block';
            document.getElementById('backup-name').value = 'Backup ' + new Date().toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
            document.getElementById('backup-name').focus();
        });

        document.getElementById('cancel-backup-btn').addEventListener('click', function() {
            document.getElementById('backup-form-card').style.display = 'none';
        });

        document.getElementById('start-backup-btn').addEventListener('click', startBackup);
        document.getElementById('refresh-backups-btn').addEventListener('click', function() {
            loadStats();
            loadBackups();
        });
    });

    function loadStats() {
        Monsoon.api('/api/v1/backups/stats').then(function(json) {
            var d = json.data || {};
            document.getElementById('stat-total').textContent = d.total_backups || 0;
            document.getElementById('stat-size').textContent = formatSize(d.total_size || 0);
            var latest = d.latest_backup;
            if (latest) {
                document.getElementById('stat-latest').textContent = Monsoon.escapeHtml(latest.name || '—') + ' (' + formatDate(latest.created_at) + ')';
            } else {
                document.getElementById('stat-latest').textContent = 'None';
            }
        });
    }

    function loadBackups() {
        Monsoon.api('/api/v1/backups').then(function(json) {
            var backups = json.data || [];
            renderBackups(backups);
            scheduleAutoRefresh(backups);
        });
    }

    function renderBackups(backups) {
        var tbody = document.getElementById('backups-tbody');
        if (backups.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No backups yet. Click "Create Backup" to get started.</td></tr>';
            return;
        }

        tbody.innerHTML = backups.map(function(b) {
            return '<tr>' +
                '<td class="fw-semibold">' + Monsoon.escapeHtml(b.name) + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + Monsoon.escapeHtml(b.type) + '</span></td>' +
                '<td>' + statusBadge(b.status) + '</td>' +
                '<td>' + formatSize(b.file_size) + '</td>' +
                '<td>' + (b.db_tables_count !== null ? b.db_tables_count : '—') + '</td>' +
                '<td>' + (b.db_rows_count !== null ? b.db_rows_count.toLocaleString() : '—') + '</td>' +
                '<td class="text-muted text-nowrap">' + formatDate(b.created_at) + '</td>' +
                '<td class="text-end text-nowrap">' +
                    (b.status === 'completed' ? '<button class="btn btn-sm btn-outline-warning restore-btn" data-id="' + b.id + '" data-name="' + Monsoon.escapeHtml(b.name) + '">Restore</button> ' : '') +
                    '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' + b.id + '" data-name="' + Monsoon.escapeHtml(b.name) + '">Delete</button>' +
                '</td></tr>';
        }).join('');

        tbody.querySelectorAll('.delete-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                deleteBackup(this.dataset.id, this.dataset.name);
            });
        });
        tbody.querySelectorAll('.restore-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                restoreBackup(this.dataset.id, this.dataset.name);
            });
        });
    }

    function startBackup() {
        var name = document.getElementById('backup-name').value.trim();
        if (!name) { Monsoon.toast('Please enter a backup name.', 'danger'); return; }

        var type = document.getElementById('backup-type').value;
        var notes = document.getElementById('backup-notes').value.trim();
        var btn = document.getElementById('start-backup-btn');
        Monsoon.setLoading(btn, true);

        Monsoon.api('/api/v1/backups', {
            method: 'POST',
            body: { name: name, type: type, notes: notes }
        }).then(function() {
            Monsoon.toast('Backup created successfully.', 'success');
            document.getElementById('backup-form-card').style.display = 'none';
            loadStats();
            loadBackups();
        }).catch(function() {}).finally(function() {
            Monsoon.setLoading(btn, false, 'Start Backup');
        });
    }

    function deleteBackup(id, name) {
        if (!Monsoon.confirm('Delete backup "' + name + '"? This cannot be undone.')) return;

        Monsoon.api('/api/v1/backups/' + id, { method: 'DELETE' }).then(function() {
            Monsoon.toast('Backup deleted.', 'success');
            loadStats();
            loadBackups();
        });
    }

    function restoreBackup(id, name) {
        if (!Monsoon.confirm('WARNING: Restoring from "' + name + '" will overwrite your current database. This action cannot be undone. Are you sure you want to continue?')) return;

        var row = document.querySelector('.restore-btn[data-id="' + id + '"]');
        if (row) {
            row.disabled = true;
            row.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Restoring...';
        }

        Monsoon.api('/api/v1/backups/' + id + '/restore', { method: 'POST' }).then(function(json) {
            Monsoon.toast(json.data.message || 'Database restored successfully.', 'success');
            loadBackups();
        }).finally(function() {
            if (row) {
                row.disabled = false;
                row.innerHTML = 'Restore';
            }
        });
    }

    function scheduleAutoRefresh(backups) {
        if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; }
        var hasActive = backups.some(function(b) { return b.status === 'pending' || b.status === 'running'; });
        if (hasActive) {
            refreshTimer = setInterval(function() {
                loadStats();
                loadBackups();
            }, 5000);
        }
    }

    function statusBadge(status) {
        var map = {
            pending: '<span class="badge bg-warning text-dark">Pending</span>',
            running: '<span class="badge bg-info"><span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Running</span>',
            completed: '<span class="badge bg-success">Completed</span>',
            failed: '<span class="badge bg-danger">Failed</span>'
        };
        return map[status] || '<span class="badge bg-secondary">' + Monsoon.escapeHtml(status) + '</span>';
    }

    function formatSize(bytes) {
        if (bytes === null || bytes === undefined || bytes === 0) return '0 B';
        var units = ['B', 'KB', 'MB', 'GB', 'TB'];
        var i = Math.floor(Math.log(bytes) / Math.log(1024));
        if (i >= units.length) i = units.length - 1;
        return (bytes / Math.pow(1024, i)).toFixed(i === 0 ? 0 : 2) + ' ' + units[i];
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        var d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
})();
</script>
</body>
</html>
HTML;
}
