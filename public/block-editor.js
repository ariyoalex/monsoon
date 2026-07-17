/**
 * Monsoon CMS - Block Editor Canvas
 * Loads after block-registry.js. Provides the editing canvas for block-based content.
 * Vanilla JS only — no frameworks.
 */

const BlockEditor = {
    /** @type {Array<{id: string, type: string, data: object}>} */
    blocks: [],

    /** @type {HTMLElement|null} */
    container: null,

    /** @type {string|null} */
    selectedBlockId: null,

    /** @type {{blockId: string, startY: number}|null} */
    dragState: null,

    /** @type {Array<function>} */
    _changeCallbacks: [],

    // -------------------------------------------------------------------------
    // Initialisation
    // -------------------------------------------------------------------------

    /**
     * Initialise the editor inside a container element.
     * @param {string} containerId - ID of the container DOM element.
     */
    init(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('BlockEditor: container not found:', containerId);
            return;
        }
        this._injectStyles();
        this.renderCanvas();
        this._bindCanvasEvents();
    },

    // -------------------------------------------------------------------------
    // Styles
    // -------------------------------------------------------------------------

    _injectStyles() {
        if (document.getElementById('block-editor-styles')) return;
        const style = document.createElement('style');
        style.id = 'block-editor-styles';
        style.textContent = `
.block-editor-canvas { border: 2px dashed #dee2e6; border-radius: 8px; padding: 16px; min-height: 400px; background: #fff; }
.block-editor-block { position: relative; border: 2px solid transparent; border-radius: 6px; margin: 8px 0; padding: 12px; cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s; }
.block-editor-block:hover { border-color: #adb5bd; }
.block-editor-block.selected { border-color: #1034A6; box-shadow: 0 0 0 3px rgba(16,52,166,0.15); }
.block-editor-block-actions { position: absolute; top: 8px; right: 8px; display: none; gap: 4px; }
.block-editor-block:hover .block-editor-block-actions, .block-editor-block.selected .block-editor-block-actions { display: flex; }
.block-editor-block-actions button { width: 28px; height: 28px; border: 1px solid #dee2e6; background: #fff; border-radius: 4px; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center; }
.block-editor-block-actions button:hover { background: #f8f9fa; }
.block-editor-block-actions .block-delete:hover { color: #dc3545; border-color: #dc3545; }
.block-editor-block-content { outline: none; }
.block-editor-add-block { text-align: center; padding: 16px; }
.block-add-btn { background: none; border: 2px dashed #dee2e6; padding: 8px 24px; border-radius: 6px; color: #6c757d; cursor: pointer; font-size: 14px; }
.block-add-btn:hover { border-color: #1034A6; color: #1034A6; }
.block-editor-add-menu { position: fixed; z-index: 1000; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); width: 320px; max-height: 400px; overflow-y: auto; }
.block-editor-add-menu-header { padding: 12px; border-bottom: 1px solid #dee2e6; }
.block-editor-add-menu-header input { width: 100%; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px; }
.block-editor-add-menu-body { padding: 8px; }
.block-category-title { font-size: 11px; text-transform: uppercase; color: #6c757d; padding: 8px 12px 4px; font-weight: 600; }
.block-type-btn { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 12px; border: none; background: none; cursor: pointer; border-radius: 4px; text-align: left; font-size: 14px; }
.block-type-btn:hover { background: #f0f4ff; }
.block-type-icon { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f0f4ff; border-radius: 4px; color: #1034A6; }
.block-drop-indicator { height: 3px; background: #1034A6; border-radius: 2px; margin: -2px 0; }
`;
        document.head.appendChild(style);
    },

    // -------------------------------------------------------------------------
    // Public getters / setters
    // -------------------------------------------------------------------------

    /** @returns {Array} */
    getBlocks() {
        return this.blocks;
    },

    /**
     * Replace all blocks and re-render.
     * @param {Array} blocksArray
     */
    setBlocks(blocksArray) {
        this.blocks = Array.isArray(blocksArray) ? blocksArray.slice() : [];
        this.selectedBlockId = null;
        this.renderCanvas();
        this._fireChange();
    },

    /**
     * Load blocks from a JSON string or array.
     * @param {string|Array} data - JSON string or array of blocks.
     */
    loadJSON(data) {
        let blocks;
        if (typeof data === 'string') {
            try {
                blocks = JSON.parse(data);
            } catch (e) {
                blocks = [];
            }
        } else {
            blocks = data;
        }
        this.setBlocks(Array.isArray(blocks) ? blocks : []);
    },

    /**
     * Find a block by ID.
     * @param {string} id
     * @returns {object|undefined}
     */
    getBlock(id) {
        return this.blocks.find(b => b.id === id);
    },

    // -------------------------------------------------------------------------
    // Block mutations
    // -------------------------------------------------------------------------

    /**
     * Add a new block of the given type.
     * @param {string} type
     * @param {string} [afterId] - Insert after this block ID (appends if omitted).
     * @param {object} [data] - Override default data.
     * @returns {object} The newly created block.
     */
    addBlock(type, afterId, data) {
        const def = BlockRegistry.get(type);
        const blockData = data
            ? JSON.parse(JSON.stringify(data))
            : BlockRegistry.getDefaultData(type);
        const block = { id: crypto.randomUUID(), type, data: blockData };

        if (afterId) {
            const idx = this.blocks.findIndex(b => b.id === afterId);
            if (idx !== -1) {
                this.blocks.splice(idx + 1, 0, block);
            } else {
                this.blocks.push(block);
            }
        } else {
            this.blocks.push(block);
        }

        this.renderCanvas();
        this.selectBlock(block.id);
        this._fireChange();
        return block;
    },

    /**
     * Remove a block after confirmation.
     * @param {string} id
     */
    removeBlock(id) {
        if (!confirm('Delete this block?')) return;
        this.blocks = this.blocks.filter(b => b.id !== id);
        if (this.selectedBlockId === id) this.selectedBlockId = null;
        this.renderCanvas();
        this._fireChange();
    },

    /**
     * Move a block to a new index (0-based).
     * @param {string} id
     * @param {number} newIndex
     */
    moveBlock(id, newIndex) {
        const oldIndex = this.blocks.findIndex(b => b.id === id);
        if (oldIndex === -1 || oldIndex === newIndex) return;
        const [block] = this.blocks.splice(oldIndex, 1);
        this.blocks.splice(newIndex, 0, block);
        this.renderCanvas();
        this._fireChange();
    },

    /**
     * Swap block with its upper or lower neighbour.
     * @param {string} id
     * @param {number} direction - -1 for up, +1 for down.
     */
    _swapBlock(id, direction) {
        const idx = this.blocks.findIndex(b => b.id === id);
        const target = idx + direction;
        if (idx === -1 || target < 0 || target >= this.blocks.length) return;
        const temp = this.blocks[idx];
        this.blocks[idx] = this.blocks[target];
        this.blocks[target] = temp;
        this.renderCanvas();
        this._fireChange();
    },

    // -------------------------------------------------------------------------
    // Selection
    // -------------------------------------------------------------------------

    /**
     * Select a block by ID.
     * @param {string} id
     */
    selectBlock(id) {
        this.selectedBlockId = id;
        this._updateSelectionUI();
        this.container.dispatchEvent(
            new CustomEvent('block-editor:block-selected', { detail: { id } })
        );
    },

    /** Deselect all blocks. */
    deselectAll() {
        this.selectedBlockId = null;
        this._updateSelectionUI();
        this.container.dispatchEvent(new CustomEvent('block-editor:block-deselected'));
    },

    _updateSelectionUI() {
        if (!this.container) return;
        this.container.querySelectorAll('.block-editor-block').forEach(el => {
            el.classList.toggle('selected', el.dataset.blockId === this.selectedBlockId);
        });
    },

    // -------------------------------------------------------------------------
    // Data update
    // -------------------------------------------------------------------------

    /**
     * Merge new data into a block and re-render its content area.
     * @param {string} id
     * @param {object} newData
     */
    updateBlockData(id, newData) {
        const block = this.getBlock(id);
        if (!block) return;
        Object.assign(block.data, newData);
        const wrapper = this.container.querySelector(`[data-block-id="${id}"] .block-editor-block-content`);
        if (wrapper) {
            wrapper.innerHTML = BlockRegistry.renderBlockForEdit(block);
            this._attachContentListeners(wrapper, block);
        }
        this._fireChange();
    },

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /** Re-render the entire canvas. */
    renderCanvas() {
        if (!this.container) return;
        const canvas = document.createElement('div');
        canvas.className = 'block-editor-canvas';

        if (this.blocks.length === 0) {
            canvas.innerHTML = '<p style="text-align:center;color:#adb5bd;margin:32px 0;">No blocks yet. Click "+ Add Block" to start.</p>';
        } else {
            this.blocks.forEach(block => {
                canvas.appendChild(this._renderBlockElement(block));
            });
        }

        // Add-block button area
        const addArea = document.createElement('div');
        addArea.className = 'block-editor-add-block';
        addArea.innerHTML = '<button class="block-add-btn">+ Add Block</button>';
        canvas.appendChild(addArea);

        this.container.innerHTML = '';
        this.container.appendChild(canvas);
        this._updateSelectionUI();
    },

    /**
     * Build the DOM element for a single block.
     * @param {object} block
     * @returns {HTMLElement}
     */
    _renderBlockElement(block) {
        const wrapper = document.createElement('div');
        wrapper.className = 'block-editor-block';
        wrapper.dataset.blockId = block.id;
        wrapper.dataset.blockType = block.type;
        wrapper.draggable = true;

        // Actions
        const actions = document.createElement('div');
        actions.className = 'block-editor-block-actions';
        actions.innerHTML = `
            <button class="block-move-up" title="Move up">↑</button>
            <button class="block-move-down" title="Move down">↓</button>
            <button class="block-delete" title="Delete">✕</button>
        `;
        wrapper.appendChild(actions);

        // Content
        const content = document.createElement('div');
        content.className = 'block-editor-block-content';
        content.innerHTML = BlockRegistry.renderBlockForEdit(block);
        wrapper.appendChild(content);

        // Attach contenteditable listeners
        this._attachContentListeners(content, block);

        // Toolbar
        const def = BlockRegistry.get(block.type);
        if (def && typeof def.toolbar === 'function') {
            const toolbarHtml = def.toolbar(block.data, block);
            if (toolbarHtml) {
                const toolbar = document.createElement('div');
                toolbar.className = 'block-editor-block-toolbar';
                toolbar.innerHTML = toolbarHtml;
                wrapper.appendChild(toolbar);
            }
        }

        return wrapper;
    },

    // -------------------------------------------------------------------------
    // Content sync (contenteditable / input → block.data)
    // -------------------------------------------------------------------------

    _attachContentListeners(wrapper, block) {
        // Sync contenteditable elements
        wrapper.querySelectorAll('[contenteditable="true"]').forEach(el => {
            el.addEventListener('input', () => {
                // For paragraph / heading / quote text
                if (el.classList.contains('block-paragraph') ||
                    el.classList.contains('block-heading') ||
                    el.classList.contains('block-quote-text') ||
                    el.classList.contains('block-button-text')) {
                    block.data.text = el.innerHTML;
                }
                this._fireChange();
            });
        });

        // Sync <input> elements with data-field
        wrapper.querySelectorAll('input[data-field]').forEach(input => {
            input.addEventListener('input', () => {
                block.data[input.dataset.field] = input.value;
                this._fireChange();
            });
        });
    },

    // -------------------------------------------------------------------------
    // Canvas-level event delegation
    // -------------------------------------------------------------------------

    _bindCanvasEvents() {
        const root = this.container;

        // Delegate clicks inside canvas
        root.addEventListener('click', e => {
            // ── Action buttons ──
            if (e.target.closest('.block-move-up')) {
                e.stopPropagation();
                const id = e.target.closest('.block-editor-block')?.dataset.blockId;
                if (id) this._swapBlock(id, -1);
                return;
            }
            if (e.target.closest('.block-move-down')) {
                e.stopPropagation();
                const id = e.target.closest('.block-editor-block')?.dataset.blockId;
                if (id) this._swapBlock(id, 1);
                return;
            }
            if (e.target.closest('.block-delete')) {
                e.stopPropagation();
                const id = e.target.closest('.block-editor-block')?.dataset.blockId;
                if (id) this.removeBlock(id);
                return;
            }

            // ── Add block button ──
            if (e.target.closest('.block-add-btn')) {
                e.stopPropagation();
                this._showAddMenu(e.target.closest('.block-add-btn'));
                return;
            }

            // ── Add-menu block type button ──
            if (e.target.closest('.block-type-btn')) {
                const btn = e.target.closest('.block-type-btn');
                const type = btn.dataset.blockType;
                if (type) this.addBlock(type, this.selectedBlockId);
                this._closeAddMenu();
                return;
            }

            // ── Block selection ──
            const blockEl = e.target.closest('.block-editor-block');
            if (blockEl) {
                this.selectBlock(blockEl.dataset.blockId);
                return;
            }

            // ── Click on canvas background → deselect ──
            if (e.target.closest('.block-editor-canvas')) {
                this.deselectAll();
            }
        });

        // ── Drag & drop ──
        root.addEventListener('dragstart', e => {
            const blockEl = e.target.closest('.block-editor-block');
            if (!blockEl) return;
            this.dragState = { blockId: blockEl.dataset.blockId };
            blockEl.style.opacity = '0.4';
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', blockEl.dataset.blockId);
        });

        root.addEventListener('dragover', e => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this._showDropIndicator(e);
        });

        root.addEventListener('dragleave', e => {
            if (!e.currentTarget.contains(e.relatedTarget)) {
                this._removeDropIndicators();
            }
        });

        root.addEventListener('drop', e => {
            e.preventDefault();
            this._removeDropIndicators();
            if (!this.dragState) return;
            const targetId = this._getDropTargetId(e);
            if (targetId && targetId !== this.dragState.blockId) {
                this.moveBlock(this.dragState.blockId, this._getDropIndex(e));
            }
        });

        root.addEventListener('dragend', () => {
            if (this.dragState) {
                const el = root.querySelector(`[data-block-id="${this.dragState.blockId}"]`);
                if (el) el.style.opacity = '';
            }
            this.dragState = null;
            this._removeDropIndicators();
        });

        // ── Close add menu on outside click ──
        document.addEventListener('click', e => {
            const menu = document.querySelector('.block-editor-add-menu');
            if (menu && !menu.contains(e.target) && !e.target.closest('.block-add-btn')) {
                this._closeAddMenu();
            }
        });

        // ── Close add menu on Escape ──
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') this._closeAddMenu();
        });
    },

    // -------------------------------------------------------------------------
    // Drop indicator helpers
    // -------------------------------------------------------------------------

    _showDropIndicator(e) {
        this._removeDropIndicators();
        const canvas = this.container.querySelector('.block-editor-canvas');
        if (!canvas) return;

        const blockEls = [...canvas.querySelectorAll('.block-editor-block')];
        if (blockEls.length === 0) return;

        const indicator = document.createElement('div');
        indicator.className = 'block-drop-indicator';

        // Find insertion point
        let insertBefore = null;
        for (const el of blockEls) {
            const rect = el.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            if (e.clientY < midY) {
                insertBefore = el;
                break;
            }
        }

        if (insertBefore) {
            canvas.insertBefore(indicator, insertBefore);
        } else {
            const addBtn = canvas.querySelector('.block-editor-add-block');
            canvas.insertBefore(indicator, addBtn);
        }
    },

    _removeDropIndicators() {
        if (!this.container) return;
        this.container.querySelectorAll('.block-drop-indicator').forEach(el => el.remove());
    },

    _getDropTargetId(e) {
        const canvas = this.container.querySelector('.block-editor-canvas');
        if (!canvas) return null;
        const blockEls = [...canvas.querySelectorAll('.block-editor-block')];
        for (const el of blockEls) {
            const rect = el.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;
            if (e.clientY < midY) return el.dataset.blockId;
        }
        // Below all blocks → last block
        return blockEls.length ? blockEls[blockEls.length - 1].dataset.blockId : null;
    },

    _getDropIndex(e) {
        const canvas = this.container.querySelector('.block-editor-canvas');
        if (!canvas) return this.blocks.length;
        const blockEls = [...canvas.querySelectorAll('.block-editor-block')];
        for (let i = 0; i < blockEls.length; i++) {
            const rect = blockEls[i].getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) return i;
        }
        return blockEls.length;
    },

    // -------------------------------------------------------------------------
    // Add-block menu
    // -------------------------------------------------------------------------

    /**
     * Show the add-block popup menu near the trigger button.
     * @param {HTMLElement} trigger
     */
    _showAddMenu(trigger) {
        this._closeAddMenu();

        const menu = document.createElement('div');
        menu.className = 'block-editor-add-menu';

        // Position near trigger
        const rect = trigger.getBoundingClientRect();
        menu.style.left = rect.left + 'px';
        menu.style.top = rect.bottom + 4 + 'px';

        // Header with search
        menu.innerHTML = `
            <div class="block-editor-add-menu-header">
                <input type="text" placeholder="Search blocks…" class="block-editor-add-search">
            </div>
            <div class="block-editor-add-menu-body"></div>
        `;

        const searchInput = menu.querySelector('.block-editor-add-search');
        const body = menu.querySelector('.block-editor-add-menu-body');

        const renderMenuItems = (filter = '') => {
            body.innerHTML = '';
            const categories = BlockRegistry.getByCategory();
            const lowerFilter = filter.toLowerCase();

            for (const [cat, types] of Object.entries(categories)) {
                const matchingTypes = types.filter(type => {
                    const def = BlockRegistry.get(type);
                    return def && def.name.toLowerCase().includes(lowerFilter);
                });

                if (matchingTypes.length === 0) continue;

                const catTitle = document.createElement('div');
                catTitle.className = 'block-category-title';
                catTitle.textContent = cat;
                body.appendChild(catTitle);

                matchingTypes.forEach(type => {
                    const def = BlockRegistry.get(type);
                    const btn = document.createElement('button');
                    btn.className = 'block-type-btn';
                    btn.dataset.blockType = type;
                    btn.innerHTML = `
                        <span class="block-type-icon">${def.icon || ''}</span>
                        <span>${def.name}</span>
                    `;
                    body.appendChild(btn);
                });
            }

            if (body.children.length === 0) {
                body.innerHTML = '<div style="padding:12px;color:#6c757d;text-align:center;">No blocks found</div>';
            }
        };

        renderMenuItems();
        searchInput.addEventListener('input', () => renderMenuItems(searchInput.value));

        menu.addEventListener('click', e => {
            const btn = e.target.closest('.block-type-btn');
            if (btn) {
                const type = btn.dataset.blockType;
                if (type) this.addBlock(type, this.selectedBlockId);
                this._closeAddMenu();
            }
        });

        searchInput.focus();

        document.body.appendChild(menu);
    },

    /** Close and remove the add-block menu. */
    _closeAddMenu() {
        document.querySelectorAll('.block-editor-add-menu').forEach(m => m.remove());
    },

    // -------------------------------------------------------------------------
    // Change callbacks
    // -------------------------------------------------------------------------

    /**
     * Register a callback to fire whenever blocks change.
     * @param {function} callback
     */
    onBlocksChanged(callback) {
        if (typeof callback === 'function') {
            this._changeCallbacks.push(callback);
        }
    },

    _fireChange() {
        for (const cb of this._changeCallbacks) {
            try { cb(this.blocks); } catch (err) { console.error('BlockEditor change callback error:', err); }
        }
    },

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /**
     * Return the current block state as JSON-safe object.
     * @returns {object}
     */
    getJSON() {
        return { blocks: this.blocks.map(b => ({ id: b.id, type: b.type, data: b.data })) };
    },
};
