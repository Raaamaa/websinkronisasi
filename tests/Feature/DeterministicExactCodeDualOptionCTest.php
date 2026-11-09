<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;
use App\Services\Matching\Rules\DeterministicMatchingRule;
use App\Services\Matching\TextPreprocessor;
use App\Services\Monitor\MonitorMatrixService;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeterministicExactCodeDualOptionCTest extends TestCase
{
    protected DeterministicMatchingRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new DeterministicMatchingRule(new TextPreprocessor);
    }

    public function test_exact_code_with_matching_title_yields_confirmed_status(): void
    {
        $indicator = new LkIndicator([
            'code' => 'ZI-1.1',
            'title' => 'Pembentukan Tim Kerja Pembangunan Zona Integritas',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();
        $indicator->setRelation('evidenceItems', collect());

        $docValid = new EvidenceDocument([
            'document_name' => 'Surat Keputusan Tim ZI-1.1 WBK Tahun 2026',
            'original_filename' => 'SK_ZI-1.1.pdf',
        ]);
        $docValid->id = (string) Str::uuid();

        $candidate = $this->rule->evaluateExactCode($docValid, $indicator);

        $this->assertNotNull($candidate);
        $this->assertSame('confirmed', $candidate->status);
        $this->assertSame('exact_code', $candidate->matchMethod);
        $this->assertSame(100.0, $candidate->confidenceScore);
        $this->assertTrue($candidate->matchReasons['title_matched'] ?? false);
    }

    public function test_exact_code_with_mismatched_title_yields_perlu_verifikasi_status(): void
    {
        $indicator = new LkIndicator([
            'code' => 'ZI-1.1',
            'title' => 'Pembentukan Tim Kerja Pembangunan Zona Integritas',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();
        $indicator->setRelation('evidenceItems', collect());

        // Kode ZI-1.1 cocok tapi judul dokumen sama sekali tidak berhubungan
        $docMismatchedTitle = new EvidenceDocument([
            'document_name' => 'Laporan Keuangan BMN Kantor ZI-1.1',
            'original_filename' => 'laporan_keuangan_ZI-1.1.pdf',
        ]);
        $docMismatchedTitle->id = (string) Str::uuid();

        $candidate = $this->rule->evaluateExactCode($docMismatchedTitle, $indicator);

        $this->assertNotNull($candidate);
        $this->assertSame('perlu_verifikasi', $candidate->status);
        $this->assertSame('exact_code', $candidate->matchMethod);
        $this->assertSame(70.0, $candidate->confidenceScore);
        $this->assertFalse($candidate->matchReasons['title_matched'] ?? true);
    }

    public function test_matching_engine_persists_perlu_verifikasi_without_monitor_checkmark(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin_test_dual_c@bps.go.id'],
            [
                'name' => 'Admin Dual Option C',
                'role' => 'admin',
                'nip' => '198501012010011099',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI-DUAL', 'period' => 2026],
            [
                'name' => 'Lembar Kerja ZI Dual Option C',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'ZI-2.5'],
            [
                'title' => 'Sosialisasi Anti Gratifikasi dan Budaya Pelayanan Prima',
                'order_index' => 1,
            ]
        );

        // Dokumen dengan kode 'ZI-2.5' tetapi judul dokumen tidak cocok dengan indikator
        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Daftar Hadir Rekonsiliasi Kas Kantor ZI-2.5',
            'original_filename' => 'rekon_kas_ZI-2.5.pdf',
            'year' => 2026,
            'file_path' => 'evidence/rekon.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 2048,
            'file_hash' => hash('sha256', 'rekon_kas_ZI-2.5_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $admin->id,
        ]);

        /** @var MatchingEngineService $engine */
        $engine = app(MatchingEngineService::class);
        $matches = $engine->matchDocument($document, $lk->id);

        $this->assertCount(1, $matches);
        $match = $matches->first();
        $this->assertSame('perlu_verifikasi', $match->status);
        $this->assertSame('exact_code', $match->match_method);

        // Invarian #3: perlu_verifikasi TIDAK boleh memunculkan centang di monitor
        /** @var MonitorMatrixService $matrixService */
        $matrixService = app(MonitorMatrixService::class);
        $matrix = $matrixService->getMatrixData(['period' => 2026, 'search' => 'Daftar Hadir Rekonsiliasi Kas Kantor ZI-2.5']);

        $this->assertNotEmpty($matrix['rows']);
        $row = $matrix['rows'][0];
        $cell = $row['cells']['ZI-DUAL'] ?? null;
        $this->assertNotNull($cell);
        $this->assertFalse($cell['has_match']);
        $this->assertSame('', $cell['symbol']);
    }
}
