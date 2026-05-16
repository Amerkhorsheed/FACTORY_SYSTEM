<?php

namespace Database\Factories;

use App\Models\Truck;
use Illuminate\Database\Eloquent\Factories\Factory;

class TruckFactory extends Factory
{
    protected $model = Truck::class;

    public function definition(): array
    {
        return [
            'plate_number' => $this->faker->unique()->regexify('[A-Z]{2,3}-[0-9]{4,5}'),
            'model' => $this->faker->word(),
            'capacity_kg' => $this->faker->randomFloat(2, 500, 5000),
            'capacity_units' => $this->faker->numberBetween(50, 500),
            'status' => 'available',
            'notes' => null,
            'is_active' => true,
        ];
    }
}
