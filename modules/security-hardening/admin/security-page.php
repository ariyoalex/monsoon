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
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage/dashboard" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" aria-label="Content">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/forms" aria-label="Forms">Forms</a>
<a href="/manage/menus" aria-label="Menus">Menus</a>
<a href="/manage/widgets" aria-label="Widgets">Widgets</a>
<a href="/manage/customize" aria-label="Customize">Customize</a>
<a href="/manage/themes" aria-label="Themes">Themes</a>
<a href="/manage/seo" aria-label="SEO">SEO</a>
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
