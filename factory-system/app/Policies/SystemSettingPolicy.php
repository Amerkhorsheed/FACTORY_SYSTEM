<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SystemSettingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('system.settings.view');
    }

    public function view(User $user, SystemSetting $setting): bool
    {
        return $user->hasPermissionTo('system.settings.view');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('system.settings.edit');
    }
}
