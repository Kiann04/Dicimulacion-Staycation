<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only.
 *
 * `amount_paid` now means money an administrator has verified as received. The
 * amount a customer *claims* to have sent when uploading a payment proof needs
 * somewhere else to live, so it moves to `declared_amount`, alongside the
 * half/full choice that used to be inferable only from `amount_paid`.
 *
 * `amount_paid` is also widened from decimal(8,2) to decimal(10,2) to match
 * `total_price`; a booking could otherwise be priced higher than its own paid
 * column could hold. Widening a decimal never loses data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('bookings', 'declared_amount')) {
                $table->decimal('declared_amount', 10, 2)->nullable()->after('amount_paid');
            }

            if (! Schema::hasColumn('bookings', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('declared_amount');
            }
        });

        if (Schema::hasColumn('bookings', 'amount_paid')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->decimal('amount_paid', 10, 2)->nullable()->default(null)->change();
            });
        }
    }

    /**
     * The added columns are dropped; `amount_paid` keeps the wider precision,
     * because narrowing it back could truncate rows written since.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            foreach (['declared_amount', 'payment_type'] as $column) {
                if (Schema::hasColumn('bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
