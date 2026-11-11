<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EvidenceDocument;
use App\Models\User;

class EvidenceDocumentPolicy
{
    private function isActiveUser(User $user): bool
    {
        return $user->is_active && $user->deleted_at === null;
    }

    public function viewAny(User $user): bool
    {
        return $this->isActiveUser($user)
            && ($user->hasPermission('evidence.search') || $user->hasPermission('evidence.verify'));
    }

    public function view(User $user, EvidenceDocument $evidenceDocument): bool
    {
        if (! $this->isActiveUser($user)) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAdmin() || $user->hasPermission('evidence.verify')) {
            return true;
        }

        if ($user->hasPermission('evidence.search')) {
            return $evidenceDocument->approval_status === 'disetujui'
                || ($evidenceDocument->uploaded_by !== null && (string) $evidenceDocument->uploaded_by === (string) $user->id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->isActiveUser($user) && $user->hasPermission('evidence.upload');
    }

    public function update(User $user, EvidenceDocument $evidenceDocument): bool
    {
        if (! $this->isActiveUser($user)) {
            return false;
        }

        if ($user->hasPermission('evidence.verify') || $user->hasPermission('evidence.manage_markup')) {
            return true;
        }

        if ($user->hasPermission('evidence.revise_own')) {
            return $evidenceDocument->uploaded_by !== null
                && (string) $evidenceDocument->uploaded_by === (string) $user->id
                && $evidenceDocument->approval_status === 'revisi';
        }

        return false;
    }

    public function delete(User $user, EvidenceDocument $evidenceDocument): bool
    {
        return $this->isActiveUser($user) && $user->hasPermission('evidence.delete');
    }

    public function restore(User $user, EvidenceDocument $evidenceDocument): bool
    {
        return $this->isActiveUser($user) && $user->hasPermission('evidence.delete');
    }

    public function forceDelete(User $user, EvidenceDocument $evidenceDocument): bool
    {
        return $this->isActiveUser($user) && $user->hasPermission('evidence.delete');
    }
}
