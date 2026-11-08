<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
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

        $exitCode = Artisan::call('websinkronisasi:cleanup-test-data', [
            '--force' => true,
            '--no-interaction' => true,
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('SUKSES', $output);
        $this->assertNull(LembarKerja::find($testLk->id));
        $this->assertNull(LkIndicator::find($indicator->id));
        $this->assertNull(LkEvidenceItem::find($item->id));
    }
}
