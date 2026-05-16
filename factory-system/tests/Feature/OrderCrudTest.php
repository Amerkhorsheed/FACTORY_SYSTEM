<?php

namespace Tests\Feature;

use App\DTOs\Orders\CreateOrderDTO;
use App\Exceptions\CreditLimitExceededException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCrudTest extends TestCase
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
    public function it_lists_orders_with_pagination(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Order::factory(3)->create();

        $this->actingAs($admin)
            ->get(route('orders.index'))
            ->assertOk();
    }

    /**
     * @test
     */
    public function it_creates_a_pending_order_with_correct_totals(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['credit_limit' => 10_000_000]);
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'unit_price' => 50_000,
            'cost_price' => 30_000,
        ]);

        $dto = CreateOrderDTO::fromArray([
            'customer_id' => $customer->id,
            'order_date' => today()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2,
                    'unit_price' => 50_000, 'discount_percent' => 0],
            ],
            'created_by' => $admin->id,
        ]);

        $order = app(OrderService::class)->create($dto);

        $this->assertSame('pending', $order->status);
        $this->assertSame(100_000, $order->total_amount);
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-\d{5}$/', $order->order_number);
    }

    /**
     * @test
     */
    public function it_creates_an_order_via_controller(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['credit_limit' => 10_000_000]);
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'unit_price' => 50_000,
        ]);

        $this->actingAs($admin)
            ->post(route('orders.store'), [
                'customer_id' => $customer->id,
                'order_date' => today()->toDateString(),
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2,
                        'unit_price' => 50_000, 'discount_percent' => 0],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total_amount' => 100_000,
        ]);
    }

    /**
     * @test
     */
    public function it_deducts_stock_on_order_acceptance(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'unit_price' => 50_000,
        ]);
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 50_000,
            'line_total' => 250_000,
        ]);

        $this->actingAs($admin)
            ->post(route('orders.status.accept', $order))
            ->assertRedirect();

        $this->assertSame(95, $product->fresh()->stock_quantity);
        $this->assertSame('accepted', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->invoice);
    }

    /**
     * @test
     */
    public function it_returns_stock_on_cancellation_after_acceptance(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'unit_price' => 50_000,
        ]);
        $order = Order::factory()->accepted()->create(['customer_id' => $customer->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 50_000,
            'line_total' => 250_000,
        ]);
        $product->update(['stock_quantity' => 95]);

        $this->actingAs($admin)
            ->post(route('orders.status.cancel', $order), ['reason' => 'اختبار'])
            ->assertRedirect();

        $this->assertSame(100, $product->fresh()->stock_quantity);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    /**
     * @test
     */
    public function it_blocks_order_exceeding_credit_limit(): void
    {
        $customer = Customer::factory()->create([
            'credit_limit' => 100_000,
            'outstanding_balance' => 80_000,
        ]);
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'unit_price' => 50_000,
        ]);

        $dto = CreateOrderDTO::fromArray([
            'customer_id' => $customer->id,
            'order_date' => today()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5,
                    'unit_price' => 50_000, 'discount_percent' => 0],
            ],
            'created_by' => 1,
        ]);

        $this->expectException(CreditLimitExceededException::class);

        app(OrderService::class)->create($dto);
    }

    /**
     * @test
     */
    public function it_completes_full_lifecycle_pending_to_delivered(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['credit_limit' => 10_000_000]);
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'unit_price' => 50_000,
        ]);

        $dto = CreateOrderDTO::fromArray([
            'customer_id' => $customer->id,
            'order_date' => today()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3,
                    'unit_price' => 50_000, 'discount_percent' => 0],
            ],
            'created_by' => $admin->id,
        ]);
        $order = app(OrderService::class)->create($dto);
        $this->assertSame('pending', $order->status);

        $this->actingAs($admin)
            ->post(route('orders.status.accept', $order))
            ->assertRedirect();
        $this->assertSame('accepted', $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('orders.status.preparing', $order))
            ->assertRedirect();
        $this->assertSame('preparing', $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('orders.status.ready', $order))
            ->assertRedirect();
        $this->assertSame('ready', $order->fresh()->status);

        $this->actingAs($admin)
            ->post(route('orders.status.deliver', $order))
            ->assertRedirect();
        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('issued', $order->fresh()->invoice->status);
    }

    /**
     * @test
     */
    public function it_prevents_editing_non_editable_order(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['unit_price' => 10_000]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'shipped',
        ]);

        $this->actingAs($admin)
            ->get(route('orders.edit', $order))
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function it_prevents_deleting_shipped_order(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'shipped',
        ]);

        $this->actingAs($admin)
            ->delete(route('orders.destroy', $order))
            ->assertForbidden();

        $this->assertNotNull(Order::find($order->id));
    }

    /**
     * @test
     */
    public function it_updates_an_editable_order(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['credit_limit' => 10_000_000]);
        $product = Product::factory()->create([
            'stock_quantity' => 100,
            'unit_price' => 50_000,
        ]);
        $order = Order::factory()->pending()->create(['customer_id' => $customer->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50_000,
            'line_total' => 50_000,
        ]);

        $this->actingAs($admin)
            ->put(route('orders.update', $order), [
                'customer_id' => $customer->id,
                'order_date' => today()->toDateString(),
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3,
                        'unit_price' => 50_000, 'discount_percent' => 0],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(150_000, $order->fresh()->total_amount);
        $this->assertCount(1, $order->fresh()->items);
    }

    /**
     * @test
     */
    public function it_shows_daily_orders(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Order::factory()->create(['order_date' => today()]);

        $this->actingAs($admin)
            ->get(route('orders.daily'))
            ->assertOk();
    }
}
