# Module Development

This guide explains how to build an **official module** for Monsoon CMS, a PHP 8.4
CMS with a strictly-typed kernel and a permissioned module system. Everything
"extra" — SEO, forms, backup, security — ships as a module that plugs into the
kernel through a defined contract. The kernel enforces a module's declared
permission scope at runtime, so a module that only declares `content.read` can
never write settings or send mail unless that capability is granted.

All paths below are relative to the repository root.

---

## 1. Directory structure

Official modules live under `modules/{slug}/`. The directory name **must** match
the manifest `slug` (and `name`). A minimal module looks like this:

```
modules/
  hello-world/
    manifest.json            # required: module metadata + routes
    HelloWorld.php           # required: the module class (Namespace part: HelloWorld)
    admin/
      hello-world-page.php   # optional: admin screen for an admin route
    migrations/
      20250101000001_create_hello.sql   # optional: schema migrations (SQL)
```

| Path | Required | Purpose |
|------|----------|---------|
| `manifest.json` | yes | Declares slug, version, routes, capabilities. |
| `{ClassName}.php` | yes (for bundled modules) | The module class instantiated by the loader. |
| `admin/{route}-page.php` | only if `admin_routes` declared | Admin HTML screen, rendered via a `render{Page}Page()` function. |
| `migrations/*.sql` | only if schema needed | Run once automatically by `MigrationRunner`. |

---

## 2. manifest.json schema

The manifest is parsed by `kernel/src/ModuleManifest.php`. Every field is read
from the constructor (`ModuleManifest::__construct`). The class reads the
following keys from the decoded JSON:

| JSON key | PHP property | Type | Required | Default | Notes |
|----------|--------------|------|----------|---------|-------|
| `slug` (or `name`) | `slug` | `string` | yes* | `''` | Used as the lookup key in `ModuleLoader`. `name` is used as fallback. |
| `version` | `version` | `string` | no | `'0.0.0'` | Semantic version of the module. |
| `capabilities_required` | `capabilitiesRequired` | `array<string>` | no | `[]` | Capabilities the module intends to use. |
| `admin_routes` | `adminRoutes` | `array<string>` | no | `[]` | GET routes rendered through `admin/{route}-page.php`. |
| `public_routes` | `publicRoutes` | `array<string>` | no | `[]` | GET routes rendered as plain text by the loader. |
| `migrations` | `migrations` | `array` | no | `[]` | Migration references (declared; SQL files are picked up by `MigrationRunner`). |

> *`slug` is "required" in practice — without it the module key is empty and
> routing will not work. `seo-basics/manifest.json` declares both `name` and
> `slug`.

Additional keys present in the real `seo-basics/manifest.json`
(`description`, `author`, `permissions`, `routes`, `hooks`, `settings`) are
**not** consumed by `ModuleManifest`. They are descriptive metadata for the
admin and documentation; do not rely on them being enforced by the kernel.

### Real example — `modules/seo-basics/manifest.json`

```json
{
    "name": "seo-basics",
    "slug": "seo-basics",
    "version": "1.0.0",
    "description": "SEO essentials: meta tags, Open Graph, sitemap, robots.txt, Schema.org",
    "author": "Monsoon",
    "permissions": ["content.read", "settings.write", "settings.read"],
    "admin_routes": ["/manage/seo"],
    "routes": {},
    "hooks": ["theme:head", "theme:body:end"],
    "settings": {
        "seo_default_title": "{site_name} — {page_title}",
        "seo_default_description": "",
        "seo_og_enabled": true,
        "seo_schema_enabled": true,
        "seo_sitemap_enabled": true
    }
}
```

---

## 3. The Module class contract

The kernel discovers modules in `ModuleLoader::loadModules()`
(`kernel/src/ModuleLoader.php:122`). For a set of **recognized** slugs, the
loader maps the slug to a class name and a **namespace part**, then
instantiates the class with a `mysqli` connection and calls
`registerRoutes(Router)`:

```php
$fqcn = "Monsoon\\Modules\\{$nsPart}\\{$className}";
$instance = new $fqcn($db);
$instance->registerRoutes($this->router);
```

The currently recognized mapping (from `ModuleLoader.php:129`):

| slug | class file | class name | namespace part |
|------|-----------|------------|----------------|
| `seo-basics` | `SeoModule.php` | `SeoModule` | `SeoBasics` → `Monsoon\Modules\SeoBasics` |
| `forms` | `FormsModule.php` | `FormsModule` | `Forms` → `Monsoon\Modules\Forms` |
| `security-hardening` | `SecurityModule.php` | `SecurityModule` | `SecurityHardening` → `Monsoon\Modules\SecurityHardening` |
| `backup-restore` | `BackupModule.php` | `BackupModule` | `BackupRestore` → `Monsoon\Modules\BackupRestore` |

