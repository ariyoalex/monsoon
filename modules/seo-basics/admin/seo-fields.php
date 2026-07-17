<?php

declare(strict_types=1);

/**
 * SEO Fields for Content Editor
 * Include this in the content editor sidebar
 * @var string $contentId
 */
?>
<div class="card shadow-sm mb-4" id="seo-card">
    <div class="card-header bg-white">
        <h2 class="h6 mb-0">SEO</h2>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <label for="seo-meta-title" class="form-label fw-semibold">Meta Title</label>
            <input type="text" class="form-control form-control-sm" id="seo-meta-title" placeholder="Override page title for search engines">
            <div class="form-text">Recommended: 50-60 characters</div>
        </div>
        <div class="mb-3">
            <label for="seo-meta-desc" class="form-label fw-semibold">Meta Description</label>
            <textarea class="form-control form-control-sm" id="seo-meta-desc" rows="3" placeholder="Brief description for search results"></textarea>
            <div class="form-text">Recommended: 150-160 characters</div>
        </div>
        <hr>
        <h3 class="h6 fw-semibold">Open Graph</h3>
        <div class="mb-3">
            <label for="seo-og-title" class="form-label">OG Title</label>
            <input type="text" class="form-control form-control-sm" id="seo-og-title" placeholder="Title for social sharing">
        </div>
        <div class="mb-3">
            <label for="seo-og-desc" class="form-label">OG Description</label>
            <textarea class="form-control form-control-sm" id="seo-og-desc" rows="2" placeholder="Description for social sharing"></textarea>
        </div>
        <div class="mb-3">
            <label for="seo-og-image" class="form-label">OG Image URL</label>
            <input type="text" class="form-control form-control-sm" id="seo-og-image" placeholder="https://...">
        </div>
        <hr>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="seo-noindex">
            <label class="form-check-label" for="seo-noindex">Noindex (hide from search engines)</label>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contentId = document.getElementById('content-id')?.value;
    if (!contentId) return;

    fetch('/api/v1/seo/' + contentId)
        .then(function(r) { return r.json(); })
        .then(function(json) {
            var d = json.data || {};
            if (d.meta_title) document.getElementById('seo-meta-title').value = d.meta_title;
            if (d.meta_description) document.getElementById('seo-meta-desc').value = d.meta_description;
            if (d.og_title) document.getElementById('seo-og-title').value = d.og_title;
            if (d.og_description) document.getElementById('seo-og-desc').value = d.og_description;
            if (d.og_image) document.getElementById('seo-og-image').value = d.og_image;
            if (d.noindex) document.getElementById('seo-noindex').checked = true;
        });

    ['seo-meta-title', 'seo-meta-desc', 'seo-og-title', 'seo-og-desc', 'seo-og-image'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', saveSeo);
    });
    var noindex = document.getElementById('seo-noindex');
    if (noindex) noindex.addEventListener('change', saveSeo);
});

function saveSeo() {
    var contentId = document.getElementById('content-id')?.value;
    if (!contentId) return;

    fetch('/api/v1/seo/' + contentId, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            meta_title: document.getElementById('seo-meta-title').value,
            meta_description: document.getElementById('seo-meta-desc').value,
            og_title: document.getElementById('seo-og-title').value,
            og_description: document.getElementById('seo-og-desc').value,
            og_image: document.getElementById('seo-og-image').value,
            noindex: document.getElementById('seo-noindex').checked ? 1 : 0,
        })
    });
}
</script>
