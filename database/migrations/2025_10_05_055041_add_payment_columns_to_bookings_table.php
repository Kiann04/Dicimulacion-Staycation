<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'half_paid', 'paid'])->default('unpaid');
            $table->string('payment_proof')->nullable();
            $table->decimal('vat_amount', 8, 2)->nullable()->default(0);
            $table->decimal('amount_paid', 10, 2)->nullable()->default(0);
            $table->string('payment_method', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_proof', 'vat_amount', 'amount_paid', 'payment_method']);
        });
    }

};