### Contract rules

1. **Namespace:** the class must live in `Monsoon\Modules\{NsPart}` where
   `NsPart` matches the loader's mapping. For a new official module, submit the
   slug→class mapping to `ModuleLoader` so the kernel instantiates it.
2. **Constructor:** `public function __construct(\mysqli $db)`. The kernel passes
   the active MySQLi connection.
3. **`registerRoutes(Router $router): void`** — register API and public routes
   here. This is the only method the loader calls.
4. **Strict types:** declare `declare(strict_types=1);` at the top of the file.

### Example — `modules/backup-restore/BackupModule.php` (trimmed)

```php
declare(strict_types=1);
namespace Monsoon\Modules\BackupRestore;

use Monsoon\Kernel\Router;
use Monsoon\Kernel\Response;

final class BackupModule
{
    private \mysqli $db;
    private BackupService $backupService;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
        $this->backupService = new BackupService($db);
    }

    public function registerRoutes(Router $router): void
    {
        $router->addRoute('GET', '/api/v1/backups', function () {
            return Response::json(['data' => $this->backupService->getAll()]);
        });

        $router->addRoute('POST', '/api/v1/backups', function () {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
            $backup = $this->backupService->createBackup($data['name'] ?? 'Backup', ...);
            return Response::json(['data' => $backup], 201);
        });
    }
}
```

---

## 4. Registering routes

Routing is handled by `kernel/src/Router.php`. A route is added with:

```php
$router->addRoute(string $method, string $pattern, callable $handler): void
```

- `$method` — `GET`, `POST`, `PUT`, `DELETE` (case-insensitive; stored
  upper-cased).
- `$pattern` — a clean URL with **no file extension**. Two placeholder types
  are supported (`Router::patternToRegex`):
  - `{slug}` → matches `[a-z0-9-]+`
  - `{id}` (or any other `{name}`) → matches a UUID v4 `[a-f0-9-]{36}`
- `$handler` — `callable`. It receives `array $params` (the matched named
  segments). It must return either:
  - a `Monsoon\Kernel\Response` object, **or**
  - an `array` with `status`, `headers`, `body` keys (the router wraps it in a
    `Response`).

### Helpers from `kernel/src/Response.php`

```php
Response::json(mixed $data, int $status = 200)   // application/json
Response::html(string $body, int $status = 200)  // text/html; charset=utf-8
Response::redirect(string $url, int $status = 302)
Response::empty(int $status = 204)
Response::error(int $status, string $message)    // {"error":{"code":..,"message":..}}
```

### API routes vs. admin routes vs. public routes

- **API routes** are registered programmatically inside `registerRoutes()` and
  typically return `Response::json(...)`. See `SeoModule`/`BackupModule`.
- **Admin routes** are declared in `manifest.json` under `admin_routes` and
  rendered through an admin page file (Section 5).
- **Public routes** are declared in `manifest.json` under `public_routes`. The
  loader registers a GET handler that returns a plain text placeholder body
  (`ModuleLoader::activate` / `loadModules`). Use these for simple public
  endpoints; for anything structured, register the route in `registerRoutes()`
  instead.

### Hooks (theme integration)

A module can register callbacks against theme lifecycle hooks via
`ThemeHooks::getInstance()->register($hook, $callback, $priority)`. See
`seo-basics/SeoModule.php:46` — it registers `theme:head` and `theme:body:end`
and injects meta tags into the page head. Available hooks are documented in
`Theme-Development.md`.

---

## 5. Admin pages — and the namespacing gotcha

Admin screens are declared in `manifest.json` `admin_routes`. For each route the
loader (`ModuleLoader.php:154`) looks for a file at:

```
modules/{slug}/admin/{basename(route)}-page.php   (preferred)
modules/{slug}/admin/{basename(route)}.php        (fallback)
```

For route `/manage/seo` the loader resolves `admin/seo-page.php`; for
`/manage/backup` it resolves `admin/backup-page.php`.

### ⚠️ The render function MUST be namespaced

The loader does **not** simply include the file and echo it. After including the
file it computes a function name and, if that function exists, calls it to
produce the page body (`ModuleLoader.php:161-173`):

```php
$nsPart = $moduleClasses[$slug][1] ?? ucfirst(str_replace('-', '', $slug));
$this->router->addRoute('GET', $route, function () use ($adminPath, $slug, $nsPart, $route) {
    if (is_file($adminPath)) {
        ob_start();
        require $adminPath;
        $output = ob_get_clean();

        $pageKey = preg_replace('/[^a-z0-9]/i', '', ucwords(str_replace('-', ' ', basename($route))));
        $funcName = "Monsoon\\Modules\\{$nsPart}\\render{$pageKey}Page";

        if (function_exists($funcName)) {
            $output = $funcName();
        }
        return Response::html($output);
    }
    return Response::html("<h1>Module: $slug</h1>");
});
```

