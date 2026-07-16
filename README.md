# Monsoon CMS

An open source, modular content management system — WordPress's ease of use, rebuilt on a modern, strictly typed, permissioned core.

## Philosophy

Monsoon takes a different starting point from traditional CMS platforms. The core is a minimal, strictly-typed PHP 8.4 kernel that handles only the essentials: routing, authentication, the content model, and a permissioned module system. Everything else — page builder, SEO, forms, e-commerce, multi-site — ships as official modules that plug into the kernel through a declared manifest contract.

This keeps the core small, secure, and fast, while still giving non-technical users a one-click experience through curated modules installed by default.

## Key Features (v1)

- Minimal kernel with a permission-gated module system
- Strictly typed PHP 8.4 throughout
- Lightweight vanilla JS block editor (AJAX-driven)
- Clean URLs, REST API out of the box
- Multi-tenant data model from day one
- WordPress import tool for migration
- Bundled modules: SEO, Forms, Backup, Security Hardening
- Bootstrap 5 admin UI at `/manage/`
- UUID v4 primary keys everywhere
- GPL v3 licensed

## Architecture

```
/Monsoon
  /kernel          # Routing, auth, module loader, permission gate, content schema
  /modules         # Official modules (self-contained, each with its own manifest)
  /themes          # Theme system with starter theme
  /manage          # Admin UI (never /admin)
  /public          # Web root, front controller only
  /migrations      # Versioned schema migrations
  /tests           # Test suite
```

Every module declares its capability scope in a manifest. The kernel enforces that boundary at runtime — a module that only needs `content.read` cannot write to the database, send email, or access another module's settings.

## Requirements

- PHP 8.4+
- MySQL 8.0+ / MariaDB 10.6+
- Apache with mod_rewrite or nginx
- Shared hosting compatible

## Getting Started

*Coming soon — installation wizard and setup guide.*

## Development

```bash
# Clone the repo
git clone https://github.com/ariyoalex/monsoon.git
cd monsoon

# Start DDEV (recommended)
ddev start

# Or use your own PHP/MySQL setup
```

## License

GNU General Public License v3.0 — see [LICENSE](LICENSE).
