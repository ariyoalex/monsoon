<?php

declare(strict_types=1);

namespace Monsoon\Modules\SeoBasics;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SEO - Monsoon CMS</title>
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
<a href="/manage/seo" class="active" aria-label="SEO" aria-current="page">SEO</a>
<a href="/manage/forms" aria-label="Forms">Forms</a>
<a href="/manage/security" aria-label="Security">Security</a>
<a href="/manage/backup" aria-label="Backup">Backup</a>
<a href="/manage/logout" class="mt-4 text-danger" aria-label="Log out">Log out</a>
</nav>
</div>
<div class="content flex-grow-1">
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">SEO Settings</h1>
        <span class="badge bg-success">Module Active</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Default Meta Tags</h2>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="seo-default-title" class="form-label fw-semibold">Default Title Pattern</label>
                        <input type="text" class="form-control" id="seo-default-title" value="{site_name} \u2014 {page_title}">
                        <div class="form-text">Available placeholders: {site_name}, {page_title}, {site_description}</div>
                    </div>
                    <div class="mb-3">
                        <label for="seo-default-desc" class="form-label fw-semibold">Default Meta Description</label>
                        <textarea class="form-control" id="seo-default-desc" rows="3" placeholder="Default description for pages without a custom one"></textarea>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="save-seo-defaults" style="background-color:#1034A6;border-color:#1034A6;">Save Defaults</button>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Features</h2>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="seo-og-enabled" checked>
                        <label class="form-check-label" for="seo-og-enabled">Open Graph Tags</label>
                        <div class="form-text">Automatically generate OG meta tags for social sharing</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="seo-schema-enabled" checked>
                        <label class="form-check-label" for="seo-schema-enabled">Schema.org Markup</label>
                        <div class="form-text">Add structured data for search engines</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="seo-sitemap-enabled" checked>
                        <label class="form-check-label" for="seo-sitemap-enabled">XML Sitemap</label>
                        <div class="form-text">Auto-generate sitemap at /sitemap.xml</div>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm" id="save-seo-features" style="background-color:#1034A6;border-color:#1034A6;">Save Features</button>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Sitemap Preview</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">Current sitemap is generated automatically from published content.</p>
                    <a href="/sitemap.xml" target="_blank" class="btn btn-outline-primary btn-sm">View Sitemap</a>
                    <a href="/robots.txt" target="_blank" class="btn btn-outline-secondary btn-sm ms-2">View robots.txt</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Content SEO</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">SEO fields are available in the content editor sidebar. Edit any content item to set custom meta titles, descriptions, and Open Graph tags.</p>
                    <a href="/manage/content" class="btn btn-outline-primary btn-sm">Edit Content</a>
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
    loadSettings();

    document.getElementById('save-seo-defaults').addEventListener('click', function() {
        var btn = this;
        Monsoon.setLoading(btn, true);
        Monsoon.api('/api/v1/settings', {
            method: 'PUT',
            body: {
                seo_default_title: document.getElementById('seo-default-title').value,
                seo_default_description: document.getElementById('seo-default-desc').value
            }
        }).then(function() {
            Monsoon.toast('SEO defaults saved.', 'success');
        }).catch(function() {}).finally(function() {
            Monsoon.setLoading(btn, false, 'Save Defaults');
        });
    });

    document.getElementById('save-seo-features').addEventListener('click', function() {
        var btn = this;
        Monsoon.setLoading(btn, true);
        Monsoon.api('/api/v1/settings', {
            method: 'PUT',
            body: {
                seo_og_enabled: document.getElementById('seo-og-enabled').checked,
                seo_schema_enabled: document.getElementById('seo-schema-enabled').checked,
                seo_sitemap_enabled: document.getElementById('seo-sitemap-enabled').checked
            }
        }).then(function() {
            Monsoon.toast('SEO features saved.', 'success');
        }).catch(function() {}).finally(function() {
            Monsoon.setLoading(btn, false, 'Save Features');
        });
    });

    function loadSettings() {
        fetch('/api/v1/settings').then(function(r) { return r.json(); }).then(function(json) {
            var settings = {};
            (json.data || []).forEach(function(s) { settings[s.setting_key] = s.setting_value; });
            if (settings.seo_default_title) document.getElementById('seo-default-title').value = settings.seo_default_title;
            if (settings.seo_default_description) document.getElementById('seo-default-desc').value = settings.seo_default_description;
            if (settings.seo_og_enabled === '1' || settings.seo_og_enabled === true) document.getElementById('seo-og-enabled').checked = true;
            if (settings.seo_schema_enabled === '1' || settings.seo_schema_enabled === true) document.getElementById('seo-schema-enabled').checked = true;
            if (settings.seo_sitemap_enabled === '1' || settings.seo_sitemap_enabled === true) document.getElementById('seo-sitemap-enabled').checked = true;
        });
    }
});
</script>
</body>
</html>
