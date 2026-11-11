<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LkImportProfileResource\Pages\ListLkImportProfiles;
use App\Jobs\RematchActiveDocumentsJob;
use App\Models\AuditLog;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkImportLog;
use App\Models\LkImportProfile;
use App\Models\LkIndicator;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ExcelImportWizardLivewireTest extends TestCase
{
    protected User $admin;

    protected LembarKerja $lk;

    protected LkImportProfile $profile;

    protected ?string $testExcelPath = null;

    /** @var list<string> */
    protected array $createdIndicatorIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::withTrashed()->firstOrCreate(
            ['email' => 'admin.wizard.test@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin Wizard',
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
        if ($this->admin->trashed()) {
            $this->admin->restore();
        }
        $this->admin->update(['role' => 'super_admin', 'is_active' => true]);

        $this->lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Zona Integritas 2026',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $this->profile = LkImportProfile::firstOrCreate(
            ['name' => 'Profil Uji Wizard ZI'],
            [
                'id' => (string) Str::uuid(),
                'lk_code' => 'ZI',
                'sheet_name' => 'LKE',
                'header_row' => 1,
                'parsing_strategy' => 'numbered_list_single_cell',
                'column_mapping' => [
                    'code_col' => 'B',
                    'title_col' => 'C',
                    'evidence_items_col' => 'D',
                    'evidence_link_col' => 'E',
                    'pic_col' => 'F',
                ],
            ]
        );

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LKE');

        // Row 1: Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Kode');
        $sheet->setCellValue('C1', 'Indikator');
        $sheet->setCellValue('D1', 'Bukti Dukung');
        $sheet->setCellValue('E1', 'Tautan Drive');
        $sheet->setCellValue('F1', 'PIC');

        // Row 2: Indicator A11i
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'A11i');
        $sheet->setCellValue('C2', 'Pembentukan Tim Kerja Pembangunan ZI');
        $sheet->setCellValue('D2', "1. SK Tim Kerja Pembangunan ZI [t]\n2. Undangan & Notula Rapat Pembentukan");
        $sheet->setCellValue('E2', 'https://drive.google.com/drive/folders/1k8a0200KP010ZI2026BPS9xZ');
        $sheet->setCellValue('F2', 'Bagian Umum');

        // Row 3: Indicator A12i
        $sheet->setCellValue('A3', '2');
        $sheet->setCellValue('B3', 'A12i');
        $sheet->setCellValue('C3', 'Rencana Pembangunan Zona Integritas');
        $sheet->setCellValue('D3', '1. Dokumen Rencana Aksi ZI 2026');
        $sheet->setCellValue('E3', 'https://drive.google.com/drive/folders/1k8a0200KP010ZI2026BPS9xZ');
        $sheet->setCellValue('F3', 'Subbag Program');

        // Row 4: Indicator A111i
        $sheet->setCellValue('A4', '3');
        $sheet->setCellValue('B4', 'A111i');
        $sheet->setCellValue('C4', 'Penerapan Standar Operasional Prosedur (SOP)');
        $sheet->setCellValue('D4', "1. SOP Terpadu Layanan BPS [t]\n2. Laporan Evaluasi SOP Triwulan I");
        $sheet->setCellValue('E4', 'https://drive.google.com/drive/folders/1k8a0200KP010ZI2026BPS9xZ');
        $sheet->setCellValue('F4', 'Fungsi Diseminasi');

        // Row 5: Skipped Row (Empty code, summary/note)
        $sheet->setCellValue('A5', '');
        $sheet->setCellValue('B5', '');
        $sheet->setCellValue('C5', 'TOTAL BOBOT AREA MANAJEMEN PERUBAHAN: 60.00 | NILAI MAKSIMAL: 100');
        $sheet->setCellValue('D5', '');
        $sheet->setCellValue('E5', '');
        $sheet->setCellValue('F5', '');

        $this->testExcelPath = tempnam(sys_get_temp_dir(), 'test_wizard_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($this->testExcelPath);
    }

    protected function tearDown(): void
    {
        if ($this->testExcelPath && file_exists($this->testExcelPath)) {
            @unlink($this->testExcelPath);
        }

        if (! empty($this->createdIndicatorIds)) {
            LkEvidenceItem::whereIn('indicator_id', $this->createdIndicatorIds)->delete();
            LkIndicator::whereIn('id', $this->createdIndicatorIds)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_page_renders_4_step_wizard_and_validation_cards(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListLkImportProfiles::class)
            ->set('uploadedFilePath', $this->testExcelPath)
            ->set('uploadedFileName', basename($this->testExcelPath))
            ->assertSee('Wizard Impor Lembar Kerja dari File Excel')
            ->assertSee('Spesifikasi & Pemetaan Kolom', false)
            ->assertSee('Total Baris File')
            ->assertSee('5')
            ->assertSee('Indikator Valid')
            ->assertSee('3')
            ->assertSee('Baris Dilewati')
            ->assertSee('1')
            ->assertSee('Status Validasi')
            ->assertSee('0 Fatal')
            ->assertSee('A11i')
            ->assertSee('A12i')
            ->assertSee('A111i');
    }

    public function test_apply_profile_preset_updates_column_mapping(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ListLkImportProfiles::class)
            ->call('applyProfilePreset', $this->profile->id)
            ->assertSet('colCode', 'B')
            ->assertSet('colTitle', 'C')
            ->assertSet('colEvidence', 'D')
            ->assertSet('colDriveLink', 'E')
            ->assertSet('colPic', 'F')
            ->assertSet('parsingStrategy', 'numbered_list_single_cell');
    }

    public function test_execute_import_performs_idempotent_upsert_and_dispatches_rematch(): void
    {
        Queue::fake();

        $this->actingAs($this->admin);

        Livewire::test(ListLkImportProfiles::class)
            ->set('selectedLkId', (string) $this->lk->id)
            ->set('uploadedFilePath', $this->testExcelPath)
            ->set('uploadedFileName', basename($this->testExcelPath))
            ->call('executeImport')
            ->assertSet('currentStep', 4);

        // Verify indicators were created/upserted
        $indicator = LkIndicator::where('lk_id', $this->lk->id)->where('code', 'A11i')->first();
        $this->assertNotNull($indicator);
        $this->createdIndicatorIds[] = $indicator->id;

        // Verify items were created with resolved name
        $item = LkEvidenceItem::where('indicator_id', $indicator->id)->first();
        $this->assertNotNull($item);

        // Verify import log was created
        $log = LkImportLog::where('lk_id', $this->lk->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame(3, $log->imported_indicators);
        $this->assertSame(5, $log->imported_items);
        $this->assertSame(hash_file('sha256', $this->testExcelPath), $log->file_hash);

        // Verify audit log
        $this->assertTrue(
            AuditLog::where('entity_type', 'LembarKerja')
                ->where('entity_id', $this->lk->id)
                ->where('action', 'lk.imported')
                ->exists()
        );

        // Verify rematch queue was dispatched (LK-07)
        Queue::assertPushed(RematchActiveDocumentsJob::class);
    }

    public function test_import_without_file_is_rejected_and_creates_zero_rows(): void
    {
        $this->actingAs($this->admin);

        $initialIndicatorsCount = LkIndicator::where('lk_id', $this->lk->id)->count();
        $initialLogsCount = LkImportLog::where('lk_id', $this->lk->id)->count();

        Livewire::test(ListLkImportProfiles::class)
            ->set('selectedLkId', (string) $this->lk->id)
            ->set('uploadedFilePath', null)
            ->set('uploadedFileName', null)
            ->call('executeImport')
            ->assertNotified('Pilih berkas Excel dulu');

        $this->assertSame($initialIndicatorsCount, LkIndicator::where('lk_id', $this->lk->id)->count());
        $this->assertSame($initialLogsCount, LkImportLog::where('lk_id', $this->lk->id)->count());
    }
}