Two things to internalize:

1. **The file must declare `namespace Monsoon\Modules\{NsPart};`** where
   `{NsPart}` is exactly the namespace part the loader expects for that slug
   (e.g. `SeoBasics`, `BackupRestore`). If you forget the namespace, the loader's
   `function_exists("Monsoon\\Modules\\{NsPart}\\render{Page}Page")` check will
   fail and your page will render as **empty/partial** (only the bare included
   output, or nothing).
2. **The render function must be named
   `render{Page}Page()`** inside that namespace, where `{Page}` is the
   route basename with `-`/separators stripped and each word capitalized. The
   loader builds it as:

   ```
   pageKey = ucwords( str_replace('-',' ', basename($route)) )  with non-alnum removed
   funcName = Monsoon\Modules\{NsPart}\render{pageKey}Page
   ```

   Concretely:

   | `admin_routes` value | admin file | `pageKey` | expected function |
   |---------------------|------------|-----------|-------------------|
   | `/manage/seo` | `admin/seo-page.php` | `Seo` | `Monsoon\Modules\SeoBasics\renderSeoPage()` |
   | `/manage/backup` | `admin/backup-page.php` | `Backup` | `Monsoon\Modules\BackupRestore\renderBackupPage()` |
   | `/manage/hello-world` | `admin/hello-world-page.php` | `HelloWorld` | `Monsoon\Modules\HelloWorld\renderHelloWorldPage()` |

   The function must `return string` (the full HTML). Whatever the file echoes at
   top level is buffered but **discarded** if the render function exists.

### Correct admin page skeleton

`modules/hello-world/admin/hello-world-page.php`:

```php
<?php

declare(strict_types=1);

namespace Monsoon\Modules\HelloWorld;

function renderHelloWorldPage(): string
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hello World - Monsoon CMS</title>
</head>
<body>
  <h1>Hello World</h1>
  <p>This admin screen is rendered by renderHelloWorldPage().</p>
</body>
</html>
HTML;
}
```

> Real reference: `modules/backup-restore/admin/backup-page.php` declares
> `namespace Monsoon\Modules\BackupRestore;` and defines
> `function renderBackupPage(): string`. The `seo-basics/admin/seo-page.php` file
> currently mixes namespaced top-level HTML output with the same convention —
> follow the `render{Page}Page()` function pattern from `backup-page.php`.

---

## 6. Capabilities & the PermissionGate

Monsoon's security model is the inverse of WordPress's: a module may only
perform actions inside a **declared, kernel-enforced permission scope**. The
gate lives in `kernel/src/PermissionGate.php` as a singleton.

### Registration

The kernel calls `PermissionGate::getInstance()->registerDefaults()` at boot
(`Kernel.php:26`), which registers the 18 built-in capabilities
(`PermissionGate::registerDefaults`):

| # | Capability | Description |
|---|-----------|-------------|
| 1 | `content.read` | Read content items |
| 2 | `content.write` | Create and update content items |
| 3 | `content.delete` | Delete content items |
| 4 | `media.read` | Read media files |
| 5 | `media.write` | Upload and update media files |
| 6 | `media.delete` | Delete media files |
| 7 | `user.read` | Read user data |
| 8 | `user.write` | Create and update users |
| 9 | `user.delete` | Delete users |
| 10 | `settings.read` | Read system settings |
| 11 | `settings.write` | Update system settings |
| 12 | `mail.send` | Send email |
| 13 | `files.read` | Read files from the filesystem |
| 14 | `files.write` | Write files to the filesystem |
| 15 | `database.dump` | Export database dump |
| 16 | `auth.read` | Read authentication configuration |
| 17 | `auth.write` | Modify authentication configuration |
| 18 | `database.dump` + `auth.*` complete the set; the full 18 are listed above | — |

> Note: the canonical 18 capabilities are exactly those listed in the
> `registerDefaults()` table above (`content.read/write/delete`, `media.read/
> write/delete`, `user.read/write/delete`, `settings.read/write`, `mail.send`,
> `files.read/write`, `database.dump`, `auth.read/write`). Declare the ones your
> module needs in `manifest.json` under `capabilities_required`.

### How the gate enforces scope

A module declares its scope via `declareModuleScope(string $moduleSlug, array $capabilities)`,
then checks at runtime:

```php
$gate = PermissionGate::getInstance();

$gate->hasCapability('content.read');                 // is this a known capability?
$gate->declareModuleScope('seo-basics', [             // what THIS module may do
    'content.read', 'settings.read', 'settings.write'
]);

$gate->check('seo-basics', 'settings.write');         // true
$gate->assert('seo-basics', 'mail.send');             // throws RuntimeException if not in scope
```

- `check($moduleSlug, $capability): bool` — returns whether the capability is in
  the module's declared scope.
