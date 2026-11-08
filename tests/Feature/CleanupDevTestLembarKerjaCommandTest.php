<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkImportLog;
use App\Models\LkImportProfile;
use App\Models\LkIndicator;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class CleanupDevTestLembarKerjaCommandTest extends TestCase
{
    public function test_cleanup_command_dry_run_by_default_does_not_delete_test_lks(): void
    {
        $code = 'AUDIT-TEST-'.strtoupper(Str::random(6));
        $testLk = LembarKerja::create([
            'id' => (string) Str::uuid(),
            'code' => $code,
            'period' => '2026',
            'name' => 'LK Uji Coba Testing Dry Run',
            'category' => 'internal',
            'is_active' => true,
        ]);

        $exitCode = Artisan::call('websinkronisasi:cleanup-test-data', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('SIMULASI', $output);
        $this->assertNotNull(LembarKerja::find($testLk->id), 'LK uji coba tidak boleh terhapus pada mode dry-run');

        // Cleanup fixture
        $testLk->forceDelete();
    }

    public function test_cleanup_command_with_force_deletes_test_lks_and_cascades(): void
    {
        $code = 'DEL-TEST-'.strtoupper(Str::random(6));
        $testLk = LembarKerja::create([
            'id' => (string) Str::uuid(),
            'code' => $code,
            'period' => '2026',
            'name' => 'LK Uji Coba Testing Force',
            'category' => 'internal',
            'is_active' => true,
        ]);

        $indicator = LkIndicator::create([
            'id' => (string) Str::uuid(),
            'lk_id' => $testLk->id,
            'code' => 'DEL-IND-01',
            'title' => 'Indikator Uji Coba',
            'order_index' => 1,
        ]);

        $item = LkEvidenceItem::create([
            'id' => (string) Str::uuid(),
            'indicator_id' => $indicator->id,
            'resolved_name' => 'Item Bukti Uji',
            'raw_template_name' => 'Item Bukti [t]',
            'order_index' => 1,
        ]);

        // Buat LK ZI 2026 dengan indikator residu dan log impor
        $zi2026 = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => '2026'],
            ['name' => 'Lembar Kerja ZI 2026', 'category' => 'internal', 'is_active' => true]
        );

        // Pastikan indikator residu belum ada sebelum dibuat
        LkIndicator::where('lk_id', $zi2026->id)->where('code', 'ZI-AC08')->forceDelete();

        $residualInd = LkIndicator::create([
            'id' => (string) Str::uuid(),
            'lk_id' => $zi2026->id,
            'code' => 'ZI-AC08',
            'title' => 'Indikator Residu AC08',
            'order_index' => 99,
        ]);

        $ziLog = LkImportLog::create([
            'lk_id' => $zi2026->id,
            'user_id' => User::firstOrFail()->id,
            'profile_id' => LkImportProfile::firstOrFail()->id,
            'file_name' => 'lke_zi_2026_import.xlsx',
            'file_hash' => hash('sha256', 'lke_zi_2026_import.xlsx'),
            'status' => 'committed',
        ]);

        $exitCode = Artisan::call('websinkronisasi:cleanup-test-data', [
            '--force' => true,
            '--no-interaction' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('SUKSES', $output);

        // LK artefak terhapus
        $this->assertNull(LembarKerja::find($testLk->id));
        $this->assertNull(LkIndicator::find($indicator->id));
        $this->assertNull(LkEvidenceItem::find($item->id));

        // LK ZI 2026 dan log impornya TETAP ADA
        $this->assertNotNull(LembarKerja::find($zi2026->id), 'LK ZI 2026 tidak boleh dihapus');
        $this->assertNotNull(LkImportLog::find($ziLog->id), 'Log impor LK ZI 2026 tidak boleh dihapus');

        // Indikator residu ZI-AC08 terhapus bersih
        $this->assertNull(LkIndicator::find($residualInd->id), 'Indikator residu ZI 2026 harus terhapus');
    }
}
