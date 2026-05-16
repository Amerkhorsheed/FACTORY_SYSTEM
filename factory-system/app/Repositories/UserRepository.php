<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

/**
 * Data access for staff/admin user management.
 */
class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new User);
    }

    /** @param array<string, mixed> $filters */
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = User::with('roles')->latest('id');

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        if (! empty($filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $filters['role']));
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function loadRoles(User $user): User
    {
        return $user->load('roles');
    }

    public function syncRole(User $user, string $role): User
    {
        $user->syncRoles([$role]);

        return $this->loadRoles($user->refresh());
    }

    /** @return Collection<int, Role> */
    public function staffRoles(): Collection
    {
        return Role::query()
            ->where('name', '!=', 'customer')
            ->orderBy('name')
            ->get();
    }
}
