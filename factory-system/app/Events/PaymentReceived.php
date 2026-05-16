<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a payment is successfully recorded against an invoice.
 */
class PaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Payment $payment,
    ) {}
}
