<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $total = $this->faker->numberBetween(100_000, 1_000_000);

        return [
            'order_id' => Order::factory(),
            'customer_id' => Customer::factory(),
            'type' => 'sale',
            'status' => 'draft',
            'issue_date' => $this->faker->dateTimeBetween('-30 days', 'today'),
            'due_date' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'subtotal' => $total,
            'discount_amount' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => $total,
            'paid_amount' => 0,
            'balance_due' => $total,
            'notes' => null,
            'pdf_path' => null,
            'sent_at' => null,
            'voided_at' => null,
            'void_reason' => null,
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'draft']);
    }

    public function issued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'balance_due' => $attributes['total_amount'] ?? 100_000,
        ]);
    }

    public function paid(): static
    {
        $total = $this->faker->numberBetween(100_000, 1_000_000);

        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'subtotal' => $total,
            'total_amount' => $total,
            'paid_amount' => $total,
            'balance_due' => 0,
        ]);
    }

    public function void(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'void',
            'voided_at' => now(),
            'void_reason' => $this->faker->sentence(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'issued',
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'balance_due' => $attributes['total_amount'] ?? 100_000,
        ]);
    }
}
