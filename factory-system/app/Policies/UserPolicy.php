<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('system.users.view');
    }

    public function view(User $user, User $managedUser): bool
    {
        return $user->hasPermissionTo('system.users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('system.users.create');
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->hasPermissionTo('system.users.edit');
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $user->hasPermissionTo('system.users.delete');
    }

    public function resetPassword(User $user, User $managedUser): bool
    {
        return $user->hasPermissionTo('system.users.edit');
    }
}
