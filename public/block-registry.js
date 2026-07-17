/**
 * Monsoon CMS - Block Registry
 * Defines all core block types and provides a registry for the block editor.
 * Loaded before block-editor.js and block-toolbar.js.
 */

const BlockRegistry = {
    /** @type {Object<string, object>} block_type => definition */
    blocks: {},

    /** @type {Object<string, string[]>} category_name => block_types */
    categories: {},

    /**
     * Register a new block type.
     * @param {string} type - Unique block type identifier (e.g. 'paragraph').
     * @param {object} definition - Block definition object.
     * @param {string} definition.name - Display name for the block.
     * @param {string} definition.icon - Inline SVG string (16x16 viewBox, currentColor).
     * @param {'text'|'media'|'layout'|'widgets'} definition.category - Block category.
     * @param {object} definition.defaultData - Default data for a new block instance.
     * @param {function(object, object): string} definition.render - Render to published HTML.
     * @param {function(object, object): string} definition.edit - Render to editable HTML.
     * @param {function(object, object): string} [definition.toolbar] - Optional toolbar controls.
     * @param {function(object): {valid: boolean, errors: string[]}} [definition.validate] - Optional validation.
     */
    register(type, definition) {
        this.blocks[type] = definition;

        const cat = definition.category || 'text';
        if (!this.categories[cat]) {
            this.categories[cat] = [];
        }
        if (!this.categories[cat].includes(type)) {
            this.categories[cat].push(type);
        }
    },

    /**
     * Get a block definition by type.
     * @param {string} type - Block type identifier.
     * @returns {object|undefined} The block definition or undefined.
     */
    get(type) {
        return this.blocks[type];
    },

    /**
     * Get all registered block definitions.
     * @returns {Object<string, object>} Map of type to definition.
     */
    getAll() {
        return this.blocks;
    },

    /**
     * Get block types by category. If no category specified, returns all categories.
     * @param {string} [category] - Category name (text, media, layout, widgets). If omitted, returns all.
     * @returns {string[]|object} Array of block types for a category, or object of all categories.
     */
    getByCategory(category) {
        if (category === undefined || category === null) {
            return { ...this.categories };
        }
        return this.categories[category] || [];
    },

    /**
     * Render a block to HTML for published view.
     * @param {object} block - Block object with id, type, data, and order.
     * @param {string} block.id - UUID of the block.
     * @param {string} block.type - Block type identifier.
     * @param {object} block.data - Block data.
     * @param {number} block.order - Sort order.
     * @returns {string} HTML string.
     */
    renderBlock(block) {
        const def = this.blocks[block.type];
        if (!def || !def.render) return '';
        return def.render(block.data || {}, block);
    },

    /**
     * Render a block as editable HTML in the canvas.
     * @param {object} block - Block object with id, type, data, and order.
     * @param {string} block.id - UUID of the block.
     * @param {string} block.type - Block type identifier.
     * @param {object} block.data - Block data.
     * @param {number} block.order - Sort order.
     * @returns {string} HTML string.
     */
    renderBlockForEdit(block) {
        const def = this.blocks[block.type];
        if (!def || !def.edit) return '';
        return def.edit(block.data || {}, block);
    },

    /**
     * Get default data object for a block type (deep-cloned).
     * @param {string} type - Block type identifier.
     * @returns {object} A fresh copy of the default data.
     */
    getDefaultData(type) {
        const def = this.blocks[type];
        if (!def || !def.defaultData) return {};
        return JSON.parse(JSON.stringify(def.defaultData));
    },
};

/**
 * Generate a UUID v4.
 * @returns {string} A UUID v4 string.
 */
function _blockUuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

