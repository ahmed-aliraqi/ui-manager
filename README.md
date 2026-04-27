# UI Manager — Laravel Package

A production-ready, class-driven UI management system for Laravel. Define your UI structure in PHP, manage content from a modern SPA dashboard, and render it in Blade with a clean read-only API.

---

## Requirements

- PHP 8.2+
- Laravel 11+
- [Spatie Media Library v11](https://spatie.be/docs/laravel-medialibrary) (bundled migration included — no separate install needed)

---

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Creating Pages](#creating-pages)
4. [Creating Sections](#creating-sections)
5. [Field Types](#field-types)
6. [Field Validation](#field-validation)
7. [Repeatable Sections](#repeatable-sections)
   - [List Label Field](#list-label-field)
   - [Repeatable Section Defaults](#repeatable-section-defaults)
8. [Multi-language / Translatable Fields](#multi-language--translatable-fields)
9. [Variables System](#variables-system)
10. [Blade Usage](#blade-usage)
11. [Dashboard](#dashboard)
12. [Artisan Commands](#artisan-commands)
13. [Extending the Package](#extending-the-package)
14. [Testing](#testing)
15. [Architecture Overview](#architecture-overview)

---

## Installation

```bash
composer require ahmed-aliraqi/ui-manager
```

```bash
php artisan ui-manager:install
```

This single command publishes the config, copies the pre-built dashboard assets to `public/vendor/ui-manager/`, and runs the package migrations (including Spatie Media Library's `media` table).

Use `--force` to overwrite previously published files:

```bash
php artisan ui-manager:install --force
```

### Manual publishing (optional)

```bash
php artisan vendor:publish --tag=ui-manager-config      # config/ui-manager.php
php artisan vendor:publish --tag=ui-manager-assets      # public/vendor/ui-manager/
php artisan vendor:publish --tag=ui-manager-migrations  # customise migrations
```

---

## Configuration

After installation, `config/ui-manager.php` is available in your application:

```php
return [

    // Locales the dashboard renders input tabs for.
    'locales'        => ['en'],
    'default_locale' => 'en',

    'dashboard' => [
        'title'       => 'UI Manager',
        'home_button' => [
            'display' => false,
            'uri'     => '/dashboard',
            'label'   => 'Home',
        ],
    ],

    'routes' => [
        'prefix'         => 'ui-manager',           // dashboard at /ui-manager
        'middleware'     => ['web'],
        'api_prefix'     => 'ui-manager/api',
        'api_middleware' => ['web'],
    ],

    // Paths scanned at boot for Page and Section classes.
    'discovery' => [
        'pages_path'         => 'app/Ui/Pages',
        'sections_path'      => 'app/Ui/Sections',
        'pages_namespace'    => 'App\\Ui\\Pages',
        'sections_namespace' => 'App\\Ui\\Sections',
    ],

    'variables' => [
        'delimiter_start' => '%',
        'delimiter_end'   => '%',
        'max_depth'       => 10,
    ],

    'media' => [
        'disk' => 'public',
    ],

    'cache' => [
        'enabled' => env('UI_MANAGER_CACHE', true),
        'ttl'     => 3600,      // seconds
        'prefix'  => 'ui_manager_',
    ],
];
```

### Protecting the dashboard

Add your auth middleware to the routes keys:

```php
'routes' => [
    'middleware'     => ['web', 'auth'],
    'api_middleware' => ['web', 'auth'],
],
```

---

## Creating Pages

Pages are top-level groupings (e.g. Home, About, Contact) shown as sidebar items.

```bash
php artisan make:ui-page About
php artisan make:ui-page Marketing/Landing  # sub-namespace
```

Or manually:

```php
// app/Ui/Pages/About.php
namespace App\Ui\Pages;

use AhmedAliraqi\UiManager\Core\Page;

class About extends Page
{
    protected string $name  = 'about';
    protected int    $order = 2;

    public function getDisplayName(): string
    {
        return __('About Us');
    }
}
```

| Property   | Type   | Default | Description                          |
|------------|--------|---------|--------------------------------------|
| `$name`    | string | —       | Unique slug used in routes and cache |
| `$visible` | bool   | `true`  | Show in the dashboard sidebar        |
| `$order`   | int    | `0`     | Sidebar sort position                |

---

## Creating Sections

Sections belong to a page and hold a typed set of fields.

```bash
php artisan make:ui-section Banner --page="App\Ui\Pages\Home"
php artisan make:ui-section SocialLinks --page="App\Ui\Pages\Home" --repeatable
```

Or manually:

```php
// app/Ui/Sections/Banner.php
namespace App\Ui\Sections;

use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;
use App\Ui\Pages\Home;

class Banner extends Section
{
    protected string $layout = 'default';
    protected string $name   = 'banner';
    protected string $page   = Home::class;

    public function fields(): array
    {
        return [
            Field::text('title')
                ->default('Welcome to our site')
                ->rules(['required', 'string', 'max:255']),
            Field::editor('description'),
            Field::image('background'),
        ];
    }
}
```

> **Field defaults** are defined per-field with `->default()`. For repeatable sections, override the section-level `default()` method to seed initial items (see [Repeatable Section Defaults](#repeatable-section-defaults)).

| Property   | Type   | Default     | Description                            |
|------------|--------|-------------|----------------------------------------|
| `$name`    | string | —           | Unique slug within the page            |
| `$page`    | string | —           | Fully-qualified Page class or slug     |
| `$layout`  | string | `'default'` | Layout name                            |
| `$visible` | bool   | `true`       | Show in the dashboard                 |
| `$order`   | int    | `0`          | Sort position in page tabs            |
| `$label`   | string | `''`         | Override the auto-generated tab label |

### Multiple layouts for the same section name

Give two sections the same `$name` but different `$layout` values — they are stored as separate DB records and cached independently:

```php
// app/Ui/Sections/HeroHomepage.php
class HeroHomepage extends Section
{
    protected string $name   = 'hero';
    protected string $layout = 'homepage';
    protected string $page   = Home::class;

    public function fields(): array
    {
        return [Field::text('title')->default('Welcome home')];
    }
}

// app/Ui/Sections/HeroLanding.php
class HeroLanding extends Section
{
    protected string $name   = 'hero';
    protected string $layout = 'landing';
    protected string $page   = Home::class;

    public function fields(): array
    {
        return [Field::text('title')->default('Special offer')];
    }
}
```

**Blade — select variant by layout:**

```php
ui('hero', layout: 'homepage')->field('title')   // → homepage variant
ui('hero', layout: 'landing')->field('title')    // → landing variant
ui()->section('hero', 'homepage')->field('title') // equivalent long-form
```

Without a layout argument, the first registered variant is returned (same as before for single-layout sections).

**API — pass `?layout=` query param:**

The dashboard and API calls accept an optional `?layout=` query param to target a specific variant:

```
GET  /ui-manager/api/pages/home/sections/hero?layout=homepage
PUT  /ui-manager/api/pages/home/sections/hero?layout=landing
POST /ui-manager/api/pages/home/sections/hero/items?layout=homepage
```

**Cache invalidation:**

```php
app(UiManager::class)->flushCache('home', 'hero', 'homepage'); // flush one variant
app(UiManager::class)->flushCache('home', 'hero', 'landing');  // flush the other
```

---

### Registering sections outside `app/Ui/`

Auto-discovery scans `app/Ui/Pages` and `app/Ui/Sections` by default. To register classes from any other location, call the registries in a service provider:

```php
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;

public function boot(PageRegistry $pages, SectionRegistry $sections): void
{
    $pages->registerClass(\App\Modules\Blog\Pages\BlogPage::class);
    $sections->registerClass(\App\Modules\Blog\Sections\PostListSection::class);
}
```

---

## Field Types

All fields are created via the `Field` factory with a fluent builder API.

### Common builder methods (all types)

```php
Field::text('name')
    ->label('Display Label')          // defaults to humanised field name
    ->help('Shown below the input')
    ->rules(['required', 'string'])
    ->required()      // prepends 'required' to the rules array
    ->nullable()      // appends 'nullable' to the rules array
    ->default($value)
    ->translatable()  // enable multi-locale input (see below)
    ->hasVariable()   // export this field as a variable (e.g. %section.name%) — see Variables
```

---

### Text

```php
Field::text('title')
    ->label('Page Title')
    ->default('My Title')
    ->rules(['required', 'string', 'max:255'])
```

**Stored as:** plain string.

---

### Textarea

```php
Field::textarea('excerpt')
    ->rules(['nullable', 'string', 'max:500'])
```

**Stored as:** plain string (multi-line).

---

### Rich Editor

```php
Field::editor('body')
```

**Stored as:** HTML string. Render unescaped: `{!! ui('section')->field('body') !!}`.

---

### Select

```php
Field::select('status')
    ->options([
        'draft'     => 'Draft',
        'published' => 'Published',
        'archived'  => 'Archived',
    ])
    ->default('draft')

// Multiple selection + search:
Field::select('tags')
    ->options(['php' => 'PHP', 'js' => 'JavaScript'])
    ->multiple()
    ->searchable()
```

**Stored as:** option key string (or array of keys when `->multiple()`).

**Access the human label in Blade:**

```php
$field = ui()->section('post')->field('status');
echo $field->getString();  // "published"  (the stored key)
echo $field->label();      // "Published"  (the human label)
```

Use `->returnLabel()` on the field definition to make `getString()` return the label automatically.

---

### Image

```php
Field::image('hero')
    ->accept(['image/jpeg', 'image/png', 'image/webp'])
    ->maxSize(2048)            // KB
    ->dimensions(1920, 1080)   // recommended width × height (informational)
    ->default('https://example.com/placeholder.jpg')  // URL default
```

**Stored as:** `{ "id": 42, "url": "...", "filename": "hero.jpg" }` (Spatie Media Library record). URL defaults are returned as-is when no DB record exists.

**Access:**

```php
ui()->section('hero')->field('image')->getUrl()      // file URL
ui()->section('hero')->field('image')->getString()   // '' (not a string field)
```

---

### File

```php
Field::file('cv')
    ->accept(['application/pdf', 'application/msword'])
    ->maxSize(10240)   // KB
    ->multiple(false)
```

**Stored as:** same `{ id, url, filename }` shape as Image.

---

### Color

```php
Field::color('brand_color')
    ->default('#3b82f6')

Field::color('overlay')
    ->alpha()   // enables opacity slider (stores rgba)
```

**Stored as:** hex/rgb/rgba string.

---

### Date

```php
Field::date('launch_date')
    ->default('2025-01-01')
    ->min('2024-01-01')
    ->max('2030-12-31')
```

**Stored as:** `YYYY-MM-DD` string.

---

### Time

```php
Field::time('open_at')->default('09:00')
```

**Stored as:** `HH:MM` string.

---

### Datetime

```php
Field::datetime('published_at')
    ->min('2024-01-01T08:00')
    ->max('2030-12-31T18:00')
```

**Stored as:** ISO datetime string.

---

### Date Range

```php
Field::dateRange('sale_period')
    ->defaultRange('2025-06-01', '2025-06-30')
```

**Stored as:** `{ "start": "2025-06-01", "end": "2025-06-30" }`.

---

### URL

```php
Field::url('website')->default('https://example.com')
```

`url` validation rule is automatically appended. **Stored as:** URL string.

---

### Price

```php
Field::price('ticket_price')
    ->currency('USD')
    ->decimals(2)
    ->currencies(['USD', 'EUR', 'GBP'])  // restricts the currency selector
```

**Stored as:** `{ "amount": "49.99", "currency": "USD" }`.

**Access:**

```php
$field = ui()->section('event')->field('ticket_price');
echo $field->amount();    // 49.99 (float)
echo $field->currency();  // "USD"
```

---

## Field Validation

Every field's `->rules()` definition is enforced server-side on every save. You do not need to write any additional validation logic.

```php
public function fields(): array
{
    return [
        Field::text('heading')->rules(['required', 'max:100']),
        Field::text('email')->rules(['required', 'email']),
        Field::url('website'),       // automatically has 'url' rule
        Field::textarea('bio'),      // no rules — any value accepted
    ];
}
```

`SaveSectionRequest` dynamically resolves the section definition from the route and applies each field's rules as `fields.fieldName => [...]`.

### Translatable field validation

When a field is `->translatable()`, rules are applied per-locale:

```php
Field::text('title')
    ->translatable()
    ->rules(['required', 'max:200'])
```

This generates rules `fields.title.en` and `fields.title.ar` (for every locale in `config('ui-manager.locales')`).

### Inline error display

The dashboard automatically shows field-level 422 validation errors below each input — both in `SectionForm` and `RepeatableItemForm`.

---

## Repeatable Sections

Add `implements Repeatable` to make a section store an ordered list of items instead of a single record. Use it for navigation links, team members, FAQs, testimonials, etc.

```php
use AhmedAliraqi\UiManager\Contracts\Repeatable;

class SocialLinks extends Section implements Repeatable
{
    protected string $name = 'social';
    protected string $page = Home::class;

    public function fields(): array
    {
        return [
            Field::text('platform'),
            Field::url('url'),
            Field::image('icon'),
        ];
    }
}
```

The dashboard automatically switches to full CRUD mode with drag-and-drop reordering.

**Blade:**

```blade
@foreach(ui()->section('social') as $item)
    <a href="{{ $item->field('url') }}">
        <img src="{{ $item->field('icon')->getUrl() }}" alt="">
        {{ $item->field('platform') }}
    </a>
@endforeach
```

> **Note:** Variable placeholders (`%key%`) are expanded inside repeatable items just like in regular sections.

### List Label Field

By default the dashboard shows the first non-empty string value as the label in each item row. Set `$listField` to pin a specific field:

```php
class SocialLinks extends Section implements Repeatable
{
    protected string $name      = 'social';
    protected string $page      = Home::class;
    protected string $listField = 'platform';   // shown in the collapsed row header

    public function fields(): array
    {
        return [
            Field::text('platform'),
            Field::url('url'),
            Field::image('icon'),
        ];
    }
}
```

Works with translatable fields too — the dashboard picks the value for the active page locale.

### Repeatable Section Defaults

Override `default()` on a repeatable section to pre-seed items shown when no DB data exists yet. Once the user saves any items to the database, the `default()` result is ignored.

```php
class SocialLinks extends Section implements Repeatable
{
    protected string $name = 'social';
    protected string $page = Home::class;

    public function fields(): array
    {
        return [
            Field::text('platform'),
            Field::url('url'),
        ];
    }

    public function default(): array
    {
        return [
            ['platform' => 'Facebook', 'url' => 'https://facebook.com'],
            ['platform' => 'Twitter',  'url' => 'https://twitter.com'],
        ];
    }
}
```

For sections with **translatable fields**, use locale-keyed arrays inside each item:

```php
public function default(): array
{
    return [
        ['description' => ['ar' => 'وصف عربي', 'en' => 'English description']],
        ['description' => ['ar' => 'عنصر ثاني', 'en' => 'Second item']],
    ];
}
```

The `make:ui-section --repeatable` command automatically generates a stub `default()` method with example items so you can customise it right away.

---

## Multi-language / Translatable Fields

### 1 — Configure locales

```php
// config/ui-manager.php
'locales'        => ['en', 'ar'],
'default_locale' => 'en',
```

### 2 — Mark fields as translatable

```php
public function fields(): array
{
    return [
        Field::text('title')->translatable()->label('Page Title'),
        Field::textarea('body')->translatable(),
        Field::text('cta')->label('Button text'),  // NOT translatable
    ];
}
```

### Locale-keyed defaults

Pass a locale-keyed array to `->default()` to pre-fill each locale independently:

```php
Field::text('title')
    ->translatable()
    ->default(['en' => 'Welcome', 'ar' => 'أهلاً'])

Field::textarea('bio')
    ->translatable()
    ->default(['en' => 'About us', 'ar' => 'من نحن'])
```

A plain string default is also valid — it is applied to the `default_locale` only, leaving other locales empty:

```php
Field::text('title')
    ->translatable()
    ->default('Welcome')   // only pre-fills 'en' (the default_locale)
```

### 3 — Stored format

```json
{
  "title": { "en": "Welcome", "ar": "أهلاً" },
  "body":  { "en": "Hello world", "ar": "مرحبا بالعالم" },
  "cta":   "Get started"
}
```

### 4 — Blade usage

```php
// Current app locale (e.g. 'en'):
ui()->section('hero')->field('title')        // → "Welcome"

// Explicit locale:
ui()->section('hero')->field('title:ar')     // → "أهلاً"

// Switch app locale:
app()->setLocale('ar');
ui()->section('hero')->field('title')        // → "أهلاً"
```

**Fallback order:** requested locale → `default_locale` → first non-empty stored locale → `''`.

---

## Variables System

Any field value stored in the database can reference other values using `%key%` placeholders. They are resolved lazily when `getString()` is called — never at storage time. **Every field supports placeholders automatically** — no special flag is needed to use them.

### Marking a field as a variable source

Use `->hasVariable()` to **export** a field's value as a reusable variable that other fields (or templates) can reference:

```php
public function fields(): array
{
    return [
        Field::text('phone')->hasVariable(),   // exposes %general.phone% for use anywhere
        Field::image('logo')->hasVariable(),    // exposes %general.logo:url%, %general.logo:name%
        Field::textarea('bio'),                 // normal field — no export, but still resolves %placeholders% in its value
    ];
}
```

`->hasVariable()` controls two things in the dashboard:
- A **copy button** appears next to the field label showing the variable placeholder(s) for that field
- The field is listed in the **Variable Browser** panel so editors can discover and copy it

### Using variables in field values

Any field value can contain `%placeholder%` syntax — **no extra configuration needed**. The substitution happens when the value is read, not when it is saved:

```blade
{{-- phone is marked hasVariable(), other fields can reference it --}}
{{ ui('general')->field('phone') }}   {{-- +966 50 000 0000 --}}

{{-- A URL field storing a WhatsApp deep-link with a phone placeholder --}}
{{ ui('social')->field('whatsapp_link') }}   {{-- https://wa.me/+966500000000 --}}
```

```php
// Section definition
Field::text('phone')->hasVariable(),          // source — exported as %general.phone%
Field::url('whatsapp_link'),                  // stores "https://wa.me/%general.phone%"
                                              // resolved automatically at read time
```

### Syntax

```
%app.name%                           built-in
%general.phone%                      any section.field marked hasVariable()
%header.logo:url%                    image/file — returns the file URL
%header.logo:name%                   image/file — original filename
%header.logo:size%                   file — file size in bytes
%event.date:format(Y-m-d)%           date/datetime — formatted string
%event.starts_at:format(Y-m-d H:i)% datetime — formatted string
%promo.period:start%                 date_range — start date
%promo.period:end%                   date_range — end date
%product.price%                      price — raw stored value
%product.price:currency%             price — currency code
```

### Variable picker in the dashboard

When `->hasVariable()` is set on a field, a **copy button** appears next to the field label:

| Formats available | UI |
|-------------------|----|
| 1 format | Single copy button showing the placeholder |
| Multiple formats | Dropdown button listing all available formats |

Clicking any format copies its placeholder to the clipboard.

The **variable autocomplete** is available in every text, textarea, and URL input — type `%` to open a filtered dropdown of all known variables.

### Per-field format table

| Field type | Available placeholders |
|------------|------------------------|
| `text` / `textarea` / `editor` / `select` / `color` / `url` | `%section.field%` |
| `image` | `%section.field:url%`, `%section.field:name%` |
| `file` | `%section.field:url%`, `%section.field:name%`, `%section.field:size%` |
| `date` | `%section.field%`, `%section.field:format(Y-m-d)%` |
| `datetime` | `%section.field%`, `%section.field:format(Y-m-d H:i)%` |
| `date_range` | `%section.field:start%`, `%section.field:end%` |
| `price` | `%section.field%`, `%section.field:currency%` |

### Built-in variables

| Variable      | Resolves to           |
|---------------|-----------------------|
| `%app.name%`  | `config('app.name')`  |
| `%app.url%`   | `config('app.url')`   |
| `%app.env%`   | `config('app.env')`   |

### Custom variables

Register in a service provider:

```php
use AhmedAliraqi\UiManager\Variables\VariableRegistry;

public function boot(VariableRegistry $registry): void
{
    $registry->value('site.year', (string) now()->year);
    $registry->register('auth.user', fn () => auth()->user()?->name ?? 'Guest');
    $registry->register('mail.support', fn () => config('mail.from.address'));
}
```

### Variable Browser in the dashboard

The **Variables** button in the header opens a searchable slide-in panel listing every registered variable with one-click copy.

---

## Blade Usage

### Non-repeatable sections

```blade
{{-- Render a field (auto-cast to string) --}}
{{ ui()->section('banner')->field('title') }}

{{-- Shorthand — equivalent --}}
{{ ui('banner')->field('title') }}

{{-- HTML field (editor) — unescaped --}}
{!! ui('banner')->field('description') !!}

{{-- Image URL --}}
<img src="{{ ui('banner')->field('hero')->getUrl() }}" alt="">

{{-- Check for empty value --}}
@if(!ui('banner')->field('title')->isEmpty())
    <h1>{{ ui('banner')->field('title') }}</h1>
@endif

{{-- Explicit locale --}}
{{ ui('banner')->field('title:ar') }}

{{-- Price fields --}}
{{ ui('event')->field('price')->amount() }} {{ ui('event')->field('price')->currency() }}
```

### Repeatable sections

```blade
@foreach(ui()->section('social') as $item)
    <a href="{{ $item->field('url') }}">{{ $item->field('platform') }}</a>
@endforeach

{{-- Count items --}}
{{ ui()->section('social')->count() }}

{{-- Check if empty --}}
@if(!ui()->section('social')->isEmpty())
    ...
@endif

{{-- Get first item --}}
{{ ui()->section('social')->first()?->field('platform') }}
```

### Blade directives

```blade
@uiField('banner', 'title')
```

---

## Dashboard

Access the dashboard at `/ui-manager` (configured via `routes.prefix`).

### Features

| Feature | Description |
|---------|-------------|
| **Page sidebar** | All registered pages listed; click to open |
| **Section tabs** | One tab per visible section on a page |
| **Inline edit forms** | No intermediate preview step; form shown immediately on tab click |
| **Field components** | Auto-selected by field type (text, editor, image, color, date, price, …) |
| **Translatable fields** | Locale tab bar rendered above the input for each configured locale |
| **Deferred image upload** | File held in memory until form submit; no wasted uploads on discard |
| **Repeatable CRUD** | Add / edit / delete / drag-to-reorder items; insertion line shows drop target |
| **Toast notifications** | Save success and errors shown as slide-in toasts (bottom-right) |
| **Keyboard shortcut** | `Ctrl+S` / `Cmd+S` submits the active form |
| **Unsaved-changes warning** | Browser warns before closing/refreshing a page with unsaved edits |
| **Loading skeleton** | Animated placeholder shown while section data is fetching |
| **Inline validation errors** | Field-level 422 errors from the API are displayed below each field |
| **Variable picker** | Fields marked `->hasVariable()` show a copy button (or dropdown for multiple formats) next to their label |
| **Variable autocomplete** | Typing `%` in any text, textarea, or URL input opens a filtered inline dropdown of all known variables |
| **Variable Browser** | "Variables" button in the header opens a searchable slide-in panel |

---

## Artisan Commands

### Install

```bash
php artisan ui-manager:install          # publish config + assets, run migrations
php artisan ui-manager:install --force  # overwrite previously published files
```

### Create a Page

```bash
php artisan make:ui-page About
php artisan make:ui-page Marketing/Landing  # creates in App\Ui\Pages\Marketing\
php artisan make:ui-page AboutUs --name=about-us
```

### Create a Section

```bash
php artisan make:ui-section Banner
php artisan make:ui-section Banner --page="App\Ui\Pages\Home"
php artisan make:ui-section SocialLinks --page="App\Ui\Pages\Home" --repeatable
php artisan make:ui-section Hero --layout=marketing --force
```

When `--page` is omitted, the command shows an interactive list of all registered pages. If none are registered yet, it falls back to a free-text prompt.

---

## Extending the Package

### Register pages and sections programmatically

Auto-discovery covers `app/Ui/Pages` and `app/Ui/Sections` by default. To register classes from other locations:

```php
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;

public function boot(PageRegistry $pages, SectionRegistry $sections): void
{
    $pages->register(new MyPage());
    $sections->register(new MySection());

    // Or by class name:
    $pages->registerClass(MyPage::class);
    $sections->registerClass(MySection::class);
}
```

### Custom field types

1. **Create the PHP class** in `app/Ui/Fields/RatingField.php`:

```php
use AhmedAliraqi\UiManager\Fields\BaseField;

class RatingField extends BaseField
{
    protected int $max = 5;

    public function max(int $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function getType(): string
    {
        return 'rating';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), ['max' => $this->max]);
    }
}
```

2. **Create a Vue component** for the dashboard.

3. **Register it** in `FieldRenderer.vue` under the `'rating'` type case.

4. **Rebuild assets:** `npm run build` (only needed when modifying the dashboard frontend).

### Register custom variables

```php
use AhmedAliraqi\UiManager\Variables\VariableRegistry;

public function boot(VariableRegistry $registry): void
{
    $registry->value('site.year', (string) now()->year);
    $registry->register('store.name', fn () => Store::first()?->name ?? '');
}
```

Use `%site.year%` or `%store.name%` in any field value.

### Manually flush the section cache

```php
app(\AhmedAliraqi\UiManager\Services\UiManager::class)->flushCache('home', 'banner');
```

---

## Testing

```bash
./vendor/bin/phpunit

./vendor/bin/phpunit --filter SectionControllerTest  # single class
./vendor/bin/phpunit --filter test_section_uses_db_value_over_default  # single test
```

### Testing sections in your application

```php
use AhmedAliraqi\UiManager\Models\UiContent;

// Seed a section value:
UiContent::create([
    'layout'  => 'default',
    'page'    => 'home',
    'section' => 'banner',
    'fields'  => ['title' => 'Test Title'],
]);

// Disable cache in test environments:
config(['ui-manager.cache.enabled' => false]);
```

---

## Architecture Overview

```
src/
├── Console/
│   ├── InstallCommand.php
│   ├── MakeUiPageCommand.php
│   └── MakeUiSectionCommand.php
├── Contracts/
│   ├── HasFields.php                interface requiring fields() method
│   ├── Repeatable.php               marker interface for list-type sections
│   └── Renderable.php
├── Core/
│   ├── Page.php                     abstract base for all pages
│   └── Section.php                  abstract base for all sections
├── DTOs/
│   ├── FieldValueData.php           typed accessor: getString(), getUrl(), amount(), …
│   └── SectionData.php
├── Exceptions/
│   └── UiManagerException.php
├── Facades/
│   └── Ui.php
├── Fields/
│   ├── Field.php                    static factory (entry point)
│   ├── BaseField.php                fluent builder base; hasVariable() exports field as a variable
│   ├── TextField.php
│   ├── EditorField.php
│   ├── SelectField.php
│   ├── ImageField.php
│   ├── FileField.php
│   ├── ColorField.php
│   ├── DateField.php
│   ├── TimeField.php
│   ├── DatetimeField.php
│   ├── DateRangeField.php
│   ├── UrlField.php
│   └── PriceField.php
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   └── Api/
│   │       ├── PageController.php
│   │       ├── SectionController.php    validates reorder IDs; no events
│   │       ├── MediaController.php
│   │       └── VariableController.php
│   └── Requests/
│       └── SaveSectionRequest.php       dynamic field-level + per-locale rules
├── Models/
│   ├── UiContent.php               single table for all section data
│   ├── UiMediaFile.php             Spatie media owner model
│   └── UiMedia.php                 alias
├── Services/
│   ├── UiManager.php               main entry point — ui() helper target
│   ├── PageRegistry.php
│   ├── SectionRegistry.php
│   ├── VariableParser.php          %key:modifier()% replacement with depth guard
│   └── MediaUploadService.php
├── Support/
│   ├── ClassDiscovery.php
│   ├── SectionView.php
│   ├── RepeatableSectionView.php   IteratorAggregate — foreach-able
│   ├── SectionItemView.php
│   └── helpers.php                 ui() global helper
├── Variables/
│   └── VariableRegistry.php        resolvers + modifier handlers (format, start, end, …)
└── UiManagerServiceProvider.php
```

### Data flow

```
PHP Section class (fields() definition)
        ↓
Dashboard reads via  GET /ui-manager/api/pages/{page}/sections/{section}
        ↓
User edits and submits
        ↓
SaveSectionRequest validates (field rules + per-locale for translatable)
        ↓
SectionController saves → ui_contents.fields (JSON)
        ↓
Cache flushed
        ↓
Blade calls  ui()->section('name')->field('key')
        ↓
UiManager reads from cache (or DB if cold)
        ↓
Returns FieldValueData · variables resolved on getString() / getUrl()
```

### Database tables

| Table | Purpose |
|-------|---------|
| `ui_contents` | All section field data; `sort_order IS NULL` = single record, `NOT NULL` = repeatable item |
| `ui_media_files` | Thin owner model for Spatie Media Library |
| `media` | Spatie's standard media table (bundled migration) |

---

## License

MIT © Ahmed Fathy
