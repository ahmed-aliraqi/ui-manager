# Variables System

## Purpose

Any string field value stored in the database can reference other field values via `%placeholders%`. The variable system resolves these at read time so content editors can write `%app.name%` or `%banner.title%` in any text field and get the live value when the Blade template renders.

## Syntax

```
%key%
%section.field%
%section.field:url%   → public URL (for image/file fields)
%section.field:name%  → original filename
%section.field:size%  → file size in bytes (string)
%app.name%
%app.url%
```

Delimiters are configurable in `config/ui-manager.php`:

```php
'variables' => [
    'delimiter' => '%',
    'max_depth' => 5,
],
```

## Built-in variables

Registered in `UiManagerServiceProvider::registerBuiltInVariables()`:

| Key | Resolves to |
|---|---|
| `%app.name%` | `config('app.name')` |
| `%app.url%` | `config('app.url')` |
| `%app.env%` | `config('app.env')` |

## Section-field variables

Variables in the form `%sectionName.fieldName%` are automatically resolved by `VariableRegistry::resolve()` using a fallback that calls `UiManager::section($section)->field($field)->getString()`.

This means any published section field is implicitly available as a variable — no extra registration needed.

## Custom variables

Register in a service provider:

```php
use AhmedAliraqi\UiManager\Variables\VariableRegistry;

$registry = app(VariableRegistry::class);

// From a closure
$registry->register('user.name', fn () => auth()->user()?->name ?? 'Guest');

// From a literal value
$registry->value('site.launch_year', '2024');
```

## How parsing works

`VariableParser::parse(string $text, int $depth = 0): string`

1. If `$depth >= max_depth`, return the text unchanged (loop guard).
2. Find all `%key%` patterns in `$text` using regex.
3. For each match, call `VariableRegistry::resolve($key)`.
4. If the resolved value itself contains `%…%`, recursively call `parse(resolved, depth + 1)`.
5. Replace the placeholder with the final value.
6. Return the fully resolved string.

```
Input:  "Welcome to %app.name% — %banner.subtitle%"
Step 1: resolve app.name  → "Acme Corp"
Step 2: resolve banner.subtitle → "We build %app.env% software"
Step 3: resolve app.env (depth 1) → "production"
Output: "Welcome to Acme Corp — We build production software"
```

## Media field modifiers

Media fields (image/file) store their value as an array `{ id, url, filename }`. Using `%section.field%` without a modifier in a text context would return `''` (arrays have no string representation). Use a modifier to extract a specific property:

```
%header.logo:url%   → "https://cdn.example.com/logo.png"
%header.logo:name%  → "logo.png"
%header.logo:size%  → "102400"
```

Modifiers work for both registered variables and the automatic `section.field` fallback.

## Default image values

An `ImageField` default can be a URL string:

```php
Field::image('logo')->default('https://example.com/default-logo.png')
```

When no DB record exists, `->getUrl()` returns that string directly. The variable modifier `:url` also resolves it correctly.

## Where resolution happens

`FieldValueData::getString()` calls `VariableParser::parse()` on the raw string value before returning. `FieldValueData::getUrl()` does **not** parse variables — URLs are returned as-is.

Resolution is lazy: it runs only when `.getString()` or `__toString()` is called on the DTO, not at storage or cache time.

## Repeatable sections

**Variables are disabled inside repeatable section items.** `SectionItemView::field()` creates `FieldValueData` with `parseVariables: false`. This is intentional: repeatable items are pure data rows; variable expansion in them creates unpredictable cross-references and circular resolution risks.

## Edge cases

### Circular references

`Section A` field references `%section_b.field%` which references `%section_a.field%`.

The `max_depth` counter prevents infinite recursion. After `max_depth` nested resolutions, the innermost `%placeholder%` is left as a literal string.

### Unknown keys

If `VariableRegistry::resolve($key)` finds no registered resolver, it returns the original `%key%` string unchanged. No exception is thrown.

### Non-string values

`VariableParser::parse()` operates on strings only. `FieldValueData::getValue()` skips parsing if `rawValue` is not a string (e.g. arrays for image/select fields).

### Translatable fields and variables

For a translatable field, `FieldValueData::getValue()` first resolves the locale-appropriate string, **then** passes it through `VariableParser`. This means translatable field values can themselves contain `%variable%` placeholders — they are expanded after locale resolution.

A variable placeholder referencing a translatable field (`%section.field%`) resolves the value for the **current app locale** at the moment `VariableParser` calls `getString()`. The resolved locale is therefore determined by the request locale, not the locale of the outer field being parsed.

### Performance

Variable resolution runs at request time and is not cached separately. For performance-sensitive sites, enable the section cache (`ui-manager.cache.enabled = true`) — this caches the *raw* field values; variable resolution still runs per-request but the DB query is avoided.
