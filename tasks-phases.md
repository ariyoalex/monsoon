# Monsoon CMS — Tasks & Phases

> Production-level roadmap to build, harden, and ship Monsoon CMS.
> Each phase ends with a shippable, testable increment.

---

## Phase 0: Foundation & Scaffolding

**Goal:** Project skeleton, dev environment hardened, deployment pipeline ready.

- [ ] 0.1 — Initialize repo with proposed folder structure:
  ```
  /kernel/
  /modules/
  /themes/starter-theme/
  /manage/
  /public/
  /migrations/
  /tests/
  ```
- [ ] 0.2 — Set up DDEV with PHP 8.4, MySQL 8, proper nginx/apache config for clean URLs
- [ ] 0.3 — Create front controller (`public/index.php`) — single entry point, boots kernel
- [ ] 0.4 — `.htaccess` rewrite rules (clean URLs, route everything through front controller)
- [ ] 0.5 — Environment config loader (`.env` via PHP, no hardcoded credentials)
- [ ] 0.6 — Error handler (dev: detailed, prod: user-friendly) with logging
- [ ] 0.7 — PHPCS/PHPStan config with strict rules (`declare(strict_types=1)` enforced)
- [ ] 0.8 — Test infrastructure (PHPUnit bootstrap, base test case, first smoke test)
- [ ] 0.9 — CI pipeline (GitHub Actions: lint, type-check, unit test on push/PR)
- [ ] 0.10 — Deployment script or recipe (shared hosting: rsync; VPS: deployer or similar)

**Exit criteria:** `public/index.php` returns "Monsoon kernel booted" on `ddev start`, tests pass, CI green.

---

## Phase 1: Kernel Core

**Goal:** Minimal, strictly-typed kernel — routing, auth, content schema, module loader, permission gate.

### 1.1 — Router & Dispatcher

- [ ] 1.1.1 — URL parser: resolve clean URL to route (no `.php` extensions)
- [ ] 1.1.2 — Route registry: match patterns (`/manage/*`, `/api/*`, `/{slug}`)
- [ ] 1.1.3 — Dispatcher: load only modules relevant to matched route
- [ ] 1.1.4 — Middleware pipeline (auth check, permission gate, CSRF)
- [ ] 1.1.5 — 404 handler with themed fallback page

### 1.2 — Database Layer

- [ ] 1.2.1 — MySQLi singleton/connection manager (prepared statements only)
- [ ] 1.2.2 — Migration runner: versioned `.sql` files, apply in order, track in `schema_migrations` table
- [ ] 1.2.3 — UUID v4 generator utility (no auto-increment anywhere)
- [ ] 1.2.4 — Migration 001: create core tables (`users`, `roles`, `content_items`, `content_revisions`, `taxonomies`, `media`, `modules`, `settings`)

### 1.3 — Authentication & Users

- [ ] 1.3.1 — Password hashing (modern algorithm, e.g. `password_hash()` with argon2id)
- [ ] 1.3.2 — Login endpoint (AJAX, returns token/session)
- [ ] 1.3.3 — Session management (secure cookie, HTTP-only, SameSite)
- [ ] 1.3.4 — Logout (invalidate session)
- [ ] 1.3.5 — Password reset flow (email token, timed expiration)
- [ ] 1.3.6 — User CRUD (create, read, update, delete users)
- [ ] 1.3.7 — Role CRUD with granular capabilities stored as JSON
- [ ] 1.3.8 — Session persistence across requests (no re-login on page load)

### 1.4 — Module Loader & Permission Gate

- [ ] 1.4.1 — Module manifest schema (slug, version, capabilities, routes, migrations)
- [ ] 1.4.2 — Module discovery: scan `/modules/` directory, read manifests
- [ ] 1.4.3 — Module activation/deactivation (run migrations, register routes)
- [ ] 1.4.4 — Permission gate: intercept every module action, enforce manifest-scoped capabilities
- [ ] 1.4.5 — Capability registry (central list of all known capabilities)
- [ ] 1.4.6 — Runtime enforcement: module writing outside scope fails loudly

### 1.5 — Content Core (kernel-level)

- [ ] 1.5.1 — Content service: CRUD for `content_items` (posts, pages, custom types)
- [ ] 1.5.2 — Content status machine: draft → publish → archive (extensible by modules)
- [ ] 1.5.3 — Revision system: full JSON snapshots on every save
- [ ] 1.5.4 — Taxonomy service: CRUD for categories, tags
- [ ] 1.5.5 — Slug generation and uniqueness enforcement
- [ ] 1.5.6 — Clean URL resolution: `/{slug}` → content item

### 1.6 — REST API (kernel-level)

