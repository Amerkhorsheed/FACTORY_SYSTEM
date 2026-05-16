<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Notifications\TemporaryPasswordNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SystemSettingsSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('super_admin');
    }

    /** @test */
    public function it_lists_admin_users(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Accountant User'])->assignRole('accountant');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Accountant User');
    }

    /** @test */
    public function it_creates_a_staff_user_with_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Shipping Manager',
                'email' => 'shipper@example.test',
                'phone' => '0999999999',
                'role' => 'shipping_staff',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'shipper@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertTrue($user->hasRole('shipping_staff'));
    }

    /** @test */
    public function it_updates_a_staff_user(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['email' => 'old@example.test'])->assignRole('shipping_staff');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => 'Updated User',
                'email' => 'updated@example.test',
                'phone' => '0988888888',
                'role' => 'accountant',
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('Updated User', $user->name);
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->hasRole('accountant'));
    }

    /** @test */
    public function it_deletes_another_user_but_not_self(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create()->assignRole('accountant');

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted($user);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
    }

    /** @test */
    public function it_resets_a_user_password_and_notifies_them(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $user = User::factory()->create()->assignRole('accountant');
        $oldPassword = $user->password;

        $this->actingAs($admin)
            ->post(route('admin.users.reset-password', $user))
            ->assertRedirect();

        $this->assertNotSame($oldPassword, $user->fresh()->password);
        Notification::assertSentTo($user, TemporaryPasswordNotification::class);
    }

    /** @test */
    public function it_updates_system_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'factory_name' => 'New Factory',
                'factory_address' => 'Damascus',
                'factory_phone' => '0111111111',
                'factory_tax_number' => 'TAX-1',
                'invoice_prefix' => 'FCT',
                'invoice_due_days' => 45,
                'invoice_tax_rate' => 0,
                'invoice_footer_text' => 'Thanks',
                'invoice_bank_details' => 'Bank',
                'invoice_terms' => 'Terms',
                'default_low_threshold' => 7,
                'enable_stock_warnings' => '1',
                'default_credit_limit' => 150_000,
                'default_category' => 'A',
                'enable_arabic_numerals' => '0',
            ])
            ->assertRedirect();

        $this->assertSame('New Factory', SystemSetting::where('key', 'factory_name')->first()->value);
        $this->assertSame('45', SystemSetting::where('key', 'invoice_due_days')->first()->value);
        $this->assertSame('150000', SystemSetting::where('key', 'default_credit_limit')->first()->value);
    }

    /** @test */
    public function it_lists_and_shows_audit_logs(): void
    {
        $admin = $this->admin();
        $activity = activity('admin')
            ->causedBy($admin)
            ->event('updated')
            ->log('Admin action');

        $this->actingAs($admin)
            ->get(route('admin.audit-log.index', ['log_name' => 'admin']))
            ->assertOk()
            ->assertSee('Admin action');

        $this->actingAs($admin)
            ->get(route('admin.audit-log.show', $activity))
            ->assertOk()
            ->assertSee('Admin action');
    }

    /** @test */
    public function it_blocks_non_admin_access_to_admin_routes(): void
    {
        $customer = User::factory()->create()->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('admin.users.index'))
            ->assertRedirect(route('portal.dashboard'));
    }
}
