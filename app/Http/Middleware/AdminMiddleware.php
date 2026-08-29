<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to administrators.
 *
 * Resolves the user from the request rather than the Auth facade so the same
 * middleware works for session-authenticated Blade routes and for Sanctum
 * token-authenticated API routes, where the default guard is not the web guard.
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isAdmin()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return $user === null
                ? ApiResponse::error('Unauthenticated.', 401, 'unauthenticated')
                : ApiResponse::error('This action requires administrator access.', 403, 'forbidden');
        }

        abort(403, 'Unauthorized action. Admins only.');
    }
}
