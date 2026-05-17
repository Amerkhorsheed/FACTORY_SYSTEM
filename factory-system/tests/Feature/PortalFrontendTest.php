<?php

namespace Tests\Feature;

use App\Events\Orders\OrderPlacedByCustomer;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PortalFrontendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @return array{0: User, 1: Customer} */
    private function portalCustomer(): array
    {
        $user = User::factory()->create()->assignRole('customer');
        $customer = Customer::factory()->create([
            'user_id' => $user->id,
            'email' => $user->email,
            'portal_access' => true,
            'credit_limit' => 0,
            'outstanding_balance' => 0,
        ]);

        return [$user, $customer];
    }

    /** @test */
    public function it_renders_the_customer_portal_dashboard(): void
    {
        [$user, $customer] = $this->portalCustomer();
        Order::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee($customer->name);
    }

    /** @test */
    public function it_lists_only_the_authenticated_customer_orders(): void
    {
        [$user, $customer] = $this->portalCustomer();
        $ownOrder = Order::factory()->create(['customer_id' => $customer->id]);
        $otherOrder = Order::factory()->create();

        $this->actingAs($user)
            ->get(route('portal.orders.index'))
            ->assertOk()
            ->assertSee($ownOrder->order_number)
            ->assertDontSee($otherOrder->order_number);
    }

    /** @test */
    public function it_blocks_customer_access_to_other_customer_records(): void
    {
        [$user] = $this->portalCustomer();
        $otherOrder = Order::factory()->create();
        $otherInvoice = Invoice::factory()->issued()->create();

        $this->actingAs($user)
            ->get(route('portal.orders.show', $otherOrder))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('portal.invoices.show', $otherInvoice))
            ->assertForbidden();
    }

    /** @test */
    public function it_shows_order_and_invoice_details_for_the_customer(): void
    {
        [$user, $customer] = $this->portalCustomer();
        $product = Product::factory()->create(['name' => 'Portal Product']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);
        $invoice = Invoice::factory()->issued()->create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
        ]);

        $this->actingAs($user)
            ->get(route('portal.orders.show', $order))
            ->assertOk()
            ->assertSee('Portal Product');

        $this->actingAs($user)
            ->get(route('portal.invoices.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_number);
    }

    /** @test */
    public function it_updates_the_customer_portal_profile(): void
    {
        [$user, $customer] = $this->portalCustomer();

        $this->actingAs($user)
            ->put(route('portal.profile.update'), [
                'phone' => '0991111111',
                'phone_alt' => '0992222222',
                'address' => 'Updated customer address',
            ])
            ->assertRedirect();

        $customer->refresh();

        $this->assertSame('0991111111', $customer->phone);
        $this->assertSame('Updated customer address', $customer->address);
    }

    /** @test */
    public function it_creates_a_portal_order_using_server_side_product_pricing(): void
    {
        [$user, $customer] = $this->portalCustomer();
        $product = Product::factory()->create([
            'unit_price' => 12_500,
            'stock_quantity' => 20,
        ]);

        $this->actingAs($user)
            ->post(route('portal.orders.store'), [
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 1,
                ]],
                'notes' => 'Portal order request',
            ])
            ->assertRedirect();

        $order = Order::where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame(25_000, $order->total_amount);
        $this->assertSame(12_500, $order->items()->first()->unit_price);
    }

    /** @test */
    public function it_blocks_staff_from_customer_portal_routes(): void
    {
        $staff = User::factory()->create()->assignRole('accountant');

        $this->actingAs($staff)
            ->get(route('portal.dashboard'))
            ->assertForbidden();
    }

    /** @test */
    public function it_renders_the_profile_page(): void
    {
        [$user, $customer] = $this->portalCustomer();

        $this->actingAs($user)
            ->get(route('portal.profile'))
            ->assertOk()
            ->assertSee($customer->phone);
    }

    /** @test */
    public function it_blocks_deactivated_customer_from_portal(): void
    {
        [$user, $customer] = $this->portalCustomer();
        $user->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertRedirect();
    }

    /** @test */
    public function it_lists_only_the_authenticated_customer_invoices(): void
    {
        [$user, $customer] = $this->portalCustomer();
        $ownInvoice = Invoice::factory()->issued()->create(['customer_id' => $customer->id]);
        $otherInvoice = Invoice::factory()->issued()->create();

        $this->actingAs($user)
            ->get(route('portal.invoices.index'))
            ->assertOk()
            ->assertSee($ownInvoice->invoice_number)
            ->assertDontSee($otherInvoice->invoice_number);
    }

    /** @test */
    public function it_dispatches_event_when_portal_order_created(): void
    {
        [$user, $customer] = $this->portalCustomer();
        $product = Product::factory()->create([
            'unit_price' => 10_000,
            'stock_quantity' => 20,
        ]);

        Event::fake([OrderPlacedByCustomer::class]);

        $this->actingAs($user)
            ->post(route('portal.orders.store'), [
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]],
            ])
            ->assertRedirect();

        Event::assertDispatched(OrderPlacedByCustomer::class);
    }
}
