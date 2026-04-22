# Development Guide

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ (for frontend changes only)

## Setup

```bash
composer install
npm install          # only needed when modifying the dashboard frontend
```

## Running tests

```bash
./vendor/bin/phpunit
./vendor/bin/phpunit --filter SectionControllerTest   # single class
./vendor/bin/phpunit --filter test_section_uses_db_value_over_default  # single test
```

## Rebuilding dashboard assets

```bash
npm run build    # production → dist/
npm run dev      # Vite dev server with hot-reload
```

After building, commit the `dist/` changes. Host apps never run `npm install`.

---

## Adding a new Page

1. Create `app/Ui/Pages/ContactPage.php` in the **host application**:

```php
<?php

namespace App\Ui\Pages;

use AhmedAliraqi\UiManager\Core\Page;

class ContactPage extends Page
{
    protected string $name  = 'contact';
    protected string $label = 'Contact Page';
    protected int    $order = 3;
}
```

2. That's it. The package auto-discovers classes in `app/Ui/Pages` on boot.

If auto-discovery is disabled, register manually in a service provider:

```php
app(\AhmedAliraqi\UiManager\Services\PageRegistry::class)->registerClass(ContactPage::class);
```

---

## Adding a new Section

1. Create `app/Ui/Sections/ContactFormSection.php`:

```php
<?php

namespace App\Ui\Sections;

use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;

class ContactFormSection extends Section
{
    protected string $name   = 'contact-form';
    protected string $label  = 'Contact Form';
    protected string $page   = \App\Ui\Pages\ContactPage::class;
    protected string $layout = 'default';
    protected int    $order  = 0;

    public function fields(): array
    {
        return [
            Field::text('heading')->label('Heading')->default('Get in touch'),
            Field::textarea('intro')->label('Intro text'),
            Field::text('button_text')->label('Submit button')->default('Send'),
        ];
    }

    public function default(): array
    {
        return [
            'heading'     => 'Get in touch',
            'button_text' => 'Send',
        ];
    }
}
```

2. To make it repeatable, implement the marker interface:

```php
use AhmedAliraqi\UiManager\Contracts\Repeatable;

class FaqSection extends Section implements Repeatable
{
    // Each item is a separate DB row; default() returns an array of item field-maps
    public function default(): array
    {
        return [
            ['question' => 'What is this?', 'answer' => 'A great product.'],
        ];
    }
}
```

3. Use in Blade:

```php
// Non-repeatable
$section = ui()->section('contact-form');
echo $section->field('heading');

// Repeatable
foreach (ui()->section('faq') as $item) {
    echo $item->field('question');
    echo $item->field('answer');
}
```

### Artisan generator shortcut

```bash
php artisan make:ui-section ContactFormSection
php artisan make:ui-page ContactPage
```

---

## Adding a new Field type

1. Create `src/Fields/ColorField.php`:

```php
<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

class ColorField extends BaseField
{
    protected string $format = 'hex'; // hex | rgb | hsl

    public function format(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    public function getType(): string
    {
        return 'color';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), ['format' => $this->format]);
    }
}
```

2. Register the factory shortcut in `Fields/Field.php`:

```php
public static function color(string $name): ColorField
{
    return new ColorField($name);
}
```

3. Create the Vue component `resources/js/ui-manager/components/fields/ColorFieldComponent.vue`.

4. Register the component in `FieldRenderer.vue`:

```js
import ColorFieldComponent from './ColorFieldComponent.vue'

const fieldComponent = computed(() => {
    switch (props.field.type) {
        case 'color': return ColorFieldComponent
        // ...existing cases
    }
})
```

5. Rebuild assets: `npm run build`.

---

## Extending the Variable system

```php
// In a service provider:
$registry = app(\AhmedAliraqi\UiManager\Variables\VariableRegistry::class);

// Closure — evaluated lazily
$registry->register('store.name', fn () => Store::first()?->name ?? '');

// Literal value
$registry->value('year', (string) now()->year);
```

In any string field value: `%store.name%`, `%year%`.

---

## Extending `UiManager`

`UiManager` is `final`. Do not extend it. To add behaviour, decorate it:

```php
// Bind a decorator in AppServiceProvider::register()
$this->app->extend(UiManager::class, function (UiManager $manager) {
    return new CachedUiManagerDecorator($manager);
});
```

---

## Config reference

```php
// config/ui-manager.php

'dashboard' => [
    'title'       => 'UI Manager',
    'home_button' => ['label' => 'Back to app', 'url' => '/'],
],

'routes' => [
    'prefix'         => 'ui-manager',       // dashboard URL prefix
    'api_prefix'     => 'ui-manager/api',   // API URL prefix
    'middleware'     => ['web'],
    'api_middleware' => ['web'],
],

'discovery' => [
    'paths'      => [app_path('Ui')],
    'namespaces' => ['App\\Ui\\'],
],

'cache' => [
    'enabled' => env('UI_MANAGER_CACHE', true),
    'ttl'     => 3600,    // seconds
    'prefix'  => 'ui_manager_',
],

'media' => [
    'disk' => 'public',
],
```

---

## Publishing package assets

```bash
# Config
php artisan vendor:publish --tag=ui-manager-config

# Migrations (optional — package auto-loads them)
php artisan vendor:publish --tag=ui-manager-migrations

# Dashboard assets (required for production)
php artisan vendor:publish --tag=ui-manager-assets
```

Migrations include: `ui_contents`, `ui_media_files`, and `media` (Spatie). Run `php artisan migrate` after publishing.
