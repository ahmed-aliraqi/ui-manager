<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers\Api;

use AhmedAliraqi\UiManager\Http\Requests\SaveSectionRequest;
use AhmedAliraqi\UiManager\Models\UiContent;
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
    ) {}

    /**
     * GET /api/pages/{page}/sections/{section}
     * Returns current stored field values for a section.
     */
    public function show(string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName);

        if ($definition->isRepeatable()) {
            $items = UiContent::findRepeatableItems($page, $sectionName)
                ->map(fn (UiContent $c) => [
                    'id'         => $c->id,
                    'sort_order' => $c->sort_order,
                    'fields'     => $c->fields ?? [],
                ])->all();

            return response()->json([
                'data' => [
                    'section'    => $definition->toArray(),
                    'items'      => array_values($items),
                    'repeatable' => true,
                ],
            ]);
        }

        $record  = UiContent::findSection($page, $sectionName);
        $stored  = $record?->fields ?? [];
        $merged  = array_merge($definition->resolveDefaults(), $stored);

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
     * Save/update a non-repeatable section.
     */
    public function update(SaveSectionRequest $request, string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName);

        if ($definition->isRepeatable()) {
            return response()->json(['message' => 'Use the items endpoints for repeatable sections.'], 422);
        }

        $fields = $this->validateFields($request->input('fields', []), $definition);

        $record = UiContent::updateOrCreate(
            ['page' => $page, 'section' => $sectionName, 'sort_order' => null],
            [
                'layout' => $definition->getLayout(),
                'fields' => $fields,
            ]
        );

        $this->uiManager->flushCache($page, $sectionName);

        return response()->json(['data' => ['id' => $record->id, 'fields' => $record->fields]]);
    }

    // ------------------------------------------------------------------ Repeatable

    /**
     * POST /api/pages/{page}/sections/{section}/items
     * Add a new item to a repeatable section.
     */
    public function storeItem(SaveSectionRequest $request, string $page, string $sectionName): JsonResponse
    {
        $definition = $this->resolveSection($page, $sectionName);

        if (! $definition->isRepeatable()) {
            return response()->json(['message' => 'Section is not repeatable.'], 422);
        }

        $fields = $this->validateFields($request->input('fields', []), $definition);

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

        return response()->json(['data' => ['id' => $item->id, 'fields' => $item->fields, 'sort_order' => $item->sort_order]], 201);
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

        $fields      = $this->validateFields($request->input('fields', []), $definition);
        $item->fields = $fields;
        $item->save();

        $this->uiManager->flushCache($page, $sectionName);

        return response()->json(['data' => ['id' => $item->id, 'fields' => $item->fields]]);
    }

    /**
     * DELETE /api/pages/{page}/sections/{section}/items/{item}
     */
    public function destroyItem(string $page, string $sectionName, int $itemId): JsonResponse
    {
        UiContent::where('id', $itemId)
            ->where('page', $page)
            ->where('section', $sectionName)
            ->whereNotNull('sort_order')
            ->firstOrFail()
            ->delete();

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
        // SectionRegistry::find() handles both FQCN and page-name slugs.
        $definition = $this->sectionRegistry->find($page, $sectionName);

        if ($definition === null) {
            abort(404, "Section [{$sectionName}] not found for page [{$page}].");
        }

        return $definition;
    }

    /**
     * Filter/sanitize fields against what the section actually declares.
     *
     * @param  array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function validateFields(array $input, \AhmedAliraqi\UiManager\Core\Section $section): array
    {
        $fieldsMap = $section->getFieldsMap();
        $result    = [];

        foreach ($fieldsMap as $name => $field) {
            if (array_key_exists($name, $input)) {
                $result[$name] = $input[$name];
            }
        }

        return $result;
    }
}
