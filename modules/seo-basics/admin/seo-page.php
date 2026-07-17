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
