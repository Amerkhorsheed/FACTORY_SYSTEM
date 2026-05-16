<?php

namespace App\Contracts\Services;

use App\DTOs\Customers\CreateCustomerDTO;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomerServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator;

    public function create(CreateCustomerDTO $dto): Customer;

    public function update(Customer $customer, CreateCustomerDTO $dto): Customer;

    public function delete(Customer $customer): void;

    public function enablePortalAccess(Customer $customer, string $password): User;

    public function disablePortalAccess(Customer $customer): void;

    public function recalculateBalance(Customer $customer): void;

    public function getOrderHistory(Customer $customer): Collection;
}
