<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('staycations', function (Blueprint $table) {
    $table->id();
    $table->string('house_name');
    $table->text('house_description');
    $table->decimal('house_price', 10, 2);
    $table->string('house_image')->nullable();
    $table->string('house_location');
    $table->enum('house_availability', ['available', 'unavailable'])->default('available');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staycations');
    }
};
