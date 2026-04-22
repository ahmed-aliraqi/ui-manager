<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Layout
    |--------------------------------------------------------------------------
    | The default layout name used when a section does not declare one.
    */
    'layout' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'title'       => 'UI Manager',
        'home_button' => [
            'display' => false,
            'uri'     => '/dashboard',
            'label'   => 'Home',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix'          => 'ui-manager',
        'middleware'      => ['web'],
        'api_prefix'      => 'ui-manager/api',
        'api_middleware'  => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Discovery Paths
    |--------------------------------------------------------------------------
    | Paths and namespaces scanned at boot to register Page and Section classes.
    */
    'discovery' => [
        'pages_path'        => 'app/Ui/Pages',
        'sections_path'     => 'app/Ui/Sections',
        'pages_namespace'   => 'App\\Ui\\Pages',
        'sections_namespace' => 'App\\Ui\\Sections',
    ],

    /*
    |--------------------------------------------------------------------------
    | Variables System
    |--------------------------------------------------------------------------
    */
    'variables' => [
        'delimiter_start' => '%',
        'delimiter_end'   => '%',
        'max_depth'       => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media / Uploads
    |--------------------------------------------------------------------------
    */
    'media' => [
        'disk'       => 'public',
        'collection' => 'ui-manager',
    ],

    /*
    |--------------------------------------------------------------------------
    | SVG Icon Picker
    |--------------------------------------------------------------------------
    | Absolute path to the directory containing .svg icon files.
    | The API endpoint (GET /api/svg-icons) reads this directory and returns
    | icon names + content to the dashboard.
    | FieldValueData::toSvg() also uses this path.
    */
    'svg' => [
        'icons_path' => null, // null = resource_path('ui-icons') at runtime
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
        'prefix'  => 'ui_manager_',
    ],

];
