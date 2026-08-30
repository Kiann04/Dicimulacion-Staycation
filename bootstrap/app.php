<?php

use App\Exceptions\BookingRuleViolation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * True for anything served under the versioned API prefix.
 *
 * Membership is decided by path rather than by the `Accept` header: a browser
 * `fetch()` sends a wildcard Accept header by default, and a client that
 * omitted it must still receive JSON rather than a Blade error page.
 */
$isApiRequest = static fn (Request $request): bool => $request->is('api/*');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
        ]);

        /*
         * The public API is unauthenticated and reachable by anyone, so it is
         * throttled per caller. The `api` limiter is defined in
         * AppServiceProvider and counts through the configured cache store,
         * which needs no Redis on shared hosting.
         */
        $middleware->throttleApi();
    })

    ->withExceptions(function (Exceptions $exceptions) use ($isApiRequest): void {
        /*
         * Web behaviour is unchanged — `expectsJson()` is Laravel's own default
         * — while every /api/* failure answers in JSON.
         */
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, Throwable $exception): bool => $isApiRequest($request)
                || $request->expectsJson()
        );

        /*
         * Laravel's own 404 body would be either a stack trace (debug on) or the
         * raw "No query results for model [App\Models\Staycation] 5" message,
         * which publishes an internal class name and namespace. The model's
         * short name alone is enough for a frontend to act on.
         */
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($isApiRequest): ?JsonResponse {
            if (! $isApiRequest($request)) {
                return null;
            }

            $previous = $exception->getPrevious();

            $message = $previous instanceof ModelNotFoundException
                ? class_basename($previous->getModel()).' not found.'
                : 'Resource not found.';

            return response()->json(['message' => $message], 404);
        });

        /*
         * A booking rule violation is a well-formed request the domain refuses.
         * Phase 1 wrote these messages for the person who triggered them, so
         * they are safe to publish verbatim; 422 keeps them distinguishable
         * from a malformed request, which validation already owns.
         */
        $exceptions->render(function (BookingRuleViolation $exception, Request $request) use ($isApiRequest): ?JsonResponse {
            if (! $isApiRequest($request)) {
                return null;
            }

            return response()->json(['message' => $exception->getMessage()], 422);
        });
    })->create();
