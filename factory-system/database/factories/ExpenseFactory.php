<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'category' => $this->faker->randomElement([
                'fuel', 'maintenance', 'salary', 'rent', 'utilities', 'supplies', 'other',
            ]),
            'amount' => $this->faker->numberBetween(10_000, 500_000),
            'expense_date' => $this->faker->dateTimeBetween('-30 days', 'today'),
            'description' => $this->faker->sentence(),
            'reference' => $this->faker->optional()->regexify('[A-Z0-9]{6,10}'),
            'attachment' => null,
            'created_by' => User::factory(),
        ];
    }
}
