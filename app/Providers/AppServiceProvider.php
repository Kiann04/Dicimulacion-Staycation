<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator; // 👈 Add this

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ✅ Force database timezone to Philippine time
        DB::statement("SET time_zone = '+08:00'");

        // ✅ Tell Laravel to use Bootstrap 5 pagination styling
        Paginator::useBootstrapFive();
    }
}
