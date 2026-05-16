<?php

namespace App\Services\Erp;

use App\Models\Expense;
use App\Repositories\ExpenseRepository;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Expense management service with transaction-safe operations.
 */
class ExpenseService extends BaseService
{
    public function __construct(
        private readonly ExpenseRepository $expenses,
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->expenses->paginateWithFilters($filters, $perPage ?: 20);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Expense
    {
        return $this->transaction(function () use ($data) {
            return $this->expenses->create([
                ...$data,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Expense $expense, array $data): Expense
    {
        return $this->transaction(function () use ($expense, $data) {
            return $this->expenses->update($expense, $data);
        });
    }

    public function delete(Expense $expense): void
    {
        $this->transaction(function () use ($expense) {
            $this->expenses->delete($expense);
        });
    }

    public function getForMonth(int $year, int $month): Collection
    {
        return $this->expenses->getForMonth($year, $month);
    }

    public function getTotalForPeriod(mixed $from, mixed $to): int
    {
        return $this->expenses->getTotalForPeriod($from, $to);
    }
}
