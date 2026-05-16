<?php

namespace App\Contracts\Repositories;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface InvoiceRepositoryInterface
{
    /**
     * @return Invoice|null
     */
    public function findById(int $id);

    /**
     * @return Invoice
     */
    public function findByIdOrFail(int $id);

    public function findByNumber(string $number): ?Invoice;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getOverdue(): Collection;

    /**
     * @param  array<string, mixed>  $data
     * @return Invoice
     */
    public function create(array $data);

    /**
     * @param  array<string, mixed>  $data
     * @return Invoice
     */
    public function update(Invoice $invoice, array $data);

    public function delete(Invoice $invoice): void;
}
