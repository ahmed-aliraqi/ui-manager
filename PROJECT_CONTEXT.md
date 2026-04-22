# Project Context

## What this package does

`ahmed-aliraqi/ui-manager` is a Laravel package that gives developers a class-driven system for managing dynamic UI content — page sections, field values, and repeatable item lists — through a pre-built Vue 3 SPA dashboard. The host application defines pages and sections as PHP classes; the package handles storage, the admin UI, and the public Blade API.

## Core idea

Content management without a CMS. Developers define *what* fields exist (in PHP), the package handles *where* the data is stored (database) and *how* it's edited (dashboard). Blade views consume data through a fluent, read-only API — they never touch the database directly.

```
Developer defines → PHP Section class
User edits        → Vue SPA dashboard
Blade consumes    → ui()->section('banner')->field('title')
```

## Main features

### Pages
Named groupings of sections, auto-discovered from `app/Ui/Pages`. Each page appears as a sidebar item in the dashboard. Defined as classes extending `AhmedAliraqi\UiManager\Core\Page`.

### Sections
Belong to a page, hold a set of typed fields, stored as a single JSON `fields` column in `ui_contents`. Two kinds:
- **Single** — one record per section; e.g. a hero banner.
- **Repeatable** — multiple ordered rows; e.g. a features list. Marked with the `Repeatable` interface.

Auto-discovered from `app/Ui/Sections`.

### Fields
Typed, fluent builders. Each field has a name, label, validation rules, default value, and type-specific props. They are serialised to JSON and sent to the Vue frontend to generate the correct input component.

| Factory | Type | Stored as |
|---|---|---|
| `Field::text()` | `text` | string |
| `Field::textarea()` | `textarea` | string |
| `Field::editor()` | `editor` | HTML string |
| `Field::select()` | `select` | string (option key) |
| `Field::image()` | `image` | `{ id, url, filename }` (Spatie media) |
| `Field::file()` | `file` | `{ id, url, filename }` (Spatie media) |
| `Field::color()` | `color` | hex/rgb/hsl string |
| `Field::date()` | `date` | ISO date string (YYYY-MM-DD) |
| `Field::time()` | `time` | time string (HH:MM) |
| `Field::datetime()` | `datetime` | ISO datetime string |
| `Field::dateRange()` | `date_range` | `{ start, end }` date strings |
| `Field::url()` | `url` | URL string |
| `Field::price()` | `price` | `{ amount, currency }` |

### Repeatable sections
Sections that implement `AhmedAliraqi\UiManager\Contracts\Repeatable`. Each item is a separate row in `ui_contents` with a non-null `sort_order`. The dashboard renders an expandable list with drag-and-drop reordering.

### Variables system
Any string field value can contain `%section.field%` placeholders. The `VariableParser` service resolves them recursively (with loop/depth protection) at read time in `FieldValueData::getString()`. Custom variables are registered via `VariableRegistry`.

### Multi-language / Translatable fields
Any field can be marked translatable with `->translatable()`. The stored value becomes a locale-keyed object `{ "en": "...", "ar": "..." }`. `FieldValueData::getString()` automatically resolves the current app locale, with fallback to `default_locale`. An explicit locale is accessed via `field('title:ar')`. Configure supported locales in `config/ui-manager.php`.

### Select field label access
`SelectField` stores the option **key** by default. Call `->label()` on `FieldValueData` to get the human-readable label. Use `->returnLabel()` on the field definition to make `getString()` return the label automatically.

## How data flows

```
Class definition (Section::fields())
        ↓
Dashboard reads section via GET /api/pages/{page}/sections/{section}
        ↓
User edits form and submits
        ↓
SectionController saves to ui_contents (fields JSON column)
        ↓
Cache is flushed for that page+section key
        ↓
Blade request calls ui()->section('name')->field('key')
        ↓
UiManager reads from cache (or DB if cold)
        ↓
Returns FieldValueData — variables resolved on getString()/getUrl()
```

## Package structure (top level)

```
config/          — ui-manager.php (routes, cache, discovery, locales, media)
database/        — migrations for ui_contents, ui_media_files, media tables
dist/            — pre-built Vite assets (shipped in repo, no npm needed by hosts)
docs/            — this documentation
resources/
  js/            — Vue 3 SPA source
  views/         — dashboard.blade.php (SPA shell)
routes/
  api.php        — JSON REST API
  web.php        — SPA catch-all
src/             — all PHP source
tests/           — PHPUnit Feature + Unit tests
```
