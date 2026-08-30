<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|--------------------------------------------------------------------------
|
| The browser frontend is deployed separately from this API, so every allowed
| origin is read from the environment rather than written here. No origin is
| allowed by default: an unconfigured deployment refuses cross-origin browser
| calls instead of quietly accepting every site on the internet.
|
| FRONTEND_ORIGINS         comma-separated absolute origins, scheme included
| FRONTEND_ORIGIN_PATTERNS comma-separated regular expressions, for preview
|                          deployments whose hostname changes per build
|
*/

$splitList = static function (?string $value): array {
    return array_values(array_filter(
        array_map('trim', explode(',', (string) $value)),
        static fn (string $entry): bool => $entry !== '',
    ));
};

$allowedOrigins = $splitList(env('FRONTEND_ORIGINS'));

$supportsCredentials = filter_var(env('CORS_SUPPORTS_CREDENTIALS', false), FILTER_VALIDATE_BOOLEAN);

/*
 * A wildcard origin and credentialed requests are mutually exclusive: the
 * browser rejects `Access-Control-Allow-Origin: *` on any request carrying
 * cookies, and a server that echoed the caller's origin instead would let any
 * site ride a logged-in user's session. Rather than emit a header that fails
 * confusingly at runtime, the wildcard is dropped whenever credentials are on,
 * which fails closed and forces the origins to be named explicitly.
 */
if ($supportsCredentials) {
    $allowedOrigins = array_values(array_filter(
        $allowedOrigins,
        static fn (string $origin): bool => $origin !== '*',
    ));
}

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => $splitList(env('FRONTEND_ORIGIN_PATTERNS')),

    /*
     * Named explicitly rather than '*'. The CORS specification does not treat
     * '*' as a wildcard once credentials are involved, so an explicit list is
     * the only form that keeps working if Phase 2B turns credentials on.
     */
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 0),

    'supports_credentials' => $supportsCredentials,

];
