<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @test
     */
    public function it_lists_customers_with_pagination(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Customer::factory(3)->create();

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk();
    }

    /**
     * @test
     */
    public function it_creates_a_customer_with_auto_generated_code(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');

        $this->actingAs($admin)
            ->post(route('customers.store'), [
                'name' => 'أحمد محمد',
                'phone' => '0911111111',
                'address' => 'دمشق',
                'category' => 'B',
                'credit_limit' => 500_000,
            ])
            ->assertRedirect();

        $customer = Customer::where('phone', '0911111111')->first();
        $this->assertNotNull($customer);
        $this->assertMatchesRegularExpression('/^CUS-\d{4}-\d{5}$/', $customer->code);
    }

    /**
     * @test
     */
    public function it_enforces_unique_phone_number(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Customer::factory()->create(['phone' => '0911111111']);

        $this->actingAs($admin)
            ->post(route('customers.store'), [
                'name' => 'عميل جديد',
                'phone' => '0911111111',
                'address' => 'حلب',
                'category' => 'A',
                'credit_limit' => 0,
            ])
            ->assertSessionHasErrors('phone');
    }

    /**
     * @test
     */
    public function it_enables_portal_access_and_creates_linked_user(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['email' => 'test@example.com']);

        $this->actingAs($admin)
            ->post(route('customers.portal-access', $customer), [
                'password' => 'Test1234!',
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertTrue($customer->portal_access);
        $this->assertNotNull($customer->user_id);
        $this->assertTrue($customer->user->hasRole('customer'));
    }

    /**
     * @test
     */
    public function it_disables_portal_access(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create([
            'email' => 'disable@example.com',
            'portal_access' => true,
        ]);
        $user = User::factory()->create(['email' => 'disable@example.com']);
        $customer->update(['user_id' => $user->id]);

        $this->actingAs($admin)
            ->post(route('customers.portal-access', $customer))
            ->assertRedirect();

        $customer->refresh();
        $this->assertFalse($customer->portal_access);
        $this->assertFalse($customer->user->fresh()->is_active);
    }

    /**
     * @test
     */
    public function it_calculates_credit_availability_correctly(): void
    {
        $customer = Customer::factory()->create([
            'credit_limit' => 1_000_000,
            'outstanding_balance' => 300_000,
        ]);

        $this->assertSame(700_000, $customer->available_credit);
        $this->assertTrue($customer->canAcceptOrder(500_000));
        $this->assertFalse($customer->canAcceptOrder(800_000));
    }

    /**
     * @test
     */
    public function it_updates_a_customer(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin)
            ->put(route('customers.update', $customer), [
                'name' => 'New Name',
                'phone' => $customer->phone,
                'address' => $customer->address,
                'category' => $customer->category,
                'credit_limit' => $customer->credit_limit,
            ])
            ->assertRedirect();

        $this->assertSame('New Name', $customer->fresh()->name);
    }

    /**
     * @test
     */
    public function it_soft_deletes_a_customer(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertNull(Customer::find($customer->id));
        $this->assertNotNull(Customer::withTrashed()->find($customer->id));
    }

    /**
     * @test
     */
    public function it_blocks_deletion_when_active_orders_exist(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();
        Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'order_date' => today(),
            'subtotal' => 10_000,
            'total_amount' => 10_000,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error', __('customers.has_active_orders'));

        $this->assertNotNull(Customer::find($customer->id));
    }

    /**
     * @test
     */
    public function it_toggles_customer_activation(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('customers.activate', $customer))
            ->assertRedirect();

        $this->assertFalse($customer->fresh()->is_active);

        $this->actingAs($admin)
            ->post(route('customers.activate', $customer))
            ->assertRedirect();

        $this->assertTrue($customer->fresh()->is_active);
    }

    /**
     * @test
     */
    public function it_shows_customer_statement(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->get(route('customers.statement', $customer))
            ->assertOk();
    }
}
