# Database

## Tables

### `ui_contents`

Single source of truth for all section data — both regular and repeatable.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `layout` | string | Layout name, default `'default'` |
| `page` | string | Page name slug, e.g. `'home'` |
| `section` | string | Section name slug, e.g. `'banner'` |
| `fields` | JSON nullable | Key-value map of all field values |
| `sort_order` | smallint nullable | `NULL` = non-repeatable; `0,1,2…` = repeatable item position |
| `created_at` / `updated_at` | timestamps | |

Indexes: `(page)`, `(section)`, `(page, section, sort_order)`.

### `ui_media_files`

Thin owner model for Spatie Media Library. One row per uploaded file slot.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `created_at` / `updated_at` | timestamps | |

Actual file metadata lives in Spatie's `media` table, polymorphically linked here.

### `media` (Spatie Media Library)

Standard Spatie Media Library table — included in package migrations for zero-config installs.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `model_type` / `model_id` | morph | Points to `UiMediaFile` |
| `collection_name` | string | `'images'` or `'files'` |
| `file_name` | string | Original filename (UUID-safe name after upload) |
| `mime_type` | string | |
| `disk` | string | Filesystem disk, default `'public'` |
| `size` | bigint | Bytes |
| `manipulations` / `custom_properties` / `generated_conversions` / `responsive_images` | JSON | Spatie internals |
| `order_column` | int nullable | |

## JSON field structure

### Non-repeatable section

```json
{
  "title": "Welcome to our site",
  "subtitle": "We build great things",
  "image": {
    "id": 42,
    "url": "http://example.com/storage/ui-manager/images/photo.jpg",
    "filename": "hero.jpg"
  },
  "translatable_title": {
    "en": "Welcome",
    "ar": "أهلاً"
  },
  "status": "published"
}
```

### Translatable field storage

When a field is marked with `->translatable()`, its stored value is a **locale-keyed object**:

```json
{
  "title": {
    "en": "Hello World",
    "ar": "مرحبا بالعالم"
  }
}
```

Non-translatable fields store a plain scalar value as before. Old plain-string values for a field that has since been marked translatable are handled gracefully — returned as-is for the current locale.

### Repeatable section item (each row)

```json
{
  "label": "Facebook",
  "url": "https://facebook.com/acme",
  "logo": {
    "id": 7,
    "url": "http://example.com/storage/ui-manager/images/fb.png",
    "filename": "fb.png"
  }
}
```

## How repeatable sections are stored

- Each item is a **separate row** in `ui_contents` with the same `page` + `section` values.
- `sort_order` determines display order (0-indexed, no gaps after reorder).
- A `sort_order = NULL` row is a non-repeatable single record.
- `UiContent::findRepeatableItems($page, $section)` queries `WHERE sort_order IS NOT NULL ORDER BY sort_order, id`.
- After any delete, remaining rows are re-sequenced starting from 0.

## Media handling

Images and files are stored via **Spatie Media Library v11**.

**Upload flow**:
1. User selects a file in the dashboard (file is held in memory — not uploaded yet).
2. On form save, the frontend calls `POST /api/media` with the `file` and optionally `existing_media_id`.
3. `MediaUploadService::upload()`:
   - If `existing_media_id` is provided: finds the existing `UiMediaFile` owner and adds the new file to its `singleFile()` collection — Spatie automatically deletes the old physical file.
   - Otherwise: creates a new `UiMediaFile` record and attaches the file.
4. Returns `{ id, url, filename, mime, size }`.
5. The field value stored in `ui_contents.fields` is the JSON object above.

**Deletion flow**:
- `MediaUploadService::delete(mediaId)` calls `Media::find($id)->delete()` which cascades through Spatie to remove the file from disk.
- `SectionController::cleanupReplacedMedia()` is called on every save. It compares old vs new field values for `ImageField` / `FileField` columns and deletes orphaned media IDs.
- Deleting a repeatable item also cleans up all its media fields.

**URL resolution**:
- `FieldValueData::getUrl()` returns `rawValue['url']` (fast path).
- Falls back to `Media::find($id)->getUrl()` for live URL resolution (important for S3 signed URLs).

## Model scopes

```php
UiContent::findSection($page, $section)         // single non-repeatable row
UiContent::findRepeatableItems($page, $section) // ordered collection of items
UiContent::forPage($page)                       // query scope
UiContent::forSection($page, $section)          // query scope
```
