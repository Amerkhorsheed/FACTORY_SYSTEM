<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\Shipment;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'truck_id' => Truck::factory(),
            'driver_id' => Driver::factory(),
            'shipment_date' => $this->faker->dateTimeBetween('-1 week', '+1 week'),
            'status' => 'planned',
            'departure_time' => null,
            'return_time' => null,
            'manifest_path' => null,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
