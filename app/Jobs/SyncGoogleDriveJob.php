<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Drive\GoogleDriveSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Throwable;

class SyncGoogleDriveJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    public int $uniqueFor = 900;

    public function __construct(
        public readonly ?string $actorId = null
    ) {}

    public function uniqueId(): string
    {
        return 'sync_google_drive';
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('sync_google_drive'))->dontRelease()];
    }

    public function handle(GoogleDriveSyncService $service): void
    {
        $actor = $this->actorId !== null ? User::find($this->actorId) : null;

        try {
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
                        'failed' => $stats['failed'] ?? 0,
                        'failed_folders' => $stats['failed_folders'] ?? [],
                    ],
                ]);
            }
        } catch (Throwable $e) {
            $apiKey = (string) Config::get('evidence.drive_api_key', '');
            $cleanMsg = $apiKey !== '' ? str_replace($apiKey, '[REDACTED]', $e->getMessage()) : $e->getMessage();
            $cleanMsg = preg_replace('/([?&]key=)[^&]+/', '$1[REDACTED]', $cleanMsg) ?? 'Gagal Drive Sync';

            if ($actor !== null) {
                AuditLog::create([
                    'user_id' => $actor->id,
                    'action' => 'drive.sync_failed',
                    'entity_type' => 'DriveSyncFolder',
                    'entity_id' => (string) $actor->id,
                    'new_values' => [
                        'error' => $cleanMsg,
                    ],
                ]);
            }

            throw $e;
        }
    }
}