- [ ] 1.6.1 — REST router: `/api/v1/{resource}`
- [ ] 1.6.2 — Content endpoints: list, get, create, update, delete content items
- [ ] 1.6.3 — User endpoints: profile, update
- [ ] 1.6.4 — Taxonomy endpoints
- [ ] 1.6.5 — Media endpoints (upload, serve, delete)
- [ ] 1.6.6 — API authentication (token-based, scoped)
- [ ] 1.6.7 — Pagination, sorting, filtering on list endpoints
- [ ] 1.6.8 — Consistent error response format (JSON: `{error: {code, message}}`)

**Exit criteria:** Kernel boots, routes resolve, users can log in, content CRUD works via API, module gate blocks unauthorized access. All integration tests pass.

---

## Phase 2: Admin UI

**Goal:** Fully functional admin dashboard at `/manage/` — AJAX-driven, Bootstrap 5, no page reloads.

- [ ] 2.1 — Admin layout shell (sidebar nav, top bar, content area, toast container)
- [ ] 2.2 — Login page at `/manage/login` (AJAX submit, session-based)
- [ ] 2.3 — Dashboard home page (summary widgets, recent content, quick actions)
- [ ] 2.4 — Content listing (filterable, sortable, paginated table)
- [ ] 2.5 — Content editor (title, slug, body, status, publish date, taxonomy selection)
- [ ] 2.6 — Media library (grid view, upload via AJAX, alt-text required, insert)
- [ ] 2.7 — User management screens (list, create, edit, delete)
- [ ] 2.8 — Role/capability management screens
- [ ] 2.9 — Settings screens (site name, description, locale, etc.)
- [ ] 2.10 — Toast notification system (success, error, warning, info)
- [ ] 2.11 — Form validation (inline, server-side echoed to client)
- [ ] 2.12 — Loading states on all buttons (spinner, disabled during AJAX)
- [ ] 2.13 — Accessible by WCAG 2.1 AA (keyboard nav, ARIA labels, contrast)
- [ ] 2.14 — Mobile-responsive admin layout

**Exit criteria:** Site owner can log in, create/edit/publish content, upload media, manage users and settings — all without a full page reload. Lighthouse accessibility score ≥ 90.

---

## Phase 3: Block Editor (Vanilla JS)

**Goal:** Lightweight, AJAX-driven visual page/post builder — fast on low-end devices.

- [ ] 3.1 — Block registry: core blocks (heading, paragraph, image, list, quote, embed, separator, button)
- [ ] 3.2 — Editor canvas: render blocks in order, drag to reorder
- [ ] 3.3 — Block toolbar: inline formatting, alignment, link insertion
- [ ] 3.4 — Image block: upload or pick from media library, alt text, alignment, caption
- [ ] 3.5 — AJAX auto-save (debounced, visual indicator)
- [ ] 3.6 — Revisions browser (compare snapshots, restore)
- [ ] 3.7 — Custom block API (modules register blocks via manifest)
- [ ] 3.8 — Slash command inserter (`/heading`, `/image`, etc.)
- [ ] 3.9 — Undo/redo stack (client-side, before auto-save)
- [ ] 3.10 — Full-page preview (open published state in new tab)
- [ ] 3.11 — Keyboard shortcuts (Ctrl+S save, Ctrl+Z undo, etc.)

**Exit criteria:** Non-technical user can build a page with text, images, and layout blocks. Editor loads under 2 seconds on shared hosting, saves in under 1 second.

---

## Phase 4: Theme Engine

**Goal:** Theme system with template hierarchy, starter theme, and visual customizer.

- [ ] 4.1 — Theme manifest (`theme.json`: name, version, supports, templates)
- [ ] 4.2 — Template hierarchy: `page-{slug}.php` → `page.php` → `single.php` → `index.php`
- [ ] 4.3 — Template loader: kernel resolves template path, falls back gracefully
- [ ] 4.4 — Starter theme (Bootstrap 5, clean HTML5, responsive)
- [ ] 4.5 — Asset enqueue system (CSS, JS with versioning)
- [ ] 4.6 — Menu system (create menus, assign locations, render in theme)
- [ ] 4.7 — Widget areas (sidebar, footer — modules register widgets)
- [ ] 4.8 — Visual customizer: live preview for site title, colors, logo, typography
- [ ] 4.9 — Theme activation/deactivation in admin
- [ ] 4.10 — Theme functions pattern (modules hook into theme hooks, not kernel hooks)

**Exit criteria:** Starter theme renders pages, posts, archives. User can customize site title, colors, logo. Menus and widgets work.

---

## Phase 5: Official Modules (v1 Bundle)

