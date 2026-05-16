<?php

namespace App\Services\Invoices;

use App\Models\Payment;
use App\Repositories\PaymentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-side payment service for standalone payment screens.
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments,
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->payments->paginateWithFilters($filters, $perPage ?: 20);
    }

    /** @param array<string, mixed> $filters */
    public function total(array $filters): int
    {
        return $this->payments->sumWithFilters($filters);
    }

    public function loadDetails(Payment $payment): Payment
    {
        return $this->payments->loadDetails($payment);
    }
}
