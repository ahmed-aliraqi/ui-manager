# Dashboard

## Layout

The dashboard uses a **fixed sidebar + fixed header** layout:

- **Sidebar** (`AppSidebar`): `position: fixed; left: 0; top: 0; height: 100vh; width: 256px; z-index: 40` — always visible, scrollable independently.
- **Header** (`AppHeader`): `position: fixed; top: 0; left: 256px; right: 0; z-index: 30` — spans the content area above the main scroll.
- **Main content** (`<main>`): `margin-left: 256px; padding-top: 56px` — scrolls independently without affecting sidebar or header.

---

## SPA architecture

The dashboard is a **Vue 3 + Pinia + Vue Router** single-page application. The PHP side serves a single Blade shell (`resources/views/dashboard.blade.php`) for every route under the configured prefix (default: `/ui-manager`). The shell mounts the Vue app and passes a config object via `window.__UI_MANAGER_CONFIG__`.

```
/ui-manager/*  →  DashboardController::index()  →  dashboard.blade.php  →  #ui-manager-app
```

API calls go to `/ui-manager/api/*` (registered before the SPA catch-all so they are never intercepted by the web route).

## Asset loading

Assets are pre-built by Vite into `dist/`. The host app publishes them with:

```bash
php artisan vendor:publish --tag=ui-manager-assets
```

`DashboardController` reads `public/vendor/ui-manager/manifest.json` and injects the hashed JS/CSS URLs directly into the Blade template — no `@vite()` directive needed.

## Vue source layout

```
resources/js/ui-manager/
├── app.js                    Entry point — creates Vue app, mounts to #ui-manager-app
├── App.vue                   Root layout shell
├── assets/app.css            TailwindCSS v4 @theme variables
├── composables/
│   ├── useApi.js             Axios instance pointed at /ui-manager/api
│   └── useToast.js           Toast notifications
├── components/
│   ├── layout/
│   │   ├── AppHeader.vue     Top bar
│   │   └── AppSidebar.vue    Page list navigation
│   ├── fields/
│   │   ├── FieldRenderer.vue             Dispatches to the right field component
│   │   ├── TextFieldComponent.vue
│   │   ├── TextareaFieldComponent.vue
│   │   ├── EditorFieldComponent.vue      contenteditable rich text
│   │   ├── SelectFieldComponent.vue
│   │   ├── ImageFieldComponent.vue       Deferred upload (see below)
│   │   ├── FileFieldComponent.vue
│   │   └── VariableAutocomplete.vue
│   ├── repeatable/
│   │   ├── RepeatableSection.vue         Item list + drag-sort + add form
│   │   └── RepeatableItemForm.vue        Per-item edit form
│   ├── SectionForm.vue                   Non-repeatable section edit form
│   └── SectionPreview.vue                (legacy, unused — kept for reference)
├── pages/
│   ├── PagesIndex.vue        Home screen — list of all pages
│   ├── PageShow.vue          Page detail — section tabs + inline forms
│   └── SectionEdit.vue       Standalone section edit (accessed by direct URL)
├── router/index.js           Vue Router routes
└── stores/ui.js              Pinia store — pages, variables, CRUD actions
```

## Navigation flow

```
Sidebar (AppSidebar)
    └── lists all pages from GET /api/pages
    └── click → PageShow

PageShow
    └── tabs across the top, one per visible section
    └── click a tab → activeSectionDef changes
    └── renders SectionForm (non-repeatable) or RepeatableSection (repeatable)
    └── NO intermediate view or "Edit" button — form is shown immediately
```

## SectionForm (non-repeatable)

1. Mounted → `store.fetchSection(page, section)` → fills `form` reactive object with DB values (falling back to field defaults).
2. All fields rendered via `FieldRenderer` which selects the correct Vue component by `field.type`.
3. `provide('sectionName', section)` so `FieldRenderer` can render the `%variable%` copy button.
4. On submit → `resolvePendingUploads()` uploads any pending image files first → `store.saveSectionFields()` → PUT `/api/pages/{page}/sections/{section}`.

