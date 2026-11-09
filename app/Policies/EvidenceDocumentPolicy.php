<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EvidenceDocument;
use App\Models\User;

class EvidenceDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->hasPermission('evidence.search');
    }

    public function view(User $user, EvidenceDocument $evidenceDocument): bool
    {
        return $user->is_active && $user->hasPermission('evidence.search');
    }

    public function create(User $user): bool
    {
        // RBAC.md: Pegawai dan Admin diizinkan unggah matching, Super Admin tidak
        return $user->is_active && $user->hasPermission('evidence.upload');
    }

    public function update(User $user, EvidenceDocument $evidenceDocument): bool
    {
        return $user->is_active && $user->isSuperAdmin();
    }

    public function delete(User $user, EvidenceDocument $evidenceDocument): bool
    {
        // RBAC.md: Hanya Super Admin yang berhak soft-delete dokumen bukti
        return $user->is_active && $user->hasPermission('evidence.delete');
    }

    public function restore(User $user, EvidenceDocument $evidenceDocument): bool
    {
        return $user->is_active && $user->hasPermission('evidence.delete');
    }

    public function forceDelete(User $user, EvidenceDocument $evidenceDocument): bool
    {
        return $user->is_active && $user->hasPermission('evidence.delete');
    }
}

