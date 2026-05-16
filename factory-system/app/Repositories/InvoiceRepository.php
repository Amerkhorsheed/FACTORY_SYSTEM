<?php

namespace App\Repositories;

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete invoice repository with filtering, overdue queries, and pagination.
 */
class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Invoice);
    }

    /**
     * @return Invoice|null
     */
    public function findById(int $id)
    {
        return Invoice::with(['order', 'customer', 'payments'])->find($id);
    }

    /**
     * @return Invoice
     */
    public function findByIdOrFail(int $id)
    {
        return Invoice::with(['order', 'customer', 'payments'])->findOrFail($id);
    }

    public function findByNumber(string $number): ?Invoice
    {
        return Invoice::where('invoice_number', $number)->first();
    }

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Invoice::with(['order', 'customer'])
            ->latest('issue_date')
            ->latest('id');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('invoice_number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        if (! empty($filters['status'])) {
            is_array($filters['status'])
                ? $query->whereIn('status', $filters['status'])
                : $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['overdue'])) {
            $query->overdue();
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getOverdue(): Collection
    {
        return Invoice::with(['customer'])
            ->overdue()
            ->orderBy('due_date')
            ->get();
    }
}
