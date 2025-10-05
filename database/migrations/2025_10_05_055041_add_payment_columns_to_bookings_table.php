<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // ✅ Only add if column doesn't exist
            if (!Schema::hasColumn('bookings', 'payment_status')) {
                $table->enum('payment_status', ['unpaid', 'half_paid', 'paid'])->default('unpaid');
            }

            if (!Schema::hasColumn('bookings', 'amount_paid')) {
                $table->decimal('amount_paid', 8, 2)->nullable();
            }

            if (!Schema::hasColumn('bookings', 'payment_proof')) {
                $table->string('payment_proof')->nullable();
            }

            if (!Schema::hasColumn('bookings', 'vat_amount')) {
                $table->decimal('vat_amount', 8, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Optional: drop columns only if they exist
            if (Schema::hasColumn('bookings', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
            if (Schema::hasColumn('bookings', 'amount_paid')) {
                $table->dropColumn('amount_paid');
            }
            if (Schema::hasColumn('bookings', 'payment_proof')) {
                $table->dropColumn('payment_proof');
            }
            if (Schema::hasColumn('bookings', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
        });
    }
};
