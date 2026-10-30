<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LembarKerja;
use App\Services\Audit\AuditLogService;

class LembarKerjaObserver
{
    public function __construct(
        protected AuditLogService $auditService
    ) {}

    public function created(LembarKerja $lk): void
    {
        $this->auditService->log(
            action: 'lk.created',
            entityType: 'LembarKerja',
            entityId: $lk->id,
            newValues: $lk->only(['code', 'name', 'category', 'period', 'is_active'])
        );
    }

    public function updated(LembarKerja $lk): void
    {
        $dirty = $lk->getDirty();
        $original = [];
        foreach (array_keys($dirty) as $key) {
            $original[$key] = $lk->getOriginal($key);
        }

        $this->auditService->log(
            action: 'lk.updated',
            entityType: 'LembarKerja',
            entityId: $lk->id,
            oldValues: $original,
            newValues: $dirty
        );
    }

    public function deleted(LembarKerja $lk): void
    {
        $this->auditService->log(
            action: 'lk.deleted',
            entityType: 'LembarKerja',
            entityId: $lk->id,
            oldValues: $lk->only(['code', 'name', 'category', 'period'])
        );
    }

    public function restored(LembarKerja $lk): void
    {
        $this->auditService->log(
            action: 'lk.restored',
            entityType: 'LembarKerja',
            entityId: $lk->id,
            newValues: $lk->only(['code', 'name', 'category', 'period'])
        );
    }
}
