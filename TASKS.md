# Tasks

## ✅ Completed

### Core system
- [x] Abstract `Page` and `Section` base classes
- [x] Fluent field builder (`Field::text()->label()->default()->rules()`)
- [x] `Repeatable` marker interface for list-type sections
- [x] Auto-discovery of `app/Ui/Pages` and `app/Ui/Sections` via ClassDiscovery
- [x] `SectionRegistry` with dual FQCN/slug lookup (`pageMatchesName`)
- [x] `PageRegistry` with `findByClass()` for anonymous class resolution in tests
- [x] `VariableRegistry` + `VariableParser` with depth-guarded `%key%` replacement
- [x] Built-in variables: `app.name`, `app.url`, `app.env`
- [x] `UiManager` singleton — `ui()->section()->field()` Blade API
- [x] `SectionView` / `RepeatableSectionView` (IteratorAggregate) / `SectionItemView`
- [x] `FieldValueData` DTO with `getString()`, `getUrl()`, `__toString()`

### API & Storage
- [x] REST API: pages, sections (CRUD), items (CRUD + reorder), media, variables
- [x] `SectionController` stores ALL declared fields on every save (no partial records)
- [x] `SectionController::cleanupReplacedMedia()` deletes orphaned Spatie media on save/delete
- [x] Route registration order: API before SPA catch-all
- [x] SPA catch-all regex excludes `/api/` paths
- [x] **Bulk reorder validation** — `reorder()` verifies all submitted IDs belong to the section; returns 422 with `invalid` list if not
- [x] **Field-level request validation** — `SaveSectionRequest` dynamically builds rules from each field's `->rules()` definition; translatable fields validated per-locale (`fields.name.en`, `fields.name.ar`)

### Media
- [x] Spatie Media Library v11 integration (removed custom `ui_media` table)
- [x] `UiMediaFile` owner model with `singleFile()` collection per slot
- [x] `MediaUploadService::upload()` supports `existing_media_id` for in-place replacement
- [x] `FieldValueData::getUrl()` falls back to live Spatie URL resolution by media ID
- [x] Bundled `media` table migration (zero-config install)

### Fields
- [x] `Field::text()` / `Field::textarea()` / `Field::editor()`
- [x] `Field::select()` with `->options()`, `->multiple()`, `->searchable()`, `->returnLabel()`
- [x] `Field::image()` / `Field::file()` with deferred Spatie media upload
- [x] `Field::color()` with `->alpha()` option
- [x] `Field::date()` with `->min()` / `->max()`
- [x] `Field::time()`
- [x] `Field::datetime()` with `->min()` / `->max()`
- [x] `Field::dateRange()` with `->defaultRange()`
- [x] `Field::url()` (auto-appends `url` validation rule)
- [x] `Field::price()` with `->currency()`, `->decimals()`, `->currencies()`
- [x] Multi-language / translatable fields (`->translatable()`) — stored as `{ "en": "...", "ar": "..." }`
- [x] **Per-field variable opt-in** — `->hasVariable()` marks a field as variable-aware; each field type exposes its own set of format placeholders (see Variable system below)

### Dashboard UX
- [x] Tab click → direct edit form (no view/preview intermediate state)
- [x] `SectionForm` with deferred image upload (file held in memory until submit)
- [x] `RepeatableSection` drag-and-drop sorting with visual insertion line
- [x] Auto-expand default (unsaved) items in repeatable sections
- [x] "default" badge on unsaved repeatable items; no delete button for them
- [x] `RepeatableItemForm` Cancel button only shown for blank new forms (not for pre-filled defaults)
- [x] **Toast/notification system** — `ToastContainer.vue` renders global toasts; `SectionForm`, `RepeatableItemForm`, and `RepeatableSection` all use `useToast()` for success/error feedback
- [x] **Keyboard shortcut Ctrl+S / Cmd+S** — submits the active form; `RepeatableItemForm` only fires when the form is focused
- [x] **Unsaved-changes warning** — `SectionForm` tracks dirty state; warns via `beforeunload` before browser close/refresh
- [x] **Loading skeleton** — `SkeletonLoader.vue` shown in `SectionForm` while `fetchSection` is in flight
- [x] **Inline validation error display** — field-level 422 errors from the API are shown below each field in both `SectionForm` and `RepeatableItemForm`
- [x] **Dashboard variable browser** — `VariableBrowser.vue` slide-in panel with search and one-click copy; accessible via "Variables" button in the header
- [x] **Variable formats UI in FieldRenderer** — when `->hasVariable()` is set, shows a copy button (single format) or a dropdown (multiple formats); no button otherwise

