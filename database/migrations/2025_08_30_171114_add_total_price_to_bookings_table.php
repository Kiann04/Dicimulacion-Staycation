<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'price_per_day')) {
                $table->decimal('price_per_day', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('bookings', 'total_price')) {
                $table->decimal('total_price', 10, 2)->default(0);
            }
        });

        // Populate the columns from staycation table
        DB::statement("
            UPDATE bookings b
            JOIN staycations s ON b.staycation_id = s.id
            SET 
                b.price_per_day = s.house_price,
                b.total_price = s.house_price * DATEDIFF(b.check_out, b.check_in)
        ");
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'price_per_day')) {
                $table->dropColumn('price_per_day');
            }
            if (Schema::hasColumn('bookings', 'total_price')) {
                $table->dropColumn('total_price');
            }
        });
    }
};
