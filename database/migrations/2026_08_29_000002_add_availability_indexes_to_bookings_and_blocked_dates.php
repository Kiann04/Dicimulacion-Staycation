<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Availability lookups filter by staycation and an overlapping date range on every
 * booking preview, quote and submission. These composite indexes keep that query
 * cheap on shared hosting, where the database is the scarcest resource.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasIndex('bookings', 'bookings_availability_index')) {
                $table->index(['staycation_id', 'start_date', 'end_date'], 'bookings_availability_index');
            }
            if (! Schema::hasIndex('bookings', 'bookings_staycation_status_index')) {
                $table->index(['staycation_id', 'status'], 'bookings_staycation_status_index');
            }
        });

        Schema::table('blocked_dates', function (Blueprint $table) {
            if (! Schema::hasIndex('blocked_dates', 'blocked_dates_availability_index')) {
                $table->index(['staycation_id', 'start_date', 'end_date'], 'blocked_dates_availability_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_availability_index');
            $table->dropIndex('bookings_staycation_status_index');
        });

        Schema::table('blocked_dates', function (Blueprint $table) {
            $table->dropIndex('blocked_dates_availability_index');
        });
    }
};
