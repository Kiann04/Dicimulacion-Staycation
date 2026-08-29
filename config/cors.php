<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| The Next.js client is served from Vercel and this API from Hostinger, so
| every browser call to /api/* is cross-origin and passes a preflight. Laravel
| ships a default that allows "*"; that default is replaced here with an
| explicit allow list driven by the environment, so a production deployment
| never has to edit application code to change its frontend origin.
|
| FRONTEND_URL is the primary origin. CORS_ALLOWED_ORIGINS adds extra fixed
| origins (comma separated) and CORS_ALLOWED_ORIGIN_PATTERNS adds regular
| expressions - useful for Vercel's per-deployment preview URLs, which change
| on every push and cannot be enumerated ahead of time.
|
*/

$csv = static function (?string $value): array {
    return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn ($item) => $item !== ''));
};

$origins = $csv(env('FRONTEND_URL'));

// Local Next.js dev servers. They are only added when no explicit origin list
// was configured, so a production .env that sets FRONTEND_URL does not silently
// keep trusting localhost.
if ($origins === []) {
    $origins = ['http://localhost:3000', 'http://127.0.0.1:3000'];
}

$origins = array_values(array_unique(array_merge($origins, $csv(env('CORS_ALLOWED_ORIGINS')))));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'admin/login'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => $csv(env('CORS_ALLOWED_ORIGIN_PATTERNS')),

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-XSRF-TOKEN',
    ],

    // Rate limit headers are only readable by the browser when they are exposed,
    // and the client needs them to back off after a 429.
    'exposed_headers' => [
        'Retry-After',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
    ],

    'max_age' => 86400,

    /*
    | Set to true to support Sanctum SPA session cookies and CSRF verification
    | from Next.js frontend (e.g. localhost:3000).
    */
    'supports_credentials' => true,

];
