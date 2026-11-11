<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\LkImportProfileResource\Pages\ListLkImportProfiles;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkImportLog;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\LkImport\ExcelParserService;
use App\Services\LkImport\Resolvers\YearPlaceholderResolver;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;
use ZipArchive;

class ExcelImportRealWiringTest extends TestCase
{
    protected ExcelParserService $parser;

    protected User $admin;

    protected LembarKerja $lk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ExcelParserService(new YearPlaceholderResolver);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.realwiring@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin Real Wiring',
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
        $this->admin->update(['role' => 'super_admin', 'is_active' => true]);

        $this->lk = LembarKerja::firstOrCreate(
            ['code' => 'TEST-WIRING', 'period' => 2026],
            [
                'id' => (string) Str::uuid(),
                'name' => 'LK Real Wiring Test',
                'category' => 'internal',
                'is_active' => true,
            ]
        );
    }

    protected function tearDown(): void
    {
        LkIndicator::where('lk_id', $this->lk->id)->forceDelete();
        LkImportLog::where('lk_id', $this->lk->id)->delete();
        parent::tearDown();
    }

    public function test_macro_extensions_are_strictly_rejected(): void
    {
        $dummyPath = tempnam(sys_get_temp_dir(), 'macro_test');
        file_put_contents($dummyPath, 'PK dummy zip content');

        foreach (['xlsm', 'xltm', 'xlam', 'xlsb'] as $ext) {
            $rejected = false;
            try {
                $this->parser->validateAndLoad($dummyPath, "test_file.{$ext}");
            } catch (ValidationException $e) {
                $rejected = true;
                $this->assertStringContainsString('makro', strtolower($e->getMessage()));
            }

            $this->assertTrue($rejected, "Ekstensi .{$ext} wajib ditolak.");
        }

        @unlink($dummyPath);
    }

    public function test_hidden_vba_in_xlsx_is_strictly_rejected(): void
    {
        $tempXlsx = tempnam(sys_get_temp_dir(), 'vba_xlsx').'.xlsx';

        $zip = new ZipArchive;
        $zip->open($tempXlsx, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types/>');
        $zip->addFromString('xl/vbaProject.bin', 'DUMMY VBA BINARY PAYLOAD');
        $zip->close();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('vbaProject.bin');

        try {
            $this->parser->validateAndLoad($tempXlsx, 'dokumen_tercemar_makro.xlsx');
        } finally {
            @unlink($tempXlsx);
        }
    }

    public function test_file_size_exceeding_twenty_megabytes_is_rejected(): void
    {
        $largeFile = tempnam(sys_get_temp_dir(), 'large_excel');
        // Buat file sparse atau file dengan ukuran > 20MB
        $fp = fopen($largeFile, 'w');
        fseek($fp, 21 * 1024 * 1024);
        fwrite($fp, 'A');
        fclose($fp);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('20 MB');

        try {
            $this->parser->validateAndLoad($largeFile, 'large_file.xlsx');
        } finally {
            @unlink($largeFile);
        }
    }

    public function test_unsafe_url_schemes_are_sanitized_to_null(): void
    {
        $this->assertNull($this->parser->sanitizeUrl('javascript:alert(document.cookie)'));
        $this->assertNull($this->parser->sanitizeUrl('file:///etc/passwd'));
        $this->assertNull($this->parser->sanitizeUrl('data:text/html;base64,PHNjcmlwdD4='));
        $this->assertNull($this->parser->sanitizeUrl('vbscript:msgbox(1)'));

        $this->assertSame('https://drive.google.com/drive/folders/test1234', $this->parser->sanitizeUrl('https://drive.google.com/drive/folders/test1234'));
        $this->assertSame('http://bps.go.id/docs/1', $this->parser->sanitizeUrl('http://bps.go.id/docs/1'));
    }

    public function test_livewire_wizard_execute_import_wires_to_real_import_service(): void
    {
        // 1. Buat berkas spreadsheet valid secara programatis
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LKE');

        // Header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Kode');
        $sheet->setCellValue('C1', 'Indikator');
        $sheet->setCellValue('D1', 'Bukti Dukung');
        $sheet->setCellValue('E1', 'Tautan Drive');
        $sheet->setCellValue('F1', 'PIC');

        // Baris data 1
        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'RW-01');
        $sheet->setCellValue('C2', 'Pembentukan Tim Kerja Real Wiring 2026');
        $sheet->setCellValue('D2', "1. SK Tim Kerja\n2. Notula Rapat");
        $sheet->setCellValue('E2', 'https://drive.google.com/drive/folders/realwiring');
        $sheet->setCellValue('F2', 'Bagian Umum');

        $tempPath = tempnam(sys_get_temp_dir(), 'real_wiring_test').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $this->actingAs($this->admin);

        Livewire::test(ListLkImportProfiles::class)
            ->set('selectedLkId', (string) $this->lk->id)
            ->set('uploadedFileName', 'real_wiring_test.xlsx')
            ->set('uploadedFilePath', $tempPath)
            ->set('colCode', 'B')
            ->set('colTitle', 'C')
            ->set('colEvidence', 'D')
            ->set('colDriveLink', 'E')
            ->set('colPic', 'F')
            ->set('parsingStrategy', 'numbered_list_single_cell')
            ->call('executeImport')
            ->assertSet('currentStep', 4);

        // Verifikasi bahwa indikator nyata tersimpan di basis data dari parsing berkas asli
        $indicator = LkIndicator::where('lk_id', $this->lk->id)->where('code', 'RW-01')->first();
        $this->assertNotNull($indicator);
        $this->assertSame('Pembentukan Tim Kerja Real Wiring 2026', $indicator->title);
        $this->assertSame('https://drive.google.com/drive/folders/realwiring', $indicator->evidence_link);

        // Verifikasi item bukti diekstrak dari cell multiline
        $items = LkEvidenceItem::where('indicator_id', $indicator->id)->get();
        $this->assertCount(2, $items);

        // Verifikasi import log tercatat dengan status committed
        $log = LkImportLog::where('lk_id', $this->lk->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame('committed', $log->status);

        @unlink($tempPath);
    }
}
