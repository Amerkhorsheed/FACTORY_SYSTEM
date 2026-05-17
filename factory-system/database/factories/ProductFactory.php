<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'name' => fake()->unique()->words(4, true),
            'unit' => fake()->randomElement(['كرتون', 'كيس', 'علبة', 'لتر']),
            'unit_price' => fake()->numberBetween(1_000, 100_000),
            'cost_price' => fake()->numberBetween(500, 50_000),
            'stock_quantity' => fake()->numberBetween(0, 500),
            'low_stock_threshold' => fake()->numberBetween(5, 50),
            'is_active' => true,
            'created_by' => 1,
        ];
    }
}
