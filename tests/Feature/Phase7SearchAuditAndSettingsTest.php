<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkEvidenceItem;
use App\Models\LkImportProfile;
use App\Models\LkIndicator;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Matching\MatchingEngineService;
use App\Services\Monitor\MonitorMatrixService;
use App\Services\Search\DocumentSearchService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        SystemSetting::where('key', 'matching.similarity_threshold')->update(['value' => 75.0]);
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
     * Slice 4: TSK-705 - Pengaturan Sistem Dinamis (MATCH-11, BD-02).
     */
    public function test_super_admin_can_access_and_update_system_settings_while_others_are_denied(): void
    {
        // Super admin diizinkan membuka halaman pengaturan
        $responseSuperAdmin = $this->actingAs($this->superAdmin)->get('/admin/system-settings');
        $responseSuperAdmin->assertOk();

        // Admin dan Pegawai ditolak
        $responseAdmin = $this->actingAs($this->admin)->get('/admin/system-settings');
        $responseAdmin->assertForbidden();

        $responsePegawai = $this->actingAs($this->pegawai)->get('/admin/system-settings');
        $responsePegawai->assertForbidden();

        // Super Admin memperbarui setting
        $this->actingAs($this->superAdmin);
        $setting = SystemSetting::findOrFail('matching.similarity_threshold');
        $setting->update([
            'value' => 80.0,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->assertEquals(80.0, (float) SystemSetting::get('matching.similarity_threshold'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'setting.updated',
            'entity_type' => 'SystemSetting',
            'entity_id' => 'matching.similarity_threshold',
        ]);
    }

    public function test_dynamic_similarity_threshold_setting_affects_matching_engine_immediately(): void
    {
        // Reset setting ke 80.0 (di atas skor 59.39%)
        $setting = SystemSetting::firstOrCreate(
            ['key' => 'matching.similarity_threshold'],
            ['description' => 'Test threshold', 'value' => 80.0]
        );
        $setting->update(['value' => 80.0]);

        $lk = LembarKerja::where('code', 'ZI')->firstOrFail();
        $indicator = LkIndicator::firstOrCreate(
            ['lk_id' => $lk->id, 'code' => 'ZI-PHASE7-DYNAMIC'],
            ['title' => 'Pengujian Pengaturan Dinamis', 'order_index' => 1]
        );
        LkEvidenceItem::firstOrCreate(
            ['indicator_id' => $indicator->id, 'resolved_name' => 'Laporan Evaluasi Kinerja Organisasi BPS'],
            ['raw_template_name' => 'Laporan Evaluasi Kinerja Organisasi BPS', 'order_index' => 1]
        );

        $doc = $this->createSampleDocument([
            'document_name' => 'Evaluasi Kinerja Organisasi Tahunan BPS',
            'extracted_text' => 'Ringkasan pencapaian kinerja tahun 2026.',
        ]);

        /** @var MatchingEngineService $engine */
        $engine = app(MatchingEngineService::class);
        $candidates = $engine->matchDocument($doc);

        // Dengan threshold 80.0, dokumen dengan kemiripan ~59.4% tidak akan lolos menjadi auto
        $this->assertEmpty($candidates);

        // Turunkan threshold secara dinamis ke 55.0
        $setting->update(['value' => 55.0]);

        $candidatesLowered = $engine->matchDocument($doc);
        $this->assertNotEmpty($candidatesLowered);
        $this->assertEquals('auto', $candidatesLowered[0]->status);
    }
}
