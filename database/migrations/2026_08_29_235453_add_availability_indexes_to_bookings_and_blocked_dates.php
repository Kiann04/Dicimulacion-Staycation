<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive indexes only.
 *
 * Every availability check filters a staycation's bookings and blocked dates by
 * an overlapping date range. Without these composite indexes each check is a
 * full table scan, and the check now runs on every booking submission inside a
 * locking transaction.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<string, array<int, string>>>
     */
    private array $indexes = [
        'bookings' => [
            'bookings_availability_index' => ['staycation_id', 'start_date', 'end_date'],
            'bookings_status_index' => ['status'],
            'bookings_payment_status_index' => ['payment_status'],
        ],
        'blocked_dates' => [
            'blocked_dates_availability_index' => ['staycation_id', 'start_date', 'end_date'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes): void {
                foreach ($indexes as $name => $columns) {
                    if (! Schema::hasIndex($tableName, $name)) {
                        $table->index($columns, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $tableName => $indexes) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexes): void {
                foreach (array_keys($indexes) as $name) {
                    if (Schema::hasIndex($tableName, $name)) {
                        $table->dropIndex($name);
                    }
                }
            });
        }
    }
};
