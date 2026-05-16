<?php

namespace App\Services\Admin;

use App\Repositories\ActivityLogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    public function __construct(private readonly ActivityLogRepository $activities) {}

    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->activities->paginateWithFilters($filters, $perPage);
    }

    public function find(int $id): Activity
    {
        return $this->activities->findByIdOrFail($id);
    }

    /** @return Collection<int, string> */
    public function logNames(): Collection
    {
        return $this->activities->logNames();
    }

    /** @return Collection<int, string> */
    public function events(): Collection
    {
        return $this->activities->events();
    }
}
