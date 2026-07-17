/**
 * Monsoon CMS - Block API
 * Module-level block registration for custom blocks.
 * Loaded after block-registry.js.
 */

const BlockAPI = {
    /** @type {Object<string, Object<string, object>>} moduleName => { blockType => definition } */
    moduleBlocks: {},

    /**
     * Register a custom block type from a module manifest.
     * @param {string} moduleName - Module name (e.g., 'monsoon/comments').
     * @param {string} blockType - Block type identifier (e.g., 'comment-form').
     * @param {object} definition - Block definition (same format as BlockRegistry.register).
     * @returns {{ valid: boolean, errors: string[] }} Validation result.
     */
    register(moduleName, blockType, definition) {
        const errors = [];

        if (!moduleName || typeof moduleName !== 'string') {
            errors.push('moduleName is required and must be a string.');
        }
        if (!blockType || typeof blockType !== 'string') {
            errors.push('blockType is required and must be a string.');
        }
        if (!definition || typeof definition !== 'object') {
            errors.push('definition is required and must be an object.');
        }
        if (!definition || !definition.name) {
            errors.push('definition.name is required.');
        }
        if (!definition || typeof definition.render !== 'function') {
            errors.push('definition.render must be a function.');
        }

        if (errors.length > 0) {
            return { valid: false, errors };
        }

        const full = {
            name: definition.name || 'Custom Block',
            icon: definition.icon || '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="12" height="12" rx="2"/></svg>',
            category: definition.category || 'widgets',
            defaultData: definition.defaultData || {},
            render: definition.render,
            edit: definition.edit || ((data) => `<div contenteditable="true">${data.text || ''}</div>`),
            toolbar: definition.toolbar || null,
            validate: definition.validate || null,
        };

        BlockRegistry.register(blockType, full);

        if (!this.moduleBlocks[moduleName]) {
            this.moduleBlocks[moduleName] = {};
        }
        this.moduleBlocks[moduleName][blockType] = full;

        return { valid: true, errors: [] };
    },

    /**
     * Get all blocks registered by a specific module.
     * @param {string} moduleName
     * @returns {Object<string, object>} Map of block_type => definition.
     */
    getModuleBlocks(moduleName) {
        return this.moduleBlocks[moduleName] ? { ...this.moduleBlocks[moduleName] } : {};
    },

    /**
     * Get all custom blocks from all modules.
     * @returns {Object<string, { definition: object, moduleName: string }>} Map of block_type => entry.
     */
    getAllCustomBlocks() {
        const result = {};
        for (const [moduleName, blocks] of Object.entries(this.moduleBlocks)) {
            for (const [blockType, definition] of Object.entries(blocks)) {
                result[blockType] = { definition, moduleName };
            }
        }
        return result;
    },

    /**
     * Load blocks from module manifests via AJAX.
     * Fetches GET /api/v1/modules to get all enabled modules,
     * then for each module with blocks, registers them.
     * @returns {Promise<void>}
     */
    async loadFromModules() {
        try {
            const response = await fetch('/api/v1/modules');
            if (!response.ok) {
                console.warn('BlockAPI: Failed to fetch modules:', response.status);
                return;
            }

            const data = await response.json();
            const modules = data.modules || data;

            if (!modules || typeof modules !== 'object') {
                return;
            }

            const moduleArray = Array.isArray(modules) ? modules : Object.values(modules);

            for (const mod of moduleArray) {
                if (!mod.blocks || typeof mod.blocks !== 'object') {
                    continue;
                }

                const moduleName = mod.name || mod.moduleName || 'unknown';

                for (const [blockType, definition] of Object.entries(mod.blocks)) {
                    const result = this.register(moduleName, blockType, definition);
                    if (result.valid) {
                        console.log(`BlockAPI: Registered block "${blockType}" from module "${moduleName}"`);
                    } else {
                        console.warn(`BlockAPI: Failed to register block "${blockType}" from module "${moduleName}":`, result.errors);
                    }
                }
            }
        } catch (err) {
            console.warn('BlockAPI: loadFromModules failed:', err.message);
        }
    },

    /**
     * Create a block definition with defaults.
     * @param {object} opts - { name, icon, category, defaultData, render, edit, toolbar, validate }
     * @returns {object} Full block definition.
     */
    createBlock(opts) {
        return {
            name: opts.name || 'Custom Block',
            icon: opts.icon || '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="12" height="12" rx="2"/></svg>',
            category: opts.category || 'widgets',
            defaultData: opts.defaultData || {},
            render: opts.render || ((data) => `<div>${JSON.stringify(data)}</div>`),
            edit: opts.edit || ((data) => `<div contenteditable="true">${data.text || ''}</div>`),
            toolbar: opts.toolbar || null,
            validate: opts.validate || null,
        };
    },
};
