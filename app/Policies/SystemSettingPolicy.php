<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('settings.manage');
    }

    public function view(User $user, SystemSetting $setting): bool
    {
        return $user->is_active && $user->hasPermission('settings.manage');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('settings.manage');
    }

    public function update(User $user, SystemSetting $setting): bool
    {
        return $user->is_active && $user->hasPermission('settings.manage');
    }

    public function delete(User $user, SystemSetting $setting): bool
    {
        return $user->is_active && $user->hasPermission('settings.manage');
    }

    public function restore(User $user, SystemSetting $setting): bool
    {
        return $user->is_active && $user->hasPermission('settings.manage');
    }

    public function forceDelete(User $user, SystemSetting $setting): bool
    {
        return $user->is_active && $user->hasPermission('settings.manage');
    }
}

