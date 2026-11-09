<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\CariDokumenPage;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class CariDokumenLivewireTest extends TestCase
{
    protected User $superAdmin;

    protected User $admin;

    protected User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin_cari_dok@bps.go.id'],
            [
                'name' => 'Super Admin Cari Dokumen',
                'role' => 'super_admin',
                'nip' => '198001012005011001',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_cari_dok@bps.go.id'],
            [
                'name' => 'Admin Cari Dokumen',
                'role' => 'admin',
                'nip' => '198501012010011002',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->admin->update(['must_change_password' => false, 'is_active' => true]);

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai_cari_dok@bps.go.id'],
            [
                'name' => 'Pegawai Cari Dokumen',
                'role' => 'pegawai',
                'nip' => '199001012015011003',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->pegawai->update(['must_change_password' => false, 'is_active' => true]);
    }

    public function test_super_admin_is_blocked_from_cari_dokumen_at_server(): void
    {
        // Invarian #1: Server-side authorization check (Md/RBAC.md:83, PRD:90)
        $response = $this->actingAs($this->superAdmin)->get(CariDokumenPage::getUrl());
        $response->assertForbidden();

        $this->assertFalse(CariDokumenPage::canAccess());
    }

    public function test_admin_can_access_cari_dokumen_page(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get(CariDokumenPage::getUrl());
        $responseAdmin->assertOk();
    }

    public function test_pegawai_can_access_cari_dokumen_page(): void
    {
        $responsePegawai = $this->actingAs($this->pegawai)->get(CariDokumenPage::getUrl());
        $responsePegawai->assertOk();
    }

    public function test_inactive_user_is_blocked_from_cari_dokumen(): void
    {
        $inactivePegawai = User::firstOrCreate(
            ['email' => 'inactive_pegawai_cd@bps.go.id'],
            [
                'name' => 'Pegawai Nonaktif',
                'role' => 'pegawai',
                'nip' => '199201012018011009',
                'password_hash' => bcrypt('password'),
                'is_active' => false,
                'must_change_password' => false,
            ]
        );
        $inactivePegawai->update(['is_active' => false]);

        $response = $this->actingAs($inactivePegawai)->get(CariDokumenPage::getUrl());
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302], true));
    }

    /**
     * Helper to create a valid minimal digital PDF with readable text.
     */
    private function createDigitalPdf(string $filename, string $text): UploadedFile
    {
        $stream = 'BT /F1 12 Tf 72 712 Td ('.addcslashes($text, '()\\').') Tj ET';
        $len = strlen($stream);

        $pdf = "%PDF-1.4\n";
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
        $pdf .= "4 0 obj\n<< /Length $len >>\nstream\n$stream\nendstream\nendobj\n";
        $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        $pdf .= "0000000009 00000 n \n";
        $pdf .= "0000000058 00000 n \n";
        $pdf .= "0000000115 00000 n \n";
        $pdf .= "0000000266 00000 n \n";
        $pdf .= "0000000340 00000 n \n";
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n412\n%%EOF\n";

        return UploadedFile::fake()->createWithContent($filename, $pdf);
    }

    public function test_dry_run_evaluation_renders_results_without_saving_to_database(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'name' => 'Lembar Kerja ZI 2026',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'ZI-CARIDOK-01'],
            [
                'title' => 'Pembentukan Tim Kerja Pembangunan ZI',
                'order_index' => 1,
            ]
        );

        LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $indicator->id, 'raw_template_name' => 'SK Tim Kerja ZI'],
            [
                'resolved_name' => 'SK Tim Kerja Pembangunan ZI Tahun 2026',
                'order_index' => 1,
            ]
        );

        $file = $this->createDigitalPdf('SK_Tim_Kerja_ZI-CARIDOK-01.pdf', 'Pembentukan Tim Kerja Pembangunan ZI');

        $docCountBefore = EvidenceDocument::count();

        Livewire::actingAs($this->admin)
            ->test(CariDokumenPage::class)
            ->set('file', $file)
            ->call('evaluateDocument')
            ->assertSet('hasEvaluated', true)
            ->assertSee('ZI-CARIDOK-01')
            ->assertSee('Pembentukan Tim Kerja Pembangunan ZI')
            ->assertSee('Simpan sebagai Bukti Sah');

        // Invarian #2 & Dry-Run: DB tetap tidak bertambah
        $this->assertSame($docCountBefore, EvidenceDocument::count());
    }

    public function test_admin_and_pegawai_can_save_file_as_official_evidence(): void
    {
        $uniqueToken = uniqid();
        $file = $this->createDigitalPdf('Dokumen_Sah_Uji_Upload_'.$uniqueToken.'.pdf', 'Dokumen Pengujian Upload Sah '.$uniqueToken);

        $docCountBefore = EvidenceDocument::count();

        $component = Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('file', $file)
            ->call('evaluateDocument')
            ->call('saveAsOfficialEvidence');

        $component->assertSet('isSaved', true);

        $this->assertSame($docCountBefore + 1, EvidenceDocument::count());
    }

    public function test_user_can_set_custom_year_when_saving_official_evidence(): void
    {
        $uniqueToken = uniqid();
        $file = $this->createDigitalPdf('Dokumen_Tahun_2025_'.$uniqueToken.'.pdf', 'Dokumen Tahun 2025 '.$uniqueToken);

        $component = Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('file', $file)
            ->set('year', 2025)
            ->call('evaluateDocument')
            ->call('saveAsOfficialEvidence');

        $component->assertSet('isSaved', true);

        $savedDoc = EvidenceDocument::where('original_filename', 'Dokumen_Tahun_2025_'.$uniqueToken.'.pdf')->first();
        $this->assertNotNull($savedDoc);
        $this->assertSame(2025, (int) $savedDoc->year);
    }

    public function test_year_must_be_valid_four_digits_number(): void
    {
        $uniqueToken = uniqid();
        $file = $this->createDigitalPdf('Dokumen_Invalid_Year_'.$uniqueToken.'.pdf', 'Dokumen Invalid Year '.$uniqueToken);

        $docCountBefore = EvidenceDocument::count();

        $component = Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('file', $file)
            ->set('year', 25) // Kurang dari 4 digit
            ->call('evaluateDocument')
            ->call('saveAsOfficialEvidence');

        $component->assertHasErrors(['year']);
        $this->assertSame($docCountBefore, EvidenceDocument::count());
    }

    public function test_duplicate_upload_shows_friendly_message_without_leaking_internal_paths(): void
    {
        $uniqueToken = uniqid();
        $file = $this->createDigitalPdf('Dokumen_Duplikat_'.$uniqueToken.'.pdf', 'Dokumen Duplikat Content '.$uniqueToken);

        // Upload pertama kali agar berkas resmi terdaftar
        Livewire::actingAs($this->pegawai)
            ->test(CariDokumenPage::class)
            ->set('file', $file)
            ->call('evaluateDocument')
            ->call('saveAsOfficialEvidence')
            ->assertSet('isSaved', true);

        // Upload kedua kali dengan berkas yang sama
        $component = Livewire::actingAs($this->admin)
            ->test(CariDokumenPage::class)
            ->set('file', $file)
            ->call('evaluateDocument')
            ->call('saveAsOfficialEvidence');

        $errorMsg = (string) $component->get('errorMessage');
        $this->assertNotEmpty($errorMsg);
        $this->assertStringNotContainsString('file_path', $errorMsg);
        $this->assertStringNotContainsString('/var/www', $errorMsg);
        $this->assertStringNotContainsString('evidence/', $errorMsg);
        $this->assertStringContainsString('sudah pernah terdaftar di sistem', $errorMsg);
    }

    public function test_generic_exception_shows_friendly_message_and_logs_error(): void
    {
        $uniqueToken = uniqid();
        $file = $this->createDigitalPdf('Dokumen_Crash_'.$uniqueToken.'.pdf', 'Dokumen Crash '.$uniqueToken);

        $mockUploadService = \Mockery::mock(EvidenceUploadService::class);
        $mockUploadService->shouldReceive('upload')
            ->andThrow(new \RuntimeException('Sensitive DB connection timeout at /var/www/internal/storage/path'));
        $this->app->instance(EvidenceUploadService::class, $mockUploadService);

        Log::shouldReceive('error')
            ->once();

        $component = Livewire::actingAs($this->admin)
            ->test(CariDokumenPage::class)
            ->set('file', $file)
            ->call('evaluateDocument')
            ->call('saveAsOfficialEvidence');

        $errorMsg = (string) $component->get('errorMessage');
        $this->assertNotEmpty($errorMsg);
        $this->assertStringNotContainsString('/var/www/internal/storage/path', $errorMsg);
        $this->assertStringNotContainsString('Sensitive DB connection timeout', $errorMsg);
        $this->assertStringContainsString('Terjadi kendala saat menyimpan berkas', $errorMsg);
    }

    public function test_super_admin_cannot_initialize_livewire_component(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CariDokumenPage::class)
            ->assertForbidden();
    }

    public function test_cari_dokumen_view_has_elderly_friendly_touch_targets_and_labels(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CariDokumenPage::class)
            ->assertSeeHtml('min-h-[56px]')
            ->assertSee('Cari Dokumen & Cek Kecocokan');
    }
}
