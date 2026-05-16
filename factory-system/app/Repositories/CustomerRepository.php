<?php

namespace App\Repositories;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Concrete customer repository with search, filtering, and pagination.
 */
class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new Customer);
    }

    /**
     * @return Customer
     */
    public function findByIdOrFail(int $id)
    {
        return Customer::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Customer::query()->latest('id');

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('business_name', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (! empty($filters['has_balance'])) {
            $query->where('outstanding_balance', '>', 0);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function searchForOrder(string $term, int $limit = 8): Collection
    {
        return Customer::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'phone', 'credit_limit', 'outstanding_balance']);
    }
}
