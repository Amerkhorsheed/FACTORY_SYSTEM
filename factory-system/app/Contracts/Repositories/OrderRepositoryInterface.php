<?php

namespace App\Contracts\Repositories;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    /**
     * @return Order|null
     */
    public function findById(int $id);

    /**
     * @return Order
     */
    public function findByIdOrFail(int $id);

    public function findByNumber(string $number): ?Order;

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function getForDate(Carbon $date): Collection;

    public function getPendingForCustomer(int $customerId): Collection;

    public function getReadyOrders(): Collection;

    /**
     * @param  array<string, mixed>  $data
     * @return Order
     */
    public function create(array $data);

    /**
     * @param  array<string, mixed>  $data
     * @return Order
     */
    public function update(Order $order, array $data);

    public function delete(Order $order): void;
}
