<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LembarKerja;
use App\Models\User;

class LembarKerjaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->deleted_at === null && $user->hasPermission('lk.manage');
    }

    public function view(User $user, LembarKerja $lembarKerja): bool
    {
        return $user->is_active && $user->deleted_at === null && $user->hasPermission('lk.manage');
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->hasPermission('lk.manage');
    }

    public function update(User $user, LembarKerja $lembarKerja): bool
    {
        return $user->is_active && $user->hasPermission('lk.manage');
    }

    public function delete(User $user, LembarKerja $lembarKerja): bool
    {
        return $user->is_active && $user->hasPermission('lk.manage');
    }

    public function restore(User $user, LembarKerja $lembarKerja): bool
    {
        return $user->is_active && $user->hasPermission('lk.manage');
    }

    public function forceDelete(User $user, LembarKerja $lembarKerja): bool
    {
        return $user->is_active && $user->hasPermission('lk.manage');
    }
}
