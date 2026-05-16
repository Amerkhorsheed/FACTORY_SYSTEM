<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Shipment;
use App\Models\StockMovement;
use App\Models\SystemSetting;
use App\Models\Truck;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelLayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('ar');
    }

    /**
     * @test
     */
    public function it_generates_sequential_codes_for_coded_models(): void
    {
        $user = User::factory()->create();
        $productOne = $this->product(['created_by' => $user->id]);
        $productTwo = $this->product(['name' => 'Second Product', 'created_by' => $user->id]);
        $customer = $this->customer(['created_by' => $user->id]);
        $shipment = $this->shipment($user);
        $order = $this->order($customer, $user, ['shipment_id' => $shipment->id]);
        $invoice = $this->invoice($order, $customer, $user);
        $payment = $this->payment($invoice, $customer, $user);

        $this->assertMatchesRegularExpression('/^PRD-\d{4}-00001$/', $productOne->code);
        $this->assertMatchesRegularExpression('/^PRD-\d{4}-00002$/', $productTwo->code);
        $this->assertMatchesRegularExpression('/^CUS-\d{4}-00001$/', $customer->code);
        $this->assertMatchesRegularExpression('/^SHP-\d{4}-00001$/', $shipment->shipment_number);
        $this->assertMatchesRegularExpression('/^ORD-\d{4}-00001$/', $order->order_number);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-00001$/', $invoice->invoice_number);
        $this->assertMatchesRegularExpression('/^PAY-\d{4}-00001$/', $payment->payment_number);
    }

    /**
     * @test
     */
    public function it_casts_money_columns_and_exposes_business_attributes(): void
    {
        $user = User::factory()->create();
        $customer = $this->customer([
            'credit_limit' => 100_000,
            'outstanding_balance' => 25_000,
            'created_by' => $user->id,
        ]);
        $product = $this->product([
            'unit_price' => 12_500,
            'cost_price' => 8_000,
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
            'created_by' => $user->id,
        ]);
        $order = $this->order($customer, $user, [
            'subtotal' => 75_000,
            'total_amount' => 75_000,
            'paid_amount' => 25_000,
        ]);
        $invoice = $this->invoice($order, $customer, $user, [
            'due_date' => today()->subDay(),
            'status' => 'issued',
            'balance_due' => 75_000,
        ]);

        $this->assertIsInt($product->unit_price);
        $this->assertStringContainsString('12,500', $product->formatted_unit_price);
        $this->assertTrue($product->is_low_stock);
        $this->assertSame('low', $product->stock_status);
        $this->assertTrue($customer->canAcceptOrder(75_000));
        $this->assertFalse($customer->canAcceptOrder(76_000));
        $this->assertSame('partial', $order->payment_status);
        $this->assertSame(50_000, $order->balance_due);
        $this->assertTrue($invoice->isOverdue());
        $this->assertGreaterThanOrEqual(1, $invoice->days_overdue);
    }

    /**
     * @test
     */
    public function it_maps_relationships_and_shipment_resolution_status(): void
    {
        $user = User::factory()->create();
        $customer = $this->customer(['created_by' => $user->id]);
        $shipment = $this->shipment($user);
        $order = $this->order($customer, $user, [
            'shipment_id' => $shipment->id,
            'status' => 'delivered',
        ]);
        $product = $this->product(['created_by' => $user->id]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 10_000,
            'line_total' => 30_000,
            'returned_qty' => 1,
        ]);

        $this->assertTrue($order->customer()->is($customer));
        $this->assertTrue($order->shipment()->is($shipment));
        $this->assertTrue($item->product()->is($product));
        $this->assertSame(30_000, $item->gross_amount);
        $this->assertSame(2, $item->remaining_qty);
        $this->assertSame(1, $shipment->total_orders_count);
        $this->assertSame(100.0, $shipment->delivery_progress);
        $this->assertTrue($shipment->allOrdersResolved());
    }

    /**
     * @test
     */
    public function it_blocks_soft_delete_when_active_relations_exist(): void
    {
        $user = User::factory()->create();
        $customer = $this->customer(['created_by' => $user->id]);
        $product = $this->product(['created_by' => $user->id]);
        $order = $this->order($customer, $user, ['status' => 'pending']);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10_000,
            'line_total' => 10_000,
        ]);

        $this->expectException(DomainException::class);

        $product->delete();
    }

    /**
     * @test
     */
    public function it_records_activity_for_observed_model_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->product(['unit_price' => 10_000, 'created_by' => $user->id]);
        $product->update(['unit_price' => 15_000]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'products',
            'description' => __('activity.products.price_changed'),
            'subject_type' => Product::class,
            'subject_id' => $product->id,
        ]);
    }

    /**
     * @test
     */
    public function it_exposes_stock_movement_and_setting_helpers(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['created_by' => $user->id]);
        $movement = StockMovement::create([
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
            'quantity_before' => 0,
            'quantity_after' => 5,
            'unit_cost' => 8_000,
            'created_by' => $user->id,
        ]);
        $setting = SystemSetting::create([
            'key' => 'enable_arabic_numerals',
            'value' => 'true',
            'type' => 'boolean',
            'group' => 'general',
            'label' => 'setting label',
        ]);

        $this->assertSame(__('stock_movements.types.in'), $movement->type_label);
        $this->assertTrue($movement->isIncoming());
        $this->assertStringContainsString('8,000', $movement->formatted_unit_cost);
        $this->assertTrue($setting->typedValue());
        $this->assertCount(1, SystemSetting::getByGroup('general'));
    }

    /** @param array<string, mixed> $overrides */
    private function product(array $overrides = []): Product
    {
        $category = ProductCategory::create([
            'name' => 'Raw Materials '.uniqid(),
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Factory Product '.uniqid(),
            'unit' => 'box',
            'unit_price' => 10_000,
            'cost_price' => 7_000,
            'stock_quantity' => 100,
            'low_stock_threshold' => 10,
            'is_active' => true,
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Factory Customer '.uniqid(),
            'phone' => '09'.random_int(10000000, 99999999),
            'address' => 'Main street',
            'category' => 'B',
            'credit_limit' => 0,
            'outstanding_balance' => 0,
            'is_active' => true,
        ], $overrides));
    }

    private function shipment(User $user): Shipment
    {
        $truck = Truck::create(['plate_number' => 'TRK-'.uniqid(), 'status' => 'available', 'is_active' => true]);
        $driver = Driver::create(['name' => 'Driver', 'phone' => '09'.random_int(10000000, 99999999)]);

        return Shipment::create([
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'shipment_date' => today(),
            'status' => 'planned',
            'created_by' => $user->id,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function order(Customer $customer, User $user, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'order_date' => today(),
            'subtotal' => 10_000,
            'total_amount' => 10_000,
            'paid_amount' => 0,
            'created_by' => $user->id,
        ], $overrides));
    }

    /** @param array<string, mixed> $overrides */
    private function invoice(Order $order, Customer $customer, User $user, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'type' => 'sale',
            'status' => 'issued',
            'issue_date' => today(),
            'due_date' => today()->addDays(30),
            'subtotal' => 10_000,
            'total_amount' => 10_000,
            'paid_amount' => 0,
            'balance_due' => 10_000,
            'created_by' => $user->id,
        ], $overrides));
    }

    private function payment(Invoice $invoice, Customer $customer, User $user): Payment
    {
        return Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
            'amount' => 5_000,
            'payment_method' => 'cash',
            'payment_date' => today(),
            'received_by' => $user->id,
        ]);
    }
}
