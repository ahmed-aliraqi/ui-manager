<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-language / Translatable Fields
    |--------------------------------------------------------------------------
    | List all locale codes the dashboard should show inputs for.
    | default_locale is used as a fallback when the requested locale has no value.
    */
    'locales' => ['en'],

    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Default Layout
    |--------------------------------------------------------------------------
    | The default layout name used when a section does not declare one.
    */
    'layout' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    */
    'routes' => [
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
    | Cache
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => true,
        'ttl'     => 3600,
        'prefix'  => 'ui_manager_',
    ],

];
