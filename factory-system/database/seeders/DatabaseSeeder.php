<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Master database seeder.
 * Order matters — DO NOT reorder.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            SystemSettingsSeeder::class,
            ProductCategorySeeder::class,
        ]);
    }
}
