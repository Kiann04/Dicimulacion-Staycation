<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows both administrators and staff. Used for read-only back-office screens
 * where staff need visibility but must not be able to mutate anything; the
 * mutating routes carry the stricter "admin" middleware on top of this.
 */
class BackOfficeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isStaffOrAdmin()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return $user === null
                ? ApiResponse::error('Unauthenticated.', 401, 'unauthenticated')
                : ApiResponse::error('This action requires back-office access.', 403, 'forbidden');
        }

        abort(403, 'Unauthorized action.');
    }
}
