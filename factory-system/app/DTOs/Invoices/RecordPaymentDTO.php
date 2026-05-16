<?php

namespace App\DTOs\Invoices;

/**
 * Immutable DTO for recording a payment against an invoice.
 */
final readonly class RecordPaymentDTO
{
    public function __construct(
        public int $invoiceId,
        public int $customerId,
        public int $amount,
        public string $method,
        public string $paymentDate,
        public ?string $reference = null,
        public ?string $notes = null,
        public ?int $receivedBy = null,
    ) {}
}
