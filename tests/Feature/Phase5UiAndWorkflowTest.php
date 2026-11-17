<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\CariDokumenPage;
use App\Filament\Pages\VerificationQueuePage;
use App\Filament\Resources\EvidenceDocumentResource;
use App\Filament\Resources\EvidenceDocumentResource\Pages\CreateEvidenceDocument;
use App\Models\DocumentLkLink;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Evidence\DocumentSimilarityService;
use App\Services\Monitor\MonitorMatrixService;
use App\Services\Search\DocumentSearchService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class Phase5UiAndWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $admin;

    protected User $pegawaiA;

    protected User $pegawaiB;

    protected LembarKerja $lkX;

    protected LembarKerja $lkY;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'sa_phase5@bps.go.id'],
            [
                'name' => 'Super Admin Phase 5',
                'role' => 'super_admin',
                'nip' => '198101012005011001',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_phase5@bps.go.id'],
            [
                'name' => 'Admin Phase 5',
                'role' => 'admin',
                'nip' => '198601012010011002',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $this->pegawaiA = User::firstOrCreate(
            ['email' => 'pegawai_a_phase5@bps.go.id'],
            [
                'name' => 'Pegawai A Phase 5',
                'role' => 'pegawai',
                'nip' => '199101012015011003',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $this->pegawaiB = User::firstOrCreate(
            ['email' => 'pegawai_b_phase5@bps.go.id'],
            [
                'name' => 'Pegawai B Phase 5',
                'role' => 'pegawai',
                'nip' => '199201012016011004',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $this->lkX = LembarKerja::firstOrCreate(
            ['code' => 'LK-P5-X', 'period' => 2026],
            [
                'name' => 'Lembar Kerja X Phase 5',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        $this->lkY = LembarKerja::firstOrCreate(
            ['code' => 'LK-P5-Y', 'period' => 2026],
            [
                'name' => 'Lembar Kerja Y Phase 5',
                'category' => 'eksternal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );
    }

    public function test_pengajuan_saya_scoping_pegawai_a_does_not_see_pegawai_b(): void
    {
        $docA = EvidenceDocument::create([
            'document_name' => 'Dokumen Milik Pegawai A '.Str::random(6),
            'document_number' => 'B-001/A/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiA->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_a.pdf',
            'original_filename' => 'test_a.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $docB = EvidenceDocument::create([
            'document_name' => 'Dokumen Milik Pegawai B '.Str::random(6),
            'document_number' => 'B-002/B/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiB->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_b.pdf',
            'original_filename' => 'test_b.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        // Pegawai A query: hanya melihat dokumen A
        $this->actingAs($this->pegawaiA);
        $recordsA = EvidenceDocumentResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($docA->id, $recordsA);
        $this->assertNotContains($docB->id, $recordsA);

        // Pegawai B query: hanya melihat dokumen B
        $this->actingAs($this->pegawaiB);
        $recordsB = EvidenceDocumentResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($docB->id, $recordsB);
        $this->assertNotContains($docA->id, $recordsB);

        // Admin & Super Admin melihat semua
        $this->actingAs($this->admin);
        $recordsAdmin = EvidenceDocumentResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($docA->id, $recordsAdmin);
        $this->assertContains($docB->id, $recordsAdmin);
    }

    public function test_pegawai_a_cannot_view_or_edit_pending_record_of_pegawai_b(): void
    {
        $docB = EvidenceDocument::create([
            'document_name' => 'Dokumen Rahasia B '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiB->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_b_priv.pdf',
            'original_filename' => 'test_b_priv.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $this->actingAs($this->pegawaiA);

        // Akses langsung record B yang masih menunggu harus 403
        $response = $this->get('/admin/evidence-documents/'.$docB->id);
        $this->assertEquals(403, $response->getStatusCode());

        $editResponse = $this->get('/admin/evidence-documents/'.$docB->id.'/edit');
        $this->assertEquals(403, $editResponse->getStatusCode());
    }

    public function test_verification_queue_approval_workflow_usul_x_approved_as_y(): void
    {
        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Usul X Disetujui Y '.Str::random(6),
            'document_number' => 'B-USUL/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiA->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_workflow.pdf',
            'original_filename' => 'workflow.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        // Simpan usulan LK X
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $this->lkX->id,
            'link_source' => 'proposed',
        ]);

        $this->actingAs($this->admin);

        // Admin menyetujui dengan LK Y
        Livewire::test(VerificationQueuePage::class)
            ->call('approveDocument', $doc->id, [$this->lkY->id]);

        $doc->refresh();

        // Status disetujui, approved_by terisi, approved_at terisi
        $this->assertEquals('disetujui', $doc->approval_status);
        $this->assertEquals($this->admin->id, $doc->approved_by);
        $this->assertNotNull($doc->approved_at);

        // LK X terhapus
        $this->assertDatabaseMissing('document_lk_links', [
            'document_id' => $doc->id,
            'lk_id' => $this->lkX->id,
        ]);

        // LK Y berstatus 'admin'
        $this->assertDatabaseHas('document_lk_links', [
            'document_id' => $doc->id,
            'lk_id' => $this->lkY->id,
            'link_source' => 'admin',
        ]);

        // Cari Dokumen hanya memunculkan LK Y
        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);
        $searchResult = $searchService->search($doc->document_name);
        $matchedDoc = $searchResult->firstWhere('id', $doc->id);
        $this->assertNotNull($matchedDoc);
        $this->assertTrue($matchedDoc->lembarKerja->contains('id', $this->lkY->id));
        $this->assertFalse($matchedDoc->lembarKerja->contains('id', $this->lkX->id));
    }

    public function test_verification_queue_approve_without_lk_fails(): void
    {
        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Tanpa LK '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiA->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_no_lk.pdf',
            'original_filename' => 'no_lk.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(VerificationQueuePage::class)
            ->call('approveDocument', $doc->id, [])
            ->assertHasErrors();

        $this->assertEquals('menunggu', $doc->fresh()->approval_status);
    }

    public function test_verification_queue_revisi_or_tolak_without_notes_fails(): void
    {
        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Uji Catatan Wajib '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiA->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_notes.pdf',
            'original_filename' => 'notes.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $this->actingAs($this->admin);

        // Revisi tanpa catatan gagal
        Livewire::test(VerificationQueuePage::class)
            ->call('requestRevision', $doc->id, '')
            ->assertHasErrors();

        $this->assertEquals('menunggu', $doc->fresh()->approval_status);

        // Tolak tanpa catatan gagal
        Livewire::test(VerificationQueuePage::class)
            ->call('rejectDocument', $doc->id, '')
            ->assertHasErrors();

        $this->assertEquals('menunggu', $doc->fresh()->approval_status);

        // Tolak dengan catatan berhasil
        Livewire::test(VerificationQueuePage::class)
            ->call('rejectDocument', $doc->id, 'Dokumen kadaluarsa dan tidak relevan.')
            ->assertHasNoErrors();

        $this->assertEquals('ditolak', $doc->fresh()->approval_status);
        $this->assertEquals('Dokumen kadaluarsa dan tidak relevan.', $doc->fresh()->approval_notes);
    }

    public function test_super_admin_can_open_verification_queue_page(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/admin/verification-queue');
        $response->assertOk();
    }

    public function test_storage_warning_banner_visible_only_to_super_admin_on_matrix(): void
    {
        // Paksa threshold rendah agar warning aktif
        config(['evidence.storage_warning_percent' => 0]);

        $this->actingAs($this->superAdmin);
        Livewire::test(VerificationQueuePage::class)
            ->assertSee('Peringatan Kapasitas Penyimpanan');

        $this->actingAs($this->admin);
        Livewire::test(VerificationQueuePage::class)
            ->assertDontSee('Peringatan Kapasitas Penyimpanan');
    }

    public function test_cari_dokumen_returns_only_approved_documents(): void
    {
        $approvedDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Sah Disetujui '.Str::random(6),
            'document_number' => 'B-APPROVED/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->pegawaiA->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_appr.pdf',
            'original_filename' => 'appr.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $pendingDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Belum Disetujui '.Str::random(6),
            'document_number' => 'B-PENDING/2026',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiA->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_pend.pdf',
            'original_filename' => 'pend.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        // Cari Dokumen diakses oleh pegawai
        $this->actingAs($this->pegawaiA);
        Livewire::test(CariDokumenPage::class)
            ->set('query', 'Dokumen')
            ->call('search')
            ->assertSee($approvedDoc->document_name)
            ->assertDontSee($pendingDoc->document_name);

        // Super Admin akses Cari Dokumen tetap 403
        $this->actingAs($this->superAdmin);
        $response = $this->get('/admin/cari-dokumen');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_matrix_does_not_duplicate_for_document_with_multiple_indicator_links_on_one_lk(): void
    {
        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Multi Indikator LK '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->admin->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_multi.pdf',
            'original_filename' => 'multi.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $ind1 = LkIndicator::firstOrCreate(
            ['lk_id' => $this->lkX->id, 'code' => 'P5-IND-1'],
            ['title' => 'Indikator 1', 'order_index' => 1]
        );
        $ind2 = LkIndicator::firstOrCreate(
            ['lk_id' => $this->lkX->id, 'code' => 'P5-IND-2'],
            ['title' => 'Indikator 2', 'order_index' => 2]
        );
        $ind3 = LkIndicator::firstOrCreate(
            ['lk_id' => $this->lkX->id, 'code' => 'P5-IND-3'],
            ['title' => 'Indikator 3', 'order_index' => 3]
        );

        // 3 baris di document_lk_links untuk 1 LK
        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $this->lkX->id, 'indicator_id' => $ind1->id, 'link_source' => 'backfill']);
        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $this->lkX->id, 'indicator_id' => $ind2->id, 'link_source' => 'backfill']);
        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $this->lkX->id, 'indicator_id' => $ind3->id, 'link_source' => 'backfill']);

        /** @var MonitorMatrixService $matrixService */
        $matrixService = app(MonitorMatrixService::class);
        $matrixData = $matrixService->getMatrixData([
            'period' => 2026,
            'search' => $doc->document_name,
        ]);

        // Baris matriks tepat 1
        $matchingRows = collect($matrixData['rows'])->where('document_id', $doc->id);
        $this->assertCount(1, $matchingRows);

        $row = $matchingRows->first();
        $this->assertEquals('✔', $row['cells'][$this->lkX->code]['symbol']);
    }

    public function test_similar_document_warning_returns_max_5_candidates(): void
    {
        /** @var DocumentSimilarityService $simService */
        $simService = app(DocumentSimilarityService::class);

        // Buat 8 dokumen mirip
        for ($i = 1; $i <= 8; $i++) {
            EvidenceDocument::create([
                'document_name' => "Surat Keputusan Bersama Tim Evaluasi Terpadu {$i}",
                'document_number' => "B-{$i}/SKB/2026",
                'year' => 2026,
                'source_type' => 'upload',
                'approval_status' => 'disetujui',
                'uploaded_by' => $this->admin->id,
                'file_hash' => hash('sha256', "unique_hash_{$i}_".Str::random(8)),
                'file_path' => "evidence/test_sim_{$i}.pdf",
                'original_filename' => "sim_{$i}.pdf",
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 1024,
                'processing_status' => 'selesai',
            ]);
        }

        $candidates = $simService->findSimilarCandidates(
            'Surat Keputusan Bersama Tim Evaluasi Terpadu',
            'B-1/SKB/2026',
            null,
            null,
            5
        );

        $this->assertLessThanOrEqual(5, count($candidates));
    }

    public function test_recent_uploads_scopes_to_own_for_pegawai_and_all_for_admin(): void
    {
        $docA = EvidenceDocument::create([
            'document_name' => 'Dokumen Terbaru A '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiA->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_recent_a.pdf',
            'original_filename' => 'recent_a.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $docB = EvidenceDocument::create([
            'document_name' => 'Dokumen Terbaru B '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'uploaded_by' => $this->pegawaiB->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_recent_b.pdf',
            'original_filename' => 'recent_b.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        // Pegawai A hanya melihat dokumennya sendiri di recent uploads
        $this->actingAs($this->pegawaiA);
        $recentA = app(CreateEvidenceDocument::class)->getRecentUploads()->pluck('id')->toArray();
        $this->assertContains($docA->id, $recentA);
        $this->assertNotContains($docB->id, $recentA);

        // Admin melihat semua dokumen
        $this->actingAs($this->admin);
        $recentAdmin = app(CreateEvidenceDocument::class)->getRecentUploads()->pluck('id')->toArray();
        $this->assertContains($docA->id, $recentAdmin);
        $this->assertContains($docB->id, $recentAdmin);
    }

    public function test_recent_uploads_lk_count_counts_official_distinct_links(): void
    {
        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Multi Link Resmi '.Str::random(6),
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'uploaded_by' => $this->admin->id,
            'file_hash' => hash('sha256', Str::random(16)),
            'file_path' => 'evidence/test_recent_lk.pdf',
            'original_filename' => 'recent_lk.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'processing_status' => 'selesai',
        ]);

        $ind1 = LkIndicator::firstOrCreate(['lk_id' => $this->lkX->id, 'code' => 'P5-REC-1'], ['title' => 'Ind 1', 'order_index' => 1]);
        $ind2 = LkIndicator::firstOrCreate(['lk_id' => $this->lkX->id, 'code' => 'P5-REC-2'], ['title' => 'Ind 2', 'order_index' => 2]);

        // 2 link resmi untuk lkX (indikator berbeda)
        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $this->lkX->id, 'indicator_id' => $ind1->id, 'link_source' => 'backfill']);
        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $this->lkX->id, 'indicator_id' => $ind2->id, 'link_source' => 'backfill']);

        // 1 link resmi untuk lkY
        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $this->lkY->id, 'link_source' => 'admin']);

        // 1 link non-resmi 'proposed' untuk lkX (dilarang dihitung)
        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $this->lkX->id, 'link_source' => 'proposed']);

        $this->actingAs($this->admin);
        $recents = app(CreateEvidenceDocument::class)->getRecentUploads();
        $targetDoc = $recents->firstWhere('id', $doc->id);

        $this->assertNotNull($targetDoc);
        // Harus tepat 2 LK resmi yang berbeda (lkX dan lkY)
        $this->assertEquals(2, $targetDoc->official_lks_count);
    }

    public function test_upload_form_displays_google_drive_guidance_over_20mb_and_requires_at_least_one_lk(): void
    {
        $this->actingAs($this->pegawaiA);

        $this->get('/admin/evidence-documents/create')
            ->assertOk()
            ->assertSee('Berkas lebih dari 20 MB (mis. video): unggah dulu ke Google Drive, lalu kirim tautannya.');

        Livewire::test(CreateEvidenceDocument::class)
            ->fillForm([
                'document_name' => 'Dokumen Uji Wajib LK',
                'year' => 2026,
                'source_type' => 'link',
                'external_link' => 'https://drive.google.com/file/d/contoh123/view',
                'lk_ids' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['lk_ids']);
    }
}
