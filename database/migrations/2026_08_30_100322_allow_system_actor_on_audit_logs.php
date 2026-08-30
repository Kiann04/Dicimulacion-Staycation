<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive: widen `audit_logs.user_id` to allow a null actor.
 *
 * Some auditable actions have no signed-in user — the scheduled sweep that marks
 * finished stays as completed, for instance. Until now that column was NOT NULL,
 * so such an action could only be recorded by inventing an actor or by not being
 * audited at all. A null actor reads as "the system".
 *
 * Relaxing NOT NULL never invalidates an existing row, and the foreign key is
 * left in place so a real actor is still required to exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('audit_logs', 'user_id')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Deliberately not reinstated as NOT NULL: rows written since could carry a
     * null actor, and tightening the column would fail or discard them.
     */
    public function down(): void
    {
        //
    }
};
