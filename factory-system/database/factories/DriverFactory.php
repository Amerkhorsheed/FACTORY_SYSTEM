<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'license_number' => $this->faker->unique()->regexify('[A-Z0-9]{8,12}'),
            'license_expiry' => $this->faker->dateTimeBetween('+1 year', '+3 years'),
            'is_active' => true,
            'notes' => null,
        ];
    }
}
