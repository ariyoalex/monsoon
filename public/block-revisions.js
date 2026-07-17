/**
 * Monsoon CMS - Block Revisions Browser
 * Loads after block-registry.js, block-editor.js, block-toolbar.js.
 * Provides a modal UI for browsing, previewing, and restoring block revisions.
 * Vanilla JS only — no frameworks.
 */

const BlockRevisions = {
    /** @type {Array<{id: number, snapshot: string, created_at: string}>} */
    revisions: [],

    /** @type {object|null} Currently selected revision object. */
    currentRevision: null,

    /** @type {HTMLElement|null} The overlay modal element. */
    overlay: null,

    /** @type {string|null} The content ID for the current editor. */
    contentId: null,

    // -------------------------------------------------------------------------
    // Initialisation
    // -------------------------------------------------------------------------

    /**
     * Initialise the revisions browser for a given content ID.
     * Injects CSS and prepares the modal container.
     * @param {string} contentId - The content ID being edited.
     */
    init(contentId) {
        this.contentId = contentId;
        this._injectStyles();
    },

    // -------------------------------------------------------------------------
    // Styles
    // -------------------------------------------------------------------------

    /**
     * Inject the revisions browser CSS into the document head.
     * @private
     */
    _injectStyles() {
        if (document.getElementById('block-revisions-styles')) return;
        const style = document.createElement('style');
        style.id = 'block-revisions-styles';
        style.textContent = `
.block-revisions-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}
.block-revisions-modal {
    background: #fff;
    border-radius: 12px;
    width: 90vw;
    max-width: 1100px;
    height: 80vh;
    display: flex;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
}
.block-revisions-list {
    width: 320px;
    border-right: 1px solid #dee2e6;
    overflow-y: auto;
    padding: 0;
}
.block-revisions-list-header {
    padding: 16px;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}
.block-revisions-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
}
.block-revisions-item:hover {
    background: #f0f4ff;
}
.block-revisions-item.active {
    background: #e8edff;
    border-left: 3px solid #1034A6;
}
.block-revisions-item .revision-date {
    font-size: 13px;
    font-weight: 500;
}
.block-revisions-item .revision-author {
    font-size: 12px;
    color: #6c757d;
}
.block-revisions-preview {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
}
.block-revisions-actions {
    padding: 16px;
    border-top: 1px solid #dee2e6;
    text-align: right;
}
.block-revisions-empty {
    color: #adb5bd;
    text-align: center;
    padding: 48px;
}
`;
        document.head.appendChild(style);
    },

    // -------------------------------------------------------------------------
    // API
    // -------------------------------------------------------------------------

    /**
     * Load revisions from the server via AJAX.
     * Populates the revisions list.
     * @returns {Promise<void>}
     */
    async loadRevisions() {
        if (!this.contentId) return;

        try {
            const response = await fetch(`/api/v1/content/${this.contentId}/revisions`);
            const json = await response.json();
            this.revisions = Array.isArray(json.data) ? json.data : [];
        } catch (err) {
            console.error('BlockRevisions: failed to load revisions', err);
            this.revisions = [];
        }
    },

    // -------------------------------------------------------------------------
    // Browser modal
    // -------------------------------------------------------------------------

    /**
     * Show the revisions browser modal.
     * Fetches revisions, builds the DOM, and renders the list.
     * @returns {Promise<void>}
     */
    async showBrowser() {
        await this.loadRevisions();
        this._renderOverlay();
    },

    /**
     * Hide and remove the revisions browser modal.
     */
    hideBrowser() {
        if (this.overlay) {
            this.overlay.remove();
            this.overlay = null;
        }
        this.currentRevision = null;
    },

    // -------------------------------------------------------------------------
    // Preview
    // -------------------------------------------------------------------------

    /**
     * Preview a specific revision by rendering its blocks into the preview panel.
     * @param {number} id - The revision ID to preview.
     */
    previewRevision(id) {
        const revision = this.revisions.find(r => r.id === id);
        if (!revision) return;

        this.currentRevision = revision;

        // Update active state in list
        if (this.overlay) {
            this.overlay.querySelectorAll('.block-revisions-item').forEach(item => {
                item.classList.toggle('active', parseInt(item.dataset.revisionId, 10) === id);
            });
        }

        this._renderPreview(revision);
    },

    // -------------------------------------------------------------------------
    // Restore
    // -------------------------------------------------------------------------

    /**
     * Restore the editor to a specific revision.
     * Parses the revision snapshot, loads it into BlockEditor, and closes the modal.
     * @param {number} id - The revision ID to restore.
     */
    restoreRevision(id) {
        const revision = this.revisions.find(r => r.id === id);
        if (!revision) return;

        let blocks;
        try {
            blocks = JSON.parse(revision.snapshot);
        } catch (err) {
            console.error('BlockRevisions: failed to parse snapshot', err);
            blocks = [];
        }

        if (Array.isArray(blocks)) {
            BlockEditor.loadJSON(blocks);
        }

        this.hideBrowser();
        Monsoon.toast('Revision restored', 'success');
    },

    // -------------------------------------------------------------------------
    // Private rendering helpers
    // -------------------------------------------------------------------------

    /**
     * Build and display the overlay modal.
     * @private
     */
    _renderOverlay() {
        this.hideBrowser();

        const overlay = document.createElement('div');
        overlay.className = 'block-revisions-overlay';
        overlay.innerHTML = `
            <div class="block-revisions-modal">
                <div class="block-revisions-list">
                    <div class="block-revisions-list-header">
                        <span>Revisions</span>
                        <button type="button" class="btn-close float-end" aria-label="Close"></button>
                    </div>
                    <div class="block-revisions-list-items"></div>
                </div>
                <div style="flex:1;display:flex;flex-direction:column;">
                    <div class="block-revisions-preview">
                        <div class="block-revisions-empty">Select a revision to preview</div>
                    </div>
                    <div class="block-revisions-actions" style="display:none;">
                        <button type="button" class="btn btn-primary btn-sm block-revisions-restore-btn">
                            Restore this revision
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.overlay = overlay;
        document.body.appendChild(overlay);

        this._renderList();
        this._bindOverlayEvents();
    },

    /**
     * Render the list of revisions inside the left panel.
     * @private
     */
    _renderList() {
        if (!this.overlay) return;
        const container = this.overlay.querySelector('.block-revisions-list-items');
        if (!container) return;

        if (this.revisions.length === 0) {
            container.innerHTML = '<div class="block-revisions-empty">No revisions available</div>';
            return;
        }

        let html = '';
        this.revisions.forEach(revision => {
            const date = this._formatDate(revision.created_at);
            const author = revision.author || 'Unknown';
            html += `
                <div class="block-revisions-item" data-revision-id="${revision.id}">
                    <div class="revision-date">${this._escapeHtml(date)}</div>
                    <div class="revision-author">${this._escapeHtml(author)}</div>
                </div>
            `;
        });
        container.innerHTML = html;
    },

    /**
     * Render a revision's blocks into the preview panel.
     * Uses BlockRegistry.renderBlock() for each block.
     * @param {object} revision - The revision object.
     * @private
     */
    _renderPreview(revision) {
        if (!this.overlay) return;
        const previewPanel = this.overlay.querySelector('.block-revisions-preview');
        const actionsPanel = this.overlay.querySelector('.block-revisions-actions');
        if (!previewPanel) return;

        let blocks;
        try {
            blocks = JSON.parse(revision.snapshot);
        } catch (err) {
            previewPanel.innerHTML = '<div class="block-revisions-empty">Failed to parse revision snapshot</div>';
            if (actionsPanel) actionsPanel.style.display = 'none';
            return;
        }

        if (!Array.isArray(blocks) || blocks.length === 0) {
            previewPanel.innerHTML = '<div class="block-revisions-empty">This revision contains no blocks</div>';
            if (actionsPanel) actionsPanel.style.display = 'none';
            return;
        }

        let html = '';
        blocks.forEach(block => {
            html += BlockRegistry.renderBlock(block);
        });

        previewPanel.innerHTML = html;
        if (actionsPanel) {
            actionsPanel.style.display = 'block';
            actionsPanel.dataset.revisionId = revision.id;
        }
    },

    /**
     * Bind event listeners on the overlay modal.
     * @private
     */
    _bindOverlayEvents() {
        if (!this.overlay) return;

        // Close button (X)
        const closeBtn = this.overlay.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hideBrowser());
        }

        // Overlay background click
        this.overlay.addEventListener('click', e => {
            if (e.target === this.overlay) {
                this.hideBrowser();
            }
        });

        // Revision item click
        this.overlay.querySelectorAll('.block-revisions-item').forEach(item => {
            item.addEventListener('click', () => {
                const id = parseInt(item.dataset.revisionId, 10);
                if (id) this.previewRevision(id);
            });
        });

        // Restore button
        const restoreBtn = this.overlay.querySelector('.block-revisions-restore-btn');
        if (restoreBtn) {
            restoreBtn.addEventListener('click', () => {
                const id = parseInt(restoreBtn.parentElement.dataset.revisionId, 10);
                if (id && confirm('Restore this revision? Current unsaved changes will be lost.')) {
                    this.restoreRevision(id);
                }
            });
        }

        // Escape key
        this._escHandler = e => {
            if (e.key === 'Escape' && this.overlay) {
                this.hideBrowser();
            }
        };
        document.addEventListener('keydown', this._escHandler);
    },

    // -------------------------------------------------------------------------
    // Utility
    // -------------------------------------------------------------------------

    /**
     * Format a date string for display in the revisions list.
     * @param {string} dateString - ISO date string.
     * @returns {string} Formatted date string.
     * @private
     */
    _formatDate(dateString) {
        if (!dateString) return 'Unknown date';
        try {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMin = Math.floor(diffMs / 60000);
            const diffHr = Math.floor(diffMs / 3600000);
            const diffDay = Math.floor(diffMs / 86400000);

            if (diffMin < 1) return 'Just now';
            if (diffMin < 60) return `${diffMin} minute${diffMin !== 1 ? 's' : ''} ago`;
            if (diffHr < 24) return `${diffHr} hour${diffHr !== 1 ? 's' : ''} ago`;
            if (diffDay < 7) return `${diffDay} day${diffDay !== 1 ? 's' : ''} ago`;

            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        } catch {
            return dateString;
        }
    },

    /**
     * Escape HTML to prevent XSS.
     * @param {string} text - The text to escape.
     * @returns {string} Escaped HTML string.
     * @private
     */
    _escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
};
