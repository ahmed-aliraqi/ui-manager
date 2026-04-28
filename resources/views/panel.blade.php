{{--
    UI Manager Panel — embeddable widget for any Laravel Blade view.

    Usage:
        @uiManagerPanel

    With custom options (overrides config):
        @uiManagerPanel(['locales' => ['en', 'ar'], 'defaultLocale' => 'ar'])
--}}
@php
    $manifestPath = public_path('vendor/ui-manager/manifest.json');
    $manifest = file_exists($manifestPath)
        ? (json_decode(file_get_contents($manifestPath), true) ?? [])
        : [];

    $base = rtrim(asset('vendor/ui-manager'), '/');

    // Collect all CSS from every entry + chunk in the manifest
    $cssPaths = [];
    foreach ($manifest as $entry) {
        foreach ($entry['css'] ?? [] as $css) {
            $url = $base . '/' . ltrim($css, '/');
            if (!in_array($url, $cssPaths, true)) {
                $cssPaths[] = $url;
            }
        }
    }

    // Resolve the panel entry
    $panelEntry  = $manifest['resources/js/ui-manager/panel.js'] ?? null;
    $panelJs     = $panelEntry ? $base . '/' . ltrim($panelEntry['file'], '/') : null;

    // Resolve imported chunks (deep: follow each chunk's own imports)
    $resolveChunks = function (array $imports) use ($manifest, $base): array {
        $chunks = [];
        foreach ($imports as $key) {
            $chunk = $manifest[$key] ?? null;
            if ($chunk && isset($chunk['file'])) {
                $chunks[] = $base . '/' . ltrim($chunk['file'], '/');
            }
        }
        return $chunks;
    };
    $chunkJs = $resolveChunks($panelEntry['imports'] ?? []);

    // Config passed to the panel (merged with the package defaults)
    $panelConfig = json_encode(array_merge([
        'apiBase'      => url(config('ui-manager.routes.api_prefix', 'ui-manager/api')),
        'locales'      => config('ui-manager.locales', ['en']),
        'defaultLocale'=> config('ui-manager.default_locale', 'en'),
    ], $uiPanelOptions ?? []), JSON_UNESCAPED_UNICODE);
@endphp

{{-- ── CSS ────────────────────────────────────────────────────────────── --}}
@foreach($cssPaths as $css)
<link rel="stylesheet" href="{{ $css }}">
@endforeach

{{-- ── Mount element ───────────────────────────────────────────────────── --}}
<div data-ui-manager-panel data-config='{{ $panelConfig }}'></div>

{{-- ── JS (chunks first, then entry) ─────────────────────────────────── --}}
@foreach($chunkJs as $chunk)
<script type="module" src="{{ $chunk }}"></script>
@endforeach

@if($panelJs)
<script type="module" src="{{ $panelJs }}"></script>
@endif
