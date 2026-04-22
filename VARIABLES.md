# Variables System

## Purpose

Any string field value stored in the database can reference other field values via `%placeholders%`. The variable system resolves these at read time so content editors can write `%app.name%` or `%banner.title%` in any text field and get the live value when the Blade template renders.

## Syntax

```
%key%
%section.field%
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

## Where resolution happens

`FieldValueData::getString()` calls `VariableParser::parse()` on the raw string value before returning. `FieldValueData::getUrl()` does **not** parse variables — URLs are returned as-is.

Resolution is lazy: it runs only when `.getString()` or `__toString()` is called on the DTO, not at storage or cache time.

## Edge cases

### Circular references

`Section A` field references `%section_b.field%` which references `%section_a.field%`.

The `max_depth` counter prevents infinite recursion. After `max_depth` nested resolutions, the innermost `%placeholder%` is left as a literal string.

### Unknown keys

If `VariableRegistry::resolve($key)` finds no registered resolver, it returns the original `%key%` string unchanged. No exception is thrown.

### Non-string values

`VariableParser::parse()` operates on strings only. `FieldValueData::getValue()` skips parsing if `rawValue` is not a string (e.g. arrays for image/select fields).

### Performance

Variable resolution runs at request time and is not cached separately. For performance-sensitive sites, enable the section cache (`ui-manager.cache.enabled = true`) — this caches the *raw* field values; variable resolution still runs per-request but the DB query is avoided.