// ---------------------------------------------------------------------------
// Image block CSS (injected once)
// ---------------------------------------------------------------------------
(function _injectImageBlockStyles() {
    if (document.getElementById('image-block-styles')) return;
    const style = document.createElement('style');
    style.id = 'image-block-styles';
    style.textContent = `
        .image-block-placeholder { border: 2px dashed #dee2e6; border-radius: 8px; padding: 32px; text-align: center; cursor: pointer; transition: border-color 0.2s; }
        .image-block-placeholder:hover { border-color: #1034A6; }
        .image-block-placeholder .placeholder-icon { font-size: 2rem; margin-bottom: 8px; }
        .image-block-preview { position: relative; }
        .image-block-preview img { width: 100%; }
        .media-picker-item:hover { border-color: #1034A6 !important; }
        .media-picker-item.selected { border-color: #1034A6 !important; background: #f0f4ff; }
    `;
    document.head.appendChild(style);
})();

// ---------------------------------------------------------------------------
// Media Library Picker
// ---------------------------------------------------------------------------
function openMediaPicker(onSelect) {
    const overlay = document.createElement('div');
    overlay.className = 'media-picker-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;z-index:2000;background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center;';

    overlay.innerHTML = `
        <div class="media-picker-modal" style="background:#fff;border-radius:12px;width:90vw;max-width:800px;max-height:80vh;display:flex;flex-direction:column;overflow:hidden;">
            <div style="padding:16px;border-bottom:1px solid #dee2e6;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="margin:0;font-size:1.1rem;">Select Media</h3>
                <button class="media-picker-close" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">&times;</button>
            </div>
            <div class="media-picker-grid" style="flex:1;overflow-y:auto;padding:16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;">
                <div style="text-align:center;padding:32px;color:#6c757d;">Loading media...</div>
            </div>
            <div style="padding:12px 16px;border-top:1px solid #dee2e6;text-align:right;">
                <input type="file" class="media-picker-file-input" accept="image/*" style="display:none;">
                <button class="media-picker-upload-btn" style="padding:6px 16px;border:1px solid #dee2e6;border-radius:6px;background:#fff;cursor:pointer;font-size:0.875rem;">Upload New</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    const grid = overlay.querySelector('.media-picker-grid');
    const fileInput = overlay.querySelector('.media-picker-file-input');

    function close() { overlay.remove(); }

    overlay.querySelector('.media-picker-close').addEventListener('click', close);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) close();
    });

    async function loadMedia() {
        try {
            const res = await fetch('/api/v1/media');
            const items = await res.json();
            renderGrid(items);
        } catch (err) {
            grid.innerHTML = '<div style="text-align:center;padding:32px;color:#dc3545;">Failed to load media.</div>';
        }
    }

    function renderGrid(items) {
        if (!items.length) {
            grid.innerHTML = '<div style="text-align:center;padding:32px;color:#6c757d;">No media files yet. Upload one!</div>';
            return;
        }
        grid.innerHTML = items.map(item => `
            <div class="media-picker-item" data-id="${item.id}" data-url="${item.url}" style="cursor:pointer;border:2px solid transparent;border-radius:6px;overflow:hidden;transition:border-color 0.15s;">
                <img src="${item.url}" alt="${item.alt_text || ''}" style="width:100%;height:80px;object-fit:cover;display:block;background:#f4f6fa;">
                <div style="padding:4px 6px;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${item.filename}</div>
            </div>
        `).join('');

        grid.querySelectorAll('.media-picker-item').forEach(el => {
            el.addEventListener('click', () => {
                onSelect({ id: el.dataset.id, url: el.dataset.url });
                close();
            });
        });
    }

    overlay.querySelector('.media-picker-upload-btn').addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', async () => {
        const file = fileInput.files[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        try {
            const res = await fetch('/api/v1/media', { method: 'POST', body: formData });
            if (res.ok) loadMedia();
        } catch (err) {
            console.error('Upload failed:', err);
        }
    });

    loadMedia();
}

// ---------------------------------------------------------------------------
// 1. Paragraph
// ---------------------------------------------------------------------------
BlockRegistry.register('paragraph', {
    name: 'Paragraph',
    icon: `<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="2" y1="3" x2="14" y2="3"/>
        <line x1="2" y1="7" x2="10" y2="7"/>
        <line x1="2" y1="11" x2="14" y2="11"/>
    </svg>`,

    category: 'text',

    defaultData: { text: '' },

    /**
     * Render paragraph for published view.
     * @param {object} data - Block data.
     * @param {string} data.text - Paragraph text (may contain inline HTML).
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    render(data) {
        return `<p>${data.text || ''}</p>`;
    },

    /**
     * Render paragraph for editor canvas.
     * @param {object} data - Block data.
     * @param {string} data.text - Paragraph text.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    edit(data, block) {
        return `<div class="block-paragraph" contenteditable="true" data-placeholder="Type something...">${data.text || ''}</div>`;
    },

    /**
     * Validate paragraph data.
     * @param {object} data - Block data.
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validate(data) {
        return { valid: true, errors: [] };
    },
});

// ---------------------------------------------------------------------------
// 2. Heading
// ---------------------------------------------------------------------------
BlockRegistry.register('heading', {
    name: 'Heading',
    icon: `<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <text x="2" y="13" font-family="sans-serif" font-size="13" font-weight="700" fill="currentColor" stroke="none">H</text>
    </svg>`,

    category: 'text',

    defaultData: { text: '', level: 2 },

    /**
     * Render heading for published view.
     * @param {object} data - Block data.
     * @param {string} data.text - Heading text.
     * @param {number} data.level - Heading level 1-6.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    render(data) {
        const level = parseInt(data.level, 10) || 2;
        return `<h${level}>${data.text || ''}</h${level}>`;
    },

    /**
     * Render heading for editor canvas.
     * @param {object} data - Block data.
     * @param {string} data.text - Heading text.
     * @param {number} data.level - Heading level 1-6.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    edit(data, block) {
        const level = parseInt(data.level, 10) || 2;
        let controls = '<div class="block-heading-controls mb-1">';
        for (let i = 1; i <= 6; i++) {
            const active = i === level ? ' btn-outline-primary active' : ' btn-outline-secondary';
            controls += `<button type="button" class="btn btn-sm${active}" data-level="${i}">H${i}</button> `;
        }
        controls += '</div>';
        return `${controls}<div class="block-heading" contenteditable="true" data-placeholder="Heading" data-tag="h${level}">${data.text || ''}</div>`;
    },

    /**
     * Render heading-specific toolbar controls.
     * @param {object} data - Block data.
     * @param {number} data.level - Current heading level.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    toolbar(data, block) {
        const level = parseInt(data.level, 10) || 2;
        let html = '<div class="btn-group btn-group-sm">';
        for (let i = 1; i <= 6; i++) {
            const active = i === level ? ' active' : '';
            html += `<button type="button" class="btn btn-outline-secondary${active}" data-heading-level="${i}">H${i}</button>`;
        }
        html += '</div>';
        return html;
    },

    /**
     * Validate heading data.
     * @param {object} data - Block data.
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validate(data) {
        const errors = [];
        if (!data.text || data.text.trim() === '') {
            errors.push('Heading text is required.');
        }
        const level = parseInt(data.level, 10);
        if (isNaN(level) || level < 1 || level > 6) {
            errors.push('Heading level must be 1\u20136.');
        }
        return { valid: errors.length === 0, errors };
    },
});

// ---------------------------------------------------------------------------
// 3. Image
// ---------------------------------------------------------------------------
BlockRegistry.register('image', {
    name: 'Image',
    icon: `<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1.5" y="2.5" width="13" height="11" rx="1.5"/>
        <circle cx="5.5" cy="6" r="1.5"/>
        <polyline points="1.5,12 5,9 8,11.5 10.5,9 14.5,12.5"/>
    </svg>`,

    category: 'media',

    defaultData: { src: '', alt: '', caption: '', align: 'center' },

    /**
     * Render image for published view.
     * @param {object} data - Block data.
     * @param {string} data.src - Image source URL.
     * @param {string} data.alt - Alt text.
     * @param {string} data.caption - Optional caption.
     * @param {string} data.align - Alignment: left, center, right.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    render(data) {
        if (!data.src) return '';
        const align = data.align || 'center';
        let html = `<figure class="block-image align-${align}">`;
        html += `<img src="${data.src}" alt="${data.alt || ''}">`;
        if (data.caption) {
            html += `<figcaption>${data.caption}</figcaption>`;
        }
        html += '</figure>';
        return html;
    },

    /**
     * Render image for editor canvas.
     * @param {object} data - Block data.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    edit(data, block) {
        const blockId = block.id;
        let html = '<div class="image-block-editor">';
        if (data.src) {
            html += `<div class="image-block-preview">
                <img src="${data.src}" alt="${data.alt || ''}" style="max-width:100%;border-radius:4px;">
            </div>`;
        } else {
            html += `<div class="image-block-placeholder" data-action="pick-media" onclick="openMediaPicker(function(item){ var b=document.querySelector('[data-block-id=\\'${blockId}\\']');if(b){var inp=b.querySelector('input[data-field=\\'src\\']');if(inp){inp.value=item.url;inp.dispatchEvent(new Event('input',{bubbles:true}));}var alt=b.querySelector('input[data-field=\\'alt\\']');if(alt&&!alt.value){alt.value=item.alt_text||'';alt.dispatchEvent(new Event('input',{bubbles:true}));}}});">
                <div class="placeholder-icon">🖼</div>
                <p>Click to pick from Media Library</p>
                <p class="small text-muted">or drag & drop an image</p>
            </div>`;
        }
        html += `<div class="image-block-fields" style="margin-top: 8px;">
            <input type="text" class="form-control form-control-sm mb-2" data-field="src" placeholder="Image URL or paste path" value="${data.src || ''}">
            <input type="text" class="form-control form-control-sm mb-2" data-field="alt" placeholder="Alt text (required)" value="${data.alt || ''}">
            <input type="text" class="form-control form-control-sm" data-field="caption" placeholder="Caption (optional)" value="${data.caption || ''}">
        </div>`;
        html += '</div>';
        return html;
    },

    /**
     * Render image-specific toolbar controls.
     * @param {object} data - Block data.
     * @param {string} data.align - Current alignment.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    toolbar(data, block) {
        const align = data.align || 'center';
        const options = [
            { value: 'left', label: 'Left' },
            { value: 'center', label: 'Center' },
            { value: 'right', label: 'Right' },
        ];
        let html = '<div class="btn-group btn-group-sm">';
        options.forEach(opt => {
            const active = opt.value === align ? ' active' : '';
            html += `<button type="button" class="btn btn-outline-secondary${active}" data-image-align="${opt.value}">${opt.label}</button>`;
        });
        html += '</div>';
        return html;
    },

    /**
     * Validate image data.
     * @param {object} data - Block data.
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validate(data) {
        const errors = [];
        if (!data.src || data.src.trim() === '') {
            errors.push('Image source is required.');
        }
        if (!data.alt || data.alt.trim() === '') {
            errors.push('Alt text is required for accessibility.');
        }
        return { valid: errors.length === 0, errors };
    },
});

// ---------------------------------------------------------------------------
// 4. List
// ---------------------------------------------------------------------------
BlockRegistry.register('list', {
    name: 'List',
    icon: `<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="6" y1="3" x2="14" y2="3"/>
        <line x1="6" y1="8" x2="14" y2="8"/>
        <line x1="6" y1="13" x2="14" y2="13"/>
        <circle cx="3" cy="3" r="1" fill="currentColor" stroke="none"/>
        <circle cx="3" cy="8" r="1" fill="currentColor" stroke="none"/>
        <circle cx="3" cy="13" r="1" fill="currentColor" stroke="none"/>
    </svg>`,

    category: 'text',

    defaultData: { items: [''], ordered: false },

    /**
     * Render list for published view.
     * @param {object} data - Block data.
     * @param {string[]} data.items - Array of list item strings.
     * @param {boolean} data.ordered - Whether to use ol or ul.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    render(data) {
        const tag = data.ordered ? 'ol' : 'ul';
        const items = (data.items || []).map(item => `<li>${item}</li>`).join('');
        return `<${tag}>${items}</${tag}>`;
    },

    /**
     * Render list for editor canvas.
     * @param {object} data - Block data.
     * @param {string[]} data.items - Array of list item strings.
     * @param {boolean} data.ordered - Whether to use ol or ul.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    edit(data, block) {
        const items = data.items || [''];
        const tag = data.ordered ? 'ol' : 'ul';
        let html = `<${tag} class="block-list">`;
        items.forEach((item, i) => {
            html += `<li>
                <div class="d-flex align-items-center">
                    <span class="block-list-handle me-2 text-muted" style="cursor:move;">\u2807</span>
                    <div contenteditable="true" class="flex-grow-1" data-index="${i}" data-placeholder="List item">${item}</div>
                    <button type="button" class="btn btn-sm btn-link text-danger ms-1 block-list-remove" data-index="${i}">&times;</button>
                </div>
            </li>`;
        });
        html += `</${tag}>`;
        html += '<button type="button" class="btn btn-sm btn-outline-secondary mt-1 block-list-add">+ Add item</button>';
        return html;
    },

    /**
     * Render list-specific toolbar controls.
     * @param {object} data - Block data.
     * @param {boolean} data.ordered - Current list type.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    toolbar(data, block) {
        const ordered = data.ordered;
        return `<div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary${!ordered ? ' active' : ''}" data-list-ordered="false">Bullet</button>
            <button type="button" class="btn btn-outline-secondary${ordered ? ' active' : ''}" data-list-ordered="true">Numbered</button>
        </div>`;
    },

    /**
     * Validate list data.
     * @param {object} data - Block data.
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validate(data) {
        const errors = [];
        if (!data.items || !Array.isArray(data.items) || data.items.length === 0) {
            errors.push('List must have at least one item.');
        }
        return { valid: errors.length === 0, errors };
    },
});

// ---------------------------------------------------------------------------
// 5. Quote
// ---------------------------------------------------------------------------
BlockRegistry.register('quote', {
    name: 'Quote',
    icon: `<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 4h4v4H5l-1 4"/>
        <path d="M9 4h4v4h-2l-1 4"/>
    </svg>`,

    category: 'text',

    defaultData: { text: '', attribution: '' },

    /**
     * Render quote for published view.
     * @param {object} data - Block data.
     * @param {string} data.text - Quote text.
     * @param {string} data.attribution - Optional attribution.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    render(data) {
        let html = '<blockquote>';
        html += `<p>${data.text || ''}</p>`;
        if (data.attribution) {
            html += `<footer>${data.attribution}</footer>`;
        }
        html += '</blockquote>';
        return html;
    },

    /**
     * Render quote for editor canvas.
     * @param {object} data - Block data.
     * @param {string} data.text - Quote text.
     * @param {string} data.attribution - Attribution text.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    edit(data, block) {
        let html = '<div class="block-quote">';
        html += `<div contenteditable="true" class="block-quote-text mb-1" data-placeholder="Quote text" style="border-left:3px solid #dee2e6;padding-left:12px;font-style:italic;">${data.text || ''}</div>`;
        html += `<input type="text" class="form-control form-control-sm" data-field="attribution" placeholder="Attribution (optional)" value="${data.attribution || ''}">`;
        html += '</div>';
        return html;
    },

    /**
     * Validate quote data.
     * @param {object} data - Block data.
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validate(data) {
        const errors = [];
        if (!data.text || data.text.trim() === '') {
            errors.push('Quote text is required.');
        }
        return { valid: errors.length === 0, errors };
    },
});

// ---------------------------------------------------------------------------
// 6. Embed
// ---------------------------------------------------------------------------
BlockRegistry.register('embed', {
    name: 'Embed',
    icon: `<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="1.5" y="2.5" width="13" height="11" rx="1.5"/>
        <polygon points="6.5,6 11,8 6.5,10" fill="currentColor" stroke="none"/>
    </svg>`,

    category: 'widgets',

    defaultData: { url: '', provider: '' },

    /**
     * Detect embed provider from URL.
     * @param {string} url - The embed URL.
     * @returns {string} Provider name or empty string.
     */
    _detectProvider(url) {
        if (!url) return '';
        if (/youtube\.com\/watch|youtu\.be\//.test(url)) return 'youtube';
        if (/vimeo\.com\/\d+/.test(url)) return 'vimeo';
        if (/soundcloud\.com\//.test(url)) return 'soundcloud';
        if (/spotify\.com\//.test(url)) return 'spotify';
        return '';
    },

    /**
     * Convert a URL to an embeddable iframe src.
     * @param {string} url - The original URL.
     * @param {string} provider - Detected provider name.
     * @returns {string} Embeddable URL.
     */
    _getEmbedUrl(url, provider) {
        if (!url) return '';
        if (provider === 'youtube') {
            const match = url.match(/(?:v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
            return match ? `https://www.youtube.com/embed/${match[1]}` : url;
        }
        if (provider === 'vimeo') {
            const match = url.match(/vimeo\.com\/(\d+)/);
            return match ? `https://player.vimeo.com/video/${match[1]}` : url;
        }
        return url;
    },

    /**
     * Render embed for published view.
     * @param {object} data - Block data.
     * @param {string} data.url - Embed URL.
     * @param {string} data.provider - Optional provider override.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    render(data) {
        if (!data.url) return '';
        const provider = data.provider || this._detectProvider(data.url);
        const embedUrl = this._getEmbedUrl(data.url, provider);
        if (provider) {
            return `<div class="block-embed"><iframe src="${embedUrl}" frameborder="0" allowfullscreen style="width:100%;aspect-ratio:16/9;"></iframe></div>`;
        }
        return `<div class="block-embed"><a href="${data.url}" target="_blank" rel="noopener noreferrer">${data.url}</a></div>`;
    },

    /**
     * Render embed for editor canvas.
     * @param {object} data - Block data.
     * @param {string} data.url - Embed URL.
     * @param {string} data.provider - Optional provider override.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    edit(data, block) {
        let html = '<div class="block-embed-editor">';
        html += `<div class="mb-2">
            <input type="url" class="form-control form-control-sm" data-field="url" placeholder="Paste a URL (YouTube, Vimeo, etc.)" value="${data.url || ''}">
        </div>`;
        const provider = data.provider || this._detectProvider(data.url);
        if (data.url && provider) {
            const embedUrl = this._getEmbedUrl(data.url, provider);
            html += `<div class="block-embed-preview ratio ratio-16x9 mb-2"><iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe></div>`;
        } else if (data.url) {
            html += `<div class="mb-2"><a href="${data.url}" target="_blank" rel="noopener noreferrer" class="text-truncate d-block">${data.url}</a></div>`;
        }
        html += '</div>';
        return html;
    },

    /**
     * Validate embed data.
     * @param {object} data - Block data.
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validate(data) {
        const errors = [];
        if (!data.url || data.url.trim() === '') {
            errors.push('Embed URL is required.');
        }
        return { valid: errors.length === 0, errors };
    },
});

// ---------------------------------------------------------------------------
// 7. Separator
// ---------------------------------------------------------------------------
BlockRegistry.register('separator', {
    name: 'Separator',
    icon: `<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
        <line x1="1" y1="8" x2="15" y2="8"/>
    </svg>`,

    category: 'layout',

    defaultData: { style: 'hr' },

    /**
     * Render separator for published view.
     * @param {object} data - Block data.
     * @param {string} data.style - Separator style: 'hr' or 'decorative'.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    render(data) {
        if (data.style === 'decorative') {
            return '<div class="block-separator decorative"></div>';
        }
        return '<hr class="block-separator">';
    },

    /**
     * Render separator for editor canvas.
     * @param {object} data - Block data.
     * @param {string} data.style - Separator style.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    edit(data, block) {
        const style = data.style || 'hr';
        if (style === 'decorative') {
            return '<div class="block-separator decorative my-2"></div>';
        }
        return '<hr class="block-separator my-2">';
    },

    /**
     * Render separator-specific toolbar controls.
     * @param {object} data - Block data.
     * @param {string} data.style - Current style.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    toolbar(data, block) {
        const style = data.style || 'hr';
        return `<div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary${style === 'hr' ? ' active' : ''}" data-separator-style="hr">Line</button>
            <button type="button" class="btn btn-outline-secondary${style === 'decorative' ? ' active' : ''}" data-separator-style="decorative">Decorative</button>
        </div>`;
    },

    /**
     * Validate separator data.
     * @param {object} data - Block data.
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validate(data) {
        return { valid: true, errors: [] };
    },
});

// ---------------------------------------------------------------------------
// 8. Button
// ---------------------------------------------------------------------------
BlockRegistry.register('button', {
    name: 'Button',
    icon: `<svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 3l4 5-4 5"/>
        <line x1="9" y1="13" x2="13" y2="13"/>
    </svg>`,

    category: 'widgets',

    defaultData: { text: 'Click here', url: '', style: 'primary' },

    /**
     * Render button for published view.
     * @param {object} data - Block data.
     * @param {string} data.text - Button label.
     * @param {string} data.url - Button href.
     * @param {string} data.style - Bootstrap style: primary, secondary, outline, link.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    render(data) {
        const text = data.text || 'Click here';
        const url = data.url || '#';
        const style = data.style || 'primary';
        return `<a href="${url}" class="btn btn-${style}">${text}</a>`;
    },

    /**
     * Render button for editor canvas.
     * @param {object} data - Block data.
     * @param {string} data.text - Button label.
     * @param {string} data.url - Button href.
     * @param {string} data.style - Bootstrap style.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    edit(data, block) {
        const text = data.text || 'Click here';
        const url = data.url || '';
        const style = data.style || 'primary';
        let html = '<div class="block-button-editor">';
        html += `<div class="mb-2"><a href="#" class="btn btn-${style}" onclick="return false;"><span contenteditable="true" class="block-button-text" data-placeholder="Button text">${text}</span></a></div>`;
        html += `<input type="url" class="form-control form-control-sm" data-field="url" placeholder="Button URL" value="${url}">`;
        html += '</div>';
        return html;
    },

    /**
     * Render button-specific toolbar controls.
     * @param {object} data - Block data.
     * @param {string} data.style - Current button style.
     * @param {object} block - Full block object.
     * @returns {string} HTML string.
     */
    toolbar(data, block) {
        const style = data.style || 'primary';
        const options = [
            { value: 'primary', label: 'Primary' },
            { value: 'secondary', label: 'Secondary' },
            { value: 'outline', label: 'Outline' },
            { value: 'link', label: 'Link' },
        ];
        let html = '<div class="btn-group btn-group-sm">';
        options.forEach(opt => {
            const active = opt.value === style ? ' active' : '';
            html += `<button type="button" class="btn btn-outline-secondary${active}" data-button-style="${opt.value}">${opt.label}</button>`;
        });
        html += '</div>';
        return html;
    },

    /**
     * Validate button data.
     * @param {object} data - Block data.
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validate(data) {
        const errors = [];
        if (!data.text || data.text.trim() === '') {
            errors.push('Button text is required.');
        }
        if (!data.url || data.url.trim() === '') {
            errors.push('Button URL is required.');
        }
        return { valid: errors.length === 0, errors };
    },
});