- `assert($moduleSlug, $capability): void` — throws `RuntimeException` if the
  capability is not in scope. Use this as a guard before sensitive operations.

Official modules should `assert()` their required capability before performing
the corresponding action, mirroring the manifest's `capabilities_required`.

---

## 7. Migrations

Schema changes live as SQL files in the module's `migrations/` directory and are
run by `kernel/src/MigrationRunner.php`. Rules:

- File glob: `[0-9]*.sql` (numeric-prefixed), sorted ascending.
- Each file is executed with `multi_query`, wrapped in a transaction; on
  failure the transaction rolls back and the migration is recorded as failed.
- Applied migrations are tracked in the `schema_migrations` table (created
  automatically), so each migration runs **exactly once**.
- Use `utf8mb4` / InnoDB consistently; match the core data model (UUID v4
  primary keys, prepared statements everywhere — never concatenate queries).

Example `modules/hello-world/migrations/20250101000001_create_hello.sql`:

```sql
CREATE TABLE IF NOT EXISTS hello_world (
    id CHAR(36) NOT NULL PRIMARY KEY,
    greeting VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

> Tip: generate UUIDs with `Monsoon\Kernel\Uuid::v4()` from PHP when inserting.

---

## 8. How the kernel lazy-loads modules per route

The dispatcher does **not** boot every module on every request. In
`Kernel::handle()` (`kernel/src/Kernel.php:36`):

1. A `Router` is built and the middleware pipeline (CSRF + Auth) is attached.
2. If a database is reachable, `ApiRouter::register(...)` and
   `$this->moduleLoader->loadModules($db)` run. `loadModules()` calls
   `discover()` (scans `modules/*` for a `manifest.json`), then for each
   **recognized** slug instantiates the module class and calls
   `registerRoutes($router)`.
3. Only routes that were actually registered match a request. A module's API
   handlers are only invoked when a URL matches one of its registered patterns.

This is why route registration must happen in `registerRoutes()` and why admin
routes must be declared in `manifest.json` — the loader builds the route table
from exactly those sources. Modules "load only what a site actually uses"
(PRD §11.1).

---

## 9. Minimal "Hello World" module (complete)

### `modules/hello-world/manifest.json`

```json
{
    "name": "hello-world",
    "slug": "hello-world",
    "version": "1.0.0",
    "description": "A minimal Monsoon module that says hello.",
    "author": "Monsoon",
    "capabilities_required": ["content.read"],
    "admin_routes": ["/manage/hello-world"],
    "public_routes": [],
    "migrations": []
}
```

### `modules/hello-world/HelloWorld.php`

```php
<?php

declare(strict_types=1);

namespace Monsoon\Modules\HelloWorld;

use Monsoon\Kernel\Router;
use Monsoon\Kernel\Response;

final class HelloWorld
{
    private \mysqli $db;

    public function __construct(\mysqli $db)
    {
        $this->db = $db;
    }

    public function registerRoutes(Router $router): void
    {
        $router->addRoute('GET', '/api/v1/hello/{slug}', function (array $params) {
            return Response::json([
                'data' => ['greeting' => 'Hello, ' . ($params['slug'] ?? 'world') . '!'],
            ]);
        });
    }
}
```

### `modules/hello-world/admin/hello-world-page.php`

```php
<?php

declare(strict_types=1);

namespace Monsoon\Modules\HelloWorld;

function renderHelloWorldPage(): string
{
    return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hello World - Monsoon CMS</title>
</head>
<body>
  <h1>Hello World</h1>
  <p>Your first official Monsoon module is working.</p>
</body>
</html>
HTML;
}
```

### Wiring it up

Add the slug→class mapping to `ModuleLoader::loadModules()` so the kernel
instantiates it (mirror the existing `$moduleClasses` array):

```php
'hello-world' => ['HelloWorld', 'HelloWorld'],
```

Then visiting `/manage/hello-world` renders the admin page, and
`/api/v1/hello/monsoon` returns JSON. The route `/api/v1/hello/{slug}` matches a
UUID-shaped `{slug}` only if the value is a UUID; use `{slug}` for slug-shaped
segments (`[a-z0-9-]+`) instead.

---

## 10. Reference file map

| Concern | File |
|---------|------|
| Manifest parsing | `kernel/src/ModuleManifest.php` |
| Discovery & loading | `kernel/src/ModuleLoader.php` |
| Permission scope enforcement | `kernel/src/PermissionGate.php` |
| Routing | `kernel/src/Router.php` |
| Responses | `kernel/src/Response.php` |
| Migrations | `kernel/src/MigrationRunner.php` |
| Boot / wiring | `kernel/src/Kernel.php` |
| UUID helper | `kernel/src/Uuid.php` |
| Real examples | `modules/seo-basics/*`, `modules/backup-restore/*` |
