<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers\Api;

use AhmedAliraqi\UiManager\Fields\FileField;
use AhmedAliraqi\UiManager\Fields\ImageField;
use AhmedAliraqi\UiManager\Http\Requests\SaveSectionRequest;
use AhmedAliraqi\UiManager\Models\UiContent;
use AhmedAliraqi\UiManager\Services\MediaUploadService;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Services\UiManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class SectionController extends Controller
{
    public function __construct(
        private readonly SectionRegistry $sectionRegistry,
        private readonly UiManager $uiManager,
        private readonly MediaUploadService $mediaService,
    ) {}

    /**
     * GET /api/pages/{page}/sections/{section}
     */
    public function show(string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName);

        if ($definition->isRepeatable()) {
            $dbItems = UiContent::findRepeatableItems($page, $sectionName)
                ->map(fn (UiContent $c) => [
                    'id'         => $c->id,
                    'sort_order' => $c->sort_order,
                    'fields'     => $c->fields ?? [],
                ])->all();

            if ($dbItems === []) {
                $dbItems = array_values(array_map(
                    fn (array $fields, int $idx) => ['id' => null, 'sort_order' => $idx, 'fields' => $fields],
                    $definition->default(),
                    array_keys($definition->default()),
                ));
            }

            return response()->json([
                'data' => [
                    'section'    => $definition->toArray(),
                    'items'      => array_values($dbItems),
                    'repeatable' => true,
                ],
            ]);
        }

        $record = UiContent::findSection($page, $sectionName);
        $stored = $record?->fields ?? [];
        $merged = array_merge($definition->resolveDefaults(), $stored);

        return response()->json([
            'data' => [
                'section'    => $definition->toArray(),
                'fields'     => $merged,
                'repeatable' => false,
            ],
        ]);
    }

    /**
     * PUT /api/pages/{page}/sections/{section}
     */
    public function update(SaveSectionRequest $request, string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName);

        if ($definition->isRepeatable()) {
            return response()->json(['message' => 'Use the items endpoints for repeatable sections.'], 422);
        }

        $newFields = $this->normalizeFields($request->input('fields', []), $definition);

        // Clean up media whose IDs changed or were cleared
        $existing = UiContent::findSection($page, $sectionName);
        $this->cleanupReplacedMedia($existing?->fields ?? [], $newFields, $definition);

        $record = UiContent::updateOrCreate(
            ['page' => $page, 'section' => $sectionName, 'sort_order' => null],
            ['layout' => $definition->getLayout(), 'fields' => $newFields],
        );

        $this->uiManager->flushCache($page, $sectionName);

        return response()->json(['data' => ['id' => $record->id, 'fields' => $record->fields]]);
    }

    // ------------------------------------------------------------------ Repeatable

    /**
     * POST /api/pages/{page}/sections/{section}/items
     */
    public function storeItem(SaveSectionRequest $request, string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName);

        if (! $definition->isRepeatable()) {
            return response()->json(['message' => 'Section is not repeatable.'], 422);
        }

        $fields = $this->normalizeFields($request->input('fields', []), $definition);

        $maxOrder = UiContent::forSection($page, $sectionName)
            ->whereNotNull('sort_order')
            ->max('sort_order') ?? -1;

        $item = UiContent::create([
            'layout'     => $definition->getLayout(),
            'page'       => $page,
            'section'    => $sectionName,
            'fields'     => $fields,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->uiManager->flushCache($page, $sectionName);

        return response()->json([
            'data' => ['id' => $item->id, 'fields' => $item->fields, 'sort_order' => $item->sort_order],
        ], 201);
    }

    /**
     * PUT /api/pages/{page}/sections/{section}/items/{item}
     */
    public function updateItem(SaveSectionRequest $request, string $page, string $sectionName, int $itemId): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName);
        $item       = UiContent::where('id', $itemId)
            ->where('page', $page)
            ->where('section', $sectionName)
            ->firstOrFail();

        $newFields = $this->normalizeFields($request->input('fields', []), $definition);

        $this->cleanupReplacedMedia($item->fields ?? [], $newFields, $definition);

        $item->fields = $newFields;
        $item->save();

        $this->uiManager->flushCache($page, $sectionName);

        return response()->json(['data' => ['id' => $item->id, 'fields' => $item->fields]]);
    }

    /**
     * DELETE /api/pages/{page}/sections/{section}/items/{item}
     */
    public function destroyItem(string $page, string $sectionName, int $itemId): JsonResponse
    {
        $item = UiContent::where('id', $itemId)
            ->where('page', $page)
            ->where('section', $sectionName)
            ->whereNotNull('sort_order')
            ->firstOrFail();

        // Delete media owned by this item
        $definition = $this->resolveSection($page, $sectionName);
        $this->cleanupReplacedMedia($item->fields ?? [], [], $definition);

        $item->delete();

        // Re-sequence remaining items
        UiContent::findRepeatableItems($page, $sectionName)
            ->each(function (UiContent $c, int $idx): void {
                $c->sort_order = $idx;
                $c->save();
            });

        $this->uiManager->flushCache($page, $sectionName);

        return response()->json(null, 204);
    }

    /**
     * POST /api/pages/{page}/sections/{section}/reorder
     * Body: { "order": [id1, id2, id3] }
     */
    public function reorder(Request $request, string $page, string $sectionName): JsonResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        foreach ($request->input('order') as $position => $id) {
            UiContent::where('id', $id)
                ->where('page', $page)
                ->where('section', $sectionName)
                ->update(['sort_order' => $position]);
        }

        $this->uiManager->flushCache($page, $sectionName);

        return response()->json(['message' => 'Reordered.']);
    }

    // ------------------------------------------------------------------ private

    private function resolveSection(string $page, string $sectionName): \AhmedAliraqi\UiManager\Core\Section
    {
        $definition = $this->sectionRegistry->find($page, $sectionName);

        if ($definition === null) {
            abort(404, "Section [{$sectionName}] not found for page [{$page}].");
        }

        return $definition;
    }

    /**
     * Map submitted field input to all declared fields, using field defaults
     * for any key not present in $input.  This ensures every field is always
     * persisted — not just the ones the client happens to send.
     *
     * @param  array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeFields(array $input, \AhmedAliraqi\UiManager\Core\Section $section): array
    {
        $result = [];

        foreach ($section->getFieldsMap() as $name => $field) {
            $result[$name] = array_key_exists($name, $input)
                ? $input[$name]
                : $field->getDefault();
        }

        return $result;
    }

    /**
     * Delete Spatie media records for image/file fields that were replaced or cleared.
     *
     * @param  array<string, mixed> $oldFields
     * @param  array<string, mixed> $newFields  (empty array = deleting the whole item)
     */
    private function cleanupReplacedMedia(
        array $oldFields,
        array $newFields,
        \AhmedAliraqi\UiManager\Core\Section $section,
    ): void {
        foreach ($section->getFieldsMap() as $name => $field) {
            if (! ($field instanceof ImageField) && ! ($field instanceof FileField)) {
                continue;
            }

            $oldValue = $oldFields[$name] ?? null;
            $newValue = $newFields[$name] ?? null;

            $oldId = is_array($oldValue) ? ($oldValue['id'] ?? null) : null;
            $newId = is_array($newValue) ? ($newValue['id'] ?? null) : null;

            if ($oldId !== null && $oldId !== $newId) {
                $this->mediaService->delete((int) $oldId);
            }
        }
    }
}
