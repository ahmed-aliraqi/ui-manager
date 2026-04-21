<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers\Api;

use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class PageController extends Controller
{
    public function __construct(
        private readonly PageRegistry $pageRegistry,
        private readonly SectionRegistry $sectionRegistry,
    ) {}

    /**
     * GET /api/pages
     * Returns all registered pages with their sections.
     */
    public function index(): JsonResponse
    {
        $pages = array_map(function ($page) {
            $data     = $page->toArray();
            // Pass page name (slug); SectionRegistry resolves both FQCN and name slugs.
            $sections = $this->sectionRegistry->forPage($page->getName());

            $data['sections'] = array_values(
                array_map(fn ($s) => $s->toArray(), $sections)
            );

            return $data;
        }, $this->pageRegistry->all());

        return response()->json(['data' => array_values($pages)]);
    }

    /**
     * GET /api/pages/{page}
     * Returns a single page with full section + field metadata.
     */
    public function show(string $page): JsonResponse
    {
        $pageInstance = $this->pageRegistry->findOrFail($page);
        $sections     = $this->sectionRegistry->forPage($pageInstance->getName());

        $data             = $pageInstance->toArray();
        $data['sections'] = array_values(
            array_map(fn ($s) => $s->toArray(), $sections)
        );

        return response()->json(['data' => $data]);
    }
}
