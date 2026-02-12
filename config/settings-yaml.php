<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base Path
    |--------------------------------------------------------------------------
    |
    | The base path where settings files are located. This directory should
    | contain instance-specific subdirectories and a shared defaults directory.
    |
    */
    'base_path' => base_path('instance'),

    /*
    |--------------------------------------------------------------------------
    | Shared Directory
    |--------------------------------------------------------------------------
    |
    | The name of the directory containing shared default settings.
    | Instance-specific settings override these defaults.
    |
    */
    'shared_directory' => '_shared',

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings can be cached for performance. Caching is automatically
    | disabled in testing environments.
    |
    */
    'cache' => [
        'enabled' => env('SETTINGS_CACHE_ENABLED', true),
        'ttl' => env('SETTINGS_CACHE_TTL', 86400), // 24 hours
        'prefix' => 'settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Variable Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration key prefix for environment variable interpolation.
    | Variables like ${API_KEY} will look up config($env_config_prefix.'.API_KEY').
    |
    */
    'env_config_prefix' => 'credentials',
];
