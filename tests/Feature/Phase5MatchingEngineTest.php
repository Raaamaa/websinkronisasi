<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessEvidenceDocumentJob;
use App\Jobs\RematchActiveDocumentsJob;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Evidence\TextExtractionService;
use App\Services\Matching\MatchingEngineService;
use App\Services\Matching\TextPreprocessor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase5MatchingEngineTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        DocumentMatch::query()->forceDelete();
        EvidenceDocument::query()->forceDelete();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.matching@bps.go.id'],
            [
                'name' => 'Admin Matching Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
    }

    protected function tearDown(): void
    {
        DocumentMatch::query()->forceDelete();
        EvidenceDocument::query()->forceDelete();
        parent::tearDown();
    }

    /**
     * Helper untuk membuat master LK dan Indikator uji.
     */
    private function createSampleLkAndIndicator(string $lkCode = 'ZI', string $indicatorCode = '1.2.a', string $indicatorTitle = 'Standar Operasional Prosedur Pelayanan'): array
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => $lkCode, 'period' => 2026],
            [
                'name' => "Lembar Kerja {$lkCode} 2026",
                'category' => 'internal',
                'is_active' => true,
            ]
        );

        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => $indicatorCode],
            [
                'title' => $indicatorTitle,
                'order_index' => 1,
            ]
        );
        $indicator->update(['title' => $indicatorTitle]);

        $item = LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $indicator->id, 'order_index' => 1],
            [
                'raw_template_name' => 'SOP Pelayanan tahun [t]',
                'resolved_name' => 'Standar Operasional Prosedur Pelayanan tahun 2026',
            ]
        );
        $item->update([
            'raw_template_name' => 'SOP Pelayanan tahun [t]',
            'resolved_name' => 'Standar Operasional Prosedur Pelayanan tahun 2026',
        ]);

        return [$lk, $indicator, $item];
    }

    /**
     * 1. TSK-501: Preprocessor normalizes text and expands synonyms.
     */
    public function test_text_preprocessor_normalizes_text_and_expands_synonyms(): void
    {
        $preprocessor = new TextPreprocessor;

        $input = 'SK & SOP Tim Monev Renja BPS 2026!';
        $normalized = $preprocessor->normalize($input);

        // Harus lowercase, simbol dibersihkan, singkatan di-ekspansi
        $this->assertStringContainsString('surat keputusan', $normalized);
        $this->assertStringContainsString('standar operasional prosedur', $normalized);
        $this->assertStringContainsString('monitoring evaluasi', $normalized);
        $this->assertStringContainsString('rencana kerja', $normalized);

        // Stopwords filtering
        $textWithStopwords = 'surat keputusan tentang standar pelayanan pada satker';
        $filtered = $preprocessor->removeStopwords($textWithStopwords);
        $this->assertStringNotContainsString('tentang', $filtered);
        $this->assertStringNotContainsString('pada', $filtered);
    }

    /**
     * 2. TSK-502, MATCH-03: Deterministic exact_code matches code in filename (Score: 100.0, confirmed).
     */
    public function test_deterministic_rule_matches_exact_code_in_filename_as_confirmed(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('ZI', '1.2.a');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'SOP Layanan Publik Kantor',
            'original_filename' => '1.2.a SOP Layanan.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test1.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_test_exact_code_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $engine = app(MatchingEngineService::class);
        $matches = $engine->matchDocument($document);

        $this->assertCount(1, $matches);
        $match = $matches->first();

        $this->assertSame($document->id, $match->document_id);
        $this->assertSame($indicator->id, $match->indicator_id);
        $this->assertSame('confirmed', $match->status);
        $this->assertSame('exact_code', $match->match_method);
        $this->assertEquals(100.0, $match->confidence_score);
        $this->assertSame('1.2.a', $match->match_reasons['matched_code'] ?? null);
    }

    /**
     * 3. TSK-502, MATCH-03: Deterministic exact_number matches document_number (Score: 100.0, confirmed).
     */
    public function test_deterministic_rule_matches_exact_document_number_as_confirmed(): void
    {
        $docNumber = '01/BPS/RB/2026';
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('ZI', '2.1.b', "SK Tim Reformasi Nomor {$docNumber}");

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'SK Tim RB Kantor',
            'document_number' => $docNumber,
            'original_filename' => 'sk_tim_rb.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test2.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_test_exact_number_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $engine = app(MatchingEngineService::class);
        $matches = $engine->matchDocument($document);

        $this->assertCount(1, $matches);
        $match = $matches->first();

        $this->assertSame('confirmed', $match->status);
        $this->assertSame('exact_number', $match->match_method);
        $this->assertEquals(100.0, $match->confidence_score);
    }

    /**
     * 4. TSK-503: Heuristic rule computes similarity with year difference penalty.
     */
    public function test_heuristic_rule_computes_title_and_content_similarity_with_year_penalty(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('PEKPPP', 'F01', 'Standar Pelayanan Publik');
        // Item resolved name adalah tahun 2026
        $item->update(['resolved_name' => 'Standar Pelayanan Publik BPS Tahun 2026']);

        // Dokumen dengan tahun berbeda (2024), harus kena penalti -20 poin
        $documentDiffYear = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Standar Pelayanan Publik BPS',
            'original_filename' => 'sp_bps.pdf',
            'year' => 2024,
            'file_path' => 'evidence/test3a.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_diff_year_'.uniqid()),
            'extracted_text' => 'Dokumen standar pelayanan publik pada kantor BPS',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        // Dokumen dengan tahun cocok (2026)
        $documentSameYear = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Standar Pelayanan Publik BPS',
            'original_filename' => 'sp_bps_2026.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test3b.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_same_year_'.uniqid()),
            'extracted_text' => 'Dokumen standar pelayanan publik pada kantor BPS tahun 2026',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $engine = app(MatchingEngineService::class);

        $matchesSame = $engine->matchDocument($documentSameYear);
        $matchesDiff = $engine->matchDocument($documentDiffYear);

        $scoreSame = $matchesSame->first()?->confidence_score ?? 0.0;
        $scoreDiff = $matchesDiff->first()?->confidence_score ?? 0.0;

        $this->assertGreaterThan($scoreDiff, $scoreSame, 'Skor tahun yang sama harus lebih tinggi dibanding tahun yang berbeda');
    }

    /**
     * 5. TSK-504, AC-10: Matching score above threshold creates 'auto' match with reasons.
     */
    public function test_matching_score_above_threshold_creates_auto_match_with_reasons(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('PEKPPP', 'F02', 'Laporan Kepuasan Masyarakat');
        $item->update(['resolved_name' => 'Laporan Hasil Survei Kepuasan Masyarakat Tahun 2026']);

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Laporan Hasil Survei Kepuasan Masyarakat',
            'original_filename' => 'laporan_skm_2026.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test4.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_skm_'.uniqid()),
            'extracted_text' => 'Laporan hasil survei kepuasan masyarakat terhadap standar pelayanan tahun 2026',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $engine = app(MatchingEngineService::class);
        $matches = $engine->matchDocument($document);

        $this->assertCount(1, $matches);
        $match = $matches->first();

        // AC-10: Berstatus auto, skor >= 75.0, memiliki alasan pencocokan
        $this->assertSame('auto', $match->status);
        $this->assertGreaterThanOrEqual(75.0, $match->confidence_score);
        $this->assertNotEmpty($match->match_reasons);
        $this->assertArrayHasKey('score_components', $match->match_reasons);
    }

    /**
     * 6. TSK-504, AC-09: Matching score below threshold does not create match.
     */
    public function test_matching_score_below_threshold_does_not_create_match(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('PEKPPP', 'F03', 'Analisis Jabatan Pegawai');
        $item->update(['resolved_name' => 'Dokumen Analisis Beban Kerja dan Jabatan']);

        // Dokumen yang sama sekali tidak berhubungan
        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Daftar Menu Makanan Kantin Kantor',
            'original_filename' => 'menu_kantin.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test5.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_menu_'.uniqid()),
            'extracted_text' => 'Daftar makanan catering prasmanan rapat kantor',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $engine = app(MatchingEngineService::class);
        $matches = $engine->matchDocument($document);

        // AC-09: Tidak boleh ada match yang terbentuk
        $this->assertCount(0, $matches);
        $this->assertDatabaseMissing('document_matches', [
            'document_id' => $document->id,
        ]);
    }

    /**
     * 7. TSK-505, MATCH-06, AC-11: Rejected match is never overwritten by rematching worker.
     */
    public function test_rejected_match_is_never_overwritten_by_rematching_worker(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('ZI', '3.1.a', 'Standar Operasional Prosedur');
        $item->update(['resolved_name' => 'SOP Layanan']);

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'SOP Layanan',
            'original_filename' => 'sop_layanan.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test6.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_reject_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        // 1. Simpan match awal
        $engine = app(MatchingEngineService::class);
        $matches = $engine->matchDocument($document);
        $this->assertNotEmpty($matches);

        // 2. Admin menolak match untuk indikator terkait (status = 'rejected')
        $targetMatch = DocumentMatch::where('document_id', $document->id)
            ->where('indicator_id', $indicator->id)
            ->firstOrFail();

        $targetMatch->update([
            'status' => 'rejected',
            'verified_by' => $this->admin->id,
            'verified_at' => now(),
        ]);

        $this->assertDatabaseHas('document_matches', [
            'document_id' => $document->id,
            'indicator_id' => $indicator->id,
            'status' => 'rejected',
        ]);

        // 3. Rematch dijalankan ulang untuk dokumen ini
        $rematched = $engine->matchDocument($document);

        // MATCH-06, AC-11: Status HARUS TETAP 'rejected', dilarang tertimpa menjadi 'auto'
        $freshMatch = DocumentMatch::where('document_id', $document->id)
            ->where('indicator_id', $indicator->id)
            ->first();

        $this->assertNotNull($freshMatch);
        $this->assertSame('rejected', $freshMatch->status, 'Match berstatus rejected tidak boleh ditimpa oleh rematch otomatis');
    }

    /**
     * 8. TSK-505: Confirmed match is never demoted to auto by rematching.
     */
    public function test_confirmed_match_is_never_demoted_to_auto_by_rematching(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('ZI', '3.2.b', 'Laporan Kinerja');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Laporan Kinerja Instansi',
            'original_filename' => 'lakip.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test7.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_confirmed_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        // Simpan match sebagai confirmed
        $match = DocumentMatch::create([
            'document_id' => $document->id,
            'indicator_id' => $indicator->id,
            'matched_item_id' => $item->id,
            'status' => 'confirmed',
            'match_method' => 'manual',
            'confidence_score' => 100.0,
            'match_reasons' => ['source' => 'manual_admin'],
            'verified_by' => $this->admin->id,
            'verified_at' => now(),
        ]);

        // Rematch otomatis dijalankan
        $engine = app(MatchingEngineService::class);
        $engine->matchDocument($document);

        $freshMatch = $match->fresh();
        $this->assertSame('confirmed', $freshMatch->status, 'Match status confirmed tidak boleh diturunkan derajatnya menjadi auto');
    }

    /**
     * 9. TSK-506, MATCH-07, AC-27: RematchActiveDocumentsJob matches all active documents against LK.
     */
    public function test_rematch_active_documents_job_matches_all_eligible_documents(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('KIP', '1.1', 'SK Pembentukan PPID');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'SK Pembentukan PPID Tahun 2026',
            'original_filename' => '1.1 SK PPID.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test8.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_rematch_job_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $this->assertSame(0, DocumentMatch::where('document_id', $document->id)->count());

        // Jalankan Job Rematch
        $job = new RematchActiveDocumentsJob($lk->id);
        $job->handle(app(MatchingEngineService::class));

        // Dokumen harus cocok dengan indikator 1.1 KIP
        $this->assertDatabaseHas('document_matches', [
            'document_id' => $document->id,
            'indicator_id' => $indicator->id,
            'status' => 'confirmed',
        ]);
    }

    /**
     * 10. MATCH-01: ProcessEvidenceDocumentJob triggers matching engine automatically upon completion.
     */
    public function test_process_evidence_document_job_triggers_matching_pipeline_automatically(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkAndIndicator('SAKIP', 'A.1', 'Rencana Strategis');

        $uuid = (string) Str::uuid();
        $filePath = "evidence/{$uuid}.pdf";
        Storage::disk('private')->put($filePath, 'dummy content');

        $document = EvidenceDocument::create([
            'id' => $uuid,
            'document_name' => 'Rencana Strategis BPS 2026',
            'original_filename' => 'A.1 Renstra.pdf',
            'year' => 2026,
            'file_path' => $filePath,
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_job_trigger_'.uniqid()),
            'processing_status' => 'memproses',
            'uploaded_by' => $this->admin->id,
        ]);

        $job = new ProcessEvidenceDocumentJob($document->id);
        $job->handle(app(TextExtractionService::class));

        // Harus otomatis dipicu matching dan terbentuk relasi match
        $this->assertDatabaseHas('document_matches', [
            'document_id' => $document->id,
            'indicator_id' => $indicator->id,
            'status' => 'confirmed',
        ]);
    }

    /**
     * 11. AC-08: Document matching against multiple LKs creates multiple matches.
     */
    public function test_document_matching_against_multiple_lks_creates_multiple_matches(): void
    {
        // LK 1: ZI
        [$lk1, $ind1, $item1] = $this->createSampleLkAndIndicator('ZI', '5.1', 'Standar Pelayanan Terpadu Satu Pintu');
        $item1->update(['resolved_name' => 'Standar Pelayanan Terpadu Satu Pintu BPS Tahun 2026']);

        // LK 2: PEKPPP
        [$lk2, $ind2, $item2] = $this->createSampleLkAndIndicator('PEKPPP', 'P01', 'Standar Pelayanan Terpadu Satu Pintu');
        $item2->update(['resolved_name' => 'Standar Pelayanan Terpadu Satu Pintu BPS Tahun 2026']);

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Standar Pelayanan Terpadu Satu Pintu BPS Tahun 2026',
            'original_filename' => 'sp_terpadu_2026.pdf',
            'year' => 2026,
            'file_path' => 'evidence/test10.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_multi_lk_'.uniqid()),
            'extracted_text' => 'Standar pelayanan terpadu satu pintu BPS tahun 2026',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $engine = app(MatchingEngineService::class);
        $matches = $engine->matchDocument($document);

        // AC-08: Dokumen cocok dengan LK 1 dan LK 2
        $this->assertGreaterThanOrEqual(2, $matches->count());
        $matchedIndicatorIds = $matches->pluck('indicator_id')->toArray();

        $this->assertContains($ind1->id, $matchedIndicatorIds);
        $this->assertContains($ind2->id, $matchedIndicatorIds);
    }
}
