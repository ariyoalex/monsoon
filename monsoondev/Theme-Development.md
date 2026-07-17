# Theme Development

This guide explains how to build a **theme** for Monsoon CMS. Themes live under
`themes/{name}/` and are described by a `theme.json` manifest. The kernel's
`ThemeLoader` resolves templates through a hierarchy and exposes site settings,
menus, widget areas, a visual customizer, and lifecycle hooks that modules can
use to inject content.

All paths are relative to the repository root. The reference theme is
`themes/starter/` (its `theme.json` is the canonical example).

---

## 1. Theme structure

```
themes/
  starter/
    theme.json        # required: manifest (name, supports, templates, assets, settings)
    index.php         # required: fallback template
    page.php          # renders a single page (by slug)
    single.php        # renders a single post
    404.php           # not-found template
    header.php        # optional partial (get_template_part('header'))
    footer.php        # optional partial (get_template_part('footer'))
    front-page.php    # optional: site front page
    archive.php       # optional: post listings
    search.php        # optional: search results
    style.css         # enqueued CSS (declared in theme.json assets.css)
    theme.js          # enqueued JS  (declared in theme.json assets.js)
```

| Path | Required | Purpose |
|------|----------|---------|
| `theme.json` | yes | Manifest describing supports, templates, assets, settings. |
| `index.php` | yes | Final fallback when no other template matches. |
| other `*.php` | no | Mapped via the `templates` map in `theme.json`. |

---

## 2. theme.json manifest

Parsed by `ThemeLoader::loadManifest()` (`kernel/src/ThemeLoader.php:92`). The
loader merges in two runtime keys: `_path` (absolute theme directory) and
`_name` (theme slug). The canonical manifest is `themes/starter/theme.json`:

```json
{
    "name": "Starter",
    "version": "1.0.0",
    "description": "Clean, responsive starter theme for Monsoon CMS",
    "author": "Monsoon",
    "supports": {
        "title": true,
        "description": true,
        "logo": true,
        "menus": ["primary", "footer"],
        "widgetAreas": ["sidebar", "footer"],
        "blockTypes": ["paragraph", "heading", "image", "list", "quote", "embed", "separator", "button"],
        "customColors": true,
        "customTypography": true
    },
    "templates": {
        "front-page": "front-page.php",
        "page": "page.php",
        "single": "single.php",
        "archive": "archive.php",
        "search": "search.php",
        "404": "404.php",
        "index": "index.php"
    },
    "assets": {
        "css": ["style.css"],
        "js": ["theme.js"]
    },
    "settings": {
        "primaryColor": "#1034A6",
        "sidebarColor": "#1A1A1A",
        "backgroundColor": "#F4F6FA",
        "fontBody": "Graphik",
        "fontHeading": "Means"
    }
}
```

### Field reference

| Key | Type | Required | Meaning |
|-----|------|----------|---------|
| `name` | `string` | recommended | Human-readable theme name. |
| `version` | `string` | no | Theme version. |
| `description` | `string` | no | Short description. |
| `author` | `string` | no | Author / vendor. |
| `supports` | `object` | no | Declares theme features (see below). |
| `templates` | `object` | no | Maps a logical template key → file name. |
| `assets.css` | `array<string>` | no | CSS files enqueued for the theme. |
| `assets.js` | `array<string>` | no | JS files enqueued for the theme. |
| `settings` | `object` | no | Visual customizer defaults (see §7). |

### `supports` sub-keys

| Key | Value | Effect |
|-----|-------|--------|
| `title` | `bool` | Theme exposes/edits a page title. |
| `description` | `bool` | Theme exposes a meta description. |
| `logo` | `bool` | Theme supports a custom logo. |
| `menus` | `array<string>` | Named menu locations (e.g. `primary`, `footer`). |
| `widgetAreas` | `array<string>` | Named widget areas (e.g. `sidebar`, `footer`). |
| `blockTypes` | `array<string>` | Block types the editor may use (paragraph, heading, image, list, quote, embed, separator, button). |
| `customColors` | `bool` | Visual customizer may set colors. |
| `customTypography` | `bool` | Visual customizer may set fonts. |

