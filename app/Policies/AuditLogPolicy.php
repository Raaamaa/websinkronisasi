<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->deleted_at === null && $user->hasPermission('audit.view');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->is_active && $user->deleted_at === null && $user->hasPermission('audit.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AuditLog $auditLog): bool
    {
        // Invarian #5 & AUDIT-02: audit_logs bersifat murni append-only
        return false;
    }

    public function delete(User $user, AuditLog $auditLog): bool
    {
        // Invarian #5 & AUDIT-02: audit_logs dilarang dihapus
        return false;
    }

    public function restore(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    public function forceDelete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
