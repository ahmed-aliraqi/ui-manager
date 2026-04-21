<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers\Api;

use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Variables\VariableRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class VariableController extends Controller
{
    public function __construct(
        private readonly VariableRegistry $variableRegistry,
        private readonly SectionRegistry $sectionRegistry,
    ) {}

    /**
     * GET /api/variables
     * Returns all available variable keys for the autocomplete UI.
     */
    public function index(): JsonResponse
    {
        $keys = $this->variableRegistry->keys();

        // Auto-generate section.field keys from registered sections
        $start     = config('ui-manager.variables.delimiter_start', '%');
        $end       = config('ui-manager.variables.delimiter_end', '%');
        $variables = [];

        foreach ($keys as $key) {
            $variables[] = [
                'key'         => $key,
                'placeholder' => "{$start}{$key}{$end}",
                'source'      => 'registry',
            ];
        }

        return response()->json(['data' => $variables]);
    }
}