---

## 3. Template hierarchy

The loader resolves a template via `ThemeLoader::getTemplatePath(string $template)`
(`kernel/src/ThemeLoader.php:111`). It looks up `$templates[$template]` first,
falling back to `$template . '.php'`. The documented hierarchy — most specific
to most general — is:

```
page-{slug}.php   →  a specific page by slug
page.php          →  any single page
single.php        →  a single post
archive.php       →  post listings
search.php        →  search results
404.php           →  not found
index.php         →  ultimate fallback
```

`ThemeLoader::renderTemplate(string $template, array $data)` resolves the path,
falls back to `index` if the requested template is missing, then `extract()`s
`$data` and `include`s the file. If neither the requested template nor `index`
exists, it echoes `<p>Template not found.</p>`.

> Practical guidance: always provide `index.php` as the safety net. Provide
> `page.php` and `single.php` for the common cases, then add `page-{slug}.php`
> only when a specific page needs a bespoke layout.

### Active theme selection

`ThemeLoader` reads the active theme from `settings.theme_active` (falling back
to `starter` when no DB is present). Set it with
`ThemeLoader::setActiveTheme(string $name)`; read it with `getActiveThemeName()`
or `getActiveTheme()`.

---

## 4. Template loader & data

To render a template from PHP:

```php
$loader = new Monsoon\Kernel\ThemeLoader(__DIR__ . '/themes');
$loader->renderTemplate('page', [
    'title'   => 'About',
    'content' => $contentArray,   // becomes $content inside the template
]);
```

Inside `page.php`, the extracted variables (`$title`, `$content`, …) are in
scope. A simple `page.php`:

```php
<?php // themes/starter/page.php
declare(strict_types=1);
?>
<?php get_template_part('header'); ?>

<main class="container py-4">
    <h1><?= htmlspecialchars($title ?? '', ENT_QUOTES) ?></h1>
    <?php foreach (($blocks ?? []) as $block): ?>
        <?= render_block($block) ?>
    <?php endforeach; ?>
</main>

<?php get_template_part('footer'); ?>
```

---

## 5. Assets (enqueue)

CSS and JS are declared in `theme.json` under `assets.css` / `assets.js`. The
declared file names are resolved relative to the theme directory
(`{theme._path}/{file}`). Reference them in your templates with absolute web
paths, e.g. `/themes/starter/style.css`. There is no automatic enqueue function
in core; themes link their assets directly in `header.php` / `footer.php`.

`themes/starter/header.php` excerpt:

```html
<link rel="stylesheet" href="/themes/starter/style.css">
```

The visual customizer writes color/font overrides into the `settings` table
(`theme_%`, `site_%`, `*_color`, `font_%` keys — see
`ThemeLoader::getThemeSettings()`); read them via
`ThemeLoader::getThemeSettings()` to inline CSS variables.

---

## 6. Menu system

Menus are managed by `kernel/src/MenuService.php` and stored in the `menus`
table. Each menu has a `name` and a `location` (which should match a
`supports.menus` entry such as `primary` or `footer`). Items are stored as JSON.

Common calls:

```php
$menuService = new Monsoon\Kernel\MenuService($db);

$menus        = $menuService->getAll();          // all menus
$primary      = $menuService->getByLocation('primary');  // menu for a location
$one          = $menuService->findById($uuid);  // a specific menu
$created      = $menuService->create([           // create
    'name' => 'Footer', 'location' => 'footer', 'items' => []
]);
$updated      = $menuService->update($id, ['items' => $items]);
$deleted      = $menuService->delete($id);
```

`$menu['items']` is a decoded array of menu items you iterate in the template to
build `<nav>` markup. There is a global `wp_nav_menu(array $args)` stub in
`TemplateFunctions.php` for template compatibility, but the canonical path is to
use `MenuService` directly.

---

## 7. Widget areas

