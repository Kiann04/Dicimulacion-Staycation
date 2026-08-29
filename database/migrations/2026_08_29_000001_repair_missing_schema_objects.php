<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconstructs schema objects that exist in the deployed MySQL database but were
 * never committed as migrations (blocked_dates, booking_history, several bookings
 * and reviews columns). Every operation is guarded so this migration is a no-op on
 * the existing database and produces a correct schema on a fresh install.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blocked_dates')) {
            Schema::create('blocked_dates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staycation_id')->constrained('staycations')->cascadeOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->string('reason')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('booking_history')) {
            Schema::create('booking_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('booking_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('staycation_id')->nullable();
                $table->string('name')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('total_price', 10, 2)->default(0);
                $table->string('payment_status')->nullable();
                $table->string('payment_proof')->nullable();
                $table->string('action_by')->nullable();
                $table->timestamp('action_at')->nullable();
                $table->timestamp('deleted_at')->nullable();
            });
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'transaction_number')) {
                $table->string('transaction_number')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'message_to_admin')) {
                $table->text('message_to_admin')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (! Schema::hasColumn('reviews', 'staycation_id')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->unsignedBigInteger('staycation_id')->nullable();
            });
        }

        Schema::table('staycation_images', function (Blueprint $table) {
            if (! Schema::hasColumn('staycation_images', 'staycation_id')) {
                $table->foreignId('staycation_id')->constrained('staycations')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('staycation_images', 'image_path')) {
                $table->string('image_path');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive: these objects predate the migration and are
        // relied upon by the live database. Dropping them here would cause data loss.
    }
};
