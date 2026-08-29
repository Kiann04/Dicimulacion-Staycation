<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staycation;
use App\Policies\BookingPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\StaycationPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $policies = [
        Booking::class => BookingPolicy::class,
        Staycation::class => StaycationPolicy::class,
        Payment::class => PaymentPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->forceDatabaseTimezone();

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Paginator::useBootstrapFive();
    }

    /**
     * Pins the MySQL session to Philippine time so date comparisons match what
     * guests see. SET time_zone is MySQL-specific syntax, so it is skipped on any
     * other driver - without this guard the sqlite connection used by the test
     * suite fails to boot at all.
     */
    private function forceDatabaseTimezone(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        try {
            DB::statement("SET time_zone = '+08:00'");
        } catch (Throwable) {
            // A misconfigured or unreachable database must not prevent the
            // application from booting; the connection will surface its own error.
        }
    }
}
