<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Notifications\PaymentReceived as PaymentReceivedNotification;

/**
 * Send a payment confirmation notification to the customer.
 *
 * Queued — notification dispatch does not block the main request.
 */
class NotifyCustomerOnPaymentReceived
{
    /**
     * Handle the event.
     */
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment->load('invoice.customer');

        if ($payment->invoice?->customer?->user) {
            $payment->invoice->customer->user->notify(
                new PaymentReceivedNotification($payment)
            );
        }
    }
}
