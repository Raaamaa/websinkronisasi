<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LkImportProfile;
use App\Models\User;

class LkImportProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function view(User $user, LkImportProfile $profile): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function update(User $user, LkImportProfile $profile): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function delete(User $user, LkImportProfile $profile): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function restore(User $user, LkImportProfile $profile): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function forceDelete(User $user, LkImportProfile $profile): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }
}
