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

## Multi-language / Translatable fields

### Config

In `config/ui-manager.php`, set the locales your application supports:

```php
'locales'        => ['en', 'ar'],
'default_locale' => 'en',
```

The dashboard will render locale tab inputs for every configured locale.

### Field definition

Mark any field translatable with `->translatable()`:

```php
public function fields(): array
{
    return [
        Field::text('title')->translatable()->label('Page Title'),
        Field::textarea('body')->translatable()->label('Body'),
        Field::text('cta')->label('CTA Button'),    // NOT translatable
    ];
}
```

### Stored format

```json
{
  "title": { "en": "Welcome", "ar": "أهلاً" },
  "body":  { "en": "Hello world", "ar": "مرحبا بالعالم" },
  "cta":   "Get started"
}
```

### Blade usage

```php
// Current app locale (e.g. 'en'):
ui()->section('hero')->field('title')          // → "Welcome"

// Explicit locale:
ui()->section('hero')->field('title:ar')       // → "أهلاً"

// Switch app locale and call normally:
app()->setLocale('ar');
ui()->section('hero')->field('title')          // → "أهلاً"
```

### Fallback order

1. Requested locale value (if set and non-empty)
2. `default_locale` value
3. First non-empty locale value in the stored object
4. `''`

---

## Select field — key vs label

```php
Field::select('status')
    ->options([
        'draft'     => 'Draft',
        'published' => 'Published',
        'archived'  => 'Archived',
    ])
```

### Blade usage

```php
$field = ui()->section('post')->field('status');

echo $field->getString();   // → "published"  (the stored key)
echo $field->label();       // → "Published"  (the human-readable label)
```

### Return label by default

Use `->returnLabel()` on the field definition if you always want the label:

```php
Field::select('status')
    ->options(['draft' => 'Draft', 'published' => 'Published'])
    ->returnLabel()
```

```php
echo ui()->section('post')->field('status')->getString();  // → "Published"
echo ui()->section('post')->field('status')->label();      // → "Published"
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

// Multi-language support
'locales'        => ['en'],          // all locales the dashboard shows inputs for
'default_locale' => 'en',           // fallback when requested locale is missing
```

---

## Installation

Run the install command after adding the package:

```bash
php artisan ui-manager:install
```

This single command:
1. Publishes `config/ui-manager.php` to the host application
2. Publishes the pre-built dashboard assets to `public/vendor/ui-manager/`
3. Runs `php artisan migrate` to create the required tables

Use `--force` to overwrite previously published files:

```bash
php artisan ui-manager:install --force
```

### Manual publishing (optional)

If you need granular control over what gets published:

```bash
# Config only
php artisan vendor:publish --tag=ui-manager-config

# Dashboard assets only
php artisan vendor:publish --tag=ui-manager-assets

# Migrations (package auto-loads them, only needed to customise)
php artisan vendor:publish --tag=ui-manager-migrations
```
