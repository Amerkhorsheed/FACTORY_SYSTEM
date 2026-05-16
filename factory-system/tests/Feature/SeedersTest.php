<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProductCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_seeds_all_roles_and_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'super_admin']);
        $this->assertDatabaseHas('roles', ['name' => 'accountant']);
        $this->assertDatabaseHas('roles', ['name' => 'shipping_staff']);
        $this->assertDatabaseHas('roles', ['name' => 'customer']);

        $this->assertSame(47, Permission::count());
    }

    /**
     * @test
     */
    public function it_assigns_all_permissions_to_super_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $superAdmin = Role::findByName('super_admin', 'web');

        $this->assertSame(Permission::count(), $superAdmin->permissions->count());
    }

    /**
     * @test
     */
    public function it_assigns_correct_permissions_to_shipping_staff(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $shippingStaff = Role::findByName('shipping_staff', 'web');
        $expected = [
            'orders.view',
            'orders.confirm_delivery',
            'shipments.view',
            'shipments.update_status',
            'shipments.view_manifest',
            'invoices.view',
            'products.view',
        ];

        $this->assertSame(
            collect($expected)->sort()->values()->toArray(),
            $shippingStaff->permissions->pluck('name')->sort()->values()->toArray()
        );
    }

    /**
     * @test
     */
    public function it_seeds_system_settings(): void
    {
        $this->seed(SystemSettingsSeeder::class);

        $this->assertTrue(SystemSetting::where('key', 'factory_name')->exists());
        $this->assertTrue(SystemSetting::where('key', 'invoice_due_days')->exists());
        $this->assertTrue(SystemSetting::where('key', 'default_credit_limit')->exists());
        $this->assertTrue(SystemSetting::where('key', 'enable_arabic_numerals')->exists());
    }

    /**
     * @test
     */
    public function it_creates_default_admin_users_with_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', 'admin@factory.local')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('super_admin'));

        $accountant = User::where('email', 'accountant@factory.local')->first();
        $this->assertNotNull($accountant);
        $this->assertTrue($accountant->hasRole('accountant'));

        $staff = User::where('email', 'staff@factory.local')->first();
        $this->assertNotNull($staff);
        $this->assertTrue($staff->hasRole('shipping_staff'));
    }

    /**
     * @test
     */
    public function it_seeds_product_categories(): void
    {
        $this->seed(ProductCategorySeeder::class);

        $this->assertDatabaseCount('product_categories', 8);
        $this->assertDatabaseHas('product_categories', ['name' => 'مواد غذائية', 'is_active' => true]);
        $this->assertDatabaseHas('product_categories', ['name' => 'متنوع', 'is_active' => true]);
    }

    /**
     * @test
     */
    public function full_database_seeder_runs_without_errors(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(4, Role::count());
        $this->assertSame(47, Permission::count());
        $this->assertSame(3, User::count());
        $this->assertSame(16, SystemSetting::count());
    }
}
