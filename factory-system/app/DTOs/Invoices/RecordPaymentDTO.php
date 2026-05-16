<?php

namespace App\DTOs\Invoices;

/**
 * Minimal DTO for recording a payment against an invoice.
 */
final class RecordPaymentDTO
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly int $amount,
        public readonly string $method,
        public readonly ?string $reference = null,
        public readonly ?string $notes = null,
    ) {}
}