Widget areas are managed by `kernel/src/WidgetService.php` and stored across two
tables: `widget_areas` (per-theme, each has a `slug`) and `widgets` (each belongs
to an `area_id`, has a `type`, `title`, `settings` JSON, and an `order`).

API surface:

```php
$widgetService = new Monsoon\Kernel\WidgetService($db);

$areas   = $widgetService->getAreas('starter');        // widget areas for a theme
$widgets = $widgetService->getWidgets($areaId);        // widgets in an area (ordered)
$area    = $widgetService->createArea([                // create an area
    'name' => 'Sidebar', 'slug' => 'sidebar', 'theme' => 'starter'
]);
$widget  = $widgetService->createWidget([              // create a widget
    'area_id' => $areaId, 'type' => 'text', 'title' => 'Note', 'settings' => [], 'order' => 0
]);
$widgetService->updateWidget($id, ['settings' => $settings]);
$widgetService->deleteWidget($id);
```

In a template, read the areas declared by the theme (`supports.widgetAreas`),
fetch each area's widgets, and render them in the matching region (e.g. a
`sidebar` area renders into an `<aside>`).

### Visual customizer settings keys

The customizer persists theme options in `settings`. `ThemeLoader::getThemeSettings()`
returns keys matching `site_%`, `theme_%`, `*_color`, or `font_%`. The starter
theme declares these defaults under `theme.json` → `settings`:

| Setting key | Example | Meaning |
|-------------|---------|---------|
| `primaryColor` | `#1034A6` | Brand / primary button color. |
| `sidebarColor` | `#1A1A1A` | Sidebar background. |
| `backgroundColor` | `#F4F6FA` | Page background (Fog). |
| `fontBody` | `Graphik` | Body font family. |
| `fontHeading` | `Means` | Heading font family. |

Read and write them with `ThemeLoader::getThemeSettings()` /
`updateThemeSetting(string $key, string $value)`. These should be surfaced by
the customizer UI when `supports.customColors` / `supports.customTypography` are
`true`.

---

## 8. Theme hooks

Themes emit lifecycle hooks that modules subscribe to via
`kernel/src/ThemeHooks.php` (a singleton: `ThemeHooks::getInstance()`). The
`seo-basics` module registers `theme:head` and `theme:body:end` to inject meta
tags. The hook system supports:

```php
$hooks = Monsoon\Kernel\ThemeHooks::getInstance();

$hooks->register(string $hook, callable $callback, int $priority = 10): void;
$hooks->unregister(string $hook, callable $callback): void;
$hooks->apply(string $hook, mixed $data = null): mixed;   // filter: return value is passed on
$hooks->doAction(string $hook): void;                     // action: no return value
$hooks->hasHooks(string $hook): bool;
$hooks->getRegisteredHooks(): array;
```

### Calling hooks from a theme

In `header.php`, emit the head hook so modules can inject markup:

```php
<?php
$data = Monsoon\Kernel\ThemeHooks::getInstance()->apply('theme:head', [
    'content' => $content ?? null,
    'head_extra' => '',
]);
echo $data['head_extra'] ?? '';
?>
```

At the end of `body`, fire an action hook:

```php
<?php Monsoon\Kernel\ThemeHooks::getInstance()->doAction('theme:body:end'); ?>
```

### Available hooks (as used in the codebase)

| Hook | Kind | Fired by | Used by |
|------|------|----------|---------|
| `theme:head` | filter (`apply`) | theme `header.php` | `seo-basics` injects `<meta>`/OG/Schema into `head_extra`. |
| `theme:body:end` | action (`doAction`) | theme end of `<body>` | `seo-basics` for trailing scripts/markup. |

> These two hooks are the ones referenced by the shipped `seo-basics` module.
> Register additional hooks from your theme by calling `apply`/`doAction` at the
> appropriate lifecycle point; modules discover them by the same string name.

---

## 9. Template functions

`kernel/src/TemplateFunctions.php` provides global helpers usable inside
templates (no namespace required):

