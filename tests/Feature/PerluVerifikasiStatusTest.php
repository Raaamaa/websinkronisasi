<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Matching\Rules\HeuristicMatchingRule;
use App\Services\Matching\Similarity\TrigramSimilarity;
use App\Services\Matching\TextPreprocessor;
use App\Services\Monitor\MonitorMatrixService;
use Illuminate\Support\Str;
use Tests\TestCase;

class PerluVerifikasiStatusTest extends TestCase
{
    protected HeuristicMatchingRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new HeuristicMatchingRule(
            new TextPreprocessor,
            new TrigramSimilarity
        );
    }

    public function test_score_between_sixty_and_seventy_four_produces_perlu_verifikasi_status(): void
    {
        $lk = LembarKerja::where('is_active', true)->first();

        // Kasus 4 dari U1: skor kemiripan 69.70% (antara 60% dan 74.9%)
        $doc = new EvidenceDocument([
            'document_name' => 'SK Penetapan Pengelola Informasi dan Dokumentasi',
            'year' => $lk->period,
        ]);
        $doc->id = (string) Str::uuid();

        $ind = new LkIndicator([
            'code' => 'KIP-TEST',
            'title' => 'Penetapan Struktur PPID dan Penetapan Daftar Informasi Publik',
            'order_index' => 1,
        ]);
        $ind->id = (string) Str::uuid();
        $ind->setRelation('lembarKerja', $lk);
        $ind->setRelation('evidenceItems', collect());

        $candidate = $this->rule->evaluate($doc, $ind);

        $this->assertNotNull($candidate);
        $this->assertGreaterThanOrEqual(60.0, $candidate->confidenceScore);
        $this->assertLessThan(75.0, $candidate->confidenceScore);
        $this->assertSame('perlu_verifikasi', $candidate->status);
    }

    public function test_perlu_verifikasi_status_does_not_render_checkmark_on_monitor_matrix(): void
    {
        $lk = LembarKerja::where('is_active', true)->first();
        $indicator = LkIndicator::where('lk_id', $lk->id)->first();

        $user = User::where('role', 'super_admin')->first();

        $doc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'original_filename' => 'dok_verif_test.pdf',
            'document_name' => 'Dokumen Uji Antrean Perlu Verifikasi',
            'file_hash' => hash('sha256', Str::random(20)),
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_path' => 'private/documents/'.Str::uuid().'.pdf',
            'uploaded_by' => $user->id,
            'processing_status' => 'selesai',
            'year' => $lk->period,
        ]);

        // Simpan match dengan status perlu_verifikasi
        $match = DocumentMatch::create([
            'id' => (string) Str::uuid(),
            'document_id' => $doc->id,
            'indicator_id' => $indicator->id,
            'status' => 'perlu_verifikasi',
            'match_method' => 'title_similarity',
            'confidence_score' => 68.5,
            'match_reasons' => ['test' => true],
        ]);

        $monitorService = app(MonitorMatrixService::class);
        $matrixData = $monitorService->getMatrixData([
            'period' => (string) $lk->period,
            'search' => 'Dokumen Uji Antrean Perlu Verifikasi',
        ]);

        // Cari baris dokumen yang baru dibuat di matriks
        $docRow = collect($matrixData['rows'])->firstWhere('document_id', $doc->id);
        $this->assertNotNull($docRow);

        // Sel untuk Lembar Kerja ini wajib kosong murni tanpa centang
        $lkCode = $lk->code;
        $cell = $docRow['cells'][$lkCode] ?? null;
        $this->assertNotNull($cell);
        $this->assertFalse($cell['has_match'], 'Status perlu_verifikasi dilarang memunculkan centang di monitor (Invarian #1).');
        $this->assertSame('', $cell['symbol'], 'Sel tanpa auto/confirmed wajib kosong murni (Invarian #1 & AC-09).');

        // Bersihkan data uji
        $match->delete();
        $doc->forceDelete();
    }

    public function test_review_threshold_setting_controls_perlu_verifikasi_floor(): void
    {
        $lk = LembarKerja::where('is_active', true)->first();

        // Dokumen dan indikator yang menghasilkan skor ~69.70%
        $doc = new EvidenceDocument([
            'document_name' => 'SK Penetapan Pengelola Informasi dan Dokumentasi',
            'year' => $lk->period,
        ]);
        $doc->id = (string) Str::uuid();

        $ind = new LkIndicator([
            'code' => 'KIP-TEST-REV',
            'title' => 'Penetapan Struktur PPID dan Penetapan Daftar Informasi Publik',
            'order_index' => 1,
        ]);
        $ind->id = (string) Str::uuid();
        $ind->setRelation('lembarKerja', $lk);
        $ind->setRelation('evidenceItems', collect());

        // Naikkan review_threshold ke 72.0 (di atas skor 69.7%)
        SystemSetting::updateOrCreate(
            ['key' => 'matching.review_threshold'],
            ['value' => 72.0, 'description' => 'Ambang review test']
        );

        $candidate = $this->rule->evaluate($doc, $ind);
        // Dengan review_threshold = 72.0, skor 69.7% berada di bawah threshold -> candidate null
        $this->assertNull($candidate, 'Kandidat di bawah matching.review_threshold wajib null');

        // Kembalikan review_threshold ke 60.0 -> kandidat diterima sebagai perlu_verifikasi
        SystemSetting::updateOrCreate(
            ['key' => 'matching.review_threshold'],
            ['value' => 60.0, 'description' => 'Ambang review test']
        );

        $candidate = $this->rule->evaluate($doc, $ind);
        $this->assertNotNull($candidate);
        $this->assertSame('perlu_verifikasi', $candidate->status);
    }
}
