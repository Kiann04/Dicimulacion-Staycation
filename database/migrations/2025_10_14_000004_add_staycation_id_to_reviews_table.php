<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Denormalised copy of the booking's staycation, so reviews can be listed
     * and counted per staycation without joining through bookings. Nullable
     * because older reviews were written without it.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'staycation_id')) {
                $table->foreignId('staycation_id')
                    ->nullable()
                    ->after('booking_id')
                    ->constrained('staycations')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'staycation_id')) {
                $table->dropForeign(['staycation_id']);
                $table->dropColumn('staycation_id');
            }
        });
    }
};
