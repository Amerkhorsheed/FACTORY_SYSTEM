<?php

namespace App\Contracts\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    public function findById(int $id): ?Customer;

    public function findByIdOrFail(int $id): Customer;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function searchForOrder(string $term, int $limit = 8): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): Customer;

    /** @param array<string, mixed> $data */
    public function update(Customer $customer, array $data): Customer;

    public function delete(Customer $customer): void;
}
