/**
 * Monsoon CMS - Block Preview
 * Provides a "Preview" button in the content editor that opens
 * a full-page preview of the published content in a new browser tab.
 * Vanilla JS only — no frameworks.
 */

const BlockPreview = {
    /** @type {HTMLElement|null} The preview button element. */
    button: null,

    // -------------------------------------------------------------------------
    // Initialisation
    // -------------------------------------------------------------------------

    /**
     * Initialise the preview feature.
     * Locates the content ID and injects the preview button into the page header.
     * @returns {void}
     */
    init() {
        const contentId = this._getContentId();
        if (!contentId) return;

        this._injectStyles();
        this._createButton(contentId);
        this._bindEvents();
    },

    // -------------------------------------------------------------------------
    // Styles
    // -------------------------------------------------------------------------

    /**
     * Inject minimal CSS for the preview button.
     * @private
     */
    _injectStyles() {
        if (document.getElementById('block-preview-styles')) return;
        const style = document.createElement('style');
        style.id = 'block-preview-styles';
        style.textContent = `
.block-preview-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
`;
        document.head.appendChild(style);
    },

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Open a full-page preview for the given content ID in a new tab.
     * @param {string} contentId - The content ID to preview.
     */
    openPreview(contentId) {
        if (!contentId) return;
        const url = `/manage/content/${contentId}/preview`;
        window.open(url, '_blank');
    },

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Create the preview button and append it to the page header.
     * @param {string} contentId - The content ID for the preview link.
     * @private
     */
    _createButton(contentId) {
        const header = document.querySelector('.editor-header')
            || document.querySelector('.page-header')
            || document.querySelector('header');
        if (!header) return;

        // Check for an existing button to avoid duplicates
        if (header.querySelector('.block-preview-btn')) return;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-outline-secondary btn-sm block-preview-btn';
        btn.innerHTML = `
            <svg viewBox="0 0 16 16" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 8s3-5.5 7-5.5S15 8 15 8s-3 5.5-7 5.5S1 8 1 8z"/>
                <circle cx="8" cy="8" r="2.5"/>
            </svg>
            Preview
        `;
        btn.dataset.contentId = contentId;

        // Insert before the save button if present, otherwise append
        const saveBtn = header.querySelector('.btn-primary, [type="submit"]');
        if (saveBtn) {
            header.insertBefore(btn, saveBtn);
        } else {
            header.appendChild(btn);
        }

        this.button = btn;
    },

    /**
     * Bind the click event on the preview button.
     * @private
     */
    _bindEvents() {
        if (this.button) {
            this.button.addEventListener('click', () => {
                const contentId = this.button.dataset.contentId;
                this.openPreview(contentId);
            });
        }
    },

    /**
     * Get the content ID from the current page's hidden input or URL.
     * @returns {string|null} The content ID or null if not found.
     * @private
     */
    _getContentId() {
        // Check hidden input first
        const hiddenInput = document.getElementById('content-id')
            || document.querySelector('input[name="content_id"]')
            || document.querySelector('input[name="id"]');
        if (hiddenInput && hiddenInput.value) {
            return hiddenInput.value;
        }

        // Fall back to URL query parameter
        const params = new URLSearchParams(window.location.search);
        return params.get('id') || null;
    },
};
