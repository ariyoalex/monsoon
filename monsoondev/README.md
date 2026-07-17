# Monsoon CMS — Developer Resources (`monsoondev/`)

This directory is the developer hub for building on and contributing to **Monsoon CMS**.
It is intentionally kept outside the shipped `kernel/`, `modules/`, and `themes/` directories so it never pollutes the runtime.

## Contents

| File | What it covers |
|------|----------------|
| [`Architecture-Overview.md`](./Architecture-Overview.md) | Boot sequence, request lifecycle, layers, invariants, module system, known gaps. |
| [`API-Reference.md`](./API-Reference.md) | Full REST API reference (`/api/v1`): every route, auth, params, examples, response envelopes. |
| [`Module-Development.md`](./Module-Development.md) | How to build an official module: manifest schema, Module class contract, admin pages, capabilities, migrations, Hello World example. |
| [`Theme-Development.md`](./Theme-Development.md) | How to build a theme: `theme.json`, template hierarchy, asset enqueue, menus, widgets, customizer, hooks, minimal example. |

## Quick Start for Developers

```bash
# 1. Start the local environment
ddev start

# 2. Install dependencies (classmap for modules)
composer dump-autoload

# 3. Open the site
#    Public:  https://monsoon.ddev.site/
#    Admin:   https://monsoon.ddev.site/manage/dashboard
#    Login:   admin@monsoon.local / admin123

# 4. Run code quality checks
composer phpstan        # static analysis
composer phpcs          # coding standard
vendor/bin/phpunit      # tests
```

## Conventions (non-negotiable)

- `declare(strict_types=1)` in every PHP file.
- MySQLi prepared statements only.
- UUID v4 primary keys.
- Clean URLs (no `.php` extensions).
- Admin lives under `/manage/`.
- Vanilla JS only in core — no build step.
- No CSS gradients, no emoji in the UI.

## Where to look in the codebase

- Kernel: `kernel/src/`
- Modules: `modules/{slug}/`
- Themes: `themes/{slug}/`
- Public assets: `public/`
- Migrations: `migrations/`
- Roadmap: `tasks-phases.md` (repo root)

## Product context

The product requirements and feature-parity matrix against WordPress live in `Monsoon_CMS_PRD.md` (repo root). The build roadmap is in `tasks-phases.md`.
