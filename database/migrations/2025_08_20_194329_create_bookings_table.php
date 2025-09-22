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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('staycation_id')->constrained('staycations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Guest details
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // Booking details
            $table->string('status')->default('waiting');
            $table->string('guest_number')->nullable();
            $table->date('start_date');
            $table->date('end_date');

            // Pricing
            $table->decimal('total_price', 10, 2)->default(0);

            // Payment
            $table->string('payment_status')->default('Pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
