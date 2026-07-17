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
