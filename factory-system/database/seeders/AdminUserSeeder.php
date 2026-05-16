<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the initial application accounts.
 * Credentials are development defaults — change immediately after first login.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@factory.local'],
            [
                'name' => 'مدير النظام',
                'phone' => '0911000000',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->assignRole('super_admin');

        $accountant = User::firstOrCreate(
            ['email' => 'accountant@factory.local'],
            [
                'name' => 'المحاسب',
                'phone' => '0922000000',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $accountant->assignRole('accountant');

        $staff = User::firstOrCreate(
            ['email' => 'staff@factory.local'],
            [
                'name' => 'موظف الشحن',
                'phone' => '0933000000',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $staff->assignRole('shipping_staff');

        $this->command->info('Admin users seeded (3 accounts).');
    }
}
