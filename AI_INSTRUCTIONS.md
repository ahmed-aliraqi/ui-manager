# AI Instructions

**Before implementing anything, read all `.md` files in the project root.**

Order: `PROJECT_CONTEXT.md` → `ARCHITECTURE.md` → `DATABASE.md` → `DASHBOARD.md` → `VARIABLES.md` → `TASKS.md` → `DEVELOPMENT_GUIDE.md`

---

## Prime directives

1. **Do not rebuild the project.** The package is production-ready. Every change is a targeted fix or addition.
2. **Fix, do not rewrite.** If something is broken, find the root cause and change the minimum necessary code. Do not redesign surrounding systems.
3. **Respect the existing architecture.** New features follow the same patterns already in the codebase.
4. **The public Blade API is sacred.** `ui()->section('name')->field('key')` must continue to work. Any change that breaks this is wrong.

---

## The class-based system

Pages and Sections are PHP classes, not database records. Developers define *structure* in code; the database stores *values* only.

```php
// ALWAYS add new functionality as PHP classes
class HeroSection extends Section { ... }   // ✅ correct
UiSection::create(['name' => 'hero', ...])  // ❌ never do this
```

---

## Architecture rules

### Service layer
All database access goes through `UiManager`, `SectionRegistry`, `PageRegistry`, or the model scopes. Controllers may use these services but must not build raw queries themselves.

```php
// ✅ correct
$data = UiContent::findSection($page, $section);

// ❌ wrong
$data = DB::table('ui_contents')->where('page', $page)->first();
```

### No logic in Blade
Blade templates call `ui()->section()->field()` and output strings. No if/else business logic, no DB access, no service calls inside Blade files (except the `ui()` helper itself).

### Cache key discipline
The cache key uses the **page name slug**, never the FQCN. `UiManager::buildSingleView()` always calls `resolvePageName()` before building the key. `flushCache($page, $section)` is called with the slug from route parameters. These two must always match — if they diverge, saved changes silently disappear.

### Route order
API routes are registered **before** the SPA catch-all in `UiManagerServiceProvider::registerRoutes()`. Do not change this order.

### Media
Never write directly to Storage for UI Manager media. Always go through `MediaUploadService`. Images are stored via Spatie Media Library; the `ui_media_files` table owns the Spatie media records.

---

## Coding standards

- PHP 8.2+, `declare(strict_types=1)` in every file
- `final` classes for services and controllers (they are not meant to be extended)
- No `public` properties — use getters
- No inline business logic; extract to private methods
- Field names are snake_case slugs
- Section/page names are kebab-case slugs
- All array shapes that are passed between layers should be documented with `@param array<string, mixed>`
- Do not add comments that restate what the code does; add comments only for non-obvious *why*

---

## Frontend rules

- Vue 3 Composition API with `<script setup>` — no Options API
- Pinia for state — no Vuex, no local component-level stores for shared state
- All API calls go through `stores/ui.js` actions or the `api` instance from `useApi.js`
- Image fields hold `{ _pending: true, file, localUrl, existingMediaId }` until the form is submitted; never upload on file-select
- `provide('sectionName', ...)` must be called in any component that renders `FieldRenderer` so the variable copy button works
- Do not add new npm dependencies without a strong reason; prefer native browser APIs

---

## Testing rules

- Every new PHP feature needs a test in `tests/Feature/Api/` (HTTP-level) or `tests/Unit/`
- Tests use SQLite in-memory via Orchestra Testbench — no real database needed
- The `TestCase` base class provides `Storage::fake('public')` and Spatie's service provider
- Disable the cache in tests: `config(['ui-manager.cache.enabled' => false])` (set in `TestCase::getEnvironmentSetUp`)
- All 48 existing tests must remain green after any change

---

## Common mistakes to avoid

| Mistake | Correct approach |
|---|---|
| Storing page/section structure in DB | Define as PHP classes |
| Using page FQCN in cache key | Resolve to name slug first |
| Uploading image immediately on file-select | Store as `_pending`, upload on form submit |
| Using `@vite()` for package assets | Read `manifest.json` manually in `DashboardController` |
| Rebuilding the whole component because one thing changed | Find the one thing, change only that |
| Registering API routes after web catch-all | API routes must come first in `registerRoutes()` |
| Calling `validateFields` that ignores missing keys | Use `normalizeFields` which fills defaults for all declared fields |

---

## Workflow

1. Read all `.md` docs
2. Read the specific files relevant to the task (grep/glob first)
3. Write a plan — state which files change and why
4. Make changes one file at a time
5. Run `./vendor/bin/phpunit` — all tests must pass
6. If frontend changed, run `npm run build`
7. Update `TASKS.md` — move completed items, add new known issues
