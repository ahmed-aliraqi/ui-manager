<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $config['title'] }}</title>

    {{-- Preload fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/js/ui-manager/app.js'], 'vendor/ui-manager')

    <style>
        [v-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-background text-foreground antialiased" v-cloak>

    <div id="ui-manager-app"></div>

    <script>
        window.__UI_MANAGER_CONFIG__ = @json($config);
    </script>

</body>
</html>
