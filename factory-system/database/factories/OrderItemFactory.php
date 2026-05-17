<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 50),
            'unit_price' => fake()->numberBetween(1_000, 50_000),
            'discount_percent' => 0,
            'discount_amount' => 0,
            'line_total' => fake()->numberBetween(10_000, 500_000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
