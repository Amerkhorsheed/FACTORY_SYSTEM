<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Activitylog\Models\Activity;

class ActivityLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('system.audit_log.view');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasPermissionTo('system.audit_log.view');
    }
}
