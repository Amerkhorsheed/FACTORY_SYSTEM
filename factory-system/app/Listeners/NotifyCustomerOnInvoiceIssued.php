<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Notifications\InvoiceIssued as InvoiceIssuedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOnInvoiceIssued implements ShouldQueue
{
    public string $queue = 'notifications';

    public bool $afterCommit = true;

    public function handle(InvoiceIssued $event): void
    {
        $invoice = $event->invoice->load('customer.user');

        if ($invoice->customer?->user) {
            $invoice->customer->user->notify(new InvoiceIssuedNotification($invoice));
        }
    }
}
