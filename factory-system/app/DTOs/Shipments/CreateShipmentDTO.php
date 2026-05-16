<?php

namespace App\DTOs\Shipments;

use Carbon\Carbon;

/**
 * @property-read int $truck_id
 * @property-read int $driver_id
 * @property-read Carbon $shipment_date
 * @property-read string|null $notes
 */
final readonly class CreateShipmentDTO
{
    public function __construct(
        public int $truck_id,
        public int $driver_id,
        public Carbon $shipment_date,
        public ?string $notes = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'truck_id' => $this->truck_id,
            'driver_id' => $this->driver_id,
            'shipment_date' => $this->shipment_date,
            'notes' => $this->notes,
        ];
    }
}