**Goal:** All v1-bundled official modules — shippable, tested, permission-gated.

### 5.1 — SEO Basics Module

- [ ] 5.1.1 — Per-page meta title, description, canonical URL
- [ ] 5.1.2 — Open Graph tags (og:title, og:description, og:image)
- [ ] 5.1.3 — XML sitemap generation (auto-updated on content changes)
- [ ] 5.1.4 — robots.txt generation (kernel delegates to module)
- [ ] 5.1.5 — Schema.org structured data (Article, WebPage, BreadcrumbList)
- [ ] 5.1.6 — SEO settings page (global defaults, per-content-type overrides)
- [ ] 5.1.7 — Manifest: `seo-basics` with `content.read`, `settings.write`, `settings.read`

### 5.2 — Forms Module

- [ ] 5.2.1 — Drag-and-drop form builder in admin
- [ ] 5.2.2 — Field types: text, email, textarea, select, checkbox, radio, file upload
- [ ] 5.2.3 — Form rendering via shortcode/block (embed in any content)
- [ ] 5.2.4 — Spam protection (honeypot, time check, optional reCAPTCHA integration)
- [ ] 5.2.5 — Submission storage and export (CSV)
- [ ] 5.2.6 — Email notification on submission (to site owner)
- [ ] 5.2.7 — Manifest: `forms` with `content.read`, `content.write`, `mail.send`

### 5.3 — Backup & Restore Module

- [ ] 5.3.1 — Database dump (MySQLi, all tables, compressed)
- [ ] 5.3.2 — File backup (uploads directory + theme files, compressed)
- [ ] 5.3.3 — Scheduled backups (daily/weekly via cron simulation in admin)
- [ ] 5.3.4 — One-click restore from backup file
- [ ] 5.3.5 — Backup listing and download from admin
- [ ] 5.3.6 — Manifest: `backup-restore` with `database.dump`, `files.read`, `files.write`

### 5.4 — Security Hardening Module

- [ ] 5.4.1 — Login rate limiting (per IP, exponential backoff)
- [ ] 5.4.2 — Two-factor authentication (TOTP, QR code setup)
- [ ] 5.4.3 — Audit log (all admin actions: who, what, when, IP)
- [ ] 5.4.4 — Audit log viewer in admin (filterable, searchable)
- [ ] 5.4.5 — File integrity check (checksum core + module files, alert on changes)
- [ ] 5.4.6 — Security settings page (rate limit config, 2FA toggle, audit retention)
- [ ] 5.4.7 — Manifest: `security-hardening` with `auth.read`, `auth.write`, `user.read`, `settings.write`

**Exit criteria:** All four modules installable via module loader, appear in admin, pass permission gate audits. Each module has unit + integration tests.

---

## Phase 6: WordPress Migration

**Goal:** One-click WordPress import — posts, pages, media, users, categories, tags.

- [ ] 6.1 — WXR (WordPress eXtended RSS) parser
- [ ] 6.2 — Content mapper: post/page → content_items, preserving slugs and publish dates
- [ ] 6.3 — Media import: download and attach, preserve alt text if present
- [ ] 6.4 — User import: create or map, flag duplicates
- [ ] 6.5 — Taxonomy mapper: categories/tags → taxonomies with slug preservation
- [ ] 6.6 — Shortcode flagging: detect unsupported shortcodes, report clearly
- [ ] 6.7 — Import progress UI (steps, progress bar, error log)
- [ ] 6.8 — SEO rankings preservation (301 redirects from old WP URL structure)
- [ ] 6.9 — Import rollback (undo a failed or unwanted import)
- [ ] 6.10 — Manifest: `wp-importer` with `content.read`, `content.write`, `media.write`, `user.read`, `user.write`

**Exit criteria:** Standard WordPress export file imports fully. Slugs, dates, and media URLs preserved. Unsupported shortcodes flagged, not silently dropped.

---

## Phase 7: Performance Hardening

**Goal:** Lighthouse ≥ 90 on default theme, admin loads under 1s on shared hosting.

- [ ] 7.1 — Page caching (static HTML cache for unauthenticated visitors, auto-purge on content change)
- [ ] 7.2 — Asset minification (CSS/JS combine and minify in admin)
- [ ] 7.3 — Database query optimization (indexes on all foreign keys and queried columns)
- [ ] 7.4 — Lazy module loading (only boot modules for the current route)
- [ ] 7.5 — Image optimization (automatic WebP conversion on upload, responsive srcset)
- [ ] 7.6 — Admin AJAX endpoint batching (batch multiple saves into one request)
- [ ] 7.7 — CDN-ready: asset URLs configurable via setting
- [ ] 7.8 — Performance test suite (Lighthouse CI, k6 or similar load testing)

