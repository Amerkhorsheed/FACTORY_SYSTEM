<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Notifications\TemporaryPasswordNotification;
use App\Repositories\UserRepository;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserManagementService extends BaseService
{
    public function __construct(private readonly UserRepository $users) {}

    /** @param array<string, mixed> $filters */
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->users->paginateWithFilters($filters, $perPage);
    }

    /** @return Collection<int, Role> */
    public function staffRoles(): Collection
    {
        return $this->users->staffRoles();
    }

    public function loadForEdit(User $user): User
    {
        return $this->users->loadRoles($user);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        return $this->transaction(function () use ($data): User {
            $role = $data['role'];
            unset($data['role'], $data['password_confirmation']);

            $data['password'] = Hash::make($data['password']);
            $data['is_active'] = true;

            /** @var User $user */
            $user = $this->users->create($data);

            return $this->users->syncRole($user, $role);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        return $this->transaction(function () use ($user, $data): User {
            $role = $data['role'];
            unset($data['role'], $data['password_confirmation']);

            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }

            /** @var User $updated */
            $updated = $this->users->update($user, $data);

            return $this->users->syncRole($updated, $role);
        });
    }

    public function delete(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages(['user' => __('admin.cannot_delete_self')]);
        }

        $this->transaction(function () use ($user): void {
            $this->users->delete($user);
        });
    }

    public function resetPassword(User $user): string
    {
        return $this->transaction(function () use ($user): string {
            $temporaryPassword = Str::random(12);

            $this->users->update($user, ['password' => Hash::make($temporaryPassword)]);
            $user->notify(new TemporaryPasswordNotification($temporaryPassword));

            return $temporaryPassword;
        });
    }
}
