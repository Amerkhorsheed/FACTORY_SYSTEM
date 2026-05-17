<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Notifications\PaymentReceived as PaymentReceivedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Send a payment confirmation notification to the customer.
 *
 * Queued — notification dispatch does not block the main request.
 */
class NotifyCustomerOnPaymentReceived implements ShouldQueue
{
    public string $queue = 'notifications';

    public bool $afterCommit = true;

    /**
     * Handle the event.
     */
    public function handle(PaymentReceived $event): void
    {
        $payment = $event->payment->load('invoice.customer.user');

        if ($payment->invoice?->customer?->user) {
            $payment->invoice->customer->user->notify(
                new PaymentReceivedNotification($payment)
            );
        }
    }
}
