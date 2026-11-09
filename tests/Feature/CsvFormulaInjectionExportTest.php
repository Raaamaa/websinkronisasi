<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\MonitorMatrixPage;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CsvFormulaInjectionExportTest extends TestCase
{
    protected string $docId;

    protected function tearDown(): void
    {
        if (isset($this->docId)) {
            EvidenceDocument::where('id', $this->docId)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_csv_export_sanitizes_spreadsheet_formula_injection_characters(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin.csvexport@bps.go.id'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Admin CSV Export Test',
                'role' => 'admin',
                'is_active' => true,
                'password' => bcrypt('password'),
            ]
        );

        LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'name' => 'Lembar Kerja ZI 2026',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        $doc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => "=cmd|'/C calc'!A0",
            'document_number' => '+628123456789',
            'year' => 2026,
            'file_path' => 'evidence/test_injection.pdf',
            'original_filename' => 'injection.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_formula_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $admin->id,
        ]);
        $this->docId = $doc->id;

        $this->actingAs($admin);

        $response = Livewire::test(MonitorMatrixPage::class)
            ->set('period', 2026)
            ->call('exportMatrix');

        $streamedResponse = $response->instance()->exportMatrix();

        ob_start();
        $streamedResponse->sendContent();
        $csvContent = ob_get_clean();

        $this->assertNotEmpty($csvContent);
        // Formula injection '=' wajib di-escape dengan tanda kutip tunggal "'"
        $this->assertStringContainsString("'=cmd|'/C calc'!A0", $csvContent);
        $this->assertStringContainsString("'+628123456789", $csvContent);
    }
}
