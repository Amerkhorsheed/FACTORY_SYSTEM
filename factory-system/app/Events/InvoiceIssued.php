<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an invoice is formally issued to a customer.
 */
class InvoiceIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}
}
