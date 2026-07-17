<?php

declare(strict_types=1);

namespace Monsoon\Modules\Forms;

function renderFormsPage(): string
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forms - Monsoon CMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/admin.css">
<style>
.field-card { border: 1px solid #E1E5EC; border-radius: 0.5rem; padding: 1rem; margin-bottom: 0.75rem; background: #fff; position: relative; }
.field-card .drag-handle { cursor: grab; color: #aaa; margin-right: 0.5rem; }
.field-card .remove-field { position: absolute; top: 0.5rem; right: 0.5rem; background: none; border: none; color: #D33F3F; cursor: pointer; font-size: 1.1rem; }
.field-type-badge { font-size: 0.7rem; padding: 0.15rem 0.4rem; border-radius: 1rem; background: #E8ECF4; color: #555; }
.submission-row:hover { background: #F8F9FC; }
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
<a href="/manage/forms" class="active" aria-label="Forms" aria-current="page">Forms</a>
<a href="/manage/security" aria-label="Security">Security</a>
<a href="/manage/backup" aria-label="Backup">Backup</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">Forms</h1>
<button class="btn btn-primary" id="new-form-btn" style="background-color:#1034A6;border-color:#1034A6;" aria-label="Create new form">New Form</button>
</div>

<div id="forms-list-view">
<div class="card shadow-sm">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr><th>Name</th><th>Fields</th><th>Submissions</th><th>Created</th><th></th></tr>
</thead>
<tbody id="forms-tbody">
<tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>
</tbody>
</table>
</div>
</div>
</div>
</div>

<div id="form-editor-view" style="display:none;">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0" id="editor-title">New Form</h1>
<div class="d-flex gap-2">
<button class="btn btn-outline-secondary" id="back-to-list-btn">Back to List</button>
<button class="btn btn-primary" id="save-form-btn" style="background-color:#1034A6;border-color:#1034A6;">Save Form</button>
</div>
</div>
<div class="row">
<div class="col-lg-8">
<div class="card shadow-sm mb-4">
<div class="card-body">
<div class="mb-3">
<label for="form-name" class="form-label fw-semibold">Form Name</label>
<input type="text" class="form-control" id="form-name" placeholder="e.g. Contact Us" required>
</div>
<div class="mb-3">
<label for="form-success-msg" class="form-label fw-semibold">Success Message</label>
<input type="text" class="form-control" id="form-success-msg" value="Thank you for your submission!">
</div>
<div class="mb-3">
<label for="form-redirect" class="form-label fw-semibold">Redirect URL (optional)</label>
<input type="url" class="form-control" id="form-redirect" placeholder="https://example.com/thank-you">
</div>
</div>
</div>
<div class="card shadow-sm mb-4">
<div class="card-header bg-white d-flex justify-content-between align-items-center">
<h2 class="h6 mb-0">Fields</h2>
<div class="dropdown">
<button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-label="Add field">Add Field</button>
<ul class="dropdown-menu">
<li><a class="dropdown-item add-field-btn" href="#" data-type="text">Text Input</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="email">Email</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="tel">Phone</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="number">Number</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="textarea">Text Area</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="select">Dropdown</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="checkbox">Checkbox</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="radio">Radio Buttons</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="date">Date</a></li>
<li><a class="dropdown-item add-field-btn" href="#" data-type="url">URL</a></li>
</ul>
</div>
</div>
<div class="card-body" id="fields-container">
<p class="text-muted text-center py-3" id="no-fields-msg">No fields yet. Click "Add Field" to start building your form.</p>
</div>
</div>
</div>
<div class="col-lg-4">
<div class="card shadow-sm mb-4">
<div class="card-header bg-white"><h2 class="h6 mb-0">Settings</h2></div>
<div class="card-body">
<div class="mb-3">
<label for="form-notification-email" class="form-label fw-semibold">Notification Email</label>
<input type="email" class="form-control" id="form-notification-email" placeholder="you@example.com">
<small class="text-muted">Receive an email when the form is submitted.</small>
</div>
<div class="mb-3 form-check form-switch">
<input type="checkbox" class="form-check-input" id="form-honeypot" checked>
<label class="form-check-label" for="form-honeypot">Honeypot Spam Protection</label>
</div>
<div class="mb-0">
<label for="form-time-limit" class="form-label fw-semibold">Min. Submit Time (seconds)</label>
<input type="number" class="form-control" id="form-time-limit" value="5" min="0" max="300">
<small class="text-muted">Submissions faster than this are rejected.</small>
</div>
</div>
</div>
</div>
</div>
</div>

<div id="submissions-view" style="display:none;">
<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0" id="submissions-title">Submissions</h1>
<div class="d-flex gap-2">
<a class="btn btn-outline-success" id="export-csv-btn" href="#" aria-label="Export CSV">Export CSV</a>
<button class="btn btn-outline-secondary" id="back-from-subs-btn">Back to List</button>
</div>
</div>
<div class="card shadow-sm">
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead id="submissions-thead"></thead>
<tbody id="submissions-tbody">
<tr><td colspan="100" class="text-center text-muted py-4">Loading...</td></tr>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</div>

<div class="modal fade" id="field-modal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h2 class="modal-title h5" id="field-modal-title">Edit Field</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
<div class="mb-3">
<label for="field-label" class="form-label fw-semibold">Label</label>
<input type="text" class="form-control" id="field-label" placeholder="e.g. Your Name">
</div>
<div class="mb-3">
<label for="field-name" class="form-label fw-semibold">Field Name</label>
<input type="text" class="form-control" id="field-name" placeholder="e.g. full_name">
<small class="text-muted">Used as the key in submission data.</small>
</div>
<div class="mb-3">
<label for="field-type-display" class="form-label fw-semibold">Type</label>
<input type="text" class="form-control" id="field-type-display" readonly>
</div>
<div class="mb-3" id="field-options-group" style="display:none;">
<label for="field-options" class="form-label fw-semibold">Options (one per line)</label>
<textarea class="form-control" id="field-options" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
</div>
<div class="mb-3 form-check">
<input type="checkbox" class="form-check-input" id="field-required">
<label class="form-check-label" for="field-required">Required</label>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="button" class="btn btn-primary" id="save-field-btn" style="background-color:#1034A6;border-color:#1034A6;">Save Field</button>
</div>
</div>
</div>
</div>

<div class="toast-container" id="toast-container" aria-live="polite"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/manage.js"></script>
<script>
(function() {
    var forms = [];
    var editingFormId = null;
    var editingFieldIdx = null;
    var formFields = [];
    var fieldModal = null;

    document.addEventListener('DOMContentLoaded', function() {
        fieldModal = new bootstrap.Modal(document.getElementById('field-modal'));
        loadForms();

        document.getElementById('new-form-btn').addEventListener('click', function() {
            startNewForm();
        });
        document.getElementById('back-to-list-btn').addEventListener('click', function() {
            showView('list');
        });
        document.getElementById('back-from-subs-btn').addEventListener('click', function() {
            showView('list');
        });
        document.getElementById('save-form-btn').addEventListener('click', saveForm);
        document.getElementById('save-field-btn').addEventListener('click', saveField);

        document.querySelectorAll('.add-field-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                addField(this.dataset.type);
            });
        });

        document.getElementById('field-type-display').addEventListener('change', function() {
            var t = this.value;
            document.getElementById('field-options-group').style.display =
                (t === 'select' || t === 'checkbox' || t === 'radio') ? 'block' : 'none';
        });
    });

    function showView(view) {
        document.getElementById('forms-list-view').style.display = view === 'list' ? 'block' : 'none';
        document.getElementById('form-editor-view').style.display = view === 'editor' ? 'block' : 'none';
        document.getElementById('submissions-view').style.display = view === 'submissions' ? 'block' : 'none';
        if (view === 'list') loadForms();
    }

    function loadForms() {
        fetch('/api/v1/forms')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                forms = data.data || [];
                renderFormsList();
            })
            .catch(function() {
                document.getElementById('forms-tbody').innerHTML =
                    '<tr><td colspan="5" class="text-center text-danger py-4">Failed to load forms.</td></tr>';
            });
    }

    function renderFormsList() {
        var tbody = document.getElementById('forms-tbody');
        if (forms.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No forms yet. Create your first form.</td></tr>';
            return;
        }
        tbody.innerHTML = forms.map(function(f) {
            var fieldCount = (f.fields || []).length;
            var date = new Date(f.created_at).toLocaleDateString();
            return '<tr>' +
                '<td class="fw-semibold">' + Monsoon.escapeHtml(f.name) + '</td>' +
                '<td>' + fieldCount + ' field' + (fieldCount !== 1 ? 's' : '') + '</td>' +
                '<td><a href="#" class="view-subs-link" data-id="' + f.id + '" data-name="' + Monsoon.escapeHtml(f.name) + '">' +
                    '<span id="sub-count-' + f.id + '">...</span></a></td>' +
                '<td class="text-muted">' + date + '</td>' +
                '<td class="text-nowrap">' +
                    '<button class="btn btn-sm btn-outline-secondary edit-form-btn" data-id="' + f.id + '">Edit</button> ' +
                    '<button class="btn btn-sm btn-outline-danger delete-form-btn" data-id="' + f.id + '">Delete</button>' +
                '</td></tr>';
        }).join('');

        tbody.querySelectorAll('.edit-form-btn').forEach(function(btn) {
            btn.addEventListener('click', function() { editForm(this.dataset.id); });
        });
        tbody.querySelectorAll('.delete-form-btn').forEach(function(btn) {
            btn.addEventListener('click', function() { deleteForm(this.dataset.id); });
        });
        tbody.querySelectorAll('.view-subs-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                viewSubmissions(this.dataset.id, this.dataset.name);
            });
        });

        forms.forEach(function(f) {
            fetch('/api/v1/forms/' + f.id + '/submissions')
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    var el = document.getElementById('sub-count-' + f.id);
                    if (el) el.textContent = d.total || 0;
                });
        });
    }

    function startNewForm() {
        editingFormId = null;
        formFields = [];
        document.getElementById('editor-title').textContent = 'New Form';
        document.getElementById('form-name').value = '';
        document.getElementById('form-success-msg').value = 'Thank you for your submission!';
        document.getElementById('form-redirect').value = '';
        document.getElementById('form-notification-email').value = '';
        document.getElementById('form-honeypot').checked = true;
        document.getElementById('form-time-limit').value = '5';
        renderFields();
        showView('editor');
    }

    function editForm(id) {
        var form = forms.find(function(f) { return f.id === id; });
        if (!form) return;
        editingFormId = id;
        formFields = JSON.parse(JSON.stringify(form.fields || []));
        document.getElementById('editor-title').textContent = 'Edit Form';
        document.getElementById('form-name').value = form.name || '';
        document.getElementById('form-success-msg').value = form.success_message || '';
        document.getElementById('form-redirect').value = form.redirect_url || '';
        document.getElementById('form-notification-email').value = form.notification_email || '';
        document.getElementById('form-honeypot').checked = !!form.honeypot_enabled;
        document.getElementById('form-time-limit').value = form.time_limit_seconds || 5;
        renderFields();
        showView('editor');
    }

    function renderFields() {
        var container = document.getElementById('fields-container');
        var noMsg = document.getElementById('no-fields-msg');
        if (formFields.length === 0) {
            container.innerHTML = '';
            container.appendChild(noMsg);
            noMsg.style.display = 'block';
            return;
        }
        noMsg.style.display = 'none';
        container.innerHTML = formFields.map(function(f, i) {
            var requiredBadge = f.required ? ' <span class="text-danger">*</span>' : '';
            return '<div class="field-card" data-idx="' + i + '">' +
                '<div class="d-flex align-items-center">' +
                '<span class="drag-handle" title="Drag to reorder">&#9776;</span>' +
                '<div class="flex-grow-1">' +
                '<span class="fw-semibold">' + Monsoon.escapeHtml(f.label || f.name || 'Unnamed') + requiredBadge + '</span> ' +
                '<span class="field-type-badge">' + Monsoon.escapeHtml(f.type) + '</span>' +
                '</div>' +
                '<button class="edit-field-btn btn btn-sm btn-outline-secondary me-2" data-idx="' + i + '">Edit</button>' +
                '<button class="remove-field" data-idx="' + i + '" title="Remove field" aria-label="Remove field">&times;</button>' +
                '</div></div>';
        }).join('');

        container.querySelectorAll('.edit-field-btn').forEach(function(btn) {
            btn.addEventListener('click', function() { editField(parseInt(this.dataset.idx)); });
        });
        container.querySelectorAll('.remove-field').forEach(function(btn) {
            btn.addEventListener('click', function() { removeField(parseInt(this.dataset.idx)); });
        });
    }

    function addField(type) {
        var labels = {
            text: 'Text Field', email: 'Email', tel: 'Phone', number: 'Number',
            textarea: 'Text Area', select: 'Dropdown', checkbox: 'Checkbox',
            radio: 'Radio Buttons', date: 'Date', url: 'URL'
        };
        formFields.push({
            type: type,
            name: '',
            label: labels[type] || 'Field',
            required: false,
            options: []
        });
        editField(formFields.length - 1);
    }

    function editField(idx) {
        editingFieldIdx = idx;
        var f = formFields[idx];
        document.getElementById('field-modal-title').textContent = 'Edit Field';
        document.getElementById('field-label').value = f.label || '';
        document.getElementById('field-name').value = f.name || '';
        document.getElementById('field-type-display').value = f.type;
        document.getElementById('field-required').checked = !!f.required;

        var showOpts = (f.type === 'select' || f.type === 'radio');
        document.getElementById('field-options-group').style.display = showOpts ? 'block' : 'none';
        document.getElementById('field-options').value = (f.options || []).join('\n');

        fieldModal.show();
    }

    function saveField() {
        var label = document.getElementById('field-label').value.trim();
        var name = document.getElementById('field-name').value.trim();
        if (!label) { Monsoon.toast('Label is required.', 'danger'); return; }
        if (!name) { name = label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, ''); }

        var f = formFields[editingFieldIdx];
        f.label = label;
        f.name = name;
        f.required = document.getElementById('field-required').checked;

        var type = f.type;
        if (type === 'select' || type === 'radio') {
            f.options = document.getElementById('field-options').value
                .split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
        }

        fieldModal.hide();
        renderFields();
    }

    function removeField(idx) {
        formFields.splice(idx, 1);
        renderFields();
    }

    function saveForm() {
        var name = document.getElementById('form-name').value.trim();
        if (!name) { Monsoon.toast('Form name is required.', 'danger'); return; }

        var data = {
            name: name,
            fields: formFields,
            success_message: document.getElementById('form-success-msg').value || 'Thank you for your submission!',
            redirect_url: document.getElementById('form-redirect').value || null,
            notification_email: document.getElementById('form-notification-email').value || null,
            honeypot_enabled: document.getElementById('form-honeypot').checked ? 1 : 0,
            time_limit_seconds: parseInt(document.getElementById('form-time-limit').value) || 5
        };

        var btn = document.getElementById('save-form-btn');
        Monsoon.setLoading(btn, true);

        var url = editingFormId ? '/api/v1/forms/' + editingFormId : '/api/v1/forms';
        var method = editingFormId ? 'PUT' : 'POST';

        Monsoon.api(url, { method: method, body: data })
            .then(function() {
                Monsoon.toast('Form saved successfully.', 'success');
                showView('list');
            })
            .catch(function() {})
            .finally(function() { Monsoon.setLoading(btn, false, 'Save Form'); });
    }

    function deleteForm(id) {
        if (!Monsoon.confirm('Delete this form and all its submissions? This cannot be undone.')) return;
        fetch('/api/v1/forms/' + id, { method: 'DELETE' })
            .then(function(r) {
                if (r.ok) {
                    Monsoon.toast('Form deleted.', 'success');
                    loadForms();
                } else {
                    Monsoon.toast('Failed to delete form.', 'danger');
                }
            })
            .catch(function() { Monsoon.toast('Network error.', 'danger'); });
    }

    function viewSubmissions(formId, formName) {
        document.getElementById('submissions-title').textContent = 'Submissions: ' + formName;
        document.getElementById('export-csv-btn').href = '/api/v1/forms/' + formId + '/export';

        fetch('/api/v1/forms/' + formId + '/submissions')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var subs = data.data || [];
                var thead = document.getElementById('submissions-thead');
                var tbody = document.getElementById('submissions-tbody');

                if (subs.length === 0) {
                    thead.innerHTML = '';
                    tbody.innerHTML = '<tr><td colspan="100" class="text-center text-muted py-4">No submissions yet.</td></tr>';
                    showView('submissions');
                    return;
                }

                var allKeys = [];
                subs.forEach(function(s) {
                    Object.keys(s.data || {}).forEach(function(k) {
                        if (allKeys.indexOf(k) === -1 && k.charAt(0) !== '_') allKeys.push(k);
                    });
                });

                thead.innerHTML = '<tr><th>Date</th><th>IP</th>' +
                    allKeys.map(function(k) { return '<th>' + Monsoon.escapeHtml(k) + '</th>'; }).join('') +
                    '<th></th></tr>';

                tbody.innerHTML = subs.map(function(s) {
                    var date = new Date(s.created_at).toLocaleString();
                    var cells = allKeys.map(function(k) {
                        return '<td>' + Monsoon.escapeHtml(String(s.data[k] || '')) + '</td>';
                    }).join('');
                    return '<tr class="submission-row"><td class="text-muted text-nowrap">' + date + '</td>' +
                        '<td class="text-muted">' + Monsoon.escapeHtml(s.ip_address || '') + '</td>' +
                        cells +
                        '<td><button class="btn btn-sm btn-outline-danger delete-sub-btn" data-id="' + s.id + '">Delete</button></td></tr>';
                }).join('');

                tbody.querySelectorAll('.delete-sub-btn').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        deleteSubmission(this.dataset.id, formId, formName);
                    });
                });

                showView('submissions');
            })
            .catch(function() {
                document.getElementById('submissions-tbody').innerHTML =
                    '<tr><td colspan="100" class="text-center text-danger py-4">Failed to load submissions.</td></tr>';
                showView('submissions');
            });
    }

    function deleteSubmission(subId, formId, formName) {
        if (!Monsoon.confirm('Delete this submission?')) return;
        fetch('/api/v1/forms/submissions/' + subId, { method: 'DELETE' })
            .then(function(r) {
                if (r.ok) {
                    Monsoon.toast('Submission deleted.', 'success');
                    viewSubmissions(formId, formName);
                } else {
                    Monsoon.toast('Failed to delete submission.', 'danger');
                }
            })
            .catch(function() { Monsoon.toast('Network error.', 'danger'); });
    }
})();
</script>
</body>
</html>
HTML;
}
