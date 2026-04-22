<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * Returns the list of SVG icons available in the package's resources/icons/ directory.
 *
 * GET /api/svg-icons
 * Response:
 *   { "data": [{ "name": "facebook.svg", "content": "<svg>…</svg>" }, …] }
 *
 * The config key 'ui-manager.svg.icons_path' can override the default package folder.
 */
final class SvgController extends Controller
{
    public function index(): JsonResponse
    {
        // Package's own resources/icons/ is the primary source.
        // config override is supported for custom icon sets.
        $path = config('ui-manager.svg.icons_path')
            ?? dirname(__DIR__, 4) . '/resources/icons';

        if (! is_dir($path)) {
            return response()->json(['data' => []]);
        }

        $icons = [];

        foreach (glob(rtrim($path, '/\\') . '/*.svg') ?: [] as $file) {
            $content = file_get_contents($file);

            if ($content === false) {
                continue;
            }

            $icons[] = [
                'name'    => basename($file),
                'content' => $content,
            ];
        }

        usort($icons, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return response()->json(['data' => $icons]);
    }
}
