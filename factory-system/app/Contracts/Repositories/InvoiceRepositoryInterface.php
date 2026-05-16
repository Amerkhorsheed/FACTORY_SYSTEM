<?php

namespace App\Contracts\Repositories;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceRepositoryInterface
{
    public function findById(int $id): ?Invoice;

    public function findByIdOrFail(int $id): Invoice;

    public function findByNumber(string $number): ?Invoice;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getOverdue(): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): Invoice;

    /** @param array<string, mixed> $data */
    public function update(Invoice $invoice, array $data): Invoice;

    public function delete(Invoice $invoice): void;
}
