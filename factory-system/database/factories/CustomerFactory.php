<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'business_name' => fake()->optional()->company(),
            'phone' => '09'.fake()->numerify('########'),
            'phone_alt' => fake()->optional(0.3)->numerify('09########'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'region' => fake()->optional()->word(),
            'category' => fake()->randomElement(['A', 'B', 'C']),
            'credit_limit' => fake()->randomElement([0, 500_000, 1_000_000]),
            'outstanding_balance' => 0,
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
            'portal_access' => false,
            'created_by' => User::factory(),
        ];
    }
}
