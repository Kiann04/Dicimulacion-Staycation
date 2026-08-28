<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Archive table for bookings that have been permanently removed. Rows are
     * copied here immediately before the booking is force deleted, so there is
     * deliberately no foreign key on booking_id -- the referenced booking is
     * gone by design.
     */
    public function up(): void
    {
        Schema::create('booking_history', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('booking_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('staycation_id')->nullable()->index();

            $table->string('name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('payment_status')->nullable();
            $table->string('payment_proof')->nullable();

            $table->string('action_by')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamp('deleted_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_history');
    }
};