## Repeatable sections — variable behaviour

**Variables (`%placeholder%`) are NOT parsed inside repeatable items.** `SectionItemView` creates `FieldValueData` with `parseVariables: false`. This keeps repeatable rows as pure data; cross-referencing across rows or other sections would be unpredictable.

## RepeatableSection

1. Mounted → `store.fetchSection()` → `data.items` array loaded.
2. Default items (`id: null`) auto-expanded.
3. Each item rendered as a collapsible card with `RepeatableItemForm` inside.
4. **Drag-and-drop**: items have `draggable="true"`. `onDragOver` splices the array live; `onDragEnd` calls `store.reorderItems()` → POST `.../reorder`. Errors are surfaced via an inline `reorderError` message (no longer silently swallowed).
5. Add-item form at the bottom (Cancel button shown only for truly blank forms).
6. Delete button hidden for `id: null` (default/unsaved) items.

## RepeatableItemForm

- Reuses the same `resolvePendingUploads()` pattern as `SectionForm`.
- Calls `store.addItem()` when `item.id` is null (new or default), `store.updateItem()` when editing an existing row.
- Emits `saved` with the returned record so the parent list updates immediately.

## Image upload — deferred model

Files are **not uploaded when selected**. Instead:

1. User picks a file → `ImageFieldComponent` emits `{ _pending: true, file: File, localUrl: blobUrl, existingMediaId: oldId|null }`.
2. A "pending" badge shows on the preview.
3. On form submit, `resolvePendingUploads()` detects `_pending: true` entries, calls `POST /api/media` for each, and replaces the pending object with `{ id, url, filename }` before sending the section fields.

This means abandoned edits never waste storage.

## Translatable fields in the dashboard

When a field definition includes `"translatable": true` (serialised from `->translatable()`), `FieldRenderer` renders a **locale tab bar** above the field input. Each tab corresponds to a locale from `window.__UI_MANAGER_CONFIG__.locales`.

- The active locale determines which input is shown.
- The model value for a translatable field is a plain JS object `{ en: "...", ar: "..." }`.
- `SectionForm` initialises each translatable field as a locale-keyed object, pre-populating existing values from the DB and filling missing locales with empty strings.
- On save, the locale object is sent to the API as-is; the PHP backend stores it verbatim in the `fields` JSON column.

Locales and the default locale are injected from `config('ui-manager.locales')` via `DashboardController` into `window.__UI_MANAGER_CONFIG__` and consumed through `composables/useConfig.js → useLocales()`.

## Pinia store (`stores/ui.js`)

| Action | API call |
|---|---|
| `fetchPages()` | `GET /pages` |
| `fetchSection(page, section)` | `GET /pages/{page}/sections/{section}` |
| `saveSectionFields(page, section, fields)` | `PUT /pages/{page}/sections/{section}` |
| `addItem(page, section, fields)` | `POST /pages/{page}/sections/{section}/items` |
| `updateItem(page, section, id, fields)` | `PUT /pages/{page}/sections/{section}/items/{id}` |
| `deleteItem(page, section, id)` | `DELETE /pages/{page}/sections/{section}/items/{id}` |
| `reorderItems(page, section, order)` | `POST /pages/{page}/sections/{section}/reorder` |
| `fetchVariables()` | `GET /variables` |

## Building assets

The `dist/` directory is committed to the repository — host apps do not need Node.js.

For package development:

```bash
npm install
npm run dev    # Vite dev server (hot-reload)
npm run build  # Production build → dist/
```

Key Vite settings (`vite.config.js`):
- `base: '/vendor/ui-manager/'` — asset URL prefix after publishing
- `publicDir: false` — prevents recursive copy of `public/` into `dist/`
- `build.manifest: 'manifest.json'` — emits manifest at `dist/manifest.json`
- `build.outDir: 'dist'` — separate from `public/` to avoid publish path nesting