**Exit criteria:** Public pages score ≥ 90 Lighthouse Performance. Admin dashboard < 1s load on shared hosting (1 CPU, 512MB RAM, standard HDD).

---

## Phase 8: Security Audit & Hardening

**Goal:** Production-ready security posture.

- [ ] 8.1 — Penetration test: SQL injection (prepared statements verified), XSS (output escaping audit), CSRF (token on every form)
- [ ] 8.2 — Permission gate end-to-end validation (every module, every capability boundary)
- [ ] 8.3 — Session fixation and hijacking tests
- [ ] 8.4 — File upload validation (MIME verification, extension whitelist, size limits, path traversal)
- [ ] 8.5 — Rate limiting on all auth endpoints (login, password reset, API)
- [ ] 8.6 — Security headers (Content-Security-Policy, X-Frame-Options, X-Content-Type-Options, Referrer-Policy)
- [ ] 8.7 — Dependency vulnerability scan (composer audit, manual review)
- [ ] 8.8 — Security.txt endpoint (`/.well-known/security.txt`)
- [ ] 8.9 — Bug bounty / disclosure policy documented

**Exit criteria:** Third-party security audit passes (or self-certified with documented mitigations). All OWASP Top 10 categories addressed.

---

## Phase 9: Launch Readiness

**Goal:** Public release — installable, documented, supportable.

- [ ] 9.1 — Installation wizard (first-run: DB config, admin account creation, optional demo content)
- [ ] 9.2 — Updater: one-click update notifications and application for core + modules
- [ ] 9.3 — User documentation: admin guide (screenshots, workflows)
- [ ] 9.4 — Developer documentation: module development guide, API reference, manifest spec
- [ ] 9.5 — Public website + marketing page (monsooncms.org or similar)
- [ ] 9.6 — Community guidelines: contribution guide, code of conduct, security disclosure
- [ ] 9.7 — Support channels: GitHub Discussions, basic email support
- [ ] 9.8 — Release checklist: tag v1.0.0, publish release notes, announce

**Exit criteria:** User can download, install on shared hosting, and publish a page in under 10 minutes. Docs cover admin and developer workflows.

---

## Phase 10: Phase 2 Features (Post-v1)

**Goal:** Multi-site, workflow engine, i18n, module marketplace, GraphQL.

- [ ] 10.1 — Multi-site manager: tenant provisioning, shared or isolated DB per site
- [ ] 10.2 — Content workflow: draft → review → approve → publish → archive, role-based gates
- [ ] 10.3 — Locale-aware content: per-field translations, translation status
- [ ] 10.4 — Module marketplace: discovery, one-click install, sandbox certification pipeline
- [ ] 10.5 — GraphQL endpoint alongside REST
- [ ] 10.6 — Marketplace review process documented and automated

**Exit criteria:** Multi-site creates and manages independent sites. Content goes through approval workflow. Content is translatable. Marketplace has at least one third-party module.

---

## Phase 11: Phase 3+ Features (Future)

**Goal:** Commerce, automations, headless delivery, AI assistance.

- [ ] 11.1 — Commerce module: products, cart, checkout, payment gateway, order management
- [ ] 11.2 — Booking module: calendar, availability, reservations
- [ ] 11.3 — Membership/paywall module: subscription tiers, content gating
- [ ] 11.4 — Visual automation builder (Zapier-like, native)
- [ ] 11.5 — Headless delivery network: edge caching for API consumers
- [ ] 11.6 — AI-assisted content drafting inside editor
- [ ] 11.7 — AI-assisted image tagging and alt text generation

**Exit criteria:** Each feature functions as an installable module with tests and documentation.

---

## Architectural Invariants (Never Compromise)

1. **Permission gate is absolute** — no module bypasses its declared capability scope, ever.
2. **`declare(strict_types=1)` in every PHP file** — no exceptions.
3. **MySQLi prepared statements only** — no string-concatenated queries, even for "safe" values.
4. **UUID v4 for all primary keys** — no auto-increment integers.
5. **No `.php` in URLs** — clean URLs everywhere, enforced in the router.
6. **Admin lives at `/manage/`** — never `/admin/`.
7. **AJAX for every admin action** — no full-page reloads on form submit.
8. **Full-snapshot revisions** — never diffs, restore must be a single operation.
9. **Alt text required on media upload** — not optional, not deferred.
10. **No frontend framework in core** — vanilla JS only. No build pipeline required.
11. **No CSS gradients, no emoji in UI** — consistent, calm admin aesthetic.
12. **No hardcoded credentials or paths** — everything through config/env.
