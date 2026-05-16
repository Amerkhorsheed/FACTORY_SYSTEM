<?php

namespace App\Contracts\Services;

use App\DTOs\Orders\CreateOrderDTO;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderServiceInterface
{
    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator;

    public function create(CreateOrderDTO $dto): Order;

    public function update(Order $order, CreateOrderDTO $dto): Order;

    public function delete(Order $order): void;
}
