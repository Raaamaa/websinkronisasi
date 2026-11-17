<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentLkLink;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkIndicator;
use App\Models\User;
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
            'approval_status' => 'disetujui',
            'file_path' => 'evidence/test_m1.pdf',
            'original_filename' => 'doc_multi.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'hash_multi_m_'.uniqid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        DocumentLkLink::create(['document_id' => $document->id, 'lk_id' => $lkZI->id, 'link_source' => 'admin']);
        DocumentLkLink::create(['document_id' => $document->id, 'lk_id' => $lkPEKPPP->id, 'link_source' => 'admin']);

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
            'approval_status' => 'disetujui',
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
}