### Variable system
- [x] `VariableParser` regex extended to support `format(...)` modifier with parentheses
- [x] New modifiers: `:format(Y-m-d)` (date/datetime), `:start` / `:end` (date range), `:amount` / `:currency` (price)
- [x] Per-field format lists (exposed as `variable_formats` in JSON):
  - `text` / `textarea` / `editor` / `select` / `color` / `url` — `%section.field%`
  - `image` — `%section.field:url%`, `%section.field:name%`
  - `file` — `%section.field:url%`, `%section.field:name%`, `%section.field:size%`
  - `date` — `%section.field%`, `%section.field:format(Y-m-d)%`
  - `datetime` — `%section.field%`, `%section.field:format(Y-m-d H:i)%`
  - `date_range` — `%section.field:start%`, `%section.field:end%`
  - `price` — `%section.field%`, `%section.field:currency%`

### Bug fixes
- [x] **Cache key mismatch** — `UiManager` now resolves page FQCN to name slug before building cache key so `flushCache()` actually invalidates the correct entry
- [x] **Default persistence** — DB values always returned after save; cache flush key now matches storage key
- [x] `v-cloak` scoped to `#ui-manager-app` div (not `<body>`) to prevent page-blank bug
- [x] Vite `publicDir: false` prevents recursive directory nesting during build
- [x] Manual manifest reading in `DashboardController` instead of `@vite()` directive
- [x] **Variable modifiers** — `:url`, `:name`, `:size`, `:format()`, `:start`, `:end`, `:amount`, `:currency` all resolve correctly; regex extended in `VariableParser`
- [x] **Variables disabled in repeatable items** — `SectionItemView` passes `parseVariables: false` to `FieldValueData`; avoids cross-row references and circular resolution
- [x] **`getString()` on media fields** — returns `''` instead of `'Array'` when rawValue is an array
- [x] **Default image as URL string** — `Field::image('x')->default('https://...')` now works correctly via `getUrl()`
- [x] **Fixed dashboard layout** — sidebar and header are `position: fixed`; main content scrolls independently
- [x] **Drag & drop error surfacing** — reorder failures now shown as toast notification; `console.error` logged
- [x] **`make:ui-section` interactive page selection** — auto-discovers registered pages via `PageRegistry::all()`, shows `choice()` list; falls back to free-text when none are registered

### Artisan generators
- [x] `make:ui-page`
- [x] `make:ui-section`

### Tests
- [x] Feature tests: page controller, section CRUD, repeatable, reorder, media upload, image replacement, media cleanup on item delete
- [x] Unit tests: `UiManager` section/repeatable views, default fallback, DB value precedence, cache-key fix with caching enabled
- [x] Variable modifier tests: `:url`, `:name`, `:size`, `:format()`, `:start`, `:end`, `:amount`, `:currency`, unknown key, `extractKeys` includes modifiers
- [x] Field validation tests: required field, email validation, translatable per-locale validation
- [x] Repeatable no-variables test: `getString()` returns raw text without substitution
- [x] Image default test: URL string default resolves via `getUrl()`; `getString()` on array returns `''`
- [x] **Bulk reorder validation tests** — `ReorderValidationTest`: foreign IDs rejected with 422; valid IDs accepted
- [x] All 125 tests passing

---

## 🚧 In Progress

*(none)*

---

## ❌ Pending

### Dashboard
- [ ] Repeatable section: drag-and-drop visual insertion line at drop target (current implementation uses opacity-based feedback; a true line indicator between items needs additional drag tracking refinement)
- [ ] `onBeforeRouteLeave` guard in `PageShow.vue` to warn when switching between pages with a dirty `SectionForm`

### Fields
- [ ] `Field::repeatable()` — nested repeatable inside a non-repeatable section
- [ ] `SelectField` with async/search option loading from an API endpoint
- [ ] Image field: crop / resize / focal-point controls in `ImageFieldComponent.vue`
- [ ] File field: upload progress bar during `POST /api/media`

### API & Storage
- [ ] Soft-delete support for repeatable items (trash + restore)
- [ ] Section-level versioning / history (audit log of field changes)

### Variables
- [ ] Variable validation: warn in the dashboard when a stored value references an unknown key

### System
- [ ] Role-based access: per-page or per-section permission middleware configurable in `config/ui-manager.php`
- [ ] README and installation docs update to reflect Spatie Media Library v11 requirement
