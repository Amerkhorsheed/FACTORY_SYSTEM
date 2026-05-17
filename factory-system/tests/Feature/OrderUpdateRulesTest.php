<?php

namespace Tests\Feature;

use App\DTOs\Orders\CreateOrderDTO;
use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Orders\OrderService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderUpdateRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function it_updates_orders_using_current_product_prices(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['credit_limit' => 10_000_000]);
        $product = Product::factory()->create(['stock_quantity' => 100, 'unit_price' => 50_000]);
        $order = $this->createPendingOrder($customer, $product);

        app(OrderService::class)->update($order, $this->dto($customer, $product, 2, 1, $admin));

        $order->refresh();
        $this->assertSame(100_000, $order->total_amount);
        $this->assertSame(50_000, $order->items()->first()->unit_price);
    }

    /** @test */
    public function it_blocks_updates_when_stock_is_insufficient(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['credit_limit' => 10_000_000]);
        $product = Product::factory()->create(['stock_quantity' => 2, 'unit_price' => 50_000]);
        $order = $this->createPendingOrder($customer, $product);

        $this->expectException(InsufficientStockException::class);

        app(OrderService::class)->update($order, $this->dto($customer, $product, 3, 50_000, $admin));
    }

    /** @test */
    public function it_blocks_updates_exceeding_customer_credit(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['credit_limit' => 100_000, 'outstanding_balance' => 0]);
        $product = Product::factory()->create(['stock_quantity' => 100, 'unit_price' => 50_000]);
        $order = $this->createPendingOrder($customer, $product);

        $this->expectException(CreditLimitExceededException::class);

        app(OrderService::class)->update($order, $this->dto($customer, $product, 3, 50_000, $admin));
    }

    /** @test */
    public function it_syncs_stock_and_invoice_when_updating_an_accepted_order(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $customer = Customer::factory()->create(['credit_limit' => 10_000_000]);
        $product = Product::factory()->create(['stock_quantity' => 95, 'unit_price' => 50_000]);
        $order = Order::factory()->accepted()->create([
            'customer_id' => $customer->id,
            'subtotal' => 250_000,
            'total_amount' => 250_000,
            'created_by' => $admin->id,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_price' => 50_000,
            'line_total' => 250_000,
        ]);
        $invoice = Invoice::factory()->draft()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'subtotal' => 250_000,
            'total_amount' => 250_000,
            'paid_amount' => 0,
            'balance_due' => 250_000,
        ]);

        $this->actingAs($admin);
        app(OrderService::class)->update($order, $this->dto($customer, $product, 3, 50_000, $admin));

        $this->assertSame(97, $product->fresh()->stock_quantity);
        $this->assertSame(150_000, $order->fresh()->total_amount);
        $this->assertSame(150_000, $invoice->fresh()->balance_due);
    }

    private function createPendingOrder(Customer $customer, Product $product): Order
    {
        $order = Order::factory()->pending()->create([
            'customer_id' => $customer->id,
            'total_amount' => 50_000,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 50_000,
            'line_total' => 50_000,
        ]);

        return $order;
    }

    private function dto(Customer $customer, Product $product, int $quantity, int $unitPrice, User $admin): CreateOrderDTO
    {
        return CreateOrderDTO::fromArray([
            'customer_id' => $customer->id,
            'order_date' => today()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_percent' => 0,
                ],
            ],
            'created_by' => $admin->id,
        ]);
    }
}
