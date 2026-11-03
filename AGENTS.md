# AGENTS.md

## What this is

GlotPress is a **WordPress plugin** that provides a collaborative, web-based gettext translation tool. It has its own MVC-ish framework, custom database tables, and custom URL router layered on top of WordPress. It is served under `<home_url>/glotpress/` and **requires pretty permalinks** (the rewrite engine) to function.

## Commands

Dependencies: `composer install` (PHP tooling) and `npm install` (build + wp-env).

### Local environment (wp-env / Docker)
```bash
npm run env:start        # start WP + set pretty permalinks; site at http://localhost:8888/glotpress/
npm run env:stop
npm run env:phpunit      # run the full PHPUnit suite inside wp-env
npm run env:phpunit -- --filter test_multiple_imports_singular   # single test (note the `--`)
```

### PHP (run directly if you have a configured WP test DB via bin/install-wp-tests.sh)
```bash
composer test            # phpunit (excludes the `locales` group by default, see phpunit.xml.dist)
composer test:locales    # phpunit --group locales
composer lint            # phpcs (WordPress Coding Standards)
composer format          # phpcbf (auto-fix)
vendor/bin/phpunit --filter test_name          # single test
vendor/bin/phpunit tests/phpunit/testcases/test_urls.php   # single file
```
PHPUnit is pinned to **9.x** (the WordPress test suite is not compatible with 10+). Uses `yoast/phpunit-polyfills`.

### JS / CSS
```bash
npm run build            # grunt: uglify assets/js/*.js -> *.min.js, cssmin assets/css/*.css -> *.min.css
npm run watch            # rebuild minified assets on change
npm run lint:js          # wp-scripts lint-js
npm run lint:js-fix
```
Assets are **hand-written, not bundled** — edit the source `.js`/`.css` in `assets/` and regenerate the committed `.min.*` files with `npm run build`. There is no webpack/React build step.

## Architecture

### Bootstrap
`glotpress.php` (plugin entry) guards on PHP/WP version and permalink structure, then requires `gp-settings.php`, which wires up every include: constants, the `wp_gp_*` table map on `$wpdb`, the `GP` class, Things, Routes, and Formats. Nearly all state hangs off the static singleton class **`GP`** (`gp-includes/gp.php`) — `GP::$router`, `GP::$project`, `GP::$translation`, `GP::$formats`, etc.

### Things = the model layer (`gp-includes/things/`)
`GP_Thing` (`gp-includes/thing.php`) is a lightweight active-record base over GlotPress's **own tables** (`wp_gp_projects`, `wp_gp_originals`, `wp_gp_translations`, `wp_gp_translation_sets`, `wp_gp_glossaries`, `wp_gp_glossary_entries`, `wp_gp_permissions`, `wp_gp_meta`) — GlotPress does **not** use WP posts/postmeta. Each subclass declares `$field_names`, `$int_fields`, `$table_basename`, and validation via `restrict_fields()`. CRUD goes through `create()`/`save()`/`update()`/`delete()` and querying through `find_one()`/`find_many()`/`one()`/`many()`, with `after_create()`/`after_save()`/`after_delete()` hooks. All queries funnel through `GP_Thing::prepare()` (wpdb::prepare wrapper) — follow the existing `// phpcs:ignore WordPress.DB.PreparedSQL` patterns when adding SQL. Schema lives in `gp-includes/schema.php`.

The domain: a **Project** has **Originals** (source strings) and **Translation Sets** (a locale+slug pairing); a Translation Set has **Translations**. **Glossaries**/**Glossary Entries** and **Permissions** (validator/admin) round it out.

### Routing = the controller layer (`gp-includes/router.php`, `route.php`, `routes/`)
`GP_Router::default_routes()` maps `method:regex` URL patterns to `array( 'GP_Route_*', 'method_name' )`. `GP_Router::route()` matches the current request, instantiates the controller, and calls the method with regex capture groups as arguments. **Route ordering matters** — literal-suffixed routes must precede catch-all `$project`/`$set` patterns (see comments in `default_routes()`). Controllers extend `GP_Route` (`route.php`), which provides `tmpl()`, `die_with_error()`, `redirect()`, `validate()`, and the `gp_before_request`/`gp_after_request` action hooks. Append `/api/` to a route for the JSON/API variant (`$route->api`).

### Templates = the view layer (`gp-templates/`)
Plain PHP templates rendered via `gp_tmpl_load()` / `GP_Route::tmpl()`. HTML output helpers live in `gp-includes/template.php` and `template-links.php` (`gp_link_*`, `gp_breadcrumb`, `gp_pagination`, `gp_select`, …). `*.api.php` templates render API responses. Themes can override templates by placing them in the active theme.

### Formats (`gp-includes/formats/`)
Import/export drivers extending `GP_Format` — PO/MO, Android XML, `.strings`, `.properties`, JSON, Jed 1.x, NGX, PHP, RESX. Registered into `GP::$formats` in `gp-settings.php`.

### Translation validation (`gp-includes/warnings.php`, `errors.php`, `validation.php`)
`GP_Translation_Warnings` (non-blocking) and `GP_Translation_Errors` (blocking) validate translations against originals — placeholder mismatches, tag balance, length, etc. Built-in rule sets are registered in `gp-settings.php`.

### WP-CLI (`gp-includes/cli/`)
Commands registered under the `glotpress` namespace (`gp-includes/cli.php`), e.g. `wp glotpress add-admin`, `wp glotpress import-originals`, `wp glotpress translation-set`.

## Conventions

- **WordPress Coding Standards** enforced via `phpcs.xml.dist` (WordPress-Core / -Docs / -Extra + PHPCompatibilityWP). Run `composer lint` before finishing. Text domain is `glotpress`.
- **PHP 7.4+** is the floor (`composer.json`, plugin header, phpcs `testVersion 7.4-`). Do not use syntax newer than 7.4 in shipped code.
- Everything is prefixed `gp_` / `GP_` / `GP::`. Procedural helpers live in `gp-includes/misc.php` and template files.
- **Escaping**: output-escaping is checked by phpcs; `phpcs.xml.dist` lists the project's custom auto-escaped/escaping functions (e.g. `gp_link*`, `esc_translation`) — reuse those rather than adding `phpcs:ignore`.
- **Base branch is `develop`** (not `main`/`stable`). Branch names: `ISSUEID-keywords`. PRs target `develop` and should reference `Fixes #issue` / `Part of #issue`.
- **Schema changes** require bumping `GP_DB_VERSION` in `glotpress.php`; the upgrade routine (`gp-includes/install-upgrade.php`) runs on admin load when the stored version is lower. Releases bump `GP_VERSION` (also in `glotpress.php` / readme) and flip `GP_SCRIPT_DEBUG` to false via `npm run prepare-release`.

## Testing notes

- Tests are in `tests/phpunit/testcases/` (`test_*.php`), grouped into `tests_things/`, `tests_routes/`, `tests_formats/`, `tests_testlib/`. Base classes: `tests/phpunit/lib/testcase*.php` (`GP_UnitTestCase`, route/request cases). Factories and fixtures under `tests/phpunit/`.
- CI (`.github/workflows/phpunit.yml`) runs the matrix PHP 7.4 & 8.3 × WP latest & nightly × single-site & multisite, plus a separate `--group locales` run. `coding-standards.yml` runs phpcs.
- To add a test, drop it in the matching `testcases` file/dir; PHPUnit autodiscovers `test_*` files.
