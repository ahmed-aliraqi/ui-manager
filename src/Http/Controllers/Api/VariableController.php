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
     *
     * Sources:
     *  - 'registry' : explicitly registered via VariableRegistry::register()/value()
     *  - 'section'  : auto-derived from Section fields that have ->hasVariable()
     */
    public function index(): JsonResponse
    {
        $start     = config('ui-manager.variables.delimiter_start', '%');
        $end       = config('ui-manager.variables.delimiter_end', '%');
        $variables = [];
        $seen      = [];

        foreach ($this->variableRegistry->keys() as $key) {
            $placeholder        = "{$start}{$key}{$end}";
            $seen[$placeholder] = true;
            $variables[]        = [
                'key'         => $key,
                'placeholder' => $placeholder,
                'source'      => 'registry',
            ];
        }

        foreach ($this->sectionRegistry->all() as $section) {
            foreach ($section->fields() as $field) {
                if (! $field->isVariableEnabled()) {
                    continue;
                }

                foreach ($field->getVariableFormats($section->getName()) as $placeholder) {
                    if (isset($seen[$placeholder])) {
                        continue;
                    }

                    $seen[$placeholder] = true;
                    $key                = trim($placeholder, $start . $end);

                    $variables[] = [
                        'key'         => $key,
                        'placeholder' => $placeholder,
                        'label'       => $field->getLabel(),
                        'source'      => 'section',
                    ];
                }
            }
        }

        return response()->json(['data' => $variables]);
    }
}
