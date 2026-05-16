<?php

namespace App\Contracts\Services;

use App\DTOs\Invoices\RecordPaymentDTO;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InvoiceServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator;

    public function createFromOrder(Order $order): Invoice;

    public function issue(Invoice $invoice): Invoice;

    public function void(Invoice $invoice, string $reason): Invoice;

    public function recordPayment(RecordPaymentDTO $dto): Payment;

    public function deletePayment(Payment $payment): void;
}
