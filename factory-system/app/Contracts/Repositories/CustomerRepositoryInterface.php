<?php

namespace App\Contracts\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    /**
     * @return Customer|null
     */
    public function findById(int $id);

    /**
     * @return Customer
     */
    public function findByIdOrFail(int $id);

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function searchForOrder(string $term, int $limit = 8): Collection;

    /**
     * @param  array<string, mixed>  $data
     * @return Customer
     */
    public function create(array $data);

    /**
     * @param  array<string, mixed>  $data
     * @return Customer
     */
    public function update(Customer $customer, array $data);

    public function delete(Customer $customer): void;
}
