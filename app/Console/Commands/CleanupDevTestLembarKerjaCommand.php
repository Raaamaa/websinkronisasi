<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LembarKerja;
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
    protected $description = 'Bersihkan instrumen LK, indikator residu, dan user dummy hasil uji coba otomatis terdahulu';

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

        // 1. Identifikasi LK Artefak & Duplikat ZI 2026
        $testLks = LembarKerja::withTrashed()
            ->where(function ($q): void {
                $q->where('code', 'LIKE', '%TEST%')
                    ->orWhere('name', 'LIKE', '%Testing%')
                    ->orWhere('name', 'LIKE', '%Uji Coba%')
                    ->orWhere('name', 'LIKE', '%AC14%')
                    ->orWhere('code', 'LIKE', 'AUDIT-%')
                    ->orWhere('code', 'LIKE', 'DEL-%')
                    ->orWhere(function ($zq): void {
                        $zq->where('code', 'ZI')
                            ->where('period', '2026')
                            ->where('name', 'LIKE', '%Lembar Kerja ZI 2026%');
                    });
            })
            ->get();

        $this->info("1. Ditemukan {$testLks->count()} baris Lembar Kerja uji coba.");

        $totalIndicatorsCleaned = 0;
        foreach ($testLks as $lk) {
            $indCount = $lk->indicators()->count();
            $totalIndicatorsCleaned += $indCount;
            $this->line("- [{$lk->id}] {$lk->code} ({$lk->period}): {$lk->name} ({$indCount} indikator)");

            if (! $isDryRun) {
                LembarKerja::withoutEvents(function () use ($lk): void {
                    DB::transaction(function () use ($lk): void {
                        $lk->indicators()->each(function ($ind): void {
                            $ind->evidenceItems()->forceDelete();
                            $ind->matches()->forceDelete();
                            $ind->forceDelete();
                        });
                        $lk->forceDelete();
                    });
                });
            }
        }

        // 2. Identifikasi Pengguna Dummy @example.*
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

        $this->info("2. Ditemukan {$orphanExampleUsers->count()} user @example.* yatim (dapat dihapus aman).");
        $this->info("3. Ditemukan {$auditLinkedExampleUsers->count()} user @example.* terikat audit_logs (hanya dinonaktifkan demi Invarian #5).");

        if (! $isDryRun) {
            DB::transaction(function () use ($orphanExampleUsers, $auditLinkedExampleUsers): void {
                // Hapus user yatim
                foreach ($orphanExampleUsers as $u) {
                    $u->forceDelete();
                }

                // Nonaktifkan user terikat audit_logs tanpa merusak referensi audit
                User::query()
                    ->whereIn('id', $auditLinkedExampleUsers->pluck('id'))
                    ->update(['is_active' => false]);
            });
        }

        if ($isDryRun) {
            $this->warn('SIMULASI SELESAI: Tidak ada data yang diubah. Jalankan dengan --force untuk eksekusi nyata.');
        } else {
            $this->info('SUKSES: Seluruh artefak dev DB berhasil dibersihkan.');
        }

        return 0;
    }
}
