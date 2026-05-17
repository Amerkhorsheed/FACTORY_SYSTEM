<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Shipment;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemTestSeeder extends Seeder
{
    private const CATEGORY_COUNT = 100;

    private const PRODUCT_COUNT = 500;

    private const CUSTOMER_COUNT = 500;

    private const TRUCK_COUNT = 50;

    private const DRIVER_COUNT = 100;

    private const EXPENSE_COUNT = 1000;

    private const SHIPMENT_COUNT = 500;

    private const ORDER_COUNT = 5000;

    /** @var array<string, int> */
    private const ORDER_STATUS_WEIGHTS = [
        'pending' => 10,
        'accepted' => 10,
        'ready' => 10,
        'shipped' => 30,
        'delivered' => 30,
        'cancelled' => 10,
    ];

    /**
     * Seed realistic massive data for system load testing.
     */
    public function run(): void
    {
        $this->info('Starting massive system test data generation...');

        DB::disableQueryLog();

        $admin = User::query()->first() ?? User::factory()->create();

        $this->info('Creating categories and products...');
        $categories = ProductCategory::factory()->count(self::CATEGORY_COUNT)->create();
        $products = Product::factory()
            ->count(self::PRODUCT_COUNT)
            ->recycle($categories)
            ->state(['created_by' => $admin->id])
            ->create();

        $this->info('Creating customers...');
        $customers = Customer::factory()
            ->count(self::CUSTOMER_COUNT)
            ->state(['created_by' => $admin->id])
            ->create();

        $this->info('Creating logistics entities...');
        $trucks = Truck::factory()->count(self::TRUCK_COUNT)->create();
        $drivers = Driver::factory()->count(self::DRIVER_COUNT)->create();

        $this->info('Creating expenses...');
        Expense::factory()
            ->count(self::EXPENSE_COUNT)
            ->state(['created_by' => $admin->id])
            ->create();

        $this->info('Creating shipments...');
        $shipments = Shipment::factory()
            ->count(self::SHIPMENT_COUNT)
            ->recycle($trucks)
            ->recycle($drivers)
            ->state(['created_by' => $admin->id])
            ->create();

        $this->info('Creating orders and transactions. This may take a while...');
        $bar = $this->command?->getOutput()->createProgressBar(self::ORDER_COUNT);
        $bar?->start();

        $statusPool = $this->statusPool();

        for ($i = 0; $i < self::ORDER_COUNT; $i++) {
            $customer = $customers->random();
            $status = $statusPool[array_rand($statusPool)];
            $shipmentId = in_array($status, ['shipped', 'delivered'], true)
                ? $shipments->random()->id
                : null;

            $order = Order::factory()->create(array_merge(
                [
                    'customer_id' => $customer->id,
                    'status' => $status,
                    'shipment_id' => $shipmentId,
                    'created_by' => $admin->id,
                ],
                $this->orderStatusAttributes($status, $admin->id)
            ));

            $orderProducts = $products->random(random_int(2, 4));
            $orderTotal = 0;

            foreach ($orderProducts as $product) {
                $qty = random_int(5, 50);
                $unitPrice = $product->unit_price;
                $totalPrice = $unitPrice * $qty;
                $orderTotal += $totalPrice;

                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $totalPrice,
                ]);
            }

            [$invoiceStatus, $paidAmount] = $this->invoiceState($status, $orderTotal);

            $order->update([
                'total_amount' => $orderTotal,
                'subtotal' => $orderTotal,
                'paid_amount' => $paidAmount,
            ]);

            $invoice = Invoice::factory()->create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'status' => $invoiceStatus,
                'subtotal' => $orderTotal,
                'total_amount' => $orderTotal,
                'paid_amount' => $paidAmount,
                'balance_due' => max(0, $orderTotal - $paidAmount),
                'created_by' => $admin->id,
            ]);

            if ($paidAmount > 0 && $invoiceStatus !== 'void') {
                Payment::factory()->create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $customer->id,
                    'amount' => $paidAmount,
                    'received_by' => $admin->id,
                ]);
            }

            $bar?->advance();
        }

        $bar?->finish();
        $this->command?->newLine();
        $this->info('Massive system test data generation completed successfully.');
    }

    /** @return array<int, string> */
    private function statusPool(): array
    {
        $pool = [];

        foreach (self::ORDER_STATUS_WEIGHTS as $status => $weight) {
            $pool = array_merge($pool, array_fill(0, $weight, $status));
        }

        return $pool;
    }

    /** @return array<string, mixed> */
    private function orderStatusAttributes(string $status, int $adminId): array
    {
        return match ($status) {
            'accepted', 'ready' => [
                'accepted_by' => $adminId,
                'accepted_at' => now(),
            ],
            'shipped' => [
                'accepted_by' => $adminId,
                'accepted_at' => now()->subDay(),
                'shipped_by' => $adminId,
                'shipped_at' => now(),
            ],
            'delivered' => [
                'accepted_by' => $adminId,
                'accepted_at' => now()->subDays(2),
                'shipped_by' => $adminId,
                'shipped_at' => now()->subDay(),
                'delivered_at' => now(),
            ],
            'cancelled' => [
                'cancel_reason' => 'System test cancellation',
            ],
            default => [],
        };
    }

    /** @return array{0: string, 1: int} */
    private function invoiceState(string $orderStatus, int $orderTotal): array
    {
        if ($orderStatus === 'cancelled') {
            return ['void', 0];
        }

        if ($orderStatus === 'pending') {
            return ['draft', 0];
        }

        if ($orderStatus === 'delivered') {
            if (random_int(1, 100) > 20) {
                return ['paid', $orderTotal];
            }

            return ['partial', $this->partialPayment($orderTotal)];
        }

        if (random_int(1, 100) > 70) {
            return ['partial', $this->partialPayment($orderTotal)];
        }

        return ['issued', 0];
    }

    private function partialPayment(int $total): int
    {
        return intdiv($total * random_int(10, 90), 100);
    }

    private function info(string $message): void
    {
        $this->command?->info($message);
    }
}
