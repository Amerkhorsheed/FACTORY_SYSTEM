<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentDispatched
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Shipment $shipment) {}
}
