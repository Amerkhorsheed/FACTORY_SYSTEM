<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Authorization policy for Customer model actions.
 */
class CustomerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.view');
    }

    public function viewPortal(User $user, Customer $customer): bool
    {
        return $user->hasRole('customer') && $user->customer?->id === $customer->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.edit');
    }

    public function updatePortal(User $user, Customer $customer): bool
    {
        return $this->viewPortal($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.delete');
    }

    public function manageCredit(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.manage_credit');
    }

    public function viewBalance(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.view_balance');
    }
}
