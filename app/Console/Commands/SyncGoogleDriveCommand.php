<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Drive\GoogleDriveSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncGoogleDriveCommand extends Command
{
    protected $signature = 'evidence:sync-drive';

    protected $description = 'Sinkronisasi berkas Google Drive baca-saja ke Bukti Dukung';

    public function handle(GoogleDriveSyncService $service): int
    {
        $this->info('Memulai sinkronisasi Google Drive...');

        try {
            $stats = $service->sync();
            $this->info("Sinkronisasi selesai: {$stats['new']} baru, {$stats['updated']} diperbarui, {$stats['skipped']} dilewati.");

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Gagal sinkronisasi Google Drive: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
