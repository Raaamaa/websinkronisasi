<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDevTestLembarKerjaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websinkronisasi:cleanup-test-data {--force : Eksekusi pembersihan nyata} {--dry-run : Simulasi tanpa modifikasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan instrumen LK artefak, indikator residu ZI 2026, dan user dummy hasil uji coba otomatis terdahulu';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = ! $this->option('force') || (bool) $this->option('dry-run');

        if (! $isDryRun && ! $this->input->getOption('no-interaction')) {
            if (! $this->confirm('Apakah Anda yakin ingin mengeksekusi pembersihan nyata data uji coba dev DB? Tindakan ini tidak dapat dibatalkan.', false)) {
                $this->warn('Pembersihan dibatalkan oleh pengguna.');

                return 1;
            }
        }

        // 1. Kategori LK Artefak Eksplisit (AUDIT-*, DEL-*, SOFT_DELETE_TEST, ZI_IDEMPOTENT_TEST)
        $artifactLks = LembarKerja::withTrashed()
            ->where(function ($q): void {
                $q->where('code', 'LIKE', 'AUDIT-%')
                    ->orWhere('code', 'LIKE', 'DEL-%')
                    ->orWhereIn('code', ['SOFT_DELETE_TEST', 'ZI_IDEMPOTENT_TEST']);
            })
            ->get();

        $artifactLkCount = $artifactLks->count();
        $artifactIndCount = 0;
        $artifactLogCount = 0;

        foreach ($artifactLks as $lk) {
            $artifactIndCount += $lk->indicators()->count();
            $artifactLogCount += $lk->importLogs()->count();
        }

        $this->info("1. KATEGORI LK ARTEFAK: Ditemukan {$artifactLkCount} baris LK, {$artifactIndCount} indikator child, {$artifactLogCount} log impor child.");
        foreach ($artifactLks as $lk) {
            $this->line("   - [{$lk->id}] {$lk->code} ({$lk->period}): {$lk->name} (indicators: {$lk->indicators()->count()}, logs: {$lk->importLogs()->count()})");
        }

        // 2. Kategori 13 Indikator Residu ZI 2026 (LK ZI 2026 dan log impornya TETAP DIPERTAHANKAN)
        $targetResidualCodes = [
            '1.1', '4.1', '4.2',
            'ZI-PHASE7-SEARCH', 'ZI-PHASE7-DYNAMIC',
            'ZI-AC08', 'ZI-AC10', 'ZI-AC11', 'ZI-AC12', 'ZI-AC16', 'ZI-AC27',
            'IDEM.AC23.1', 'A11i',
        ];

        $zi2026Lk = LembarKerja::where('code', 'ZI')->where('period', '2026')->first();
        $residualIndicators = collect();
        if ($zi2026Lk !== null) {
            $residualIndicators = LkIndicator::where('lk_id', $zi2026Lk->id)
                ->whereIn('code', $targetResidualCodes)
                ->get();
        }

        $residualItemsCount = 0;
        $residualMatchesCount = 0;
        foreach ($residualIndicators as $ind) {
            $residualItemsCount += $ind->evidenceItems()->count();
            $residualMatchesCount += $ind->matches()->count();
        }

        $this->info("2. KATEGORI INDIKATOR RESIDU ZI 2026: Ditemukan {$residualIndicators->count()} indikator residu, {$residualItemsCount} evidence items child, {$residualMatchesCount} matches child (LK ZI 2026 & log impor dipertahankan).");
        foreach ($residualIndicators as $ind) {
            $this->line("   - [{$ind->id}] {$ind->code}: {$ind->title} (items: {$ind->evidenceItems()->count()}, matches: {$ind->matches()->count()})");
        }

        // 3. Kategori User Dummy @example.* Yatim vs Terikat Audit
        $orphanExampleUsers = User::query()
            ->where('email', 'LIKE', '%@example.%')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('audit_logs')
                    ->whereColumn('audit_logs.user_id', 'users.id');
            })
            ->get();

        $auditLinkedExampleUsers = User::query()
            ->where('email', 'LIKE', '%@example.%')
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('audit_logs')
                    ->whereColumn('audit_logs.user_id', 'users.id');
            })
            ->get();

        $this->info("3. KATEGORI USER @EXAMPLE.*: Ditemukan {$orphanExampleUsers->count()} user yatim (dihapus permanen), {$auditLinkedExampleUsers->count()} user terikat audit_logs (dinonaktifkan is_active=false demi Invarian #5).");

        if (! $isDryRun) {
            LembarKerja::withoutEvents(function () use ($artifactLks, $residualIndicators, $orphanExampleUsers, $auditLinkedExampleUsers): void {
                DB::transaction(function () use ($artifactLks, $residualIndicators, $orphanExampleUsers, $auditLinkedExampleUsers): void {
                    // Eksekusi pembersihan 1: LK Artefak
                    foreach ($artifactLks as $lk) {
                        $lk->indicators()->each(function ($ind): void {
                            $ind->evidenceItems()->forceDelete();
                            $ind->matches()->forceDelete();
                            $ind->forceDelete();
                        });
                        $lk->importLogs()->delete();
                        $lk->forceDelete();
                    }

                    // Eksekusi pembersihan 2: 13 Indikator Residu ZI 2026 (tanpa menghapus LK ZI 2026)
                    foreach ($residualIndicators as $ind) {
                        $ind->evidenceItems()->forceDelete();
                        $ind->matches()->forceDelete();
                        $ind->forceDelete();
                    }

                    // Eksekusi pembersihan 3: User yatim
                    foreach ($orphanExampleUsers as $u) {
                        $u->forceDelete();
                    }

                    // Eksekusi pembersihan 4: Nonaktifkan user terikat audit_logs
                    User::query()
                        ->whereIn('id', $auditLinkedExampleUsers->pluck('id'))
                        ->update(['is_active' => false]);
                });
            });
        }

        if ($isDryRun) {
            $this->warn('SIMULASI SELESAI: Tidak ada data yang dimutasi. Jalankan dengan --force untuk eksekusi nyata.');
        } else {
            $this->info('SUKSES: Seluruh artefak dev DB berhasil dibersihkan.');
        }

        return 0;
    }
}
