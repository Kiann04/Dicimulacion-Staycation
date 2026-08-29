<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to staff members. See AdminMiddleware for why the user is
 * resolved from the request rather than the Auth facade.
 */
class StaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isStaff()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return $user === null
                ? ApiResponse::error('Unauthenticated.', 401, 'unauthenticated')
                : ApiResponse::error('This action requires staff access.', 403, 'forbidden');
        }

        abort(403, 'Unauthorized action. Staff only.');
    }
}
