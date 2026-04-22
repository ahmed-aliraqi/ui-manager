# Architecture

## Folder structure

```
src/
├── Console/          MakeUiPageCommand, MakeUiSectionCommand
├── Contracts/        HasFields, Repeatable (marker), Renderable
├── Core/             Page (abstract), Section (abstract)
├── DTOs/             FieldValueData, SectionData
├── Exceptions/       UiManagerException
├── Facades/          Ui (points to UiManager)
├── Fields/           BaseField, Field (factory), TextField, TextareaField,
│                     EditorField, SelectField, ImageField, FileField
├── Http/
│   ├── Controllers/
│   │   ├── Api/      PageController, SectionController, MediaController, VariableController
│   │   └──           DashboardController
│   └── Requests/     SaveSectionRequest
├── Models/           UiContent, UiMediaFile (Spatie owner), UiMedia (alias)
├── Services/         UiManager, PageRegistry, SectionRegistry,
│                     MediaUploadService, VariableParser
├── Support/          ClassDiscovery, SectionView, RepeatableSectionView,
│                     SectionItemView, helpers.php
├── Variables/        VariableRegistry
└── UiManagerServiceProvider.php
```

## Core classes

### `Core\Page` (abstract)

```php
abstract class Page
{
    protected string $name;       // slug, e.g. "home"
    protected bool   $visible = true;
    protected int    $order   = 0;

    abstract public function sections(): array; // auto-discovered; not required
}
```

Registered in `PageRegistry`. Auto-discovered from `app/Ui/Pages` on boot.

### `Core\Section` (abstract)

```php
abstract class Section implements HasFields
{
    protected string $layout = 'default';
    protected string $name;          // slug, e.g. "banner"
    protected string $page;          // Page FQCN or name slug
    protected bool   $visible = true;
    protected int    $order   = 0;
    protected string $label   = '';

    abstract public function fields(): array;   // return BaseField[]
    public function default(): array { return []; } // initial values
}
```

A section is **repeatable** when it also implements `Contracts\Repeatable` (marker interface, no methods).

### `Fields\BaseField`

Fluent builder. All field classes extend it.

```php
Field::text('title')
    ->label('Page Title')
    ->default('Hello')
    ->rules(['required', 'max:200'])
    ->help('Shown in the hero banner');
```

`toArray()` serialises the field definition to JSON for the Vue frontend.

## Section discovery

`UiManagerServiceProvider::boot()` triggers `ClassDiscovery` (using Symfony Finder + PHP Reflection) to scan:
- `app/Ui/Pages`  → registers each class via `PageRegistry::registerClass()`
- `app/Ui/Sections` → registers each class via `SectionRegistry::registerClass()`

Discovery paths and namespaces are configurable in `config/ui-manager.php` under `discovery`.

## Rendering — how `ui()->section('banner')->field('title')` works

1. `ui()` helper returns the singleton `UiManager`.
2. `UiManager::section('banner')` calls `SectionRegistry::findByName('banner')` to get the Section definition object.
3. If the section is non-repeatable → `buildSingleView(definition)`:
   - Resolves the page name from FQCN via `PageRegistry`.
   - Builds a cache key using `md5("{pageName}_{sectionName}_")`.
   - Cache hit → returns stored array; miss → queries `UiContent::findSection()`.
   - Merges: `array_merge(definition->resolveDefaults(), dbData)` — DB always wins.
   - Returns `SectionView`.
4. `SectionView::field('title')` looks up the field definition, reads the value from the merged array, wraps it in `FieldValueData`.
5. `FieldValueData::getString()` runs `VariableParser::parse()` on the raw string value before returning.

For repeatable sections `UiManager::buildRepeatableView()` returns `RepeatableSectionView` which implements `IteratorAggregate` — it can be used in a `foreach` directly.

## `default()` vs DB logic

| Situation | Source of truth |
|---|---|
| No DB record exists | `Section::resolveDefaults()` (field defaults merged with `default()`) |
| DB record exists | DB `fields` column; defaults fill any missing keys at the view layer |
| After any save | Cache is flushed; next read re-hydrates from DB |

**Critical invariant**: The cache key **must** use the resolved page name slug (not the FQCN). `UiManager::buildSingleView` resolves this before building the key. `flushCache(page, section)` receives the slug from route params — they must match.

## Service registration

All services are singletons bound in `UiManagerServiceProvider::register()`:

| Binding | Class |
|---|---|
| `PageRegistry` | Auto-bound singleton |
| `SectionRegistry` | Auto-bound singleton |
| `VariableRegistry` | Auto-bound singleton |
| `VariableParser` | Singleton, injected with VariableRegistry |
| `MediaUploadService` | Auto-bound singleton |
| `UiManager` | Singleton, injected with SectionRegistry + PageRegistry |

API routes are registered **before** the SPA web catch-all in `registerRoutes()` — order matters because Laravel matches routes in registration order.
