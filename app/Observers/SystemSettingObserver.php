<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SystemSetting;
use App\Services\Audit\AuditLogService;

class SystemSettingObserver
{
    public function __construct(
        protected AuditLogService $auditService
    ) {}

    public function updated(SystemSetting $setting): void
    {
        $this->auditService->log(
            action: 'setting.updated',
            entityType: 'SystemSetting',
            entityId: $setting->key,
            oldValues: ['value' => $setting->getOriginal('value')],
            newValues: ['value' => $setting->value]
        );
    }
}
