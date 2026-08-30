<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Requests per minute allowed against the public API from one caller.
     */
    private const API_REQUESTS_PER_MINUTE = 60;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        $this->configureApiRateLimiting();
    }

    /**
     * Throttle the public API per authenticated user, or per IP for guests.
     *
     * The API is unauthenticated and reachable from any origin, so the cheapest
     * protection against a runaway client is a per-caller budget. Counting runs
     * through the configured cache store, so shared hosting needs no Redis.
     */
    private function configureApiRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(self::API_REQUESTS_PER_MINUTE)
                ->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });
    }
}
