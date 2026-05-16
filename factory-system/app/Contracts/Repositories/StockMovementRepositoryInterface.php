<?php

namespace App\Contracts\Repositories;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;

interface StockMovementRepositoryInterface
{
    /**
     * @return StockMovement|null
     */
    public function findById(int $id);

    /**
     * @return StockMovement
     */
    public function findByIdOrFail(int $id);

    public function getForProduct(int $productId, int $limit = 50): Collection;

    public function getForDateRange(int $productId, string $from, string $to): Collection;

    /**
     * @param  array<string, mixed>  $data
     * @return StockMovement
     */
    public function create(array $data);
}
