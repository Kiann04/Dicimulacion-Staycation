<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Duplicate of 2025_08_16_192129_add_two_factor_columns_to_users_table, which
 * already adds these columns. The file is kept (rather than deleted) so the
 * migrations table on the deployed database stays consistent, but each column is
 * now guarded so a fresh `migrate` no longer fails with a duplicate column error.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->after('password')->nullable();
            }

            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->after('two_factor_secret')->nullable();
            }

            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->after('two_factor_recovery_codes')->nullable();
            }
        });
    }

    public function down(): void
    {
        // No-op: the original migration owns these columns and is responsible for
        // dropping them. Dropping here would remove columns this migration never added.
    }
};
