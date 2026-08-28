<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'message_to_admin')) {
                $table->text('message_to_admin')->nullable()->after('transaction_number');
            }

            if (! Schema::hasColumn('bookings', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'message_to_admin')) {
                $table->dropColumn('message_to_admin');
            }

            if (Schema::hasColumn('bookings', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
