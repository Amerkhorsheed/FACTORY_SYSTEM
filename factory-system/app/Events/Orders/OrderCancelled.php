<?php

namespace App\Events\Orders;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired when an order is cancelled. */
class OrderCancelled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Order $order) {}
}
