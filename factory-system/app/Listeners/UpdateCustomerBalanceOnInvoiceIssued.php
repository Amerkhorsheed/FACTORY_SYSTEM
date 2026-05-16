<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;

/**
 * Update the customer's outstanding balance when an invoice is issued.
 *
 * Synchronous — balance must be accurate immediately after issuance.
 */
class UpdateCustomerBalanceOnInvoiceIssued
{
    /**
     * Handle the event.
     */
    public function handle(InvoiceIssued $event): void
    {
        $invoice = $event->invoice->load('customer');

        if ($invoice->customer) {
            $customer = $invoice->customer;
            $customer->outstanding_balance = $customer->invoices()
                ->whereNotIn('status', ['void', 'paid'])
                ->sum('balance_due');
            $customer->save();
        }
    }
}
