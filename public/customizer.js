/**
 * Monsoon CMS - Visual Customizer
 * Handles live preview and settings synchronization.
 */
const MonsoonCustomizer = {
    previewFrame: null,
    settings: {},

    init() {
        this.previewFrame = document.getElementById('customize-preview');
        this.loadSettings();
        this.bindControls();
        this.listenForPreviewEvents();
    },

    loadSettings() {
        fetch('/api/v1/settings')
            .then(r => r.json())
            .then(json => {
                (json.data || []).forEach(s => {
                    var key = s.setting_key || s.key;
                    var val = s.setting_value || s.value;
                    if (key && val !== undefined) {
                        this.settings[key] = val;
                    }
                });
                this.populateControls();
                this.applySettings();
            })
            .catch(() => {});
    },

    populateControls() {
        var s = this.settings;
        var title = document.getElementById('site-title');
        var tagline = document.getElementById('site-tagline');
        var primaryColor = document.getElementById('primary-color');
        var sidebarColor = document.getElementById('sidebar-color');
        var bgColor = document.getElementById('background-color');
        var bodyFont = document.getElementById('body-font');
        var headingFont = document.getElementById('heading-font');

        if (title && s.site_name) title.value = s.site_name;
        if (tagline && s.site_tagline) tagline.value = s.site_tagline;
        if (primaryColor && s.primaryColor) primaryColor.value = s.primaryColor;
        if (sidebarColor && s.sidebarColor) sidebarColor.value = s.sidebarColor;
        if (bgColor && s.backgroundColor) bgColor.value = s.backgroundColor;
        if (bodyFont && s.bodyFont) bodyFont.value = s.bodyFont;
        if (headingFont && s.headingFont) headingFont.value = s.headingFont;
    },

    bindControls() {
        var self = this;

        var siteTitle = document.getElementById('site-title');
        if (siteTitle) {
            siteTitle.addEventListener('input', function(e) {
                self.settings.site_name = e.target.value;
                self.updatePreview();
            });
        }

        var siteTagline = document.getElementById('site-tagline');
        if (siteTagline) {
            siteTagline.addEventListener('input', function(e) {
                self.settings.site_tagline = e.target.value;
                self.updatePreview();
            });
        }

        document.querySelectorAll('[data-setting]').forEach(function(el) {
            el.addEventListener('input', function(e) {
                self.settings[e.target.dataset.setting] = e.target.value;
                self.updatePreview();
            });
            el.addEventListener('change', function(e) {
                self.settings[e.target.dataset.setting] = e.target.value;
                self.updatePreview();
            });
        });
    },

    updatePreview() {
        if (this.previewFrame && this.previewFrame.contentWindow) {
            this.previewFrame.contentWindow.postMessage({
                type: 'customize-update',
                settings: this.settings
            }, '*');
        }
    },

    listenForPreviewEvents() {
        var self = this;
        window.addEventListener('message', function(e) {
            if (e.data && e.data.type === 'customize-ready') {
                self.updatePreview();
            }
        });
    },

    save() {
        var self = this;
        var btn = document.getElementById('save-publish-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Saving...';
        }

        var fieldMap = {
            'site_name': 'site_name',
            'site_tagline': 'site_tagline',
            'primaryColor': 'theme_primary_color',
            'sidebarColor': 'theme_sidebar_color',
            'backgroundColor': 'theme_background_color',
            'bodyFont': 'theme_body_font',
            'headingFont': 'theme_heading_font',
            'menuPrimary': 'theme_menu_primary',
            'menuFooter': 'theme_menu_footer'
        };

        var promises = Object.entries(this.settings).map(function(entry) {
            var key = entry[0];
            var value = entry[1];
            var settingKey = fieldMap[key] || key;
            return fetch('/api/v1/settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ setting_key: settingKey, setting_value: value })
            }).then(function(r) {
                if (r.status === 201) return r.json();
                return r.json().then(function(j) { throw new Error(j.error?.message || 'Save failed'); });
            });
        });

        Promise.all(promises).then(function() {
            Monsoon.toast('Settings saved successfully.', 'success');
        }).catch(function(err) {
            Monsoon.toast(err.message || 'Failed to save settings.', 'danger');
        }).finally(function() {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'Save &amp; Publish';
            }
        });
    },

    handleIconUpload(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            var self = this;
            reader.onload = function(e) {
                var preview = document.getElementById('site-icon-preview');
                if (preview) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Site icon" style="width:100%;height:100%;object-fit:cover;border-radius:4px;">';
                }
                self.settings.site_icon = e.target.result;
                self.updatePreview();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
};
