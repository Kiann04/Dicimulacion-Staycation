<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed initial admin and staff users safely using environment values.
     */
    public function run(): void
    {
        $adminEmail = env('ADMIN_SEED_EMAIL', 'admin@dicimulacion.local');
        $adminPassword = env('ADMIN_SEED_PASSWORD', 'AdminPassword123!');
        $adminName = env('ADMIN_SEED_NAME', 'System Administrator');

        User::firstOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'usertype' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $staffEmail = env('STAFF_SEED_EMAIL', 'staff@dicimulacion.local');
        $staffPassword = env('STAFF_SEED_PASSWORD', 'StaffPassword123!');
        $staffName = env('STAFF_SEED_NAME', 'Staff Operator');

        User::firstOrCreate(
            ['email' => $staffEmail],
            [
                'name' => $staffName,
                'password' => Hash::make($staffPassword),
                'usertype' => 'staff',
                'email_verified_at' => now(),
            ]
        );
    }
}
