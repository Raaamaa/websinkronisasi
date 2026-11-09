<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active && ($actor->isSuperAdmin() || $actor->isAdmin());
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->is_active && ($actor->isSuperAdmin() || $actor->isAdmin());
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && $actor->isSuperAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($actor->isAdmin()) {
            return $target->isPegawai(); // Admin HANYA boleh mengupdate Pegawai
        }

        return false;
    }

    public function delete(User $actor, User $target): bool
    {
        if (! $actor->is_active || ! $actor->isSuperAdmin() || $actor->id === $target->id) {
            return false;
        }

        // Invarian #4 & AUTH-06: Dilarang menghapus satu-satunya Super Admin aktif terakhir
        if ($target->isSuperAdmin() && $target->is_active) {
            $activeSuperAdminCount = User::query()
                ->where('role', 'super_admin')
                ->where('is_active', true)
                ->count();

            if ($activeSuperAdminCount <= 1) {
                return false;
            }
        }

        return true;
    }

    public function updateRole(User $actor, User $target): bool
    {
        return $actor->is_active && $actor->isSuperAdmin();
    }

    public function toggleActive(User $actor, User $target): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        // Invarian #4 & AUTH-06: Dilarang menonaktifkan satu-satunya Super Admin aktif terakhir
        if ($target->isSuperAdmin() && $target->is_active) {
            $activeSuperAdminCount = User::query()
                ->where('role', 'super_admin')
                ->where('is_active', true)
                ->count();

            if ($activeSuperAdminCount <= 1) {
                return false;
            }
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($actor->isAdmin()) {
            return $target->isPegawai(); // Admin hanya boleh toggle role Pegawai
        }

        return false;
    }
}
