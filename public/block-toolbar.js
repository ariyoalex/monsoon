/**
 * Monsoon CMS - Block Toolbar
 * Floating toolbar, slash commands, keyboard shortcuts, undo/redo, and auto-save
 * for the block editor. Loads after block-registry.js and block-editor.js.
 */

const BlockToolbar = {
    /** @type {HTMLElement|null} Floating toolbar element. */
    toolbarEl: null,

    /** @type {HTMLElement|null} Slash command menu element. */
    slashMenu: null,

    /** @type {number|null} Auto-save timeout handle. */
    autoSaveTimer: null,

    /** @type {string|null} Last saved JSON string for dirty detection. */
    lastSavedJSON: null,

    /** @type {boolean} Whether the editor has unsaved changes. */
    isDirty: false,

    /** @type {Array<string>} Undo history stack (JSON snapshots). */
    history: [],

    /** @type {number} Current index in the history stack. */
    historyIndex: -1,

    /** @type {HTMLElement|null} Save status indicator element. */
    saveStatusEl: null,

    /** @type {number} Maximum number of undo states to retain. */
    maxHistory: 50,

    /** @type {string[]} Text block types that support formatting. */
    textBlockTypes: ['paragraph', 'heading', 'list', 'quote'],

    /**
     * Initialize the toolbar system. Injects CSS, creates DOM elements,
     * binds event listeners, and registers the auto-save callback.
     */
    init() {
        this._injectCSS();
        this._createToolbar();
        this._createSlashMenu();
        this._createSaveStatus();
        this._bindEvents();
        this._pushHistory();
        BlockEditor.onBlocksChanged(() => {
            this._pushHistory();
            this.checkDirty();
            this.scheduleAutoSave();
        });
    },

    /**
     * Inject all required CSS for toolbar, slash menu, and save status.
     * @private
     */
    _injectCSS() {
        if (document.getElementById('block-toolbar-styles')) return;
        const style = document.createElement('style');
        style.id = 'block-toolbar-styles';
        style.textContent = `
.block-editor-floating-toolbar { position: fixed; z-index: 1000; background: #1a1a1a; color: #fff; border-radius: 8px; padding: 6px 12px; display: flex; align-items: center; gap: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); opacity: 0; pointer-events: none; transition: opacity 0.15s, transform 0.15s; transform: translateY(4px); }
.block-editor-floating-toolbar.visible { opacity: 1; pointer-events: auto; transform: translateY(0); }
.toolbar-block-type { font-size: 12px; color: #adb5bd; padding: 0 8px; white-space: nowrap; }
.toolbar-divider { width: 1px; height: 20px; background: #495057; margin: 0 4px; }
.toolbar-btn { width: 32px; height: 32px; border: none; background: transparent; color: #fff; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; }
.toolbar-btn:hover { background: #495057; }
.toolbar-btn.active { background: #1034A6; }
.toolbar-block-specific { display: flex; align-items: center; gap: 4px; }
.block-editor-slash-menu { position: fixed; z-index: 1001; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); width: 280px; max-height: 320px; overflow-y: auto; }
.slash-menu-header { padding: 8px; border-bottom: 1px solid #dee2e6; }
.slash-menu-header input { width: 100%; padding: 6px 10px; border: 1px solid #dee2e6; border-radius: 4px; font-size: 13px; }
.slash-menu-body { padding: 4px; }
.slash-category-title { font-size: 10px; text-transform: uppercase; color: #6c757d; padding: 6px 10px 2px; font-weight: 600; }
.slash-item { display: flex; align-items: center; gap: 8px; width: 100%; padding: 6px 10px; border: none; background: none; cursor: pointer; border-radius: 4px; text-align: left; font-size: 13px; }
.slash-item:hover { background: #f0f4ff; }
.slash-item-icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; background: #f0f4ff; border-radius: 4px; color: #1034A6; font-size: 11px; }
.slash-item-name { flex: 1; }
.slash-item-shortcut { font-size: 11px; color: #adb5bd; }
.block-editor-save-status { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #6c757d; }
.block-editor-save-status.saving { color: #6c757d; }
.block-editor-save-status.saved { color: #198754; }
.block-editor-save-status.error { color: #dc3545; }
.block-editor-save-status.dirty { color: #fd7e14; }
.block-editor-save-status .save-indicator { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
.block-editor-save-status.saving .save-indicator { animation: pulse 1s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
`;
        document.head.appendChild(style);
    },

    /**
     * Create the floating toolbar DOM element and append to body.
     * @private
     */
    _createToolbar() {
        this.toolbarEl = document.createElement('div');
        this.toolbarEl.className = 'block-editor-floating-toolbar';
        this.toolbarEl.setAttribute('role', 'toolbar');
        this.toolbarEl.setAttribute('aria-label', 'Block formatting toolbar');
        document.body.appendChild(this.toolbarEl);
    },

    /**
     * Create the slash command menu DOM element and append to body.
     * @private
     */
    _createSlashMenu() {
        this.slashMenu = document.createElement('div');
        this.slashMenu.className = 'block-editor-slash-menu';
        this.slashMenu.style.display = 'none';
        this.slashMenu.innerHTML = `
            <div class="slash-menu-header">
                <input type="text" placeholder="Search blocks..." class="slash-search-input">
            </div>
            <div class="slash-menu-body"></div>
        `;
        document.body.appendChild(this.slashMenu);
    },

    /**
     * Create the save status indicator element.
     * @private
     */
    _createSaveStatus() {
        this.saveStatusEl = document.createElement('span');
        this.saveStatusEl.className = 'block-editor-save-status';
        this.saveStatusEl.innerHTML = '<span class="save-indicator"></span><span class="save-text"></span>';
        const header = document.querySelector('.editor-header') || document.querySelector('.page-header') || document.querySelector('header');
        if (header) {
            header.appendChild(this.saveStatusEl);
        }
    },

    /**
     * Bind all event listeners for toolbar, slash menu, and keyboard shortcuts.
     * @private
     */
    _bindEvents() {
        document.addEventListener('block-editor:block-selected', (e) => {
            this.showToolbar(e.detail.blockId);
        });

        document.addEventListener('block-editor:block-deselected', () => {
            this.hideToolbar();
        });

        window.addEventListener('scroll', () => {
            if (this.toolbarEl && this.toolbarEl.classList.contains('visible')) {
                this._positionToolbar();
            }
        }, { passive: true });

        window.addEventListener('resize', () => {
            if (this.toolbarEl && this.toolbarEl.classList.contains('visible')) {
                this._positionToolbar();
            }
        });

        document.addEventListener('keydown', (e) => this._handleKeydown(e));

        document.addEventListener('click', (e) => {
            if (this.slashMenu && this.slashMenu.style.display !== 'none') {
                if (!this.slashMenu.contains(e.target)) {
                    this.hideSlashMenu();
                }
            }
        });

        document.addEventListener('input', (e) => {
            if (e.target.closest && e.target.closest('[contenteditable]')) {
                this._handleContentInput(e.target);
            }
        });

        this.toolbarEl.addEventListener('click', (e) => {
            const btn = e.target.closest('.toolbar-btn');
            if (btn && btn.dataset.action) {
                this._handleToolbarAction(btn.dataset.action, btn.dataset.value);
            }
        });

        const searchInput = this.slashMenu.querySelector('.slash-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', () => this._filterSlashMenu(searchInput.value));
            searchInput.addEventListener('keydown', (e) => this._handleSlashSearchKeydown(e));
        }

        this.slashMenu.addEventListener('click', (e) => {
            const item = e.target.closest('.slash-item');
            if (item && item.dataset.type) {
                const afterId = this.slashMenu.dataset.afterBlockId || null;
                this.handleSlashCommand(item.dataset.type, afterId);
            }
        });
    },

    /**
     * Show the floating toolbar positioned above the selected block.
     * @param {string} blockId - The ID of the selected block.
     */
    showToolbar(blockId) {
        const block = BlockEditor.getBlock(blockId);
        if (!block) return;

        const def = BlockRegistry.get(block.type);
        if (!def) return;

        const isText = this.textBlockTypes.includes(block.type);
        let html = '';

        html += `<span class="toolbar-block-type">${def.name || block.type}</span>`;

        if (isText) {
            html += '<div class="toolbar-divider"></div>';
            html += this._formatButton('bold', 'B', 'Bold (Ctrl+B)');
            html += this._formatButton('italic', 'I', 'Italic (Ctrl+I)');
            html += this._formatButton('underline', 'U', 'Underline (Ctrl+U)');
            html += this._formatButton('strikeThrough', 'S', 'Strikethrough');
            html += '<div class="toolbar-divider"></div>';
            html += this._formatButton('justifyLeft', '⫷', 'Align left');
            html += this._formatButton('justifyCenter', '⫿', 'Align center');
            html += this._formatButton('justifyRight', '⫸', 'Align right');
            html += '<div class="toolbar-divider"></div>';
            html += this._formatButton('createLink', '🔗', 'Insert link');
        }

        if (def.toolbar) {
            html += '<div class="toolbar-divider"></div>';
            html += `<div class="toolbar-block-specific">${def.toolbar(block.data || {}, block)}</div>`;
        }

        this.toolbarEl.innerHTML = html;
        this.toolbarEl.dataset.blockId = blockId;
        this._positionToolbar();
        this.toolbarEl.classList.add('visible');
    },

    /**
     * Create a toolbar button element for a formatting command.
     * @param {string} action - The execCommand action name.
     * @param {string} label - Button display text/icon.
     * @param {string} title - Tooltip text.
     * @returns {string} HTML string for the button.
     * @private
     */
    _formatButton(action, label, title) {
        return `<button type="button" class="toolbar-btn" data-action="${action}" title="${title}">${label}</button>`;
    },

    /**
     * Position the toolbar above the currently selected block element.
     * @private
     */
    _positionToolbar() {
        const blockId = this.toolbarEl.dataset.blockId;
        if (!blockId) return;
        const blockEl = document.querySelector(`[data-block-id="${blockId}"]`);
        if (!blockEl) return;

        const rect = blockEl.getBoundingClientRect();
        const toolbarHeight = this.toolbarEl.offsetHeight || 44;
        let top = rect.top - toolbarHeight - 8;
        let left = rect.left;

        if (top < 0) {
            top = rect.bottom + 8;
        }
        if (left + this.toolbarEl.offsetWidth > window.innerWidth) {
            left = window.innerWidth - this.toolbarEl.offsetWidth - 8;
        }
        if (left < 0) left = 8;

        this.toolbarEl.style.top = `${top}px`;
        this.toolbarEl.style.left = `${left}px`;
    },

    /**
     * Hide the floating toolbar.
     */
    hideToolbar() {
        if (this.toolbarEl) {
            this.toolbarEl.classList.remove('visible');
            this.toolbarEl.dataset.blockId = '';
        }
    },

    /**
     * Handle a toolbar button action.
     * @param {string} action - The action identifier.
     * @param {string} [value] - Optional value for the command.
     * @private
     */
    _handleToolbarAction(action, value) {
        const blockId = this.toolbarEl.dataset.blockId;
        if (!blockId) return;

        if (action === 'createLink') {
            const url = prompt('Enter URL:');
            if (url) {
                document.execCommand('createLink', false, url);
            }
            return;
        }

        if (action.startsWith('data-')) {
            return;
        }

        document.execCommand(action, false, value || null);
    },

    /**
     * Show the slash command menu at the given coordinates.
     * @param {number} x - Horizontal position in viewport pixels.
     * @param {number} y - Vertical position in viewport pixels.
     * @param {string} afterBlockId - ID of the block to insert after.
     */
    showSlashMenu(x, y, afterBlockId) {
        this._renderSlashMenuItems('');
        this.slashMenu.dataset.afterBlockId = afterBlockId || '';

        let menuLeft = x;
        let menuTop = y;
        const menuWidth = 280;
        const menuHeight = 320;

        if (menuLeft + menuWidth > window.innerWidth) {
            menuLeft = window.innerWidth - menuWidth - 8;
        }
        if (menuTop + menuHeight > window.innerHeight) {
            menuTop = y - menuHeight - 24;
        }
        if (menuLeft < 0) menuLeft = 8;
        if (menuTop < 0) menuTop = 8;

        this.slashMenu.style.left = `${menuLeft}px`;
        this.slashMenu.style.top = `${menuTop}px`;
        this.slashMenu.style.display = 'block';

        const input = this.slashMenu.querySelector('.slash-search-input');
        if (input) {
            input.value = '';
            input.focus();
        }
    },

    /**
     * Hide the slash command menu.
     */
    hideSlashMenu() {
        if (this.slashMenu) {
            this.slashMenu.style.display = 'none';
        }
    },

    /**
     * Render the slash menu items, optionally filtered by a search query.
     * @param {string} query - Filter string to match block names.
     * @private
     */
    _renderSlashMenuItems(query) {
        const body = this.slashMenu.querySelector('.slash-menu-body');
        const categories = BlockRegistry.getByCategory();
        const q = query.toLowerCase().trim();
        let html = '';

        for (const [category, types] of Object.entries(categories)) {
            let itemsHtml = '';
            for (const type of types) {
                const def = BlockRegistry.get(type);
                if (!def) continue;
                if (q && !def.name.toLowerCase().includes(q)) continue;
                const iconHtml = def.icon || '';
                itemsHtml += `
                    <button type="button" class="slash-item" data-type="${type}">
                        <span class="slash-item-icon">${iconHtml}</span>
                        <span class="slash-item-name">${def.name}</span>
                    </button>
                `;
            }
            if (itemsHtml) {
                html += `<div class="slash-category-title">${category}</div>${itemsHtml}`;
            }
        }

        if (!html) {
            html = '<div class="p-3 text-muted text-center" style="font-size:13px;">No blocks found</div>';
        }

        body.innerHTML = html;
    },

    /**
     * Filter slash menu items based on search input.
     * @param {string} query - The current search text.
     * @private
     */
    _filterSlashMenu(query) {
        this._renderSlashMenuItems(query);
    },

    /**
     * Handle keyboard events within the slash menu search input.
     * @param {KeyboardEvent} e - The keyboard event.
     * @private
     */
    _handleSlashSearchKeydown(e) {
        if (e.key === 'Escape') {
            this.hideSlashMenu();
            e.preventDefault();
            return;
        }
        if (e.key === 'Enter') {
            const items = this.slashMenu.querySelectorAll('.slash-item');
            if (items.length > 0) {
                const first = items[0];
                const afterId = this.slashMenu.dataset.afterBlockId || null;
                this.handleSlashCommand(first.dataset.type, afterId);
            }
            e.preventDefault();
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const items = this.slashMenu.querySelectorAll('.slash-item');
            if (items.length > 0) items[0].focus();
            return;
        }
    },

    /**
     * Handle a slash command selection. Removes the slash character, inserts
     * the new block, and closes the menu.
     * @param {string} type - Block type to insert.
     * @param {string|null} afterBlockId - ID of the block to insert after.
     */
    handleSlashCommand(type, afterBlockId) {
        const sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            const range = sel.getRangeAt(0);
            const container = range.startContainer;
            if (container.nodeType === Node.TEXT_NODE && container.textContent === '/') {
                container.textContent = '';
            } else if (container.nodeType === Node.TEXT_NODE && container.textContent.endsWith('/')) {
                container.textContent = container.textContent.slice(0, -1);
            }
        }

        BlockEditor.addBlock(type, afterBlockId);
        this.hideSlashMenu();
    },

    /**
     * Handle input events on contenteditable elements. Detects a lone "/"
     * to trigger the slash menu.
     * @param {HTMLElement} el - The contenteditable element receiving input.
     * @private
     */
    _handleContentInput(el) {
        const text = el.textContent;
        if (text !== '/') return;

        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) return;

        const range = sel.getRangeAt(0);
        const rect = range.getBoundingClientRect();

        const blockEl = el.closest('[data-block-id]');
        const afterBlockId = blockEl ? blockEl.dataset.blockId : null;

        this.showSlashMenu(rect.left, rect.bottom + 4, afterBlockId);
    },

    /**
     * Handle keydown events for keyboard shortcuts, navigation, and block deletion.
     * @param {KeyboardEvent} e - The keyboard event.
     * @private
     */
    _handleKeydown(e) {
        const isMod = e.ctrlKey || e.metaKey;

        if (isMod && e.key === 's') {
            e.preventDefault();
            this.save();
            return;
        }

        if (isMod && e.shiftKey && e.key === 'z') {
            e.preventDefault();
            this._redo();
            return;
        }

        if (isMod && e.key === 'z') {
            e.preventDefault();
            this._undo();
            return;
        }

        if (isMod && e.key === 'b') {
            e.preventDefault();
            document.execCommand('bold', false, null);
            return;
        }

        if (isMod && e.key === 'i') {
            e.preventDefault();
            document.execCommand('italic', false, null);
            return;
        }

        if (isMod && e.key === 'u') {
            e.preventDefault();
            document.execCommand('underline', false, null);
            return;
        }

        if (e.key === 'Escape' && this.slashMenu.style.display !== 'none') {
            this.hideSlashMenu();
            return;
        }

        if ((e.key === 'Delete' || e.key === 'Backspace') && !isMod) {
            const active = document.activeElement;
            if (active && active.getAttribute('contenteditable') === 'true') {
                const text = active.textContent.trim();
                if (text === '') {
                    const blockEl = active.closest('[data-block-id]');
                    if (blockEl) {
                        e.preventDefault();
                        BlockEditor.removeBlock(blockEl.dataset.blockId);
                        return;
                    }
                }
            }
        }

        if (e.key === 'ArrowUp' && !isMod) {
            const active = document.activeElement;
            if (active && active.getAttribute('contenteditable') === 'true') {
                const sel = window.getSelection();
                if (sel && sel.rangeCount > 0) {
                    const range = sel.getRangeAt(0);
                    if (this._isAtTopOfElement(active, range)) {
                        e.preventDefault();
                        this._selectAdjacentBlock('prev');
                        return;
                    }
                }
            }
        }

        if (e.key === 'ArrowDown' && !isMod) {
            const active = document.activeElement;
            if (active && active.getAttribute('contenteditable') === 'true') {
                const sel = window.getSelection();
                if (sel && sel.rangeCount > 0) {
                    const range = sel.getRangeAt(0);
                    if (this._isAtBottomOfElement(active, range)) {
                        e.preventDefault();
                        this._selectAdjacentBlock('next');
                        return;
                    }
                }
            }
        }
    },

    /**
     * Determine if the caret is at the very top of an element.
     * @param {HTMLElement} el - The contenteditable element.
     * @param {Range} range - The current selection range.
     * @returns {boolean}
     * @private
     */
    _isAtTopOfElement(el, range) {
        if (range.startOffset !== 0) return false;
        const container = range.startContainer;
        if (container === el) return true;
        if (container.nodeType === Node.TEXT_NODE && range.startOffset === 0) {
            let node = container;
            while (node && node !== el) {
                if (node.previousSibling) return false;
                node = node.parentNode;
            }
            return true;
        }
        return false;
    },

    /**
     * Determine if the caret is at the very bottom of an element.
     * @param {HTMLElement} el - The contenteditable element.
     * @param {Range} range - The current selection range.
     * @returns {boolean}
     * @private
     */
    _isAtBottomOfElement(el, range) {
        const container = range.startContainer;
        if (container === el) {
            return range.startOffset >= el.childNodes.length;
        }
        if (container.nodeType === Node.TEXT_NODE) {
            return range.startOffset >= container.textContent.length;
        }
        return false;
    },

    /**
     * Select the previous or next block in the editor.
     * @param {string} direction - 'prev' or 'next'.
     * @private
     */
    _selectAdjacentBlock(direction) {
        const blocks = document.querySelectorAll('[data-block-id]');
        if (blocks.length === 0) return;

        const current = document.activeElement.closest('[data-block-id]');
        if (!current) return;

        const arr = Array.from(blocks);
        const idx = arr.indexOf(current);
        if (idx === -1) return;

        const targetIdx = direction === 'prev' ? idx - 1 : idx + 1;
        if (targetIdx < 0 || targetIdx >= arr.length) return;

        const targetBlock = arr[targetIdx];
        const targetId = targetBlock.dataset.blockId;
        BlockEditor.selectBlock(targetId);
    },

    /**
     * Save the current editor content to the server.
     */
    async save() {
        const id = this._getContentId();
        if (!id) return;

        this._setSaveStatus('saving');

        try {
            const titleInput = document.querySelector('input[name="title"]') || document.querySelector('#page-title');
            const title = titleInput ? titleInput.value : '';
            const content = BlockEditor.getJSON();

            const csrfToken = Monsoon.getCsrfToken();

            const response = await fetch(`/api/v1/content/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ title, content }),
            });

            if (!response.ok) {
                throw new Error('Save failed');
            }

            this.lastSavedJSON = JSON.stringify(content);
            this.isDirty = false;
            this._setSaveStatus('saved');
        } catch (err) {
            console.error('BlockToolbar save error:', err);
            this._setSaveStatus('error');
        }
    },

    /**
     * Check whether the editor has unsaved changes by comparing the current
     * JSON output to the last saved snapshot.
     * @returns {boolean} True if the editor content has changed.
     */
    checkDirty() {
        const current = JSON.stringify(BlockEditor.getJSON());
        if (this.lastSavedJSON === null) {
            this.isDirty = false;
        } else {
            this.isDirty = current !== this.lastSavedJSON;
        }
        if (this.isDirty) {
            this._setSaveStatus('dirty');
        }
        return this.isDirty;
    },

    /**
     * Schedule an auto-save 3 seconds from now. Resets the timer on each call.
     */
    scheduleAutoSave() {
        if (this.autoSaveTimer !== null) {
            clearTimeout(this.autoSaveTimer);
        }
        this.autoSaveTimer = setTimeout(() => {
            if (this.isDirty) {
                this.save();
            }
            this.autoSaveTimer = null;
        }, 3000);
    },

    /**
     * Push the current editor state onto the undo history stack.
     * @private
     */
    _pushHistory() {
        const json = JSON.stringify(BlockEditor.getJSON());

        if (this.historyIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.historyIndex + 1);
        }

        this.history.push(json);

        if (this.history.length > this.maxHistory) {
            this.history.shift();
        }

        this.historyIndex = this.history.length - 1;
    },

    /**
     * Restore the previous state from the undo history stack.
     * @private
     */
    _undo() {
        if (this.historyIndex <= 0) return;
        this.historyIndex--;
        this._restoreHistory();
    },

    /**
     * Restore the next state from the redo history stack.
     * @private
     */
    _redo() {
        if (this.historyIndex >= this.history.length - 1) return;
        this.historyIndex++;
        this._restoreHistory();
    },

    /**
     * Restore the editor state at the current history index.
     * @private
     */
    _restoreHistory() {
        const json = this.history[this.historyIndex];
        if (!json) return;

        try {
            const data = JSON.parse(json);
            BlockEditor.loadJSON(data);
        } catch (err) {
            console.error('BlockToolbar history restore error:', err);
        }
    },

    /**
     * Get the content ID from the current page URL parameters.
     * @returns {string|null} The content ID or null if not found.
     * @private
     */
    _getContentId() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
    },

    /**
     * Update the save status indicator display.
     * @param {string} status - One of 'saving', 'saved', 'error', 'dirty'.
     * @private
     */
    _setSaveStatus(status) {
        if (!this.saveStatusEl) return;

        this.saveStatusEl.className = `block-editor-save-status ${status}`;
        const textEl = this.saveStatusEl.querySelector('.save-text');
        if (!textEl) return;

        const messages = {
            saving: 'Saving...',
            saved: 'Saved',
            error: 'Save failed',
            dirty: 'Unsaved changes',
        };

        textEl.textContent = messages[status] || '';

        if (status === 'saved') {
            setTimeout(() => {
                if (this.saveStatusEl.classList.contains('saved')) {
                    this.saveStatusEl.classList.remove('saved');
                    textEl.textContent = '';
                }
            }, 3000);
        }
    },
};
