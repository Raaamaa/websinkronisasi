<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Matching\DryRunMatchingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class DryRunMatchingServiceTest extends TestCase
{
    protected string $docId;

    protected function tearDown(): void
    {
        if (isset($this->docId)) {
            DocumentMatch::where('document_id', $this->docId)->forceDelete();
            EvidenceDocument::where('id', $this->docId)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_evaluate_upload_fast_path_with_existing_document_hash(): void
    {
        $user = User::where('role', 'super_admin')->firstOrFail();

        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'name' => 'Lembar Kerja ZI 2026',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'ZI-DRY-01'],
            [
                'title' => 'Indikator Utama ZI Dry Run',
                'order_index' => 1,
            ]
        );

        $item = LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $indicator->id, 'raw_template_name' => 'SK Tim Kerja Pembangunan ZI'],
            [
                'resolved_name' => 'SK Tim Kerja Pembangunan ZI 2026',
                'order_index' => 1,
            ]
        );

        $fileContent = 'Fake PDF content for fast path testing '.uniqid();
        $fileHash = hash('sha256', $fileContent);

        $doc = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'SK Tim Kerja Pembangunan ZI 2026',
            'year' => 2026,
            'file_path' => 'evidence/test_fast_path.pdf',
            'original_filename' => 'doc_fast_path.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => strlen($fileContent),
            'file_hash' => $fileHash,
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);
        $this->docId = $doc->id;

        DocumentMatch::create([
            'id' => (string) Str::uuid(),
            'document_id' => $doc->id,
            'indicator_id' => $indicator->id,
            'matched_item_id' => $item->id,
            'status' => 'confirmed',
            'confidence_score' => 100.0,
            'match_method' => 'exact_code',
        ]);

        $uploadedFile = UploadedFile::fake()->createWithContent('doc_fast_path.pdf', $fileContent);

        $docCountBefore = EvidenceDocument::count();
        $matchCountBefore = DocumentMatch::count();

        /** @var DryRunMatchingService $service */
        $service = app(DryRunMatchingService::class);
        $result = $service->evaluateUpload($uploadedFile);

        $this->assertTrue($result['matched']);
        $this->assertNotNull($result['existing_doc']);
        $this->assertSame($doc->id, $result['existing_doc']->id);
        $this->assertNotEmpty($result['results']);
        $this->assertSame('ZI-DRY-01', $result['results'][0]['indicator_code']);

        // Invarian #2: Tidak ada baris baru yang ditambahkan ke database
        $this->assertSame($docCountBefore, EvidenceDocument::count());
        $this->assertSame($matchCountBefore, DocumentMatch::count());
    }

    public function test_evaluate_upload_in_memory_simulation_for_new_document(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            [
                'name' => 'Lembar Kerja ZI 2026',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'ZI-INMEM-01'],
            [
                'title' => 'Indikator Evaluasi In-Memory',
                'order_index' => 1,
            ]
        );

        LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $indicator->id, 'raw_template_name' => 'Laporan Kinerja Bulanan BPS'],
            [
                'resolved_name' => 'Laporan Kinerja Bulanan BPS 2026',
                'order_index' => 1,
            ]
        );

        // File baru tanpa rekaman di DB
        $fileContent = 'Laporan Kinerja Bulanan BPS Tahun 2026 ZI-INMEM-01';
        $uploadedFile = UploadedFile::fake()->createWithContent('Laporan_Kinerja_Bulanan_BPS_2026.pdf', $fileContent);

        $docCountBefore = EvidenceDocument::count();
        $matchCountBefore = DocumentMatch::count();

        /** @var DryRunMatchingService $service */
        $service = app(DryRunMatchingService::class);
        $result = $service->evaluateUpload($uploadedFile);

        $this->assertNull($result['existing_doc']);
        $this->assertIsIterable($result['results']);

        // Invarian #2: Database tetap steril dari rekaman transient dry-run
        $this->assertSame($docCountBefore, EvidenceDocument::count());
        $this->assertSame($matchCountBefore, DocumentMatch::count());
    }

    public function test_evaluate_upload_respects_dynamic_review_threshold_setting(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'DRY-REV', 'period' => 2026],
            [
                'name' => 'Lembar Kerja Review Threshold Dry Run',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'DRY-REV-01'],
            [
                'title' => 'Notula Rapat Adhoc Kearsipan Unik Kantor XYZ 2026',
                'order_index' => 1,
            ]
        );

        // Dokumen dengan kemiripan ~57.50% terhadap indikator (di antara 50.0% dan 60.0%)
        $fileContent = 'Sidang Pleno Kearsipan Unik XYZ';
        $uploadedFile = UploadedFile::fake()->createWithContent('Sidang_Pleno_Kearsipan_Unik_XYZ.pdf', $fileContent);

        /** @var DryRunMatchingService $service */
        $service = app(DryRunMatchingService::class);

        // 1. Pada review_threshold default (60.0), dokumen skor ~57.5% tidak lolos ambang
        SystemSetting::updateOrCreate(['key' => 'matching.review_threshold'], ['value' => 60.0]);
        $resultDefault = $service->evaluateUpload($uploadedFile);
        $this->assertFalse($resultDefault['matched']);
        $this->assertCount(0, $resultDefault['results']);

        // 2. Turunkan review_threshold menjadi 50.0 lewat SystemSetting
        // evaluateUpload harus mengikuti matching.review_threshold baru dan mengembalikan kandidat
        SystemSetting::updateOrCreate(['key' => 'matching.review_threshold'], ['value' => 50.0]);
        try {
            $resultLower = $service->evaluateUpload($uploadedFile);
            $this->assertTrue($resultLower['matched'], 'evaluateUpload harus menghormati penurunan matching.review_threshold ke 50.0');
            $this->assertCount(1, $resultLower['results']);
            $this->assertSame('DRY-REV-01', $resultLower['results'][0]['indicator_code']);
            $this->assertSame('perlu_verifikasi', $resultLower['results'][0]['status']);
            $this->assertEquals(57.50, $resultLower['results'][0]['score']);
        } finally {
            SystemSetting::updateOrCreate(['key' => 'matching.review_threshold'], ['value' => 60.0]);
        }
    }
}
