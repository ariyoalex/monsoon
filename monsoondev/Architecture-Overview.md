# Monsoon CMS — Architecture Overview

> Internal architecture reference for developers building on or contributing to Monsoon CMS.
> Keep this in sync with `kernel/src/Kernel.php` and the kernel boot sequence.

## Boot Sequence

1. `public/index.php` is the single front controller. It boots the `Kernel`.
2. `Kernel::__construct()` builds the `Router`, registers `CsrfMiddleware` → `AuthMiddleware` as a pipeline, instantiates `PermissionGate` and registers the default capabilities, creates the `ModuleLoader`, and registers core admin routes via `AdminRoutes::register()`.
3. `Kernel::handle()`:
   - Connects to MySQLi via `Database::getInstance()->connect($config)`.
   - Sets the DB connection on `Auth`.
   - If DB is reachable, registers REST API routes (`ApiRouter::register`) and runs `ModuleLoader::loadModules($db)`, which discovers modules, instantiates their Module classes, and registers their API + admin routes.
   - Dispatches the request through the `Router`, which runs the middleware pipeline then the matched handler.
   - Sends the `Response`.

## Request Lifecycle

```
Request
  └─> public/index.php
        └─> Kernel::handle()
              └─> Router::dispatch(method, uri)
                    ├─> MiddlewarePipeline (CsrfMiddleware → AuthMiddleware)
                    └─> Matched handler (AdminRoutes | ApiRouter | Module route)
                          └─> Response::send()
```

## Layers

| Layer | Location | Responsibility |
|-------|----------|----------------|
| Front controller | `public/index.php` | Boot kernel, handle request |
| Kernel | `kernel/src/Kernel.php` | Wire router, middleware, DB, modules |
| Router | `kernel/src/Router.php` | Match clean URLs, run middleware, dispatch |
| Middleware | `kernel/src/*Middleware.php` | CSRF, auth, permission gates |
| Services | `kernel/src/*Service.php` | Business logic (content, media, taxonomy, menu, widget, theme) |
| API | `kernel/src/ApiRouter.php` | REST endpoints under `/api/v1` |
| Admin UI | `kernel/src/AdminRoutes.php` + `modules/*/admin/*.php` | HTML pages under `/manage` |
| Modules | `modules/*/` | Official + third-party feature modules |
| Themes | `themes/*/` | Public-facing rendering |
| Public assets | `public/` | `admin.css`, `manage.js`, `block-*.js`, `customizer.js`, `landing.css` |

## Core Architectural Invariants

1. **Permission gate is absolute** — modules declare capabilities in `manifest.json`; the kernel enforces them.
2. **`declare(strict_types=1)`** in every PHP file.
3. **MySQLi prepared statements only** — no concatenated SQL anywhere in core/official modules.
4. **UUID v4** primary keys everywhere (`kernel/src/Uuid.php`).
5. **No `.php` in URLs** — clean URLs enforced in the router.
6. **Admin at `/manage/`**, never `/admin/`.
7. **AJAX for every admin action** — no full page reloads on form submit.
8. **Full-snapshot revisions** — never diffs.
9. **Alt text required on media upload.**
10. **Vanilla JS only** in core — no frontend framework, no build pipeline.
11. **No CSS gradients, no emoji in UI.**
12. **No hardcoded credentials** — everything via `.env`/config.

## Database

- All tables use UUID v4 PKs (no auto-increment).
- `migrations/*.sql` are versioned and applied in order by `MigrationRunner` (tracked in `schema_migrations`).
- Settings table uses a unique key on `(scope, setting_key)`. Use `INSERT ... ON DUPLICATE KEY UPDATE` for upserts (see `ApiRouter.php` settings routes).

## Module System

- Modules live in `modules/{slug}/`.
- Each has a `manifest.json` (slug, version, capabilities, routes, migrations).
- `ModuleLoader::discover()` scans the directory; `loadModules($db)` instantiates the Module class and registers routes.
- The four v1 modules (`seo-basics`, `forms`, `security-hardening`, `backup-restore`) are explicitly mapped to their classes in `ModuleLoader::loadModules()`. A new module slug must be added to that map to be auto-instantiated.
- Admin pages are served from `modules/{slug}/admin/{route}-page.php`, which must define a namespaced `Monsoon\Modules\{NsPart}\render{Page}Page(): string` function (the loader calls it by fully-qualified name).

## Known Gaps (tracked for hardening)

- `PermissionGate` currently acts as a capability registry; runtime enforcement at the API layer is not yet wired into every handler. See `tasks-phases.md` Phase 8.
- API routes under `/api/v1` are not globally auth-gated; per-handler checks exist for the most sensitive endpoints. CSRF is skipped for `/api/` paths by design (token-based clients).

See also: [`API-Reference.md`](./API-Reference.md), [`Module-Development.md`](./Module-Development.md), [`Theme-Development.md`](./Theme-Development.md).
