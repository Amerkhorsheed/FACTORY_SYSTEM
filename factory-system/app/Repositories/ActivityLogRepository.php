<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

/**
 * Read-only data access for audit log entries.
 */
class ActivityLogRepository
{
    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = Activity::with(['causer', 'subject'])->latest('id');

        if (! empty($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['causer_id'])) {
            $query->where('causer_id', $filters['causer_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findByIdOrFail(int $id): Activity
    {
        return Activity::with(['causer', 'subject'])->findOrFail($id);
    }

    /** @return Collection<int, string> */
    public function logNames(): Collection
    {
        return Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->values();
    }

    /** @return Collection<int, string> */
    public function events(): Collection
    {
        return Activity::query()
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->values();
    }
}
