<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function it_lists_payments_with_pagination(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Payment::factory(3)->create();

        $this->actingAs($admin)
            ->get(route('payments.index'))
            ->assertOk();
    }

    /** @test */
    public function it_shows_a_payment(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $payment = Payment::factory()->create();

        $this->actingAs($admin)
            ->get(route('payments.show', $payment))
            ->assertOk();
    }

    /** @test */
    public function it_deletes_a_payment(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $invoice = Invoice::factory()->issued()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'total_amount' => 100_000,
            'balance_due' => 60_000,
            'paid_amount' => 40_000,
            'status' => 'partial',
        ]);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 40_000,
            'received_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect();

        $this->assertSoftDeleted($payment);
        $this->assertSame('issued', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->paid_amount);
    }

    /** @test */
    public function it_filters_payments_by_customer(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();
        Payment::factory()->create(['customer_id' => $customer->id]);
        Payment::factory()->create();

        $response = $this->actingAs($admin)
            ->get(route('payments.index', ['customer_id' => $customer->id]));

        $response->assertOk();
    }

    /** @test */
    public function it_blocks_unauthorized_payment_access(): void
    {
        $regularUser = User::factory()->create()->assignRole('customer');
        $payment = Payment::factory()->create();

        $this->actingAs($regularUser)
            ->get(route('payments.index'))
            ->assertRedirect(route('portal.dashboard'));
    }
}
