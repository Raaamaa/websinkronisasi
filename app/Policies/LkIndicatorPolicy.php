<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LkIndicator;
use App\Models\User;

class LkIndicatorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, LkIndicator $indicator): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('lk.indicator_manage');
    }

    public function update(User $user, LkIndicator $indicator): bool
    {
        return $user->is_active && (
            $user->hasPermission('lk.indicator_manage') ||
            $user->hasPermission('lk.manage_drive_links')
        );
    }

    public function delete(User $user, LkIndicator $indicator): bool
    {
        return $user->is_active && $user->hasPermission('lk.indicator_manage');
    }

    public function restore(User $user, LkIndicator $indicator): bool
    {
        return $user->is_active && $user->hasPermission('lk.indicator_manage');
    }

    public function forceDelete(User $user, LkIndicator $indicator): bool
    {
        return $user->is_active && $user->hasPermission('lk.indicator_manage');
    }
}

