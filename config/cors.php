<?php

/*
|--------------------------------------------------------------------------
| config/cors.php  -  who may call this API from a browser?
|--------------------------------------------------------------------------
| CORS (Cross-Origin Resource Sharing) is a browser safety rule.
| Our React app runs on http://localhost:5173 and our API on http://localhost:8000.
| Those are two different "origins", so the browser refuses the call UNLESS the API says
| "this origin is allowed". This file is where we say it.
|
| If the browser console shows "blocked by CORS policy", check FRONTEND_URL in your .env.
|
| NOTE: CORS only protects BROWSERS. It is NOT authentication. Real protection comes from
| Sanctum tokens + role/tenant checks.
*/

return [
    // Only API URLs need CORS.
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // FRONTEND_URL from .env, comma separated. Example:
    //   FRONTEND_URL=http://localhost:5173,https://youruser.github.io
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('FRONTEND_URL', 'http://localhost:5173')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // We use "Authorization: Bearer <token>" headers, not cookies, so this stays false.
    'supports_credentials' => false,
];