<?php

namespace App\Contracts\Repositories;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;

interface StockMovementRepositoryInterface
{
    public function findById(int $id): ?StockMovement;

    public function findByIdOrFail(int $id): StockMovement;

    public function getForProduct(int $productId, int $limit = 50): Collection;

    public function getForDateRange(int $productId, string $from, string $to): Collection;

    /** @param array<string, mixed> $data */
    public function create(array $data): StockMovement;
}
