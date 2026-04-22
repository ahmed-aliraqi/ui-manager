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

### Media
- [x] Spatie Media Library v11 integration (removed custom `ui_media` table)
- [x] `UiMediaFile` owner model with `singleFile()` collection per slot
- [x] `MediaUploadService::upload()` supports `existing_media_id` for in-place replacement
- [x] `FieldValueData::getUrl()` falls back to live Spatie URL resolution by media ID
- [x] Bundled `media` table migration (zero-config install)

### Dashboard UX
- [x] Tab click → direct edit form (no view/preview intermediate state)
- [x] `SectionForm` with deferred image upload (file held in memory until submit)
- [x] `RepeatableSection` drag-and-drop sorting via HTML5 Drag API
- [x] Auto-expand default (unsaved) items in repeatable sections
- [x] "default" badge on unsaved repeatable items; no delete button for them
- [x] `RepeatableItemForm` Cancel button only shown for blank new forms (not for pre-filled defaults)

### Bug fixes
- [x] **Cache key mismatch** — `UiManager` now resolves page FQCN to name slug before building cache key so `flushCache()` actually invalidates the correct entry
- [x] **Default persistence** — DB values always returned after save; cache flush key now matches storage key
- [x] `v-cloak` scoped to `#ui-manager-app` div (not `<body>`) to prevent page-blank bug
- [x] Vite `publicDir: false` prevents recursive directory nesting during build
- [x] Manual manifest reading in `DashboardController` instead of `@vite()` directive
- [x] **Variable modifiers** — `%section.field:url%`, `:name`, `:size` now resolve correctly for media fields; regex extended in `VariableParser`
- [x] **Variables disabled in repeatable items** — `SectionItemView` passes `parseVariables: false` to `FieldValueData`; avoids cross-row references and circular resolution
- [x] **`getString()` on media fields** — returns `''` instead of `'Array'` when rawValue is an array
- [x] **Default image as URL string** — `Field::image('x')->default('https://...')` now works correctly via `getUrl()`
- [x] **Fixed dashboard layout** — sidebar and header are `position: fixed`; main content scrolls independently
- [x] **Drag & drop error surfacing** — reorder failures now shown as inline message; `console.error` logged; silent catch removed
- [x] **`make:ui-section` interactive page selection** — auto-discovers registered pages via `PageRegistry::all()`, shows `choice()` list; falls back to free-text when none are registered

### Artisan generators
- [x] `make:ui-page`
- [x] `make:ui-section`

### Tests
- [x] Feature tests: page controller, section CRUD, repeatable, reorder, media upload, image replacement, media cleanup on item delete
- [x] Unit tests: `UiManager` section/repeatable views, default fallback, DB value precedence, cache-key fix with caching enabled, repeatable defaults
- [x] Variable modifier tests: `:url`, `:name`, `:size`, unknown key, `extractKeys` includes modifier
- [x] Repeatable no-variables test: `getString()` returns raw text without substitution
- [x] Image default test: URL string default resolves via `getUrl()`; `getString()` on array returns `''`
- [x] All 57 tests passing

---

## 🚧 In Progress

*(none at time of writing)*

---

## ❌ Pending

### Dashboard
- [ ] Toast/notification system for save success/error (currently inline text feedback; reorder errors shown inline)
- [ ] Keyboard shortcut `Ctrl+S` / `Cmd+S` to submit the active form
- [ ] Unsaved-changes warning when navigating away with a dirty form
- [ ] Loading skeleton for section form while `fetchSection` is in flight
- [ ] Repeatable section: drag-and-drop visual insertion line between items
- [ ] Repeatable section: inline validation error display per field

### Fields
- [ ] `Field::color()` — color picker field type
- [ ] `Field::date()` — date/datetime picker
- [ ] `Field::repeatable()` — nested repeatable inside a non-repeatable section
- [ ] `SelectField` with async/search option loading from an API endpoint
- [ ] Image field: crop / resize / focal-point controls
- [ ] File field: progress bar during upload

### API & Storage
- [ ] Soft-delete support for repeatable items (trash + restore)
- [ ] Bulk reorder endpoint validation (verify all IDs belong to the section)
- [ ] Section-level versioning / history (audit log)

### Variables
- [ ] Dashboard variable browser — searchable list with one-click copy
- [ ] Variable validation: warn when a stored value references an unknown key

### System
- [ ] `make:ui-field` Artisan generator for custom field types
- [ ] Multi-language / locale support for field values
- [ ] Role-based access: per-page or per-section permission middleware
- [ ] Webhook/event dispatch after section save (`SectionSaved` event)
- [ ] README and installation docs update to reflect Spatie Media Library requirement
