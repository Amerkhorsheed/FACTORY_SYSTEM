<?php

namespace App\Listeners;

use App\Events\Orders\OrderAccepted;
use App\Services\Invoices\InvoiceService;

/**
 * Automatically create a draft invoice when an order is accepted.
 *
 * Synchronous — runs within the same transaction as the status change.
 */
class CreateInvoiceOnOrderAccepted
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderAccepted $event): void
    {
        $order = $event->order;

        if (! $order->invoice) {
            $this->invoiceService->createFromOrder($order);
        }
    }
}
