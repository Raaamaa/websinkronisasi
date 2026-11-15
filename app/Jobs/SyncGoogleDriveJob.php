<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Drive\GoogleDriveSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGoogleDriveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly ?string $actorId = null
    ) {}

    public function handle(GoogleDriveSyncService $service): void
    {
        $actor = $this->actorId !== null ? User::find($this->actorId) : null;

        $stats = $service->sync($actor);

        if ($actor !== null) {
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'drive.sync_manual',
                'entity_type' => 'DriveSyncFolder',
                'entity_id' => (string) $actor->id,
                'new_values' => [
                    'new' => $stats['new'],
                    'updated' => $stats['updated'],
                    'skipped' => $stats['skipped'],
                ],
            ]);
        }
    }
}
