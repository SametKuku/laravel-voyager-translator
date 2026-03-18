<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Translation Engine
    |--------------------------------------------------------------------------
    | "gemini"  → Google Gemini AI (requires GEMINI_API_KEY)
    | "gtx"     → Google Translate (free, no key required)
    */
    'engine' => env('VOYAGER_TRANSLATOR_ENGINE', 'gtx'),

    /*
    |--------------------------------------------------------------------------
    | Gemini API Key
    |--------------------------------------------------------------------------
    | Get yours at https://aistudio.google.com/app/apikey
    */
    'gemini_api_key' => env('GEMINI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Gemini Model
    |--------------------------------------------------------------------------
    */
    'gemini_model' => env('VOYAGER_TRANSLATOR_GEMINI_MODEL', 'gemini-2.5-flash'),

    /*
    |--------------------------------------------------------------------------
    | Default Source Locale
    |--------------------------------------------------------------------------
    */
    'source_locale' => env('VOYAGER_TRANSLATOR_SOURCE', 'tr'),

    /*
    |--------------------------------------------------------------------------
    | Default Target Locales (Artisan command)
    |--------------------------------------------------------------------------
    */
    'target_locales' => env('VOYAGER_TRANSLATOR_TARGETS', 'en,es,ru,ar'),

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    | Items per Gemini bulk request or GTX parallel batch.
    */
    'batch_size' => 40,

    /*
    |--------------------------------------------------------------------------
    | Web UI Route Prefix
    |--------------------------------------------------------------------------
    | The URL prefix for the web interface. Access at /{route_prefix}
    | Default: /voyager-translator
    */
    'route_prefix' => env('VOYAGER_TRANSLATOR_PREFIX', 'voyager-translator'),

    /*
    |--------------------------------------------------------------------------
    | Web UI Middleware
    |--------------------------------------------------------------------------
    | Add 'auth' or your Voyager admin middleware here.
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | System Tables to Skip (SQL parsing)
    |--------------------------------------------------------------------------
    */
    'skip_tables' => [
        'users', 'roles', 'permissions', 'permission_role', 'migrations',
        'data_rows', 'data_types', 'menus', 'menu_items', 'settings',
        'failed_jobs', 'personal_access_tokens', 'password_resets',
        'password_reset_tokens', 'sessions', 'cache', 'jobs',
    ],

];
