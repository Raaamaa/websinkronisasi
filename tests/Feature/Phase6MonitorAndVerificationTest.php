<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Matching\ManualMatchService;
use App\Services\Matching\VerificationService;
use App\Services\Monitor\MonitorMatrixService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase6MonitorAndVerificationTest extends TestCase
{
    protected User $superAdmin;

    protected User $admin;

    protected User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');

        DocumentMatch::query()->forceDelete();
        EvidenceDocument::query()->forceDelete();

        $this->superAdmin = User::where('role', 'super_admin')->firstOrFail();
        $this->superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.phase6@bps.go.id'],
            [
                'name' => 'Admin Phase 6 Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->admin->update(['is_active' => true, 'must_change_password' => false]);

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai.phase6@bps.go.id'],
            [
                'name' => 'Pegawai Phase 6 Test',
                'password' => bcrypt('password'),
                'role' => 'pegawai',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->pegawai->update(['is_active' => true, 'must_change_password' => false]);
    }

    protected function tearDown(): void
    {
        DocumentMatch::query()->forceDelete();
        EvidenceDocument::query()->forceDelete();
        parent::tearDown();
    }

    private function createSampleLkWithIndicator(string $lkCode, string $category, string $indicatorCode, ?string $evidenceLink = null): array
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => $lkCode, 'period' => 2026],
            [
                'name' => "Lembar Kerja {$lkCode} 2026",
                'category' => $category,
                'is_active' => true,
            ]
        );
        $lk->update(['category' => $category, 'is_active' => true]);

        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => $indicatorCode],
            [
                'title' => "Indikator {$indicatorCode} {$lkCode}",
                'order_index' => 1,
                'evidence_link' => $evidenceLink,
            ]
        );
        $indicator->update(['evidence_link' => $evidenceLink]);

        $item = LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $indicator->id, 'order_index' => 1],
            [
                'raw_template_name' => "Bukti {$indicatorCode} [t]",
                'resolved_name' => "Bukti {$indicatorCode} 2026",
            ]
        );

        return [$lk, $indicator, $item];
    }

    /**
     * 1. MON-02, AC-08: Monitor matrix renders checkmark ✔ for 'auto' and 'confirmed' matches only.
     */
    public function test_monitor_matrix_renders_checkmark_for_auto_and_confirmed_matches_only(): void
    {
        [$lkZI, $indZI, $itemZI] = $this->createSampleLkWithIndicator('ZI', 'internal', '1.1');
        [$lkPEKPPP, $indPEKPPP, $itemPEKPPP] = $this->createSampleLkWithIndicator('PEKPPP', 'internal', 'P01');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Multi Match Uji',
            'year' => 2026,
            'file_path' => 'evidence/test_m1.pdf',
            'original_filename' => 'doc_multi.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_multi_m_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        // Match 1: ZI status 'auto'
        DocumentMatch::create([
            'document_id' => $document->id,
            'indicator_id' => $indZI->id,
            'matched_item_id' => $itemZI->id,
            'status' => 'auto',
            'match_method' => 'title_similarity',
            'confidence_score' => 85.0,
            'match_reasons' => ['score_components' => ['title_score' => 85.0]],
        ]);

        // Match 2: PEKPPP status 'confirmed'
        DocumentMatch::create([
            'document_id' => $document->id,
            'indicator_id' => $indPEKPPP->id,
            'matched_item_id' => $itemPEKPPP->id,
            'status' => 'confirmed',
            'match_method' => 'exact_code',
            'confidence_score' => 100.0,
            'match_reasons' => ['rule' => 'exact_code'],
        ]);

        $service = app(MonitorMatrixService::class);
        $matrixData = $service->getMatrixData(['period' => 2026]);

        $this->assertNotEmpty($matrixData['rows']);
        $docRow = collect($matrixData['rows'])->firstWhere('document_id', $document->id);
        $this->assertNotNull($docRow);

        // AC-08: Kolom ZI dan PEKPPP harus memiliki tanda centang
        $this->assertTrue($docRow['cells']['ZI']['has_match']);
        $this->assertSame('✔', $docRow['cells']['ZI']['symbol']);

        $this->assertTrue($docRow['cells']['PEKPPP']['has_match']);
        $this->assertSame('✔', $docRow['cells']['PEKPPP']['symbol']);
    }

    /**
     * 2. MON-02, MON-03, AC-09: Unmatched cells or 'rejected' matches are completely empty.
     */
    public function test_monitor_matrix_leaves_cells_completely_blank_when_no_match(): void
    {
        [$lkKIP, $indKIP, $itemKIP] = $this->createSampleLkWithIndicator('KIP', 'internal', 'K01');
        [$lkSAKIP, $indSAKIP, $itemSAKIP] = $this->createSampleLkWithIndicator('SAKIP', 'internal', 'S01');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Tanpa Match & Rejected',
            'year' => 2026,
            'file_path' => 'evidence/test_m2.pdf',
            'original_filename' => 'doc_blank.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_blank_m_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        // SAKIP: Berstatus 'rejected'
        DocumentMatch::create([
            'document_id' => $document->id,
            'indicator_id' => $indSAKIP->id,
            'matched_item_id' => $itemSAKIP->id,
            'status' => 'rejected',
            'match_method' => 'title_similarity',
            'confidence_score' => 60.0,
            'match_reasons' => ['rejected_by_admin' => true],
        ]);

        $service = app(MonitorMatrixService::class);
        $matrixData = $service->getMatrixData(['period' => 2026]);

        $docRow = collect($matrixData['rows'])->firstWhere('document_id', $document->id);
        $this->assertNotNull($docRow);

        // MON-02, AC-09: KIP (tidak ada match) dan SAKIP (rejected) WAJIB KOSONG MURNI
        $this->assertFalse($docRow['cells']['KIP']['has_match']);
        $this->assertSame('', $docRow['cells']['KIP']['symbol']);

        // MON-03: Rejected tidak pernah tampil di monitor
        $this->assertFalse($docRow['cells']['SAKIP']['has_match']);
        $this->assertSame('', $docRow['cells']['SAKIP']['symbol']);
    }

    /**
     * 3. MON-01: Groups columns into Internal and External categories.
     */
    public function test_monitor_matrix_groups_lks_by_internal_and_external_categories(): void
    {
        $this->createSampleLkWithIndicator('ZI', 'internal', '1.1');
        $this->createSampleLkWithIndicator('PEKPPP', 'internal', 'P01');
        $this->createSampleLkWithIndicator('DATA_SEKUNDER', 'eksternal', 'DS01');

        $service = app(MonitorMatrixService::class);
        $columns = $service->getColumnGroups(2026);

        $this->assertArrayHasKey('internal', $columns);
        $this->assertArrayHasKey('eksternal', $columns);

        $internalCodes = collect($columns['internal'])->pluck('code')->toArray();
        $eksternalCodes = collect($columns['eksternal'])->pluck('code')->toArray();

        $this->assertContains('ZI', $internalCodes);
        $this->assertContains('PEKPPP', $internalCodes);
        $this->assertContains('DATA_SEKUNDER', $eksternalCodes);
    }

    /**
     * 4. MON-04, MON-05, MON-06, MON-10, AC-12: Drill-down modal details and safe links.
     */
    public function test_clicking_checkmark_returns_drilldown_details_with_safe_links_or_placeholder(): void
    {
        $validLink = 'https://cloud.bps.go.id/s/sk-zi-2026';
        [$lk, $indicatorWithLink, $item] = $this->createSampleLkWithIndicator('ZI', 'internal', '1.1', $validLink);
        [$lk2, $indicatorNoLink, $item2] = $this->createSampleLkWithIndicator('PEKPPP', 'internal', 'P02', null);

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'SK Uji Drilldown',
            'document_number' => '99/SK/2026',
            'year' => 2026,
            'file_path' => 'evidence/test_dd.pdf',
            'original_filename' => 'sk_dd.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_dd_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $match1 = DocumentMatch::create([
            'document_id' => $document->id,
            'indicator_id' => $indicatorWithLink->id,
            'matched_item_id' => $item->id,
            'status' => 'confirmed',
            'match_method' => 'exact_code',
            'confidence_score' => 100.0,
            'match_reasons' => ['rule' => 'exact_code'],
        ]);

        $match2 = DocumentMatch::create([
            'document_id' => $document->id,
            'indicator_id' => $indicatorNoLink->id,
            'matched_item_id' => $item2->id,
            'status' => 'auto',
            'match_method' => 'title_similarity',
            'confidence_score' => 80.0,
            'match_reasons' => ['score_components' => ['title_score' => 80.0]],
        ]);

        $service = app(MonitorMatrixService::class);

        // Drilldown untuk LK ZI (memiliki tautan valid)
        $detailsZI = $service->getDrilldownDetails($document->id, $lk->id);
        $this->assertSame('SK Uji Drilldown', $detailsZI['document']['name']);
        $this->assertCount(1, $detailsZI['indicators']);
        $this->assertSame('Terverifikasi', $detailsZI['indicators'][0]['status_label']);
        $this->assertSame($validLink, $detailsZI['indicators'][0]['evidence_link']);
        $this->assertTrue($detailsZI['indicators'][0]['has_valid_link']);

        // Drilldown untuk LK PEKPPP (tidak memiliki tautan)
        $detailsPEKPPP = $service->getDrilldownDetails($document->id, $lk2->id);
        $this->assertSame('Otomatis', $detailsPEKPPP['indicators'][0]['status_label']);
        $this->assertFalse($detailsPEKPPP['indicators'][0]['has_valid_link']);
        // MON-06, AC-12: Keterangan jelas "tautan belum tersedia"
        $this->assertSame('tautan belum tersedia', $detailsPEKPPP['indicators'][0]['link_placeholder']);
    }

    /**
     * 5. RBAC.md, AC-01: Pegawai cannot access verification queue (403 Forbidden).
     */
    public function test_pegawai_cannot_access_verification_queue_returns_403(): void
    {
        $response = $this->actingAs($this->pegawai)->get('/admin/verification-queue');
        $response->assertStatus(403);
    }

    /**
     * 6. MATCH-04: Admin and Super Admin can access verification queue.
     */
    public function test_admin_and_super_admin_can_access_verification_queue(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get('/admin/verification-queue');
        $responseAdmin->assertOk();

        $responseSuper = $this->actingAs($this->superAdmin)->get('/admin/verification-queue');
        $responseSuper->assertOk();
    }

    /**
     * 7. MATCH-04, AC-10, AC-17: Admin confirms auto match -> status becomes confirmed, audit logged.
     */
    public function test_admin_can_confirm_auto_match_updating_status_to_confirmed_and_logging_audit(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkWithIndicator('ZI', 'internal', '4.1');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Konfirmasi Uji',
            'year' => 2026,
            'file_path' => 'evidence/test_verif.pdf',
            'original_filename' => 'verif.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_verif_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $match = DocumentMatch::create([
            'document_id' => $document->id,
            'indicator_id' => $indicator->id,
            'matched_item_id' => $item->id,
            'status' => 'auto',
            'match_method' => 'title_similarity',
            'confidence_score' => 88.0,
            'match_reasons' => ['title_score' => 88.0],
        ]);

        $verifService = app(VerificationService::class);
        $updatedMatch = $verifService->confirmMatch($match->id, $this->admin);

        $this->assertSame('confirmed', $updatedMatch->status);
        $this->assertSame($this->admin->id, $updatedMatch->verified_by);
        $this->assertNotNull($updatedMatch->verified_at);

        // AC-17: Audit log harus mencatat aksi verifikasi
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'match.verified',
            'entity_type' => 'DocumentMatch',
            'entity_id' => $match->id,
        ]);
    }

    /**
     * 8. MATCH-04, AC-11, AC-17: Admin rejects auto match -> status becomes rejected, checkmark removed.
     */
    public function test_admin_can_reject_auto_match_removing_it_from_monitor_and_setting_rejected(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkWithIndicator('ZI', 'internal', '4.2');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Tolak Uji',
            'year' => 2026,
            'file_path' => 'evidence/test_rej.pdf',
            'original_filename' => 'rej.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_rej_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $match = DocumentMatch::create([
            'document_id' => $document->id,
            'indicator_id' => $indicator->id,
            'matched_item_id' => $item->id,
            'status' => 'auto',
            'match_method' => 'title_similarity',
            'confidence_score' => 77.0,
            'match_reasons' => ['title_score' => 77.0],
        ]);

        $verifService = app(VerificationService::class);
        $rejectedMatch = $verifService->rejectMatch($match->id, $this->admin);

        // Status berubah jadi rejected
        $this->assertSame('rejected', $rejectedMatch->status);
        $this->assertSame($this->admin->id, $rejectedMatch->verified_by);

        // AC-11: Tanda centang seketika hilang dari matriks monitor
        $monitorService = app(MonitorMatrixService::class);
        $matrix = $monitorService->getMatrixData(['period' => 2026]);
        $row = collect($matrix['rows'])->firstWhere('document_id', $document->id);

        $this->assertFalse($row['cells']['ZI']['has_match']);
        $this->assertSame('', $row['cells']['ZI']['symbol']);

        // AC-17: Audit log mencatat penolakan
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'match.rejected',
            'entity_type' => 'DocumentMatch',
            'entity_id' => $match->id,
        ]);
    }

    /**
     * 9. MATCH-05: Admin can manually add match as confirmed.
     */
    public function test_admin_can_manually_add_match_as_confirmed(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkWithIndicator('SAKIP', 'internal', 'SK01');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Laporan Akuntabilitas Manual',
            'year' => 2026,
            'file_path' => 'evidence/test_man.pdf',
            'original_filename' => 'lakip_man.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_man_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $manualService = app(ManualMatchService::class);
        $match = $manualService->addMatch($document->id, $indicator->id, $this->admin);

        $this->assertSame('confirmed', $match->status);
        $this->assertSame('manual', $match->match_method);
        $this->assertEquals(100.0, $match->confidence_score);
        $this->assertSame($this->admin->id, $match->verified_by);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'match.manual_created',
            'entity_type' => 'DocumentMatch',
            'entity_id' => $match->id,
        ]);
    }

    /**
     * 10. AC-16: Concurrent manual match creation does not duplicate or throw 500.
     */
    public function test_concurrent_manual_match_creation_does_not_duplicate_or_throw_500(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkWithIndicator('PEKPPP', 'internal', 'P99');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Race Condition Test',
            'year' => 2026,
            'file_path' => 'evidence/test_race.pdf',
            'original_filename' => 'race.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_race_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $manualService = app(ManualMatchService::class);

        // Simulasikan dua admin menambahkan match yang persis sama secara berurutan/bersamaan
        $match1 = $manualService->addMatch($document->id, $indicator->id, $this->admin);
        $match2 = $manualService->addMatch($document->id, $indicator->id, $this->superAdmin);

        // AC-16: Hanya satu record unik tersimpan, tidak ada 500 error
        $this->assertSame($match1->id, $match2->id);
        $this->assertSame(1, DocumentMatch::where('document_id', $document->id)->where('indicator_id', $indicator->id)->count());
    }

    /**
     * 11. MATCH-05, MATCH-06: Admin can delete match manually setting status to rejected.
     */
    public function test_admin_can_manually_delete_match_setting_status_to_rejected(): void
    {
        [$lk, $indicator, $item] = $this->createSampleLkWithIndicator('KIP', 'internal', 'K88');

        $document = EvidenceDocument::create([
            'id' => (string) Str::uuid(),
            'document_name' => 'Dokumen Hapus Manual Test',
            'year' => 2026,
            'file_path' => 'evidence/test_del.pdf',
            'original_filename' => 'del.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_del_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $manualService = app(ManualMatchService::class);
        $match = $manualService->addMatch($document->id, $indicator->id, $this->admin);

        // Hapus match manual
        $manualService->deleteMatch($document->id, $indicator->id, $this->admin);

        $fresh = $match->fresh();
        // Sesuai MATCH-06, match dihapus dengan mengubah status menjadi 'rejected'
        $this->assertSame('rejected', $fresh->status);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'match.manual_deleted',
            'entity_type' => 'DocumentMatch',
            'entity_id' => $match->id,
        ]);
    }

    /**
     * 12. MON-01, RBAC.md: All roles (Super Admin, Admin, Pegawai) can access Monitor page.
     */
    public function test_all_roles_can_access_monitor_page(): void
    {
        $resSuper = $this->actingAs($this->superAdmin)->get('/admin/monitor-matrix');
        $resSuper->assertOk();

        $resAdmin = $this->actingAs($this->admin)->get('/admin/monitor-matrix');
        $resAdmin->assertOk();

        $resPegawai = $this->actingAs($this->pegawai)->get('/admin/monitor-matrix');
        $resPegawai->assertOk();
    }
}
