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
    | The locale your original content is written in.
    */
    'source_locale' => env('VOYAGER_TRANSLATOR_SOURCE', 'tr'),

    /*
    |--------------------------------------------------------------------------
    | Default Target Locales
    |--------------------------------------------------------------------------
    | Comma-separated list of locales to translate into.
    */
    'target_locales' => env('VOYAGER_TRANSLATOR_TARGETS', 'en,es,ru,ar'),

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    | Number of items sent per Gemini bulk request or GTX parallel batch.
    */
    'batch_size' => 40,

    /*
    |--------------------------------------------------------------------------
    | System Tables to Skip
    |--------------------------------------------------------------------------
    */
    'skip_tables' => [
        'users', 'roles', 'permissions', 'permission_role', 'migrations',
        'data_rows', 'data_types', 'menus', 'menu_items', 'settings',
        'failed_jobs', 'personal_access_tokens', 'password_resets',
        'password_reset_tokens', 'sessions', 'cache', 'jobs',
    ],

];
