/**
 * Monsoon CMS - Block Manifest
 * Companion to block-api.js. Defines how modules declare blocks in their manifest.
 * Loaded after block-api.js.
 */

const BlockManifest = {
    /**
     * Register all blocks from a module manifest.
     * @param {object} manifest - Module manifest with name and blocks properties.
     * @param {string} manifest.name - Module name (e.g., 'monsoon/comments').
     * @param {Object<string, object>} manifest.blocks - Map of block_type => block definition.
     * @returns {{ registered: string[], errors: { blockType: string, errors: string[] }[] }} Registration result.
     */
    register(manifest) {
        const result = { registered: [], errors: [] };

        if (!manifest || typeof manifest !== 'object') {
            result.errors.push({ blockType: '*', errors: ['manifest must be an object'] });
            return result;
        }

        if (!manifest.name || typeof manifest.name !== 'string') {
            result.errors.push({ blockType: '*', errors: ['manifest.name is required'] });
            return result;
        }

        if (!manifest.blocks || typeof manifest.blocks !== 'object') {
            return result;
        }

        for (const [blockType, definition] of Object.entries(manifest.blocks)) {
            const regResult = BlockAPI.register(manifest.name, blockType, definition);
            if (regResult.valid) {
                result.registered.push(blockType);
            } else {
                result.errors.push({ blockType, errors: regResult.errors });
            }
        }

        return result;
    },
};
