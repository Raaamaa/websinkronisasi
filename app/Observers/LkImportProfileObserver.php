<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LkImportProfile;
use App\Services\Audit\AuditLogService;

class LkImportProfileObserver
{
    public function __construct(
        protected AuditLogService $auditService
    ) {}

    public function created(LkImportProfile $profile): void
    {
        $this->auditService->log(
            action: 'lk_profile.created',
            entityType: 'LkImportProfile',
            entityId: (string) $profile->id,
            newValues: $profile->only(['name', 'parsing_strategy', 'sheet_name', 'header_row'])
        );
    }

    public function updated(LkImportProfile $profile): void
    {
        $dirty = $profile->getDirty();
        $original = [];
        foreach (array_keys($dirty) as $key) {
            $original[$key] = $profile->getOriginal($key);
        }

        $this->auditService->log(
            action: 'lk_profile.updated',
            entityType: 'LkImportProfile',
            entityId: (string) $profile->id,
            oldValues: $original,
            newValues: $dirty
        );
    }

    public function deleted(LkImportProfile $profile): void
    {
        $this->auditService->log(
            action: 'lk_profile.deleted',
            entityType: 'LkImportProfile',
            entityId: (string) $profile->id,
            oldValues: $profile->only(['name', 'parsing_strategy', 'sheet_name'])
        );
    }
}
