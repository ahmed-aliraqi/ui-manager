<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        return view('ui-manager::dashboard', [
            'config' => [
                'title'         => config('ui-manager.dashboard.title', 'UI Manager'),
                'homeButton'    => config('ui-manager.dashboard.home_button'),
                'apiBase'       => url(config('ui-manager.routes.api_prefix', 'ui-manager/api')),
                'locales'       => config('ui-manager.locales', ['en']),
                'defaultLocale' => config('ui-manager.default_locale', 'en'),
            ],
            'assets' => $this->resolveAssets(),
        ]);
    }

    /**
     * Read the pre-built Vite manifest and return resolved CSS + JS URLs.
     *
     * The manifest lives at public/vendor/ui-manager/manifest.json after
     * `php artisan vendor:publish --tag=ui-manager-assets`.
     *
     * @return array{css: string[], js: string[]}
     */
    private function resolveAssets(): array
    {
        $manifestPath = public_path('vendor/ui-manager/manifest.json');

        if (! file_exists($manifestPath)) {
            return ['css' => [], 'js' => []];
        }

        $manifest = json_decode(file_get_contents($manifestPath), true) ?? [];

        // The entry point key matches the `input` defined in vite.config.js
        $entry = $manifest['resources/js/ui-manager/app.js'] ?? null;

        if ($entry === null) {
            return ['css' => [], 'js' => []];
        }

        $base = rtrim(asset('vendor/ui-manager'), '/');

        return [
            'js'  => [$base . '/' . ltrim($entry['file'], '/')],
            'css' => array_map(
                fn (string $path) => $base . '/' . ltrim($path, '/'),
                $entry['css'] ?? []
            ),
        ];
    }
}
