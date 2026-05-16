<?php

namespace App\Contracts\Repositories;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    public function findByIdOrFail(int $id): Order;

    public function findByNumber(string $number): ?Order;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getForDate(Carbon $date): Collection;

    public function getPendingForCustomer(int $customerId): Collection;

    public function getReadyOrders(): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): Order;

    /** @param array<string, mixed> $data */
    public function update(Order $order, array $data): Order;

    public function delete(Order $order): void;
}
