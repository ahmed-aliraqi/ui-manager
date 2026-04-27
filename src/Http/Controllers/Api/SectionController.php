<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers\Api;

use AhmedAliraqi\UiManager\Core\Section;
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
     * GET /api/pages/{page}/sections/{section}?layout=
     */
    public function show(Request $request, string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName, $request->query('layout'));
        $layout     = $definition->getLayout();

        if ($definition->isRepeatable()) {
            $dbItems = UiContent::findRepeatableItems($page, $sectionName, $layout)
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

        $record = UiContent::findSection($page, $sectionName, $layout);
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
     * PUT /api/pages/{page}/sections/{section}?layout=
     */
    public function update(SaveSectionRequest $request, string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName, $request->query('layout'));
        $layout     = $definition->getLayout();

        if ($definition->isRepeatable()) {
            return response()->json(['message' => 'Use the items endpoints for repeatable sections.'], 422);
        }

        $newFields = $this->normalizeFields($request->input('fields', []), $definition);

        $existing = UiContent::findSection($page, $sectionName, $layout);
        $this->cleanupReplacedMedia($existing?->fields ?? [], $newFields, $definition);

        $record = UiContent::updateOrCreate(
            ['page' => $page, 'section' => $sectionName, 'layout' => $layout, 'sort_order' => null],
            ['fields' => $newFields],
        );

        $this->uiManager->flushCache($page, $sectionName, $layout);

        return response()->json(['data' => ['id' => $record->id, 'fields' => $record->fields]]);
    }

    // ------------------------------------------------------------------ Repeatable

    /**
     * POST /api/pages/{page}/sections/{section}/items?layout=
     */
    public function storeItem(SaveSectionRequest $request, string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName, $request->query('layout'));
        $layout     = $definition->getLayout();

        if (! $definition->isRepeatable()) {
            return response()->json(['message' => 'Section is not repeatable.'], 422);
        }

        $fields = $this->normalizeFields($request->input('fields', []), $definition);

        $maxOrder = UiContent::forSection($page, $sectionName)
            ->where('layout', $layout)
            ->whereNotNull('sort_order')
            ->max('sort_order') ?? -1;

        $item = UiContent::create([
            'layout'     => $layout,
            'page'       => $page,
            'section'    => $sectionName,
            'fields'     => $fields,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->uiManager->flushCache($page, $sectionName, $layout);

        return response()->json([
            'data' => ['id' => $item->id, 'fields' => $item->fields, 'sort_order' => $item->sort_order],
        ], 201);
    }

    /**
     * PUT /api/pages/{page}/sections/{section}/items/{item}?layout=
     */
    public function updateItem(SaveSectionRequest $request, string $page, string $sectionName, int $itemId): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName, $request->query('layout'));
        $layout     = $definition->getLayout();

        $item = UiContent::where('id', $itemId)
            ->where('page', $page)
            ->where('section', $sectionName)
            ->where('layout', $layout)
            ->firstOrFail();

        $newFields = $this->normalizeFields($request->input('fields', []), $definition);

        $this->cleanupReplacedMedia($item->fields ?? [], $newFields, $definition);

        $item->fields = $newFields;
        $item->save();

        $this->uiManager->flushCache($page, $sectionName, $layout);

        return response()->json(['data' => ['id' => $item->id, 'fields' => $item->fields]]);
    }

    /**
     * DELETE /api/pages/{page}/sections/{section}/items/{item}?layout=
     */
    public function destroyItem(Request $request, string $page, string $sectionName, int $itemId): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName, $request->query('layout'));
        $layout     = $definition->getLayout();

        $item = UiContent::where('id', $itemId)
            ->where('page', $page)
            ->where('section', $sectionName)
            ->where('layout', $layout)
            ->whereNotNull('sort_order')
            ->firstOrFail();

        $this->cleanupReplacedMedia($item->fields ?? [], [], $definition);

        $deletedId = $item->id;
        $item->delete();

        UiContent::findRepeatableItems($page, $sectionName, $layout)
            ->each(function (UiContent $c, int $idx): void {
                $c->sort_order = $idx;
                $c->save();
            });

        $this->uiManager->flushCache($page, $sectionName, $layout);

        return response()->json(null, 204);
    }

    /**
     * POST /api/pages/{page}/sections/{section}/reorder?layout=
     * Body: { "order": [id1, id2, id3] }
     */
    public function reorder(Request $request, string $page, string $sectionName): JsonResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);

        $definition = $this->resolveSection($page, $sectionName, $request->query('layout'));
        $layout     = $definition->getLayout();

        $validIds = UiContent::where('page', $page)
            ->where('section', $sectionName)
            ->where('layout', $layout)
            ->whereNotNull('sort_order')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $submittedIds = array_map('intval', $request->input('order', []));
        $invalid      = array_values(array_diff($submittedIds, $validIds));

        if ($invalid !== []) {
            return response()->json([
                'message' => 'Some IDs do not belong to this section.',
                'invalid' => $invalid,
            ], 422);
        }

        foreach ($submittedIds as $position => $id) {
            UiContent::where('id', $id)
                ->where('page', $page)
                ->where('section', $sectionName)
                ->where('layout', $layout)
                ->update(['sort_order' => $position]);
        }

        $this->uiManager->flushCache($page, $sectionName, $layout);

        return response()->json(['message' => 'Reordered.']);
    }

    // ------------------------------------------------------------------ private

    private function resolveSection(string $page, string $sectionName, ?string $layout = null): Section
    {
        $definition = $this->sectionRegistry->find($page, $sectionName, $layout);

        if ($definition === null) {
            abort(404, "Section [{$sectionName}] not found for page [{$page}].");
        }

        return $definition;
    }

    /**
     * @param  array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeFields(array $input, Section $section): array
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
     * @param  array<string, mixed> $oldFields
     * @param  array<string, mixed> $newFields
     */
    private function cleanupReplacedMedia(array $oldFields, array $newFields, Section $section): void
    {
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
