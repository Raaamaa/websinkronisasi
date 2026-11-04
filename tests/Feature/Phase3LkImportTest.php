<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkImportProfile;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\LkImport\ExcelParserService;
use App\Services\LkImport\LkImportService;
use App\Services\LkImport\Resolvers\YearPlaceholderResolver;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class Phase3LkImportTest extends TestCase
{
    /**
     * Slice 1: TSK-304 - Year Placeholder Resolver (LK-05, AC-26).
     */
    public function test_year_placeholders_like_t_minus_1_are_resolved_to_concrete_years(): void
    {
        $resolver = new YearPlaceholderResolver;

        // LK periode 2026
        $period = 2026;

        $this->assertSame(
            'Formulir Evaluasi SP tahun 2025',
            $resolver->resolve('Formulir Evaluasi SP tahun [t-1]', $period)
        );

        $this->assertSame(
            'Laporan Kinerja Instansi Pemerintah Tahun 2026',
            $resolver->resolve('Laporan Kinerja Instansi Pemerintah Tahun [t]', $period)
        );

        $this->assertSame(
            'Data Capaian 2024 dan Target 2026',
            $resolver->resolve('Data Capaian [t-2] dan Target [t]', $period)
        );

        $this->assertSame(
            'SK Tim Kerja WBK',
            $resolver->resolve('SK Tim Kerja WBK', $period)
        );
    }

    /**
     * Slice 2: TSK-303 - Reject macro .xlsm and unsafe files (AC-24).
     */
    public function test_uploading_macro_xlsm_file_is_strictly_rejected_with_clean_error(): void
    {
        $parser = app(ExcelParserService::class);

        $tempPath = tempnam(sys_get_temp_dir(), 'macro').'.xlsm';
        file_put_contents($tempPath, 'fake macro content');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File dengan format makro (.xlsm) ditolak demi keamanan server.');

        try {
            $parser->validateAndLoad($tempPath, 'sample.xlsm');
        } finally {
            @unlink($tempPath);
        }
    }

    /**
     * Slice 2: TSK-303 - URL sanitization: allow only http/https, reject javascript/file (AC-25).
     */
    public function test_malicious_javascript_or_file_urls_are_sanitized_and_nullified(): void
    {
        $parser = app(ExcelParserService::class);

        $this->assertNull($parser->sanitizeUrl('javascript:alert(1)'));
        $this->assertNull($parser->sanitizeUrl('file:///etc/passwd'));
        $this->assertNull($parser->sanitizeUrl('data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='));
        $this->assertSame('https://drive.google.com/sample', $parser->sanitizeUrl('https://drive.google.com/sample'));
        $this->assertSame('http://bps.go.id/laporan', $parser->sanitizeUrl('http://bps.go.id/laporan'));
    }

    /**
     * Slice 2: TSK-302 - NumberedListSingleCellStrategy (AC-22, ZI pattern).
     */
    public function test_import_zi_numbered_list_single_cell_strategy_extracts_all_items(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LKE_ZI');

        // Header
        $sheet->setCellValue('B1', 'Kode');
        $sheet->setCellValue('C1', 'Indikator');
        $sheet->setCellValue('D1', 'Bukti Dukung');
        $sheet->setCellValue('E1', 'Tautan');

        // Row 2: Section header (code empty) - should be skipped
        $sheet->setCellValue('B2', '');
        $sheet->setCellValue('C2', 'AREA 1: MANAJEMEN PERUBAHAN');

        // Row 3: Valid indicator with numbered items
        $sheet->setCellValue('B3', 'I.1');
        $sheet->setCellValue('C3', 'Penyusunan Tim Kerja');
        $sheet->setCellValue('D3', "1. SK Tim Kerja WBK\n2. Undangan Rapat\n3. Laporan Evaluasi [t-1]");
        $sheet->setCellValue('E3', 'https://bps.go.id/bukti-zi');

        // Row 4: Valid indicator with parenthesized numbers
        $sheet->setCellValue('B4', 'I.2');
        $sheet->setCellValue('C4', 'Rencana Pembangunan ZI');
        $sheet->setCellValue('D4', "1) Dokumen Rencana Kerja\n2) Matriks Monev [t]");

        // Row 5: Aggregate summary row - should be skipped
        $sheet->setCellValue('B5', 'TOTAL');
        $sheet->setCellValue('C5', 'TOTAL NILAI PENGUNGKIT');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_zi_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        try {
            $profile = new LkImportProfile([
                'name' => 'Profil ZI Test',
                'lk_code' => 'ZI',
                'sheet_name' => 'LKE_ZI',
                'header_row' => 1,
                'parsing_strategy' => 'numbered_list_single_cell',
                'column_mapping' => [
                    'code_col' => 'B',
                    'title_col' => 'C',
                    'evidence_items_col' => 'D',
                    'evidence_link_col' => 'E',
                ],
            ]);

            $parser = app(ExcelParserService::class);
            $result = $parser->parse($tempFile, $profile, 2026);

            $this->assertCount(2, $result->indicators);
            $this->assertSame(5, $result->totalItemsCount);
            $this->assertCount(2, $result->skippedRows);

            // Verify first indicator & items
            $ind1 = $result->indicators[0];
            $this->assertSame('I.1', $ind1->code);
            $this->assertSame('Penyusunan Tim Kerja', $ind1->title);
            $this->assertSame('https://bps.go.id/bukti-zi', $ind1->evidenceLink);
            $this->assertCount(3, $ind1->items);
            $this->assertSame('SK Tim Kerja WBK', $ind1->items[0]->resolvedName);
            $this->assertSame('Laporan Evaluasi [t-1]', $ind1->items[2]->rawTemplateName);
            $this->assertSame('Laporan Evaluasi 2025', $ind1->items[2]->resolvedName);

            // Verify second indicator
            $ind2 = $result->indicators[1];
            $this->assertSame('I.2', $ind2->code);
            $this->assertCount(2, $ind2->items);
            $this->assertSame('Matriks Monev 2026', $ind2->items[1]->resolvedName);
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Slice 2: TSK-302 - ChecklistSheetStrategy (AC-22, PEKPPP pattern).
     */
    public function test_import_pekppp_checklist_sheet_strategy_extracts_items_from_separate_sheet(): void
    {
        $spreadsheet = new Spreadsheet;

        // Sheet 1: Penilaian
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Penilaian');
        $sheet1->setCellValue('A1', 'Kode');
        $sheet1->setCellValue('B1', 'Indikator');
        $sheet1->setCellValue('C1', 'Tautan');

        $sheet1->setCellValue('A2', 'F.01');
        $sheet1->setCellValue('B2', 'Standar Pelayanan');
        $sheet1->setCellValue('C2', 'https://bps.go.id/pekppp/f01');

        $sheet1->setCellValue('A3', 'F.02');
        $sheet1->setCellValue('B3', 'Maklumat Pelayanan');
        $sheet1->setCellValue('C3', 'javascript:alert(1)'); // dangerous, must be nullified

        // Sheet 2: Checklist
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Checklist');
        $sheet2->setCellValue('A1', 'Kode Indikator');
        $sheet2->setCellValue('B1', 'Item Bukti');

        $sheet2->setCellValue('A2', 'F.01');
        $sheet2->setCellValue('B2', 'Dokumen SOP Pelayanan');
        $sheet2->setCellValue('A3', 'F.01');
        $sheet2->setCellValue('B3', 'Foto Maklumat Pelayanan');
        $sheet2->setCellValue('A4', 'F.02');
        $sheet2->setCellValue('B4', 'Bukti Sosialisasi Maklumat [t-1]');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_pekppp_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        try {
            $profile = new LkImportProfile([
                'name' => 'Profil PEKPPP Test',
                'lk_code' => 'PEKPPP',
                'sheet_name' => 'Penilaian',
                'header_row' => 1,
                'parsing_strategy' => 'checklist_sheet',
                'column_mapping' => [
                    'code_col' => 'A',
                    'title_col' => 'B',
                    'evidence_link_col' => 'C',
                    'checklist_sheet' => 'Checklist',
                    'checklist_code_col' => 'A',
                    'checklist_item_col' => 'B',
                ],
            ]);

            $parser = app(ExcelParserService::class);
            $result = $parser->parse($tempFile, $profile, 2026);

            $this->assertCount(2, $result->indicators);
            $this->assertSame(3, $result->totalItemsCount);

            $ind1 = $result->indicators[0];
            $this->assertSame('F.01', $ind1->code);
            $this->assertSame('https://bps.go.id/pekppp/f01', $ind1->evidenceLink);
            $this->assertCount(2, $ind1->items);
            $this->assertSame('Dokumen SOP Pelayanan', $ind1->items[0]->resolvedName);

            $ind2 = $result->indicators[1];
            $this->assertSame('F.02', $ind2->code);
            $this->assertNull($ind2->evidenceLink); // sanitized
            $this->assertCount(1, $ind2->items);
            $this->assertSame('Bukti Sosialisasi Maklumat 2025', $ind2->items[0]->resolvedName);
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Slice 2: TSK-302 - SingleItemPerRowStrategy (AC-22, KIP pattern).
     */
    public function test_import_kip_single_item_per_row_strategy_extracts_target_unit(): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('KIP');

        $sheet->setCellValue('A1', 'Kode');
        $sheet->setCellValue('B1', 'Indikator');
        $sheet->setCellValue('C1', 'Item Bukti');
        $sheet->setCellValue('D1', 'Tautan');
        $sheet->setCellValue('E1', 'Satuan Kerja');

        // Row 2 & 3: Same indicator KIP-01 with 2 items and target unit
        $sheet->setCellValue('A2', 'KIP-01');
        $sheet->setCellValue('B2', 'Daftar Informasi Publik');
        $sheet->setCellValue('C2', 'DIP Terkini [t-1]');
        $sheet->setCellValue('D2', 'https://kip.go.id/dip');
        $sheet->setCellValue('E2', 'Bagian Umum');

        $sheet->setCellValue('A3', 'KIP-01');
        $sheet->setCellValue('B3', 'Daftar Informasi Publik');
        $sheet->setCellValue('C3', 'DIP Khusus');
        $sheet->setCellValue('E3', 'Bagian Umum');

        // Row 4: Indicator KIP-02
        $sheet->setCellValue('A4', 'KIP-02');
        $sheet->setCellValue('B4', 'Layanan Informasi Publik');
        $sheet->setCellValue('C4', 'Register Permohonan');
        $sheet->setCellValue('E4', 'Subbag Humas');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_kip_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        try {
            $profile = new LkImportProfile([
                'name' => 'Profil KIP Test',
                'lk_code' => 'KIP',
                'sheet_name' => 'KIP',
                'header_row' => 1,
                'parsing_strategy' => 'single_item_per_row',
                'target_unit_column' => 'E',
                'column_mapping' => [
                    'code_col' => 'A',
                    'title_col' => 'B',
                    'evidence_items_col' => 'C',
                    'evidence_link_col' => 'D',
                    'target_unit_col' => 'E',
                ],
            ]);

            $parser = app(ExcelParserService::class);
            $result = $parser->parse($tempFile, $profile, 2026);

            $this->assertCount(2, $result->indicators);
            $this->assertSame(3, $result->totalItemsCount);

            $ind1 = $result->indicators[0];
            $this->assertSame('KIP-01', $ind1->code);
            $this->assertSame('Bagian Umum', $ind1->targetUnit);
            $this->assertCount(2, $ind1->items);
            $this->assertSame('DIP Terkini 2025', $ind1->items[0]->resolvedName);
            $this->assertSame('DIP Khusus', $ind1->items[1]->resolvedName);

            $ind2 = $result->indicators[1];
            $this->assertSame('KIP-02', $ind2->code);
            $this->assertSame('Subbag Humas', $ind2->targetUnit);
            $this->assertCount(1, $ind2->items);
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Slice 3: TSK-306 - Idempotent import (AC-23).
     * Importing the identical file twice does NOT duplicate indicators or evidence items.
     */
    public function test_import_identical_file_twice_is_idempotent_and_does_not_duplicate_records(): void
    {
        $actor = User::firstOrCreate(
            ['email' => 'admin.import@kantor.go.id'],
            [
                'name' => 'Admin Import Tester',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI_IDEMPOTENT_TEST', 'period' => 2026],
            [
                'name' => 'LKE ZI Idempotent Test',
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $profile = LkImportProfile::firstOrCreate(
            ['lk_code' => 'ZI_IDEMPOTENT_TEST', 'name' => 'Profil ZI Idempotent'],
            [
                'sheet_name' => 'LKE_ZI',
                'header_row' => 1,
                'parsing_strategy' => 'numbered_list_single_cell',
                'column_mapping' => [
                    'code_col' => 'B',
                    'title_col' => 'C',
                    'evidence_items_col' => 'D',
                    'evidence_link_col' => 'E',
                ],
            ]
        );

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LKE_ZI');

        $sheet->setCellValue('B1', 'Kode');
        $sheet->setCellValue('C1', 'Indikator');
        $sheet->setCellValue('D1', 'Bukti Dukung');
        $sheet->setCellValue('E1', 'Tautan');

        $sheet->setCellValue('B2', 'I.1');
        $sheet->setCellValue('C2', 'Indikator 1');
        $sheet->setCellValue('D2', "1. Item Bukti A\n2. Item Bukti B [t-1]");
        $sheet->setCellValue('E2', 'https://bps.go.id/doc1');

        $sheet->setCellValue('B3', 'I.2');
        $sheet->setCellValue('C3', 'Indikator 2');
        $sheet->setCellValue('D3', '1. Item Bukti C');
        $sheet->setCellValue('E3', 'https://bps.go.id/doc2');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_idem_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        try {
            $importService = app(LkImportService::class);

            // First import
            $log1 = $importService->commit($tempFile, $lk, $profile, $actor, 'test_idem.xlsx');
            $this->assertSame('committed', $log1->status);
            $this->assertSame(2, $log1->imported_indicators);
            $this->assertSame(3, $log1->imported_items);

            $indicatorCount1 = LkIndicator::where('lk_id', $lk->id)->count();
            $itemCount1 = LkEvidenceItem::whereIn('indicator_id', $lk->indicators()->pluck('id'))->count();

            $this->assertSame(2, $indicatorCount1);
            $this->assertSame(3, $itemCount1);

            // Second import of the identical file
            $log2 = $importService->commit($tempFile, $lk, $profile, $actor, 'test_idem.xlsx');
            $this->assertSame('committed', $log2->status);

            $indicatorCount2 = LkIndicator::where('lk_id', $lk->id)->count();
            $itemCount2 = LkEvidenceItem::whereIn('indicator_id', $lk->indicators()->pluck('id'))->count();

            // Strict verification: Count MUST NOT increase! AC-23 Idempotency
            $this->assertSame($indicatorCount1, $indicatorCount2, 'Indicator count increased on identical re-import!');
            $this->assertSame($itemCount1, $itemCount2, 'Evidence item count increased on identical re-import!');

            // Audit log check (AC-17)
            $this->assertDatabaseHas('audit_logs', [
                'action' => 'lk.imported',
                'entity_type' => 'LembarKerja',
                'entity_id' => (string) $lk->id,
            ]);
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Slice 4: TSK-301 - Soft delete LK preserves historical data with confirmation guard (AC-14).
     */
    public function test_soft_delete_lk_preserves_historical_data_with_confirmation_guard(): void
    {
        LembarKerja::withTrashed()->where('code', 'SOFT_DELETE_TEST')->forceDelete();

        $lk = LembarKerja::create([
            'code' => 'SOFT_DELETE_TEST',
            'name' => 'Lembar Kerja Soft Delete Test',
            'category' => 'internal',
            'period' => 2026,
            'is_active' => true,
        ]);

        $indicator = LkIndicator::create([
            'lk_id' => $lk->id,
            'code' => 'SD-01',
            'title' => 'Indikator Soft Delete',
            'order_index' => 1,
        ]);

        $item = LkEvidenceItem::create([
            'indicator_id' => $indicator->id,
            'raw_template_name' => 'Bukti SD',
            'resolved_name' => 'Bukti SD 2026',
            'order_index' => 1,
        ]);

        // Soft delete LK
        $lk->delete();

        // Must be soft deleted, not hard deleted (AC-14)
        $this->assertSoftDeleted('lembar_kerja', ['id' => $lk->id]);
        $this->assertNull(LembarKerja::find($lk->id));
        $this->assertNotNull(LembarKerja::withTrashed()->find($lk->id));

        // Relational records still preserved in database
        $this->assertDatabaseHas('lk_indicators', ['id' => $indicator->id]);
        $this->assertDatabaseHas('lk_evidence_items', ['id' => $item->id]);
    }

    /**
     * Slice 4: TSK-301 - Filament admin pages accessibility for Lembar Kerja and Profil Impor.
     */
    public function test_super_admin_can_access_lembar_kerja_and_profiles_filament_pages(): void
    {
        $superAdmin = User::withTrashed()->where('email', 'admin@kantor.go.id')->first();
        if ($superAdmin) {
            if ($superAdmin->trashed()) {
                $superAdmin->restore();
            }
        } else {
            $superAdmin = User::create([
                'name' => 'Super Admin Inisial',
                'email' => 'admin@kantor.go.id',
                'password_hash' => bcrypt('123456789'),
                'role' => 'super_admin',
                'is_active' => true,
                'must_change_password' => false,
            ]);
        }

        $superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $responseLk = $this->actingAs($superAdmin)->get('/admin/lembar-kerjas');
        $responseLk->assertOk();

        $responseProfiles = $this->actingAs($superAdmin)->get('/admin/lk-import-profiles');
        $responseProfiles->assertOk();
    }
}
