<?php

namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a customer places an order through the self-service portal.
 */
class OrderPlacedByCustomer
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Order $order) {}
}
