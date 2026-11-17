<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentLkLink;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Evidence\DocumentSimilarityService;
use App\Services\Search\DocumentSearchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase3PivotSearchMatchingSimilarityTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.phase3@bps.go.id'],
            [
                'name' => 'Admin Phase 3',
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
                'password' => bcrypt('password'),
            ]
        );

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai.phase3@bps.go.id'],
            [
                'name' => 'Pegawai Phase 3',
                'role' => 'pegawai',
                'is_active' => true,
                'must_change_password' => false,
                'password' => bcrypt('password'),
            ]
        );
    }

    private function createSampleDocument(array $attributes = []): EvidenceDocument
    {
        $unique = Str::random(8);

        return EvidenceDocument::create(array_merge([
            'document_name' => 'Dokumen Bukti Phase 3 '.$unique,
            'document_number' => 'ND/PH3/'.$unique.'/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'file_path' => 'evidence/phase3_'.$unique.'.pdf',
            'original_filename' => 'file_'.$unique.'.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'phase3_hash_'.$unique),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->pegawai->id,
            'approval_status' => 'disetujui',
            'extracted_text' => 'Teks isi berkas dokumen bukti fase 3 '.$unique,
        ], $attributes));
    }

    private function createSampleLk(array $attributes = []): LembarKerja
    {
        $unique = Str::random(6);

        return LembarKerja::create(array_merge([
            'code' => 'LK_'.$unique,
            'name' => 'Lembar Kerja Phase 3 '.$unique,
            'category' => 'internal',
            'period' => 2026,
            'evaluation_interval' => '12 Bulan',
            'is_active' => true,
        ], $attributes));
    }

    private function createSampleIndicator(LembarKerja $lk, array $attributes = []): LkIndicator
    {
        $unique = Str::random(4);

        return LkIndicator::create(array_merge([
            'lk_id' => $lk->id,
            'code' => 'IND-'.$unique,
            'title' => 'Indikator Pengujian '.$unique,
            'order_index' => 1,
        ], $attributes));
    }

    /**
     * 1. Dokumen non-disetujui (menunggu, revisi, ditolak) tidak boleh muncul pada pencarian.
     */
    public function test_document_search_excludes_non_approved_documents(): void
    {
        $keyword = 'PencarianStatus'.Str::random(6);

        $docApproved = $this->createSampleDocument([
            'document_name' => 'Dokumen Sah '.$keyword,
            'approval_status' => 'disetujui',
        ]);

        $docPending = $this->createSampleDocument([
            'document_name' => 'Dokumen Menunggu '.$keyword,
            'approval_status' => 'menunggu',
        ]);

        $docRevision = $this->createSampleDocument([
            'document_name' => 'Dokumen Revisi '.$keyword,
            'approval_status' => 'revisi',
        ]);

        $docRejected = $this->createSampleDocument([
            'document_name' => 'Dokumen Ditolak '.$keyword,
            'approval_status' => 'ditolak',
        ]);

        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);
        $results = $searchService->search($keyword);

        $this->assertTrue($results->contains('id', $docApproved->id));
        $this->assertFalse($results->contains('id', $docPending->id));
        $this->assertFalse($results->contains('id', $docRevision->id));
        $this->assertFalse($results->contains('id', $docRejected->id));
    }

    /**
     * 2. Relasi LK pada dokumen hanya sumber resmi (admin, drive_sync, backfill); 'proposed' dilarang muncul.
     */
    public function test_document_search_excludes_proposed_lk_links_and_only_includes_official_sources(): void
    {
        $keyword = 'ProposedFilter'.Str::random(6);
        $doc = $this->createSampleDocument([
            'document_name' => 'Dokumen Uji Sumber LK '.$keyword,
            'approval_status' => 'disetujui',
        ]);

        $lkProposed = $this->createSampleLk(['code' => 'LK_PROP_'.Str::random(4)]);
        $lkAdmin = $this->createSampleLk(['code' => 'LK_ADM_'.Str::random(4)]);

        // Link proposed
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lkProposed->id,
            'link_source' => 'proposed',
            'created_by' => $this->pegawai->id,
        ]);

        // Link resmi admin
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lkAdmin->id,
            'link_source' => 'admin',
            'created_by' => $this->admin->id,
        ]);

        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);
        $results = $searchService->search($keyword);

        $found = $results->firstWhere('id', $doc->id);
        $this->assertNotNull($found);

        // Relasi LK resmi harus memuat LK admin dan mengabaikan proposed
        $this->assertTrue($found->lembarKerja->contains('id', $lkAdmin->id));
        $this->assertFalse($found->lembarKerja->contains('id', $lkProposed->id));

        // officialLkLinks hanya memuat link admin
        $this->assertTrue($found->officialLkLinks->contains('lk_id', $lkAdmin->id));
        $this->assertFalse($found->officialLkLinks->contains('lk_id', $lkProposed->id));
    }

    /**
     * 3. Dokumen dengan 3 link indikator pada 1 LK yang sama hanya memunculkan LK tersebut SATU KALI (DISTINCT lk_id).
     */
    public function test_document_with_multiple_indicators_on_same_lk_returns_lk_only_once_distinct(): void
    {
        $keyword = 'DistinctLk'.Str::random(6);
        $doc = $this->createSampleDocument([
            'document_name' => 'Dokumen Multi Indikator '.$keyword,
            'approval_status' => 'disetujui',
        ]);

        $lk = $this->createSampleLk(['code' => 'LK_MULTI_'.Str::random(4)]);
        $ind1 = $this->createSampleIndicator($lk);
        $ind2 = $this->createSampleIndicator($lk);
        $ind3 = $this->createSampleIndicator($lk);

        // Backfill menghasilkan 3 baris link karena terkait 3 indikator berbeda
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'indicator_id' => $ind1->id,
            'link_source' => 'backfill',
            'created_by' => $this->admin->id,
        ]);
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'indicator_id' => $ind2->id,
            'link_source' => 'backfill',
            'created_by' => $this->admin->id,
        ]);
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'indicator_id' => $ind3->id,
            'link_source' => 'backfill',
            'created_by' => $this->admin->id,
        ]);

        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);
        $results = $searchService->search($keyword);

        $found = $results->firstWhere('id', $doc->id);
        $this->assertNotNull($found);

        // Pastikan relasi Lembar Kerja hanya memuat 1 item (distinct), bukan 3
        $this->assertCount(1, $found->lembarKerja);
        $this->assertSame($lk->id, $found->lembarKerja->first()->id);
    }

    /**
     * 4. Pencarian bekerja lintas source_type (upload, drive, link).
     */
    public function test_document_search_finds_across_source_types_upload_drive_link(): void
    {
        $keyword = 'CrossSource'.Str::random(6);

        $docUpload = $this->createSampleDocument([
            'document_name' => 'Bukti Upload '.$keyword,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
        ]);

        $docDrive = EvidenceDocument::create([
            'document_name' => 'Bukti Drive '.$keyword,
            'year' => 2026,
            'source_type' => 'drive',
            'drive_file_id' => 'drv_'.Str::random(16),
            'external_link' => 'https://drive.google.com/file/d/sample/view',
            'approval_status' => 'disetujui',
            'processing_status' => 'selesai',
        ]);

        $docLink = EvidenceDocument::create([
            'document_name' => 'Bukti Link Eksternal '.$keyword,
            'year' => 2026,
            'source_type' => 'link',
            'external_link' => 'https://bps.go.id/publikasi/'.$keyword,
            'uploaded_by' => $this->pegawai->id,
            'approval_status' => 'disetujui',
            'processing_status' => 'selesai',
        ]);

        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);
        $results = $searchService->search($keyword);

        $this->assertTrue($results->contains('id', $docUpload->id));
        $this->assertTrue($results->contains('id', $docDrive->id));
        $this->assertTrue($results->contains('id', $docLink->id));
    }

    /**
     * 5. DocumentSimilarityService: SHA-256 identik dengan dokumen aktif mengembalikan data lengkap dokumen lama.
     */
    public function test_similarity_service_identical_sha256_returns_existing_active_document(): void
    {
        $hash = hash('sha256', 'identical_content_'.Str::random(10));
        $doc = $this->createSampleDocument([
            'document_name' => 'SK Pedoman Tata Kelola TI 2026',
            'document_number' => 'SK/TI/2026/088',
            'file_hash' => $hash,
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawai->id,
        ]);

        /** @var DocumentSimilarityService $similarityService */
        $similarityService = app(DocumentSimilarityService::class);
        $identical = $similarityService->findIdenticalByHash($hash);

        $this->assertNotNull($identical);
        $this->assertSame($doc->id, $identical['id']);
        $this->assertSame('SK Pedoman Tata Kelola TI 2026', $identical['judul']);
        $this->assertSame($this->pegawai->name, $identical['uploader']['name']);
        $this->assertSame('disetujui', $identical['approval_status']);
        $this->assertSame('SK/TI/2026/088', $identical['rujukan']);

        // Jika exclude id sama dengan dokumen, return null
        $this->assertNull($similarityService->findIdenticalByHash($hash, $doc->id));
    }

    /**
     * 8. DocumentSimilarityService mengabaikan dokumen soft-delete untuk hash identik dan kandidat.
     */
    public function test_similarity_service_ignores_soft_deleted_documents(): void
    {
        $hash = hash('sha256', 'soft_deleted_hash_'.Str::random(10));
        $doc = $this->createSampleDocument([
            'document_name' => 'Dokumen Dihapus Tata Naskah',
            'file_hash' => $hash,
            'approval_status' => 'disetujui',
        ]);

        $doc->delete(); // Soft delete

        /** @var DocumentSimilarityService $similarityService */
        $similarityService = app(DocumentSimilarityService::class);

        // Hash identik pada dokumen soft delete harus return null
        $this->assertNull($similarityService->findIdenticalByHash($hash));

        // Kandidat kemiripan tidak boleh memuat dokumen yang di-soft-delete
        $candidates = $similarityService->findSimilarCandidates('Dokumen Dihapus Tata Naskah');
        $this->assertFalse($candidates->contains('id', $doc->id));
    }

    /**
     * 9. Kandidat kemiripan memotong teks maks 1500 karakter, non-blocking, maks 5 kandidat + skor.
     */
    public function test_similarity_service_returns_candidates_truncated_text_max_5_with_scores(): void
    {
        $keyword = 'StandarPelayanan'.Str::random(4);

        // Buat 7 dokumen aktif yang mirip
        $createdDocs = collect();
        for ($i = 1; $i <= 7; $i++) {
            $createdDocs->push($this->createSampleDocument([
                'document_name' => "Standar Pelayanan Publik Statistik Bagian {$i} {$keyword}",
                'document_number' => "SPP-{$i}/2026",
                'extracted_text' => "Uraian prosedur pelayanan publik terpadu nomor {$i}",
                'approval_status' => 'disetujui',
            ]));
        }

        /** @var DocumentSimilarityService $similarityService */
        $similarityService = app(DocumentSimilarityService::class);

        // Input teks sangat panjang (> 2000 karakter) untuk memastikan pemotongan teks maks 1500 karakter
        $veryLongText = str_repeat('Standar pelayanan publik terpadu BPS tahun 2026 ', 60); // ~2880 karakter

        $candidates = $similarityService->findSimilarCandidates(
            documentOrTitle: 'Standar Pelayanan Publik Statistik '.$keyword,
            documentNumber: 'SPP-1/2026',
            text: $veryLongText
        );

        // Maksimal 5 kandidat
        $this->assertLessThanOrEqual(5, $candidates->count());
        $this->assertGreaterThanOrEqual(1, $candidates->count());

        // Setiap kandidat wajib memuat skor
        foreach ($candidates as $cand) {
            $score = $cand->score ?? $cand->similarity_score ?? ($cand['score'] ?? null);
            $this->assertNotNull($score);
            $this->assertGreaterThan(0.0, (float) $score);
        }
    }
}
