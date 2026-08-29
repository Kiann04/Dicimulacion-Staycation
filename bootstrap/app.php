<?php

use App\Services\Booking\Exceptions\BookingException;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
            'back-office' => \App\Http\Middleware\BackOfficeMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        // Booking and payment rule violations are expected outcomes, not bugs.
        // They are rendered from the exception's own status and machine-readable
        // code so the API and the Blade controllers stay consistent.
        $exceptions->render(function (BookingException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error($e->getMessage(), $e->status(), $e->errorCode(), $e->context());
        });

        // Validation failures on an API route always answer with the documented
        // envelope, regardless of the request's Accept header.
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Unauthenticated.', 401, 'unauthenticated');
        });

        // Both forms are handled: a policy failure surfaces as an
        // AuthorizationException, which the framework then wraps in an
        // AccessDeniedHttpException. Without the second renderer a denied policy
        // check would return a 403 with no machine-readable body.
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage() !== '' ? $e->getMessage() : 'This action is unauthorized.',
                403,
                'forbidden',
            );
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error(
                $e->getMessage() !== '' ? $e->getMessage() : 'This action is unauthorized.',
                403,
                'forbidden',
            );
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Resource not found.', 404, 'not_found');
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return ApiResponse::error('Resource not found.', 404, 'not_found');
        });

        // Anything else on an API route is normalised so a client never receives
        // an HTML error page. Details are only revealed when debugging is on.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            if ($status < 500) {
                return null;
            }

            return ApiResponse::error(
                config('app.debug') ? $e->getMessage() : 'Server error.',
                500,
                'server_error',
            );
        });
    })->create();
