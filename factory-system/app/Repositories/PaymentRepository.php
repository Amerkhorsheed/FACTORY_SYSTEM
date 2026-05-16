<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Concrete payment repository for cross-invoice payment queries.
 */
class PaymentRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Payment);
    }

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->latest('payment_date')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @param array<string, mixed> $filters */
    public function sumWithFilters(array $filters): int
    {
        return (int) $this->filteredQuery($filters)->sum('amount');
    }

    public function loadDetails(Payment $payment): Payment
    {
        return $payment->load(['customer', 'invoice.customer', 'receivedByUser']);
    }

    /** @param array<string, mixed> $filters */
    private function filteredQuery(array $filters): Builder
    {
        $query = Payment::with(['customer', 'invoice.customer', 'receivedByUser']);

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (! empty($filters['invoice_id'])) {
            $query->where('invoice_id', (int) $filters['invoice_id']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        return $query;
    }
}
