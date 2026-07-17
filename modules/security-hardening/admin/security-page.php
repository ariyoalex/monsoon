<?php

declare(strict_types=1);

namespace Monsoon\Modules\SecurityHardening;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Security - Monsoon CMS</title>
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
<a href="/manage/security" class="active" aria-label="Security" aria-current="page">Security</a>
<a href="/manage/backup" aria-label="Backup">Backup</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Security Hardening</h1>
        <span class="badge bg-success">Module Active</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm" id="rate-limit-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Login Rate Limiting</h2>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="rate-limit-toggle" checked>
                        <label class="form-check-label" for="rate-limit-toggle">Enabled</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="rate-limit-max" class="form-label fw-semibold">Max Failed Attempts</label>
                        <input type="number" class="form-control form-control-sm" id="rate-limit-max" value="5" min="1" max="100">
                        <div class="form-text">Lock IP after this many failed login attempts</div>
                    </div>
                    <div class="mb-3">
                        <label for="rate-limit-window" class="form-label fw-semibold">Time Window (seconds)</label>
                        <input type="number" class="form-control form-control-sm" id="rate-limit-window" value="900" min="60" max="86400">
                        <div class="form-text">Sliding window for counting attempts (900 = 15 minutes)</div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="save-rate-limit" style="background-color:#1034A6;border-color:#1034A6;">Save Settings</button>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm" id="twofa-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Two-Factor Authentication</h2>
                    <div class="form-check form-switch">
                        <input type="checkbox" class="form-check-input" id="twofa-toggle">
                        <label class="form-check-label" for="twofa-toggle">Global Enable</label>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="twofa-user-id" class="form-label fw-semibold">User ID</label>
                        <input type="text" class="form-control form-control-sm" id="twofa-user-id" placeholder="Enter user ID to manage 2FA">
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="twofa-setup">Generate Secret</button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="twofa-disable">Disable 2FA</button>
                    </div>
                    <div id="twofa-result" class="alert alert-info d-none" role="alert">
                        <div class="mb-2"><strong>Secret:</strong> <code id="twofa-secret"></code></div>
                        <div class="mb-2"><strong>OTP Auth URL:</strong></div>
                        <div class="text-break"><small id="twofa-otpauth"></small></div>
                        <div class="mt-3">
                            <label class="form-label fw-semibold">Verify Code</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="twofa-verify-code" placeholder="6-digit code" maxlength="6">
                                <button type="button" class="btn btn-success" id="twofa-verify-btn">Verify & Enable</button>
                            </div>
                        </div>
                    </div>
                    <div id="twofa-status" class="text-muted small"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm" id="integrity-card">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">File Integrity Checker</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" class="btn btn-primary btn-sm" id="integrity-scan" style="background-color:#1034A6;border-color:#1034A6;">Scan & Store Baseline</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="integrity-check">Check Integrity</button>
                    </div>
                    <div id="integrity-result"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm" id="audit-card">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Audit Log</h2>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label for="audit-action" class="form-label fw-semibold">Filter by Action</label>
                            <select class="form-select form-select-sm" id="audit-action">
                                <option value="">All Actions</option>
                                <option value="login.failed">Login Failed</option>
                                <option value="login.success">Login Success</option>
                                <option value="2fa.enabled">2FA Enabled</option>
                                <option value="2fa.disabled">2FA Disabled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="audit-entity" class="form-label fw-semibold">Entity Type</label>
                            <select class="form-select form-select-sm" id="audit-entity">
                                <option value="">All Entities</option>
                                <option value="user">User</option>
                                <option value="content">Content</option>
                                <option value="settings">Settings</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-sm w-100" id="audit-refresh" style="background-color:#1034A6;border-color:#1034A6;">Refresh</button>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm table-striped" id="audit-table">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Entity</th>
                                    <th>IP</th>
                                </tr>
                            </thead>
                            <tbody id="audit-tbody">
                                <tr><td colspan="5" class="text-muted text-center">Click Refresh to load logs</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted" id="audit-count">0 entries</small>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" id="audit-prev" disabled>Previous</button>
                            <button type="button" class="btn btn-outline-secondary" id="audit-next">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<div class="toast-container" id="toast-container" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var auditOffset = 0;
    var auditLimit = 25;

    document.getElementById('save-rate-limit').addEventListener('click', function() {
        var enabled = document.getElementById('rate-limit-toggle').checked;
        var maxAttempts = document.getElementById('rate-limit-max').value;
        var windowSec = document.getElementById('rate-limit-window').value;

        fetch('/api/v1/settings', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                security_rate_limit_enabled: enabled,
                security_rate_limit_max: parseInt(maxAttempts, 10),
                security_rate_limit_window: parseInt(windowSec, 10)
            })
        }).then(function(r) { return r.json(); }).then(function() {
            Monsoon.toast('Rate limiting settings saved', 'success');
        });
    });

    document.getElementById('twofa-setup').addEventListener('click', function() {
        var userId = document.getElementById('twofa-user-id').value.trim();
        if (!userId) { Monsoon.toast('Enter a user ID', 'danger'); return; }

        fetch('/api/v1/2fa/setup', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        }).then(function(r) { return r.json(); }).then(function(json) {
            var d = json.data || {};
            document.getElementById('twofa-secret').textContent = d.secret;
            document.getElementById('twofa-otpauth').textContent = d.otpauth_url;
            document.getElementById('twofa-result').classList.remove('d-none');
            document.getElementById('twofa-status').textContent = 'Scan the QR code in your authenticator app, then verify.';
        });
    });

    document.getElementById('twofa-verify-btn').addEventListener('click', function() {
        var userId = document.getElementById('twofa-user-id').value.trim();
        var code = document.getElementById('twofa-verify-code').value.trim();
        if (!userId || !code) { Monsoon.toast('Enter user ID and 6-digit code', 'danger'); return; }

        fetch('/api/v1/2fa/verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, code: code })
        }).then(function(r) { return r.json(); }).then(function(json) {
            if (json.data && json.data.valid) {
                document.getElementById('twofa-status').innerHTML = '<span class="text-success fw-semibold">2FA enabled successfully!</span>';
                document.getElementById('twofa-result').classList.add('d-none');
            } else {
                document.getElementById('twofa-status').innerHTML = '<span class="text-danger fw-semibold">Invalid code, try again.</span>';
            }
        });
    });

    document.getElementById('twofa-disable').addEventListener('click', function() {
        var userId = document.getElementById('twofa-user-id').value.trim();
        if (!userId) { Monsoon.toast('Enter a user ID', 'danger'); return; }
        if (!Monsoon.confirm('Disable 2FA for this user?')) return;

        fetch('/api/v1/2fa/disable', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        }).then(function(r) { return r.json(); }).then(function() {
            document.getElementById('twofa-result').classList.add('d-none');
            document.getElementById('twofa-status').innerHTML = '<span class="text-warning fw-semibold">2FA disabled.</span>';
        });
    });

    document.getElementById('integrity-scan').addEventListener('click', function() {
        var el = document.getElementById('integrity-result');
        el.innerHTML = '<div class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Scanning files...</div>';

        fetch('/api/v1/integrity/scan', { method: 'POST' })
            .then(function(r) { return r.json(); }).then(function(json) {
                var d = json.data || {};
                el.innerHTML = '<div class="alert alert-success mb-0">Baseline stored. <strong>' + d.scanned + '</strong> files indexed.</div>';
            });
    });

    document.getElementById('integrity-check').addEventListener('click', function() {
        var el = document.getElementById('integrity-result');
        el.innerHTML = '<div class="text-muted"><span class="spinner-border spinner-border-sm me-1"></span>Checking integrity...</div>';

        fetch('/api/v1/integrity/check')
            .then(function(r) { return r.json(); }).then(function(json) {
                var d = json.data || {};
                if (d.clean) {
                    el.innerHTML = '<div class="alert alert-success mb-0">All files match the baseline. No tampering detected.</div>';
                    return;
                }
                var html = '<div class="alert alert-danger mb-0"><strong>Integrity issues detected!</strong><ul class="mb-0 mt-2">';
                (d.modified || []).forEach(function(f) { html += '<li class="text-warning">Modified: ' + Monsoon.escapeHtml(f) + '</li>'; });
                (d.added || []).forEach(function(f) { html += '<li class="text-info">Added: ' + Monsoon.escapeHtml(f) + '</li>'; });
                (d.deleted || []).forEach(function(f) { html += '<li class="text-danger">Deleted: ' + Monsoon.escapeHtml(f) + '</li>'; });
                html += '</ul></div>';
                el.innerHTML = html;
            });
    });

    document.getElementById('audit-refresh').addEventListener('click', function() { loadAuditLog(); });
    document.getElementById('audit-prev').addEventListener('click', function() {
        auditOffset = Math.max(0, auditOffset - auditLimit);
        loadAuditLog();
    });
    document.getElementById('audit-next').addEventListener('click', function() {
        auditOffset += auditLimit;
        loadAuditLog();
    });

    function loadAuditLog() {
        var action = document.getElementById('audit-action').value;
        var entity = document.getElementById('audit-entity').value;
        var params = 'limit=' + auditLimit + '&offset=' + auditOffset;
        if (action) params += '&action=' + encodeURIComponent(action);
        if (entity) params += '&entity_type=' + encodeURIComponent(entity);

        fetch('/api/v1/audit-log?' + params)
            .then(function(r) { return r.json(); }).then(function(json) {
                var logs = json.data || [];
                var tbody = document.getElementById('audit-tbody');
                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-muted text-center">No entries found</td></tr>';
                } else {
                    tbody.innerHTML = logs.map(function(row) {
                        return '<tr>'
                            + '<td>' + Monsoon.escapeHtml(row.created_at || '') + '</td>'
                            + '<td>' + Monsoon.escapeHtml(row.user_email || row.user_id || '\u2014') + '</td>'
                            + '<td><span class="badge bg-secondary">' + Monsoon.escapeHtml(row.action || '') + '</span></td>'
                            + '<td>' + Monsoon.escapeHtml((row.entity_type || '') + (row.entity_id ? ' (' + row.entity_id.substring(0, 8) + '...)' : '')) + '</td>'
                            + '<td><code>' + Monsoon.escapeHtml(row.ip_address || '\u2014') + '</code></td>'
                            + '</tr>';
                    }).join('');
                }
                document.getElementById('audit-count').textContent = logs.length + ' entries (offset ' + auditOffset + ')';
                document.getElementById('audit-prev').disabled = auditOffset === 0;
                document.getElementById('audit-next').disabled = logs.length < auditLimit;
            });
    }
});
</script>
</body>
</html>
