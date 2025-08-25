<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or “CORS”. This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    // Apply CORS to these endpoints
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Allow all HTTP methods
    'allowed_methods' => ['*'],

    // Your Expo app origins — adjust or remove '*' in production
    'allowed_origins' => [
        'exp://127.0.0.1:19000',  // Expo Go (LAN)
        'http://localhost:19006', // Expo Web
        '*',
    ],

    'allowed_origins_patterns' => [],

    // Allow all headers
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    // Maximum age (seconds) for preflight cache
    'max_age' => 0,

    // Whether to support cookies/credentials
    'supports_credentials' => true,

];
