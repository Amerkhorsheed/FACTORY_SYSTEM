<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'customer_id' => Customer::factory(),
            'amount' => $this->faker->numberBetween(10_000, 100_000),
            'payment_method' => $this->faker->randomElement(['cash', 'credit', 'check', 'bank_transfer']),
            'payment_date' => $this->faker->dateTimeBetween('-30 days', 'today'),
            'reference_number' => $this->faker->optional()->regexify('[A-Z0-9]{8,12}'),
            'notes' => null,
            'received_by' => User::factory(),
        ];
    }

    public function forInvoice(Invoice $invoice): static
    {
        return $this->state(fn (array $attributes) => [
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => min($attributes['amount'] ?? 50_000, $invoice->balance_due),
        ]);
    }
}
