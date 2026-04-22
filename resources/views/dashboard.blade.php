<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $config['title'] }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @foreach($assets['css'] as $url)
        <link rel="stylesheet" href="{{ $url }}">
    @endforeach

    {{-- v-cloak hides the mount div until Vue finishes mounting.
         It must live on the element Vue controls (#ui-manager-app),
         NOT on <body> — Vue only removes it from its own root element. --}}
    <style>
        #ui-manager-app[v-cloak] { display: none; }
    </style>
</head>
<body class="h-full antialiased">

    <div id="ui-manager-app" v-cloak></div>

    <script>
        window.__UI_MANAGER_CONFIG__ = @json($config);
    </script>

    @foreach($assets['js'] as $url)
        <script type="module" src="{{ $url }}"></script>
    @endforeach

</body>
</html>
