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
<div class="brand">Monsoon CMS</div>
<nav class="mt-3 px-2" role="navigation" aria-label="Admin navigation">
<a href="/manage/dashboard" aria-label="Dashboard">Dashboard</a>
<a href="/manage/content" aria-label="Content">Content</a>
<a href="/manage/media" aria-label="Media">Media</a>
<a href="/manage/users" aria-label="Users">Users</a>
<a href="/manage/roles" aria-label="Roles">Roles</a>
<a href="/manage/settings" aria-label="Settings">Settings</a>
<a href="/manage/forms" class="active" aria-label="Forms" aria-current="page">Forms</a>
<a href="/manage/menus" aria-label="Menus">Menus</a>
<a href="/manage/widgets" aria-label="Widgets">Widgets</a>
<a href="/manage/customize" aria-label="Customize">Customize</a>
<a href="/manage/themes" aria-label="Themes">Themes</a>
<a href="/manage/seo" aria-label="SEO">SEO</a>
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
