<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Truck;
use Illuminate\Database\Seeder;

/**
 * Seed realistic Arabic demo data for development and showcase.
 *
 * Generates products, customers, orders, and expenses with Arabic names
 * to test dashboard charts, reports, and PDF generation.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Products ─────────────────────────────────────────
        $categories = ProductCategory::all();

        $products = Product::factory()
            ->count(30)
            ->sequence(fn ($seq) => [
                'category_id' => $categories->random()->id,
            ])
            ->create();

        // ── Customers ────────────────────────────────────────
        $customers = Customer::factory()
            ->count(15)
            ->create();

        // ── Trucks & Drivers ─────────────────────────────────
        $trucks = Truck::factory()->count(4)->create();
        $drivers = Driver::factory()->count(5)->create();

        // ── Orders ───────────────────────────────────────────
        foreach ($customers->take(10) as $customer) {
            $order = Order::factory()->create([
                'customer_id' => $customer->id,
                'status' => 'pending',
            ]);

            // Add 2–4 items per order
            $orderProducts = $products->random(rand(2, 4));

            foreach ($orderProducts as $product) {
                $qty = rand(5, 50);
                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->unit_price,
                    'total_price' => $product->unit_price * $qty,
                ]);
            }

            // Recalculate order total
            $order->update([
                'total_amount' => $order->items()->sum('total_price'),
            ]);
        }

        // ── Expenses ─────────────────────────────────────────
        Expense::factory()->count(12)->create();
    }
}
