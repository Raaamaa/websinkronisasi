<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DuplicateDocumentException;
use App\Models\DocumentLkLink;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkImportProfile;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Evidence\EvidenceUploadService;
use App\Services\Monitor\MonitorMatrixService;
use App\Services\Search\DocumentSearchService;
use Database\Seeders\BpsAuditLogDemoSeeder;
use Database\Seeders\BpsDemoUserSeeder;
use Database\Seeders\DemoIndicatorAndSampleDocumentsSeeder;
use Database\Seeders\InitialLkSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase7SearchAuditAndSettingsTest extends TestCase
{
    protected User $superAdmin;

    protected User $admin;

    protected User $pegawai;

    protected function setUp(): void
    {
        parent::setUp();

        DocumentMatch::query()->forceDelete();
        EvidenceDocument::query()->forceDelete();
        LkIndicator::whereIn('code', ['ZI-PHASE7-SEARCH', 'ZI-PHASE7-DYNAMIC', 'ZI-DL-1', 'ZI-DL-2'])->forceDelete();

        $this->superAdmin = User::where('role', 'super_admin')->firstOrFail();
        $this->superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.phase7@bps.go.id'],
            [
                'name' => 'Admin Phase 7 Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->admin->update(['is_active' => true, 'must_change_password' => false]);

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai.phase7@bps.go.id'],
            [
                'name' => 'Pegawai Phase 7 Test',
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
        LkIndicator::whereIn('code', ['ZI-PHASE7-SEARCH', 'ZI-PHASE7-DYNAMIC', 'ZI-DL-1', 'ZI-DL-2'])->forceDelete();
        parent::tearDown();
    }

    /**
     * Helper to create test evidence documents.
     */
    protected function createSampleDocument(array $attributes = []): EvidenceDocument
    {
        return EvidenceDocument::create(array_merge([
            'document_name' => 'Surat Keputusan Tim Pelaksana Reformasi Birokrasi',
            'document_number' => 'SK/BPS/2026/001',
            'year' => 2026,
            'document_type' => 'SK Tim',
            'file_path' => 'evidence/test-sample.pdf',
            'original_filename' => 'sample_sk.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 10240,
            'file_hash' => hash('sha256', Str::random(32)),
            'extracted_text' => 'Berdasarkan rapat evaluasi internal Reformasi Birokrasi BPS tahun 2026 ditetapkan susunan pengelola akuntabilitas kinerja.',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
            'approval_status' => 'disetujui',
        ], $attributes));
    }

    /**
     * Slice 1: TSK-701 - FTS Multi-Kolom & Fuzzy Search.
     */
    public function test_fts_search_finds_document_by_extracted_text_content(): void
    {
        $doc = $this->createSampleDocument([
            'document_name' => 'Laporan Akuntabilitas',
            'extracted_text' => 'Analisis komprehensif mengenai tingkat kepuasan pelayanan publik statistik terpadu di kantor.',
        ]);

        $this->createSampleDocument([
            'document_name' => 'Laporan Keuangan',
            'extracted_text' => 'Realisasi anggaran belanja modal DIPA BPS kuartal pertama.',
        ]);

        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);
        $results = $searchService->search('pelayanan publik statistik');

        $this->assertCount(1, $results);
        $this->assertEquals($doc->id, $results->first()->id);
    }

    public function test_search_finds_document_by_name_and_document_number(): void
    {
        $doc1 = $this->createSampleDocument([
            'document_name' => 'Pedoman Teknis Manajemen Risiko Operasional',
            'document_number' => 'PED-BPS-MR-2026',
        ]);

        $doc2 = $this->createSampleDocument([
            'document_name' => 'Daftar Hadir Sosialisasi',
            'document_number' => 'DH/2026/099',
        ]);

        $searchService = app(DocumentSearchService::class);

        // Search by document name keyword
        $resultsByName = $searchService->search('Manajemen Risiko');
        $this->assertTrue($resultsByName->contains('id', $doc1->id));
        $this->assertFalse($resultsByName->contains('id', $doc2->id));

        // Search by document number
        $resultsByNumber = $searchService->search('DH/2026/099');
        $this->assertTrue($resultsByNumber->contains('id', $doc2->id));
        $this->assertFalse($resultsByNumber->contains('id', $doc1->id));
    }

    public function test_fuzzy_trigram_search_tolerates_minor_typos(): void
    {
        $doc = $this->createSampleDocument([
            'document_name' => 'Keputusan Pengangkatan Petugas Survei',
            'document_number' => 'SK-888',
        ]);

        $searchService = app(DocumentSearchService::class);

        // Salah ketik: "Keputussan" (double s)
        $results = $searchService->search('Keputussan');

        $this->assertTrue($results->contains('id', $doc->id));
    }

    public function test_search_excludes_soft_deleted_documents(): void
    {
        $doc = $this->createSampleDocument([
            'document_name' => 'Dokumen Rahasia BPS',
        ]);

        $searchService = app(DocumentSearchService::class);
        $this->assertTrue($searchService->search('Rahasia')->contains('id', $doc->id));

        // Soft delete the document
        $doc->delete();

        $resultsAfterDelete = $searchService->search('Rahasia');
        $this->assertFalse($resultsAfterDelete->contains('id', $doc->id));
    }

    public function test_search_sanitization_prevents_sql_injection_and_handles_malicious_input(): void
    {
        $this->createSampleDocument(['document_name' => 'Dokumen Pengujian Keamanan']);

        $searchService = app(DocumentSearchService::class);

        // AC-13: Injeksi SQL dan karakter khusus
        $maliciousQueries = [
            "'; DROP TABLE evidence_documents; --",
            '" OR ""="',
            "' UNION SELECT * FROM users --",
            "<script>alert('xss')</script>",
            '\\\\x00',
            '',
            '   ',
        ];

        foreach ($maliciousQueries as $query) {
            $results = $searchService->search($query);
            $this->assertNotNull($results);
        }

        // Pastikan tabel evidence_documents masih ada dan utuh
        $this->assertGreaterThan(0, EvidenceDocument::count());
    }

    public function test_search_handles_extremely_long_input_without_error(): void
    {
        $this->createSampleDocument(['document_name' => 'Dokumen Long Input Test']);

        $searchService = app(DocumentSearchService::class);

        // AC-13: Input 10.000 karakter
        $veryLongQuery = str_repeat('kata_sangat_panjang_yang_berulang ', 300); // ~10.200 karakter

        $results = $searchService->search($veryLongQuery);
        $this->assertNotNull($results);
    }

    /**
     * Slice 1 / TSK-702: Integrasi Pencarian pada Matriks Monitor.
     */
    public function test_monitor_matrix_renders_checkmarks_for_documents_found_via_search(): void
    {
        $lk = LembarKerja::where('code', 'ZI')->firstOrFail();
        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'ZI-PHASE7-SEARCH'],
            ['title' => 'Indikator Pengujian FTS', 'order_index' => 1]
        );

        $doc = $this->createSampleDocument([
            'document_name' => 'Laporan Hasil Evaluasi Pelayanan Statistik Terpadu',
            'extracted_text' => 'Teks hasil evaluasi PST BPS tahun 2026.',
        ]);

        DocumentMatch::create([
            'document_id' => $doc->id,
            'indicator_id' => $indicator->id,
            'status' => 'confirmed',
            'confidence_score' => 100.0,
            'match_method' => 'manual',
        ]);

        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'link_source' => 'admin',
        ]);

        /** @var MonitorMatrixService $matrixService */
        $matrixService = app(MonitorMatrixService::class);

        // Search via monitor matrix with FTS keyword
        $matrixData = $matrixService->getMatrixData([
            'period' => $lk->period,
            'search' => 'Pelayanan Statistik Terpadu',
        ]);

        $this->assertCount(1, $matrixData['rows']);
        $this->assertEquals($doc->id, $matrixData['rows'][0]['document_id']);
        $this->assertEquals('✔', $matrixData['rows'][0]['cells']['ZI']['symbol']);
    }

    /**
     * Slice 2: TSK-703 - Perekaman Jejak Audit Mutasi (AUDIT-01, AC-17).
     */
    public function test_audit_log_records_lembar_kerja_and_profile_mutations(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Create Lembar Kerja
        $lkCode = 'AUDIT-'.strtoupper(Str::random(6));
        $lk = LembarKerja::create([
            'code' => $lkCode,
            'name' => 'Lembar Kerja Uji Audit',
            'category' => 'internal',
            'period' => 2026,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'lk.created',
            'entity_type' => 'LembarKerja',
            'entity_id' => $lk->id,
        ]);

        // 2. Update Lembar Kerja
        $lk->update(['name' => 'Nama LK Baru Setelah Diupdate']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'lk.updated',
            'entity_type' => 'LembarKerja',
            'entity_id' => $lk->id,
        ]);

        // 3. Delete Lembar Kerja (Soft Delete)
        $lk->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'lk.deleted',
            'entity_type' => 'LembarKerja',
            'entity_id' => $lk->id,
        ]);

        // 4. Create LkImportProfile
        $profile = LkImportProfile::create([
            'name' => 'Profil Uji Audit',
            'lk_code' => 'ZI',
            'parsing_strategy' => 'single_item_per_row',
            'sheet_name' => 'Sheet1',
            'header_row' => 1,
            'column_mapping' => ['code' => 'A', 'name' => 'B'],
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'lk_profile.created',
            'entity_type' => 'LkImportProfile',
            'entity_id' => (string) $profile->id,
        ]);

        $profile->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'lk_profile.deleted',
            'entity_type' => 'LkImportProfile',
            'entity_id' => (string) $profile->id,
        ]);
    }

    public function test_audit_log_records_auth_login_and_logout_events(): void
    {
        // Event Login
        event(new Login('web', $this->admin, false));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'auth.login',
            'entity_type' => 'users',
            'entity_id' => (string) $this->admin->id,
        ]);

        // Event Logout
        event(new Logout('web', $this->admin));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'auth.logout',
            'entity_type' => 'users',
            'entity_id' => (string) $this->admin->id,
        ]);
    }

    public function test_audit_log_immutability_trigger_blocks_update_and_delete(): void
    {
        $logId = DB::table('audit_logs')->insertGetId([
            'user_id' => $this->superAdmin->id,
            'action' => 'test.immutable',
            'entity_type' => 'test',
            'entity_id' => '1',
            'created_at' => now(),
        ]);

        // Update must fail due to trigger (AUDIT-02)
        $updateFailed = false;
        try {
            DB::table('audit_logs')->where('id', $logId)->update(['action' => 'tampered.action']);
        } catch (\Throwable $e) {
            $updateFailed = true;
        }
        $this->assertTrue($updateFailed, 'Trigger anti-tampering gagal menolak mutasi UPDATE pada audit_logs.');

        // Delete must fail due to trigger (AUDIT-02)
        $deleteFailed = false;
        try {
            DB::table('audit_logs')->where('id', $logId)->delete();
        } catch (\Throwable $e) {
            $deleteFailed = true;
        }
        $this->assertTrue($deleteFailed, 'Trigger anti-tampering gagal menolak mutasi DELETE pada audit_logs.');
    }

    /**
     * Slice 3: TSK-704 - Filament AuditLogResource untuk Super Admin (AUDIT-03).
     */
    public function test_super_admin_can_access_audit_log_filament_resource_while_admin_and_pegawai_are_denied(): void
    {
        // Super admin diizinkan
        $responseSuperAdmin = $this->actingAs($this->superAdmin)->get('/admin/audit-logs');
        $responseSuperAdmin->assertOk();

        // Admin ditolak (RBAC.md)
        $responseAdmin = $this->actingAs($this->admin)->get('/admin/audit-logs');
        $responseAdmin->assertForbidden();

        // Pegawai ditolak (RBAC.md)
        $responsePegawai = $this->actingAs($this->pegawai)->get('/admin/audit-logs');
        $responsePegawai->assertForbidden();
    }

    /**
     * Slice 4: Fase 7 - Unduhan Aman, Penamaan Berkas Kode LK Resmi, & Pembersihan.
     */
    public function test_download_filename_uses_only_official_lk_codes_and_draft_when_no_official_links(): void
    {
        $lkX = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'Zona Integritas', 'category' => 'internal', 'is_active' => true]
        );
        $lkY = LembarKerja::firstOrCreate(
            ['code' => 'KIP', 'period' => 2026],
            ['name' => 'Keterbukaan Informasi Publik', 'category' => 'internal', 'is_active' => true]
        );

        $doc = $this->createSampleDocument([
            'document_name' => 'SK Program Kunjungan',
            'original_filename' => 'sk_kunjungan.pdf',
            'mime_type' => 'application/pdf',
            'approval_status' => 'menunggu',
        ]);

        /** @var EvidenceUploadService $uploadService */
        $uploadService = app(EvidenceUploadService::class);

        // Belum ada link resmi (hanya proposed LK X) -> awalan DRAFT
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lkX->id,
            'link_source' => 'proposed',
        ]);

        $this->assertSame('DRAFT_SK_Program_Kunjungan.pdf', $uploadService->buildDownloadFilename($doc->fresh()));

        // Admin menyetujui dengan LK Y (link_source='admin'), LK X tetap proposed -> nama hanya memuat Y (KIP)
        DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lkY->id,
            'link_source' => 'admin',
        ]);

        $this->assertSame('KIP_SK_Program_Kunjungan.pdf', $uploadService->buildDownloadFilename($doc->fresh()));
    }

    public function test_download_filename_sorts_alphabetically_limits_to_4_lks_and_deduplicates_backfill_indicators(): void
    {
        $codes = ['ZI', 'PEKPPP', 'KIP', 'SAKIP', 'DATA_SEKUNDER', 'DATA_SEKTORAL'];
        $lks = [];
        foreach ($codes as $code) {
            $lks[$code] = LembarKerja::firstOrCreate(
                ['code' => $code, 'period' => 2026],
                ['name' => "LK {$code}", 'category' => 'internal', 'is_active' => true]
            );
        }

        $doc = $this->createSampleDocument([
            'document_name' => 'SK Program Kunjungan',
            'original_filename' => 'sk.pdf',
            'mime_type' => 'application/pdf',
        ]);

        // Backfill membuat beberapa link per indikator pada LK ZI yang sama -> tidak boleh menggandakan kode ZI
        $ind1 = LkIndicator::firstOrCreate(['lk_id' => $lks['ZI']->id, 'code' => 'ZI-DL-1'], ['title' => 'Ind 1', 'order_index' => 1]);
        $ind2 = LkIndicator::firstOrCreate(['lk_id' => $lks['ZI']->id, 'code' => 'ZI-DL-2'], ['title' => 'Ind 2', 'order_index' => 2]);

        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $lks['ZI']->id, 'indicator_id' => $ind1->id, 'link_source' => 'backfill']);
        DocumentLkLink::create(['document_id' => $doc->id, 'lk_id' => $lks['ZI']->id, 'indicator_id' => $ind2->id, 'link_source' => 'backfill']);

        /** @var EvidenceUploadService $uploadService */
        $uploadService = app(EvidenceUploadService::class);
        $this->assertSame('ZI_SK_Program_Kunjungan.pdf', $uploadService->buildDownloadFilename($doc->fresh()));

        // Tambahkan 5 LK resmi lainnya sehingga total 6 LK resmi
        foreach (['PEKPPP', 'KIP', 'SAKIP', 'DATA_SEKUNDER', 'DATA_SEKTORAL'] as $idx => $code) {
            DocumentLkLink::create([
                'document_id' => $doc->id,
                'lk_id' => $lks[$code]->id,
                'link_source' => $idx % 2 === 0 ? 'admin' : 'drive_sync',
            ]);
        }

        // Urut abjad dari 6 kode: DATA_SEKTORAL, DATA_SEKUNDER, KIP, PEKPPP, SAKIP, ZI -> ambil maks 4 kode pertama
        $filename6 = $uploadService->buildDownloadFilename($doc->fresh());
        $this->assertSame('DATA_SEKTORAL_DATA_SEKUNDER_KIP_PEKPPP_SK_Program_Kunjungan.pdf', $filename6);
    }

    public function test_download_filename_sanitizes_illegal_characters_and_truncates_to_150_chars_keeping_extension(): void
    {
        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'Zona Integritas', 'category' => 'internal', 'is_active' => true]
        );

        $docIllegal = $this->createSampleDocument([
            'document_name' => "SK/BPS\\2026: *Program*? \"Kunjungan\" <Tim> |Uji|\x00\n\r\tAkhir",
            'original_filename' => 'dokumen.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
        DocumentLkLink::create(['document_id' => $docIllegal->id, 'lk_id' => $lk->id, 'link_source' => 'admin']);

        /** @var EvidenceUploadService $uploadService */
        $uploadService = app(EvidenceUploadService::class);
        $cleanName = $uploadService->buildDownloadFilename($docIllegal->fresh());

        $this->assertStringStartsWith('ZI_', $cleanName);
        $this->assertStringEndsWith('.docx', $cleanName);
        $this->assertSame(0, preg_match('/[\/\\\\:*?"<>|\x00-\x1F\x7F]/', $cleanName));

        // Nama sangat panjang (>200 karakter) -> dipotong <= 150 karakter dengan ekstensi .xlsx utuh
        $longTitle = 'Laporan_Evaluasi_Tahunan_'.str_repeat('SangatPanjangSekali_', 12);
        $docLong = $this->createSampleDocument([
            'document_name' => $longTitle,
            'original_filename' => 'tabel.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        DocumentLkLink::create(['document_id' => $docLong->id, 'lk_id' => $lk->id, 'link_source' => 'admin']);

        $truncatedName = $uploadService->buildDownloadFilename($docLong->fresh());
        $this->assertLessThanOrEqual(150, strlen($truncatedName));
        $this->assertLessThanOrEqual(150, mb_strlen($truncatedName));
        $this->assertStringStartsWith('ZI_Laporan_Evaluasi_Tahunan_', $truncatedName);
        $this->assertStringEndsWith('.xlsx', $truncatedName);
    }

    public function test_download_endpoint_enforces_visibility_rules_and_streams_renamed_upload_or_redirects_links(): void
    {
        Storage::fake('private');

        $otherPegawai = User::firstOrCreate(
            ['email' => 'pegawai.other.phase7@bps.go.id'],
            [
                'name' => 'Pegawai Lain Phase 7',
                'password' => bcrypt('password'),
                'role' => 'pegawai',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        $lk = LembarKerja::firstOrCreate(
            ['code' => 'ZI', 'period' => 2026],
            ['name' => 'Zona Integritas', 'category' => 'internal', 'is_active' => true]
        );

        $filePath = 'evidence/'.Str::uuid().'.pdf';
        Storage::disk('private')->put($filePath, '%PDF-1.4 Konten Uji Download');

        $pendingDoc = $this->createSampleDocument([
            'document_name' => 'SK Tim Kerja',
            'file_path' => $filePath,
            'original_filename' => 'sk_asli.pdf',
            'mime_type' => 'application/pdf',
            'uploaded_by' => $this->pegawai->id,
            'approval_status' => 'menunggu',
            'source_type' => 'upload',
        ]);

        // 1. Pemilik dokumen 'menunggu' boleh mengunduh (200) dengan nama DRAFT_...
        $ownerResp = $this->actingAs($this->pegawai)->get("/evidence/download/{$pendingDoc->id}");
        $ownerResp->assertOk();
        $this->assertStringContainsString('DRAFT_SK_Tim_Kerja.pdf', (string) $ownerResp->headers->get('content-disposition'));

        // 2. Pegawai lain untuk dokumen non-disetujui ('menunggu') ditolak (403)
        $this->actingAs($otherPegawai)->get("/evidence/download/{$pendingDoc->id}")->assertForbidden();

        // 3. Admin dan Super Admin boleh mengunduh dokumen 'menunggu' (200)
        $this->actingAs($this->admin)->get("/evidence/download/{$pendingDoc->id}")->assertOk();
        $this->actingAs($this->superAdmin)->get("/evidence/download/{$pendingDoc->id}")->assertOk();

        // 4. Setelah dokumen disetujui dengan LK ZI, semua pegawai aktif boleh mengunduh dengan awalan ZI_
        $pendingDoc->update(['approval_status' => 'disetujui']);
        DocumentLkLink::create(['document_id' => $pendingDoc->id, 'lk_id' => $lk->id, 'link_source' => 'admin']);

        $otherApprovedResp = $this->actingAs($otherPegawai)->get("/evidence/download/{$pendingDoc->id}");
        $otherApprovedResp->assertOk();
        $this->assertStringContainsString('ZI_SK_Tim_Kerja.pdf', (string) $otherApprovedResp->headers->get('content-disposition'));

        // 5. Dokumen soft-deleted -> 404
        $pendingDoc->delete();
        $this->actingAs($this->superAdmin)->get("/evidence/download/{$pendingDoc->id}")->assertNotFound();

        // 6. Dokumen source_type 'link' & 'drive' -> redirect aman ke external_link, abaikan input query user
        $linkDoc = EvidenceDocument::create([
            'document_name' => 'Tautan Video ZI',
            'year' => 2026,
            'source_type' => 'link',
            'external_link' => 'https://drive.google.com/file/d/12345/view',
            'approval_status' => 'disetujui',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->pegawai->id,
        ]);

        $driveDoc = EvidenceDocument::create([
            'document_name' => 'Berkas Sinkronisasi Drive',
            'year' => 2026,
            'source_type' => 'drive',
            'drive_file_id' => 'drive_file_'.Str::random(8),
            'external_link' => 'https://drive.google.com/file/d/67890/view',
            'approval_status' => 'disetujui',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->admin->id,
        ]);

        $this->actingAs($otherPegawai)
            ->get("/evidence/download/{$linkDoc->id}?url=https://evil.example.com&redirect=https://evil.example.com")
            ->assertRedirect('https://drive.google.com/file/d/12345/view');

        $this->actingAs($otherPegawai)
            ->get("/evidence/download/{$driveDoc->id}")
            ->assertRedirect('https://drive.google.com/file/d/67890/view');

        // 7. Tolak skema selain http/https atau URL tidak valid (mis. http://tanpa-tld atau skema javascript:)
        $invalidUrlDoc = EvidenceDocument::create([
            'document_name' => 'Tautan Host Tidak Valid',
            'year' => 2026,
            'source_type' => 'link',
            'external_link' => 'http://javascript:alert(1)',
            'approval_status' => 'disetujui',
            'processing_status' => 'selesai',
            'uploaded_by' => $this->pegawai->id,
        ]);

        $badResp = $this->actingAs($this->admin)->get("/evidence/download/{$invalidUrlDoc->id}");
        $this->assertContains($badResp->getStatusCode(), [404, 422]);

        DB::beginTransaction();
        try {
            DB::statement('ALTER TABLE evidence_documents DROP CONSTRAINT IF EXISTS chk_document_external_link');

            foreach (['javascript:alert(1)', 'ftp://files.bps.go.id/doc.pdf', 'data:text/html;base64,PHNjcmlwdD4='] as $unsafeUrl) {
                DB::table('evidence_documents')->where('id', $invalidUrlDoc->id)->update([
                    'external_link' => $unsafeUrl,
                ]);
                $unsafeResp = $this->actingAs($this->admin)->get("/evidence/download/{$invalidUrlDoc->id}");
                $this->assertContains($unsafeResp->getStatusCode(), [404, 422]);
            }
        } finally {
            DB::rollBack();
        }
    }

    public function test_upload_wraps_document_and_proposed_links_in_single_transaction_and_revise_handles_unique_violation(): void
    {
        Storage::fake('private');
        Queue::fake();

        /** @var EvidenceUploadService $uploadService */
        $uploadService = app(EvidenceUploadService::class);

        $file = UploadedFile::fake()->createWithContent('dok_tx.pdf', '%PDF-1.4 Transactional Upload Test '.uniqid());

        // 1. Jika pembuatan DocumentLkLink gagal (FK lk_id tidak ada), EvidenceDocument ikut rollback & berkas yatim dihapus
        $nonExistentLkId = (string) Str::uuid();
        try {
            $uploadService->upload($file, [
                'document_name' => 'Dokumen Gagal Link',
                'year' => 2026,
            ], $this->pegawai, [$nonExistentLkId]);
            $this->fail('Seharusnya gagal karena FK lk_id tidak ditemukan.');
        } catch (\Throwable) {
            // Expected
        }

        $this->assertDatabaseMissing('evidence_documents', ['document_name' => 'Dokumen Gagal Link']);
        $this->assertCount(0, Storage::disk('private')->allFiles('evidence'));

        // 2. reviseSubmission menangani UniqueConstraintViolationException -> hapus berkas baru & lempar DuplicateDocumentException
        $existingContent = '%PDF-1.4 Existing Document For Revise Conflict '.uniqid();
        $existingDoc = $uploadService->upload(
            UploadedFile::fake()->createWithContent('existing.pdf', $existingContent),
            ['document_name' => 'Dokumen Eksisting', 'year' => 2026],
            $this->pegawai
        );

        $revisingDoc = $uploadService->upload(
            UploadedFile::fake()->createWithContent('revising.pdf', '%PDF-1.4 Initial Revise Content '.uniqid()),
            ['document_name' => 'Dokumen Akan Direvisi', 'year' => 2026],
            $this->pegawai
        );
        $revisingDoc->update(['approval_status' => 'revisi', 'approval_notes' => 'Ganti lampiran']);

        EvidenceDocument::updating(function (EvidenceDocument $model) use ($existingDoc): void {
            // Simulasikan race condition saat update: ubah file_hash ke hash milik existingDoc tepat saat query update berjalan
            $model->file_hash = $existingDoc->file_hash;
        });

        try {
            $newFile = UploadedFile::fake()->createWithContent('new_revise.pdf', '%PDF-1.4 New Unique Content '.uniqid());
            $uploadService->reviseSubmission($revisingDoc, $this->pegawai, ['document_name' => 'Revisi Konflik'], $newFile);
            $this->fail('Seharusnya melempar DuplicateDocumentException saat terjadi UniqueConstraintViolation pada reviseSubmission.');
        } catch (DuplicateDocumentException $e) {
            $this->assertSame($existingDoc->id, $e->existingDocument->id);
        } finally {
            EvidenceDocument::flushEventListeners();
        }

        // Hanya 2 berkas asli yang ada di storage privat (berkas ketiga yang gagal saat revisi sudah dihapus)
        $this->assertCount(2, Storage::disk('private')->allFiles('evidence'));
    }

    public function test_demo_seeders_run_without_error_and_seed_approved_documents_with_official_links(): void
    {
        DB::beginTransaction();
        try {
            $this->seed(InitialLkSeeder::class);
            $this->seed(BpsDemoUserSeeder::class);
            $this->seed(DemoIndicatorAndSampleDocumentsSeeder::class);
            $this->seed(BpsAuditLogDemoSeeder::class);

            $approvedDemoDocs = EvidenceDocument::where('approval_status', 'disetujui')->get();
            $this->assertNotEmpty($approvedDemoDocs);

            $officialLinksCount = DocumentLkLink::whereIn('link_source', DocumentLkLink::OFFICIAL_SOURCES)->count();
            $this->assertGreaterThan(0, $officialLinksCount);
        } finally {
            DB::rollBack();
        }
    }
}
