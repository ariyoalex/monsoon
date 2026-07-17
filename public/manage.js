/**
 * Monsoon CMS - Admin Utilities
 * Shared functions for all admin pages.
 */

const Monsoon = {
    /**
     * Show a toast notification.
     * @param {string} message - The message to display.
     * @param {string} type - 'success', 'danger', 'warning', 'info'.
     * @param {number} duration - Auto-dismiss in ms (default 4000).
     */
    toast(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type} border-0 show`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${this.escapeHtml(message)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>`;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), duration);
    },

    /**
     * Set loading state on a button.
     * @param {HTMLElement} btn - The button element.
     * @param {boolean} loading - Whether to show loading state.
     * @param {string} originalText - Text to restore when done.
     */
    setLoading(btn, loading, originalText = '') {
        if (loading) {
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
        } else {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || originalText || 'Save';
            delete btn.dataset.originalText;
        }
    },

    /**
     * Validate a form field.
     * @param {HTMLElement} field - The input field.
     * @param {string} message - Error message if invalid.
     * @returns {boolean}
     */
    validateField(field, message) {
        const value = field.value.trim();
        const isValid = value !== '';
        const group = field.closest('.mb-3') || field.parentElement;

        const existing = group.querySelector('.invalid-feedback');
        if (existing) existing.remove();
        field.classList.remove('is-invalid');

        if (!isValid) {
            field.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = message;
            group.appendChild(feedback);
        }

        return isValid;
    },

    /**
     * Validate an entire form.
     * @param {HTMLFormElement} form - The form element.
     * @returns {boolean}
     */
    validateForm(form) {
        let valid = true;
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            const label = field.closest('.mb-3')?.querySelector('.form-label')?.textContent || field.name;
            if (!this.validateField(field, `${label} is required.`)) {
                valid = false;
            }
        });
        return valid;
    },

    /**
     * Escape HTML to prevent XSS.
     * @param {string} text
     * @returns {string}
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Make an API request.
     * @param {string} url - The API endpoint.
     * @param {object} options - Fetch options.
     * @returns {Promise<object>}
     */
    async api(url, options = {}) {
        const defaults = {
            headers: { 'Content-Type': 'application/json' },
        };
        const config = { ...defaults, ...options };
        if (options.body && typeof options.body === 'object') {
            config.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(url, config);
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.error?.message || 'Request failed.');
            }
            return data;
        } catch (err) {
            this.toast(err.message, 'danger');
            throw err;
        }
    },

    /**
     * Confirm a destructive action.
     * @param {string} message
     * @returns {boolean}
     */
    confirm(message) {
        return window.confirm(message);
    },

    /**
     * Format a date string for display.
     * @param {string} dateString
     * @returns {string}
     */
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    },

    /**
     * Get CSRF token from meta tag or cookie.
     * @returns {string}
     */
    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.content;
        const match = document.cookie.match(/monsoon_session=([^;]+)/);
        return '';
    },
};

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '11';
        document.body.appendChild(container);
    }

    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        let toggle = document.querySelector('.sidebar-toggle');
        if (!toggle) {
            toggle = document.createElement('button');
            toggle.className = 'sidebar-toggle';
            toggle.setAttribute('aria-label', 'Toggle navigation');
            toggle.innerHTML = '&#9776;';
            document.body.appendChild(toggle);
        }

        let overlay = document.querySelector('.sidebar-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
        }

        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('active');
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }

        toggle.addEventListener('click', () => {
            sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
        });
        overlay.addEventListener('click', closeSidebar);

        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', closeSidebar);
        });
    }
});
