<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpensePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('erp.expenses.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('erp.expenses.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('erp.expenses.create');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('erp.expenses.edit');
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->hasPermissionTo('erp.expenses.edit');
    }
}
