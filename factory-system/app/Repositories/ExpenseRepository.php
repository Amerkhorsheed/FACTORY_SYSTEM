<?php

namespace App\Repositories;

use App\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete expense repository with filtering, pagination, and date-range queries.
 */
class ExpenseRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Expense);
    }

    /**
     * @return Expense|null
     */
    public function findById(int $id)
    {
        return Expense::with('createdByUser')->find($id);
    }

    /**
     * @return Expense
     */
    public function findByIdOrFail(int $id)
    {
        return Expense::with('createdByUser')->findOrFail($id);
    }

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Expense::with('createdByUser')
            ->latest('expense_date')
            ->latest('id');

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function getForMonth(int $year, int $month): Collection
    {
        return Expense::whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->orderBy('expense_date')
            ->get();
    }

    public function getTotalForPeriod(mixed $from, mixed $to): int
    {
        return (int) Expense::whereBetween('expense_date', [$from, $to])->sum('amount');
    }
}