| Function | Signature | Purpose |
|----------|-----------|---------|
| `render_block` | `render_block(array $block): string` | Render a single block (heading, paragraph, image, list, quote, separator, button). Backed by `TemplateFunctions::renderBlock`. |
| `get_template_part` | `get_template_part(string $slug, array $data = []): void` | Include `{slug}.php` from the active theme (with extracted `$data`). Used for `header`/`footer`. |
| `bloginfo` | `bloginfo(string $key): void` | Echo a site info value (`name`, `charset`). |
| `home_url` | `home_url(string $path = ''): string` | Return the home URL (returns `/` when empty). |
| `esc_url` | `esc_url(string $url): string` | HTML-escape a URL. |
| `has_custom_logo` | `has_custom_logo(): bool` | Whether a custom logo is set (stub: returns `false`). |
| `body_class` | `body_class(): void` | Echo a `class="…"` attribute for `<body>`. |
| `wp_head` / `wp_body_open` / `wp_nav_menu` | `void` / `void` / `void` | WordPress-compatibility stubs; `wp_nav_menu` is a no-op. Prefer `MenuService` directly. |

`render_block()` example (inside a loop over content blocks):

```php
<?php foreach (($blocks ?? []) as $block): ?>
    <?= render_block($block) ?>
<?php endforeach; ?>
```

---

## 10. Minimal theme example

### `themes/monsoon-min/theme.json`

```json
{
    "name": "Monsoon Min",
    "version": "1.0.0",
    "description": "A minimal Monsoon theme.",
    "author": "Monsoon",
    "supports": {
        "title": true,
        "description": true,
        "menus": ["primary"],
        "widgetAreas": ["sidebar"],
        "blockTypes": ["paragraph", "heading", "image", "list"],
        "customColors": true,
        "customTypography": true
    },
    "templates": {
        "page": "page.php",
        "single": "single.php",
        "index": "index.php"
    },
    "assets": {
        "css": ["style.css"]
    },
    "settings": {
        "primaryColor": "#1034A6",
        "backgroundColor": "#F4F6FA",
        "fontBody": "Inter",
        "fontHeading": "Inter"
    }
}
```

### `themes/monsoon-min/index.php` (fallback)

```php
<?php
declare(strict_types=1);
extract($data ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title ?? 'Monsoon', ENT_QUOTES) ?></title>
  <link rel="stylesheet" href="/themes/monsoon-min/style.css">
  <?php
    $hd = Monsoon\Kernel\ThemeHooks::getInstance()->apply('theme:head', ['content' => $content ?? null, 'head_extra' => '']);
    echo $hd['head_extra'] ?? '';
  ?>
</head>
<body>
  <main class="container py-4">
    <h1><?= htmlspecialchars($title ?? '', ENT_QUOTES) ?></h1>
    <?php foreach (($blocks ?? []) as $block): ?>
        <?= render_block($block) ?>
    <?php endforeach; ?>
  </main>
  <?php Monsoon\Kernel\ThemeHooks::getInstance()->doAction('theme:body:end'); ?>
</body>
</html>
```

### `themes/monsoon-min/page.php`

```php
<?php
declare(strict_types=1);
get_template_part('header');
?>
<main class="container py-4">
  <h1><?= htmlspecialchars($title ?? '', ENT_QUOTES) ?></h1>
  <?php foreach (($blocks ?? []) as $block): ?>
      <?= render_block($block) ?>
  <?php endforeach; ?>
</main>
<?php get_template_part('footer'); ?>
```

---

## 11. Reference file map

| Concern | File |
|---------|------|
| Theme loading & templates | `kernel/src/ThemeLoader.php` |
| Theme lifecycle hooks | `kernel/src/ThemeHooks.php` |
| Template helper functions | `kernel/src/TemplateFunctions.php` |
| Menus | `kernel/src/MenuService.php` |
| Widget areas & widgets | `kernel/src/WidgetService.php` |
| Reference theme | `themes/starter/` (`theme.json`, `page.php`, `single.php`, `index.php`, `header.php`, `footer.php`, `404.php`) |
| Brand palette / typography | `Monsoon_CMS_PRD.md` §13 |
