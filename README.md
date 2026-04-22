# UI Manager — Laravel Package

A production-ready, class-driven UI management system for Laravel. Define your UI structure in PHP, manage content from a modern SPA dashboard, and render it in Blade with a clean API.

---

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Creating Pages](#creating-pages)
4. [Creating Sections](#creating-sections)
5. [Field Types](#field-types)
6. [Repeatable Sections](#repeatable-sections)
7. [Variables System](#variables-system)
8. [Blade Usage](#blade-usage)
9. [Dashboard](#dashboard)
10. [Artisan Commands](#artisan-commands)
11. [Extending the Package](#extending-the-package)
12. [Testing](#testing)

---

## Installation

```bash
composer require ahmed-aliraqi/ui-manager
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag=ui-manager-migrations
php artisan migrate
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=ui-manager-config
```

Publish the pre-built dashboard assets to `public/vendor/ui-manager`:

```bash
php artisan vendor:publish --tag=ui-manager-assets
```

> The dashboard frontend is pre-compiled and ships inside the package — no `npm install` or build step is required in your application.

---

## Configuration

`config/ui-manager.php`:

```php
return [
    'layout' => 'default',

    'dashboard' => [
        'title' => 'UI Manager',
        'home_button' => [
            'display' => true,
            'uri'     => '/dashboard',
            'label'   => 'Back to Dashboard',
        ],
    ],

    'routes' => [
        'prefix'         => 'ui-manager',
        'middleware'     => ['web', 'auth'],
        'api_prefix'     => 'ui-manager/api',
        'api_middleware' => ['web', 'auth'],
    ],

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

    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
    ],
];
```

---

## Creating Pages

Pages represent top-level sections of your application (e.g. Home, About, Contact).

```bash
php artisan make:ui-page About
```

Or manually:

```php
// app/Ui/Pages/About.php
namespace App\Ui\Pages;

use AhmedAliraqi\UiManager\Core\Page;

class About extends Page
{
    protected string $name = 'about';

    public function getDisplayName(): string
    {
        return __('About Us');
    }
}
```

**Page properties:**

| Property   | Type   | Description                              |
|-----------|--------|------------------------------------------|
| `$name`   | string | Unique slug identifier                   |
| `$visible`| bool   | Show in dashboard sidebar (default: true)|
| `$order`  | int    | Sort order in sidebar (default: 0)       |

---

## Creating Sections

Sections belong to a page and contain fields.

```bash
php artisan make:ui-section Banner --page="App\Ui\Pages\Home"
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
            Field::text('title')->rules(['required', 'string', 'max:255']),
            Field::editor('description'),
            Field::image('background'),
        ];
    }

    public function default(): array
    {
        return [
            'title'       => 'Welcome to our site',
            'description' => '',
        ];
    }
}
```

**Section properties:**

| Property   | Type   | Description                              |
|-----------|--------|------------------------------------------|
| `$name`   | string | Unique slug within the page              |
| `$layout` | string | Layout name (default: "default")         |
| `$page`   | string | Fully-qualified Page class name          |
| `$visible`| bool   | Show in dashboard (default: true)        |
| `$order`  | int    | Sort order within page tabs (default: 0) |
| `$label`  | string | Override display label                   |

---

## Field Types

All fields are created via the `Field` factory and support a fluent builder API.

### Text

```php
Field::text('title')
    ->label('Page Title')
    ->rules(['required', 'string', 'max:255'])
    ->default('My Title')
    ->help('The main heading of the page')
```

### Textarea

```php
Field::textarea('excerpt')
    ->rules(['nullable', 'string', 'max:500'])
```

### Rich Editor

```php
Field::editor('body')
    ->extensions(['bold', 'italic', 'link', 'bulletList'])
```

### Select

```php
Field::select('status')
    ->options([
        'draft'     => 'Draft',
        'published' => 'Published',
        'archived'  => 'Archived',
    ])
    ->default('draft')

// Multiple selection:
Field::select('tags')
    ->options([...])
    ->multiple()
    ->searchable()
```

### Image

```php
Field::image('hero')
    ->accept(['image/jpeg', 'image/png', 'image/webp'])
    ->maxSize(2048)           // KB
    ->dimensions(1920, 1080)  // optional recommended dimensions
```

### File

```php
Field::file('cv')
    ->accept(['application/pdf', 'application/msword'])
    ->maxSize(10240)
    ->multiple(false)
```

### Common builder methods (all field types)

```php
->label('Display Label')
->help('Help text shown under the field')
->rules(['required', 'string'])
->required()        // prepends 'required' to rules
->nullable()        // appends 'nullable' to rules
->default($value)
->props(['placeholder' => 'Enter value...'])
```

---

## Repeatable Sections

Repeatable sections store an ordered list of items instead of a single record. Use them for navigation links, team members, FAQs, testimonials, etc.

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
            Field::text('url'),
            Field::image('icon'),
        ];
    }
}
```

The `implements Repeatable` marker is all that's needed — the dashboard automatically switches to full CRUD mode with drag-and-drop reordering.

---

## Variables System

Variables allow dynamic values to be embedded in any text field using `%key%` syntax.

### Built-in variables

| Variable     | Value                  |
|-------------|------------------------|
| `%app.name%`| `config('app.name')`   |
| `%app.url%` | `config('app.url')`    |
| `%app.env%` | `config('app.env')`    |

### Section field variables

Any section field is automatically available as `%section_name.field_name%`:

```
Welcome to %banner.title%!
Visit us at %contact.address%
```

### Registering custom variables

In a `ServiceProvider` or `AppServiceProvider`:

```php
use AhmedAliraqi\UiManager\Variables\VariableRegistry;

public function boot(VariableRegistry $registry): void
{
    // Static value
    $registry->value('company.name', 'Acme Corp');

    // Dynamic resolver
    $registry->register('auth.user', fn () => auth()->user()?->name ?? 'Guest');

    // From config
    $registry->register('mail.support', fn () => config('mail.from.address'));
}
```

### Variable autocomplete

The dashboard's text fields display a **copy button** showing the variable key for each field. Typing `%` in any text input triggers an autocomplete dropdown showing all registered variables.

---

## Blade Usage

### Non-repeatable sections

```blade
{{-- Get a field value (returns FieldValueData, casts to string) --}}
{{ ui()->section('banner')->field('title') }}

{{-- Access the URL of an image field --}}
<img src="{{ ui()->section('banner')->field('hero')->getUrl() }}">

{{-- Shorthand helper --}}
{{ ui('banner')->field('title') }}

{{-- Check if empty --}}
@if(!ui('banner')->field('title')->isEmpty())
    <h1>{{ ui('banner')->field('title') }}</h1>
@endif
```

### Repeatable sections

```blade
{{-- Iterate with @foreach --}}
@foreach(ui()->section('social') as $item)
    <a href="{{ $item->field('url') }}">{{ $item->field('platform') }}</a>
@endforeach

{{-- Check if empty --}}
@if(!ui()->section('social')->isEmpty())
    ...
@endif

{{-- Get first item --}}
{{ ui()->section('social')->first()?->field('platform') }}
```

### Blade directives

```blade
{{-- Inline field --}}
@uiField('banner', 'title')

{{-- HTML is not escaped (for editor fields) --}}
{!! ui('hero')->field('description') !!}
```

---

## Dashboard

Access the dashboard at `/ui-manager` (configurable via `routes.prefix`).

**Features:**
- Sidebar lists all registered pages
- Page view shows tabbed sections
- Section forms auto-render the correct field component per type
- Repeatable sections have full CRUD with sort-order reordering
- Every field shows its variable key with a one-click copy button
- Variable autocomplete activates when you type `%` in text inputs
- Default values are pre-filled when no saved data exists

---

## Artisan Commands

### Create a Page

```bash
php artisan make:ui-page About
php artisan make:ui-page Marketing/Landing  # creates in a subdirectory
php artisan make:ui-page --name=about-us AboutUs
```

### Create a Section

```bash
php artisan make:ui-section Banner --page="App\Ui\Pages\Home"
php artisan make:ui-section SocialLinks --page="App\Ui\Pages\Home" --repeatable
php artisan make:ui-section Hero --layout=marketing
```

---

## Extending the Package

### Register pages and sections programmatically

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

Extend `BaseField` and register a Vue component:

```php
// PHP
class ColorField extends BaseField
{
    public function getType(): string { return 'color'; }
}
```

```js
// In your own JS entrypoint, register the component:
import ColorFieldComponent from './ColorFieldComponent.vue'
// Mount with a custom FieldRenderer override
```

### Middleware protection

```php
// config/ui-manager.php
'routes' => [
    'middleware'     => ['web', 'auth', 'can:manage-ui'],
    'api_middleware' => ['web', 'auth', 'can:manage-ui'],
],
```

### Flush cache after saves

The package flushes cache automatically after API saves. To flush manually:

```php
ui()->flushCache('home', 'banner');
ui()->flushAllCache();
```

---

## Testing

```bash
# Run the full test suite
composer test

# Or directly with phpunit
./vendor/bin/phpunit

# Run only unit tests
./vendor/bin/phpunit --testsuite Unit
```

### Testing sections in your application

```php
use AhmedAliraqi\UiManager\Models\UiContent;

// Seed a section for testing
UiContent::create([
    'layout'  => 'default',
    'page'    => 'home',
    'section' => 'banner',
    'fields'  => ['title' => 'Test Title'],
]);

// Disable cache in test environment
config(['ui-manager.cache.enabled' => false]);
```

---

## Architecture Overview

```
src/
├── Contracts/
│   ├── HasFields.php          Interface requiring fields() method
│   ├── Repeatable.php         Marker interface for list-type sections
│   └── Renderable.php
├── Core/
│   ├── Page.php               Abstract base for all pages
│   └── Section.php            Abstract base for all sections
├── Fields/
│   ├── Field.php              Static factory (entry point)
│   ├── BaseField.php          Fluent builder base
│   ├── TextField.php
│   ├── EditorField.php
│   ├── SelectField.php
│   ├── ImageField.php
│   └── FileField.php
├── Models/
│   ├── UiContent.php          Stores section field data
│   └── UiMedia.php            Uploaded file records
├── Services/
│   ├── UiManager.php          Main entry point (ui() helper)
│   ├── PageRegistry.php       Page class registry + auto-discovery
│   ├── SectionRegistry.php    Section class registry + auto-discovery
│   ├── VariableParser.php     %var% replacement engine
│   └── MediaUploadService.php File upload handler
├── Variables/
│   └── VariableRegistry.php   Stores key => resolver mappings
├── Support/
│   ├── ClassDiscovery.php     Scans dirs for Page/Section subclasses
│   ├── SectionView.php        Wraps a single section's data
│   ├── RepeatableSectionView.php  Iterable wrapper for repeatable sections
│   ├── SectionItemView.php    Single item within a repeatable section
│   └── helpers.php            ui() global helper
├── DTOs/
│   ├── FieldValueData.php     Typed field value with string/URL access
│   └── SectionData.php
├── Facades/
│   └── Ui.php
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   └── Api/
│   │       ├── PageController.php
│   │       ├── SectionController.php
│   │       ├── MediaController.php
│   │       └── VariableController.php
│   └── Requests/
│       └── SaveSectionRequest.php
├── Console/
│   ├── MakeUiPageCommand.php
│   └── MakeUiSectionCommand.php
└── UiManagerServiceProvider.php
```

---

## License

MIT © Ahmed AlIraqi
