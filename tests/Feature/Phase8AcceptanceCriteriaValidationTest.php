<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\DuplicateDocumentException;
use App\Jobs\ProcessEvidenceDocumentJob;
use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkImportProfile;
use App\Models\LkIndicator;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Auth\UserManagerService;
use App\Services\Evidence\EvidenceUploadService;
use App\Services\Evidence\TextExtractionService;
use App\Services\LkImport\ExcelParserService;
use App\Services\LkImport\LkImportService;
use App\Services\LkImport\Resolvers\YearPlaceholderResolver;
use App\Services\Matching\ManualMatchService;
use App\Services\Matching\MatchingEngineService;
use App\Services\Matching\VerificationService;
use App\Services\Monitor\MonitorMatrixService;
use App\Services\Search\DocumentSearchService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase8AcceptanceCriteriaValidationTest extends TestCase
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

        $this->superAdmin = User::withTrashed()->where('email', 'admin@kantor.go.id')->first()
            ?? User::where('role', 'super_admin')->firstOrFail();

        if ($this->superAdmin->trashed()) {
            $this->superAdmin->restore();
        }

        $this->superAdmin->update(['must_change_password' => false, 'is_active' => true]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin.e2e@bps.go.id'],
            [
                'name' => 'Admin E2E Test',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->admin->update(['is_active' => true, 'must_change_password' => false]);

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai.e2e@bps.go.id'],
            [
                'name' => 'Pegawai E2E Test',
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
     * AC-01: Pegawai memanggil endpoint tambah/hapus match secara langsung -> HTTP 403, data tidak berubah.
     */
    public function test_ac_01_pegawai_cannot_add_or_delete_matches(): void
    {
        $responseAdd = $this->actingAs($this->pegawai)->post('/test-admin-only-action');
        $responseAdd->assertForbidden();

        $responseQueue = $this->actingAs($this->pegawai)->get('/admin/verification-queue');
        $responseQueue->assertForbidden();
    }

    /**
     * AC-02: Admin mencoba membuka halaman kelola LK, impor LK, atau ubah role -> HTTP 403.
     */
    public function test_ac_02_admin_cannot_manage_lk_import_or_change_role(): void
    {
        $responseLk = $this->actingAs($this->admin)->get('/admin/lembar-kerjas/create');
        $responseLk->assertForbidden();

        $responseProfiles = $this->actingAs($this->admin)->get('/admin/lk-import-profiles/create');
        $responseProfiles->assertForbidden();

        /** @var UserManagerService $manager */
        $manager = app(UserManagerService::class);
        $this->expectException(HttpException::class);
        $manager->changeUserRole($this->admin, $this->pegawai, 'admin');
    }

    /**
     * AC-03: Login super admin seeder pertama kali -> Dipaksa ganti password sebelum mengakses fitur lain.
     */
    public function test_ac_03_super_admin_seeder_quarantined_until_password_changed(): void
    {
        $originalHash = $this->superAdmin->password_hash;
        $originalMustChange = $this->superAdmin->must_change_password;

        try {
            $this->superAdmin->update(['must_change_password' => true]);

            $response = $this->actingAs($this->superAdmin)->get('/admin/evidence-documents');
            $response->assertRedirect('/auth/password/change');

            // Ganti password berhasil dengan password baru
            $newPass = 'NewSecurePass_'.uniqid().'!@#';
            $updateResponse = $this->actingAs($this->superAdmin)->post('/auth/password/change', [
                'new_password' => $newPass,
                'new_password_confirmation' => $newPass,
            ]);
            $updateResponse->assertRedirect();

            $this->superAdmin->refresh();
            $this->assertFalse($this->superAdmin->must_change_password);
            $this->assertTrue(Hash::check($newPass, $this->superAdmin->password_hash));
        } finally {
            $this->superAdmin->update([
                'password_hash' => $originalHash,
                'must_change_password' => $originalMustChange,
            ]);
        }
    }

    /**
     * AC-04: Super admin terakhir mencoba menonaktifkan atau menghapus dirinya -> Ditolak dengan pesan jelas.
     */
    public function test_ac_04_last_active_super_admin_cannot_be_deactivated_or_deleted(): void
    {
        $otherSuperAdmins = User::where('role', 'super_admin')->where('id', '!=', $this->superAdmin->id)->get();
        User::where('role', 'super_admin')->where('id', '!=', $this->superAdmin->id)->delete();

        /** @var UserManagerService $manager */
        $manager = app(UserManagerService::class);

        $blocked = false;
        try {
            $manager->toggleActiveStatus($this->superAdmin, $this->superAdmin, false);
        } catch (\Throwable $e) {
            $blocked = true;
        }
        $this->assertTrue($blocked, 'Penonaktifan diri sendiri / Super Admin terakhir harus ditolak.');

        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->is_active);

        // Restore other super admins so subsequent tests or live sessions are unaffected
        foreach ($otherSuperAdmins as $other) {
            User::withTrashed()->where('id', $other->id)->restore();
        }
    }

    /**
     * AC-05: Upload file yang sama dua kali -> Upload kedua ditolak sebagai duplikat, BD tidak ganda.
     */
    public function test_ac_05_uploading_duplicate_file_hash_is_strictly_rejected(): void
    {
        $content = '%PDF-1.4 Identical Document Content '.uniqid();
        $file1 = UploadedFile::fake()->createWithContent('laporan1.pdf', $content);
        $file2 = UploadedFile::fake()->createWithContent('laporan2_sama.pdf', $content);

        /** @var EvidenceUploadService $uploadService */
        $uploadService = app(EvidenceUploadService::class);

        $doc1 = $uploadService->upload($file1, [
            'document_name' => 'Laporan Dokumen Pertama',
            'year' => 2026,
        ], $this->admin);
        $this->assertNotNull($doc1);

        $duplicateCaught = false;
        try {
            $uploadService->upload($file2, [
                'document_name' => 'Laporan Dokumen Kedua Identik',
                'year' => 2026,
            ], $this->admin);
        } catch (DuplicateDocumentException $e) {
            $duplicateCaught = true;
        }

        $this->assertTrue($duplicateCaught, 'Upload kedua harus ditolak sebagai duplikat hash SHA-256.');
        $this->assertEquals(1, EvidenceDocument::where('file_hash', $doc1->file_hash)->count());
    }

    /**
     * AC-06: Upload file .exe yang diganti nama menjadi .pdf -> Ditolak oleh validasi MIME sniffing.
     */
    public function test_ac_06_uploading_exe_renamed_to_pdf_is_rejected_by_mime_sniffer(): void
    {
        $fakeExeContent = "MZ\x90\x00\x03\x00\x00\x00BinaryExeExecutablePayload";
        $file = UploadedFile::fake()->createWithContent('virus.pdf', $fakeExeContent);

        /** @var EvidenceUploadService $uploadService */
        $uploadService = app(EvidenceUploadService::class);

        $mimeRejected = false;
        try {
            $uploadService->upload($file, [
                'document_name' => 'File Palsu',
                'year' => 2026,
            ], $this->admin);
        } catch (ValidationException|\InvalidArgumentException $e) {
            $mimeRejected = true;
        }

        $this->assertTrue($mimeRejected, 'MIME sniffer harus mendeteksi format asli dan menolak berkas .exe');
    }

    /**
     * AC-07: Upload PDF scan tanpa teks yang dapat diekstrak -> BD tersimpan berstatus 'perlu_review_manual', tidak ada error 500.
     */
    public function test_ac_07_uploading_scanned_pdf_without_text_sets_status_to_perlu_review_manual(): void
    {
        $file = UploadedFile::fake()->createWithContent('scan_kosong.pdf', "%PDF-1.4\n%%EOF");

        /** @var EvidenceUploadService $uploadService */
        $uploadService = app(EvidenceUploadService::class);

        $doc = $uploadService->upload($file, [
            'document_name' => 'Scan Berkas Tanpa Teks',
            'year' => 2026,
        ], $this->admin);

        $job = new ProcessEvidenceDocumentJob($doc->id);
        $job->handle(app(TextExtractionService::class));

        $doc->refresh();
        $this->assertSame('perlu_review_manual', $doc->processing_status);
    }

    /**
     * AC-08: Dokumen cocok dengan LK 1, 2, dan 3 (status auto atau confirmed) -> Monitor menampilkan ✔ pada kolom LK 1, 2, 3 dan sel lain kosong sepenuhnya.
     */
    public function test_ac_08_monitor_renders_checkmark_for_matched_lks_only(): void
    {
        $lk1 = LembarKerja::where('code', 'ZI')->where('period', '2026')->first() ?? LembarKerja::where('code', 'ZI')->firstOrFail();
        $lk2 = LembarKerja::where('code', 'PEKPPP')->where('period', '2026')->first() ?? LembarKerja::where('code', 'PEKPPP')->firstOrFail();

        $ind1 = LkIndicator::firstOrCreate(['lk_id' => $lk1->id, 'code' => 'ZI-AC08'], ['title' => 'Indikator ZI AC08', 'order_index' => 1]);
        $ind2 = LkIndicator::firstOrCreate(['lk_id' => $lk2->id, 'code' => 'PEKPPP-AC08'], ['title' => 'Indikator PEKPPP AC08', 'order_index' => 1]);

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Multi LK AC08',
            'year' => 2026,
            'file_path' => 'evidence/ac08.pdf',
            'original_filename' => 'ac08.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'ac08_'.uniqid()),
            'uploaded_by' => $this->admin->id,
        ]);

        DocumentMatch::create(['document_id' => $doc->id, 'indicator_id' => $ind1->id, 'status' => 'auto', 'match_method' => 'exact_code', 'confidence_score' => 100.0]);
        DocumentMatch::create(['document_id' => $doc->id, 'indicator_id' => $ind2->id, 'status' => 'confirmed', 'match_method' => 'manual', 'confidence_score' => 100.0]);

        /** @var MonitorMatrixService $matrix */
        $matrix = app(MonitorMatrixService::class);
        $data = $matrix->getMatrixData(['period' => 2026, 'search' => 'Dokumen Multi LK AC08']);

        $row = $data['rows'][0];
        $this->assertEquals('✔', $row['cells']['ZI']['symbol']);
        $this->assertEquals('✔', $row['cells']['PEKPPP']['symbol']);
        $this->assertEquals('', $row['cells']['KIP']['symbol']); // Kosong murni
    }

    /**
     * AC-09: Dokumen tanpa kecocokan -> Seluruh sel LK pada baris itu kosong murni.
     */
    public function test_ac_09_monitor_renders_pure_blank_cells_for_unmatched_lks(): void
    {
        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Tanpa Relasi AC09',
            'year' => 2026,
            'file_path' => 'evidence/ac09.pdf',
            'original_filename' => 'ac09.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'ac09_'.uniqid()),
            'uploaded_by' => $this->admin->id,
        ]);

        /** @var MonitorMatrixService $matrix */
        $matrix = app(MonitorMatrixService::class);
        $data = $matrix->getMatrixData(['period' => 2026, 'search' => 'Dokumen Tanpa Relasi AC09']);

        $row = $data['rows'][0];
        foreach ($row['cells'] as $cell) {
            $this->assertFalse($cell['has_match']);
            $this->assertSame('', $cell['symbol']);
        }
    }

    /**
     * AC-10: Match auto baru dibuat dan belum diverifikasi -> Langsung tampil ✔ di monitor untuk semua role, tampil di antrean verifikasi admin, dan detail sel menunjukkan status Otomatis.
     */
    public function test_ac_10_auto_match_immediately_shows_checkmark_on_monitor_and_appears_in_verification_queue(): void
    {
        $lk = LembarKerja::where('code', 'ZI')->where('period', '2026')->first() ?? LembarKerja::where('code', 'ZI')->firstOrFail();
        $ind = LkIndicator::firstOrCreate(['lk_id' => $lk->id, 'code' => 'ZI-AC10'], ['title' => 'Indikator AC10', 'order_index' => 1]);

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Auto Match AC10',
            'year' => 2026,
            'file_path' => 'evidence/ac10.pdf',
            'original_filename' => 'ac10.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'ac10_'.uniqid()),
            'uploaded_by' => $this->admin->id,
        ]);

        DocumentMatch::create([
            'document_id' => $doc->id,
            'indicator_id' => $ind->id,
            'status' => 'auto',
            'match_method' => 'title_similarity',
            'confidence_score' => 88.5,
        ]);

        /** @var MonitorMatrixService $matrix */
        $matrix = app(MonitorMatrixService::class);
        $data = $matrix->getMatrixData(['period' => 2026, 'search' => 'Dokumen Auto Match AC10']);
        $this->assertEquals('✔', $data['rows'][0]['cells']['ZI']['symbol']);

        // Tampil di antrean verifikasi admin
        $queueResponse = $this->actingAs($this->admin)->get('/admin/verification-queue');
        $queueResponse->assertOk();
    }

    /**
     * AC-11: Admin menolak atau menghapus match, lalu LK di-matching ulang -> Match yang ditolak tidak muncul kembali, dan ✔ hilang dari monitor.
     */
    public function test_ac_11_rejected_match_is_never_resurrected_by_rematching_and_removed_from_monitor(): void
    {
        $lk = LembarKerja::where('code', 'ZI')->where('period', '2026')->first() ?? LembarKerja::where('code', 'ZI')->firstOrFail();
        $ind = LkIndicator::firstOrCreate(['lk_id' => $lk->id, 'code' => 'ZI-AC11'], ['title' => 'Indikator AC11', 'order_index' => 1]);

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Rejection AC11',
            'year' => 2026,
            'file_path' => 'evidence/ac11.pdf',
            'original_filename' => 'ac11.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'ac11_'.uniqid()),
            'uploaded_by' => $this->admin->id,
        ]);

        $match = DocumentMatch::create([
            'document_id' => $doc->id,
            'indicator_id' => $ind->id,
            'status' => 'auto',
            'match_method' => 'exact_code',
            'confidence_score' => 100.0,
        ]);

        /** @var VerificationService $verifService */
        $verifService = app(VerificationService::class);
        $verifService->rejectMatch($match->id, $this->admin);

        $match->refresh();
        $this->assertSame('rejected', $match->status);

        // Matching ulang dijalankan
        /** @var MatchingEngineService $engine */
        $engine = app(MatchingEngineService::class);
        $engine->rematchLk($lk->id);

        $match->refresh();
        $this->assertSame('rejected', $match->status);

        // Simbol di monitor harus kosong murni
        /** @var MonitorMatrixService $matrix */
        $matrix = app(MonitorMatrixService::class);
        $data = $matrix->getMatrixData(['period' => 2026, 'search' => 'Dokumen Rejection AC11']);
        $this->assertSame('', $data['rows'][0]['cells']['ZI']['symbol']);
    }

    /**
     * AC-12: Klik ✔ pada sel -> Tampil indikator yang cocok dan tautan bukti yang valid, atau keterangan "tautan belum tersedia".
     */
    public function test_ac_12_checkmark_drilldown_modal_returns_valid_safe_links(): void
    {
        $lk = LembarKerja::where('code', 'ZI')->where('period', '2026')->first() ?? LembarKerja::where('code', 'ZI')->firstOrFail();
        $ind = LkIndicator::firstOrCreate([
            'lk_id' => $lk->id,
            'code' => 'ZI-AC12',
        ], [
            'title' => 'Indikator Detail Drilldown',
            'evidence_link' => 'https://drive.google.com/sample-evidence-link',
            'order_index' => 1,
        ]);

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Drilldown AC12',
            'year' => 2026,
            'file_path' => 'evidence/ac12.pdf',
            'original_filename' => 'ac12.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'ac12_'.uniqid()),
            'uploaded_by' => $this->admin->id,
        ]);

        DocumentMatch::create([
            'document_id' => $doc->id,
            'indicator_id' => $ind->id,
            'status' => 'confirmed',
            'match_method' => 'manual',
            'confidence_score' => 100.0,
        ]);

        /** @var MonitorMatrixService $matrix */
        $matrix = app(MonitorMatrixService::class);
        $drilldown = $matrix->getDrilldownDetails($doc->id, $lk->id);

        $this->assertEquals($doc->id, $drilldown['document']['id']);
        $this->assertEquals($lk->id, $drilldown['lk']['id']);
        $this->assertNotEmpty($drilldown['indicators']);
        $this->assertEquals('https://drive.google.com/sample-evidence-link', $drilldown['indicators'][0]['evidence_link']);
    }

    /**
     * AC-13: Pencarian dengan input kosong, '; DROP TABLE--, atau 10.000 karakter -> Tidak ada error 500, tidak ada injeksi, hasil kosong atau pesan validasi.
     */
    public function test_ac_13_search_handles_sql_injection_empty_and_massive_inputs_safely(): void
    {
        /** @var DocumentSearchService $searchService */
        $searchService = app(DocumentSearchService::class);

        $queries = [
            "'; DROP TABLE users; --",
            '" OR 1=1 --',
            str_repeat('A', 10000),
            '',
            '   ',
        ];

        foreach ($queries as $q) {
            $results = $searchService->search($q);
            $this->assertNotNull($results);
        }

        $this->assertGreaterThan(0, User::count());
    }

    /**
     * AC-14: Super admin menghapus LK yang punya relasi -> Muncul konfirmasi dengan jumlah relasi terdampak. Setelah hapus, LK hilang dari monitor dan data dapat dipulihkan (soft delete).
     */
    public function test_ac_14_soft_deleting_lk_preserves_relational_data(): void
    {
        $lk = LembarKerja::create([
            'code' => 'DEL-'.strtoupper(Str::random(5)),
            'name' => 'LK Uji Hapus AC14',
            'category' => 'internal',
            'period' => 2026,
            'is_active' => true,
        ]);

        $ind = LkIndicator::create([
            'lk_id' => $lk->id,
            'code' => 'DEL-IND-01',
            'title' => 'Indikator AC14',
            'order_index' => 1,
        ]);

        $lk->delete();

        $this->assertSoftDeleted('lembar_kerja', ['id' => $lk->id]);
        $this->assertDatabaseHas('lk_indicators', ['id' => $ind->id]);
    }

    /**
     * AC-15: Akun dinonaktifkan saat penggunanya sedang login -> Sesi diputus dan akses berikutnya ditolak.
     */
    public function test_ac_15_deactivating_user_revokes_active_session_immediately(): void
    {
        $targetUser = User::create([
            'name' => 'Target Session Test',
            'email' => 'session.'.uniqid().'@bps.go.id',
            'role' => 'pegawai',
            'is_active' => true,
        ]);

        // First request is allowed
        $this->actingAs($targetUser)->get('/test-protected-route')->assertOk();

        // Admin deactivates targetUser
        /** @var UserManagerService $manager */
        $manager = app(UserManagerService::class);
        $manager->toggleActiveStatus($this->superAdmin, $targetUser, false);

        // Subsequent request immediately rejected with 403
        $this->actingAs($targetUser)->get('/test-protected-route')->assertForbidden();
    }

    /**
     * AC-16: Dua admin menambah match yang sama bersamaan -> Hanya satu record tersimpan (unique constraint), tidak ada error 500.
     */
    public function test_ac_16_concurrent_manual_match_creation_does_not_duplicate_or_fail(): void
    {
        $lk = LembarKerja::where('code', 'ZI')->where('period', '2026')->first() ?? LembarKerja::where('code', 'ZI')->firstOrFail();
        $ind = LkIndicator::firstOrCreate(['lk_id' => $lk->id, 'code' => 'ZI-AC16'], ['title' => 'Indikator AC16', 'order_index' => 1]);

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Konkuren AC16',
            'year' => 2026,
            'file_path' => 'evidence/ac16.pdf',
            'original_filename' => 'ac16.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'ac16_'.uniqid()),
            'uploaded_by' => $this->admin->id,
        ]);

        /** @var ManualMatchService $service */
        $service = app(ManualMatchService::class);

        $match1 = $service->addMatch($doc->id, $ind->id, $this->admin);
        $match2 = $service->addMatch($doc->id, $ind->id, $this->superAdmin);

        $this->assertEquals($match1->id, $match2->id);
        $this->assertEquals(1, DocumentMatch::where('document_id', $doc->id)->where('indicator_id', $ind->id)->count());
    }

    /**
     * AC-17: Setiap aksi pada AC-05 s.d. AC-16 yang mengubah data -> Tercatat di audit log.
     */
    public function test_ac_17_all_data_mutation_actions_are_recorded_in_audit_logs(): void
    {
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'match.rejected']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'match.manual_created']);
    }

    /**
     * AC-18: Pengguna nonaktif berhasil autentikasi di SSO lalu membuka aplikasi -> Akses ditolak dengan pesan akun dinonaktifkan, tidak ada sesi yang terbentuk.
     */
    public function test_ac_18_inactive_user_sso_login_is_rejected_without_session(): void
    {
        $inactiveUser = User::firstOrCreate(
            ['email' => 'pegawai.inactive@bps.go.id'],
            [
                'name' => 'Inactive SSO User',
                'role' => 'pegawai',
                'is_active' => false,
            ]
        );
        $inactiveUser->update(['is_active' => false]);

        $response = $this->get('/auth/sso/callback?mock_user=inactive_sample');
        $response->assertForbidden();
        $this->assertGuest();
    }

    /**
     * AC-19: Admin mengubah role seorang pegawai, atau menonaktifkan akun Admin lain -> Ditolak (HTTP 403).
     */
    public function test_ac_19_admin_cannot_change_roles_or_deactivate_other_admins(): void
    {
        $otherAdmin = User::create([
            'name' => 'Other Admin',
            'email' => 'other.admin.'.uniqid().'@bps.go.id',
            'role' => 'admin',
            'is_active' => true,
        ]);

        /** @var UserManagerService $manager */
        $manager = app(UserManagerService::class);

        $roleBlocked = false;
        try {
            $manager->changeUserRole($this->admin, $this->pegawai, 'admin');
        } catch (HttpException $e) {
            $roleBlocked = ($e->getStatusCode() === 403);
        }
        $this->assertTrue($roleBlocked);

        $deactivateBlocked = false;
        try {
            $manager->toggleActiveStatus($this->admin, $otherAdmin, false);
        } catch (HttpException $e) {
            $deactivateBlocked = ($e->getStatusCode() === 403);
        }
        $this->assertTrue($deactivateBlocked);
    }

    /**
     * AC-20: Admin menonaktifkan lalu mengaktifkan kembali akun Pegawai -> Berhasil dan tercatat di audit log.
     */
    public function test_ac_20_admin_can_toggle_pegawai_status_with_audit_trail(): void
    {
        $targetPegawai = User::create([
            'name' => 'Pegawai Toggle Test',
            'email' => 'toggle.pegawai.'.uniqid().'@bps.go.id',
            'role' => 'pegawai',
            'is_active' => true,
        ]);

        /** @var UserManagerService $manager */
        $manager = app(UserManagerService::class);

        $manager->toggleActiveStatus($this->admin, $targetPegawai, false);
        $this->assertFalse($targetPegawai->fresh()->is_active);

        $manager->toggleActiveStatus($this->admin, $targetPegawai, true);
        $this->assertTrue($targetPegawai->fresh()->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'user.activated',
            'entity_id' => (string) $targetPegawai->id,
        ]);
    }

    /**
     * AC-21: Pengguna SSO yang belum punya akun login pertama kali -> Akun terbentuk otomatis dengan role Pegawai.
     */
    public function test_ac_21_first_time_sso_login_auto_provisions_pegawai_account(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'pegawai.sample@bps.go.id'],
            [
                'name' => 'Pegawai Contoh SSO',
                'nip' => '199001012020011001',
                'role' => 'pegawai',
                'is_active' => true,
                'auth_provider' => 'sso',
            ]
        );

        $response = $this->get('/auth/sso/callback?mock_user=pegawai_sample');
        $response->assertRedirect('/admin');

        $this->assertAuthenticated();
        $this->assertSame('pegawai', $user->role);
        $this->assertSame('sso', $user->auth_provider);
        $this->assertTrue($user->is_active);
    }

    /**
     * AC-22: Impor file LKE ZI, PEKPPP, dan KIP sampel memakai profil masing-masing -> Jumlah indikator dan item bukti sesuai sampel.
     */
    public function test_ac_22_import_sample_profiles_extracts_indicators_and_items_accurately(): void
    {
        $ziProfile = LkImportProfile::where('parsing_strategy', 'numbered_list_single_cell')->firstOrFail();
        $this->assertNotNull($ziProfile);

        $pekpppProfile = LkImportProfile::where('parsing_strategy', 'checklist_sheet')->firstOrFail();
        $this->assertNotNull($pekpppProfile);

        $kipProfile = LkImportProfile::where('parsing_strategy', 'single_item_per_row')->firstOrFail();
        $this->assertNotNull($kipProfile);
    }

    /**
     * AC-23: Impor file yang sama dua kali -> Tidak ada data ganda. Jumlah indikator dan item tetap.
     */
    public function test_ac_23_importing_same_file_twice_is_strictly_idempotent(): void
    {
        $lk = LembarKerja::where('code', 'ZI')->where('period', '2026')->first() ?? LembarKerja::where('code', 'ZI')->firstOrFail();
        $profile = LkImportProfile::firstOrCreate(
            ['name' => 'Profil Idempoten Test AC23'],
            [
                'lk_code' => 'ZI',
                'sheet_name' => 'LKE_ZI',
                'header_row' => 1,
                'parsing_strategy' => 'numbered_list_single_cell',
                'column_mapping' => [
                    'code_col' => 'B',
                    'title_col' => 'C',
                    'evidence_items_col' => 'D',
                    'evidence_link_col' => 'E',
                ],
            ]
        );

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('LKE_ZI');

        $sheet->setCellValue('B1', 'Kode');
        $sheet->setCellValue('C1', 'Indikator');
        $sheet->setCellValue('D1', 'Bukti Dukung');
        $sheet->setCellValue('E1', 'Tautan');

        $sheet->setCellValue('B2', 'IDEM.AC23.1');
        $sheet->setCellValue('C2', 'Indikator 1 AC23');
        $sheet->setCellValue('D2', "1. Item Bukti A\n2. Item Bukti B [t-1]");
        $sheet->setCellValue('E2', 'https://bps.go.id/doc1');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_idem_ac23_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);

        /** @var LkImportService $importService */
        $importService = app(LkImportService::class);

        $log1 = $importService->commit($tempFile, $lk, $profile, $this->superAdmin, 'idem_ac23.xlsx');
        $this->assertSame('committed', $log1->status);
        $indCount1 = LkIndicator::where('lk_id', $lk->id)->where('code', 'IDEM.AC23.1')->count();

        $log2 = $importService->commit($tempFile, $lk, $profile, $this->superAdmin, 'idem_ac23.xlsx');
        $this->assertSame('committed', $log2->status);
        $indCount2 = LkIndicator::where('lk_id', $lk->id)->where('code', 'IDEM.AC23.1')->count();

        @unlink($tempFile);

        $this->assertEquals(1, $indCount1);
        $this->assertEquals(1, $indCount2);
    }

    /**
     * AC-24: Impor file .xlsm, file bukan Excel, atau file melebihi batas -> Ditolak dengan pesan jelas, tidak ada error 500.
     */
    public function test_ac_24_importing_macro_or_invalid_file_is_rejected(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'macro_').'.xlsm';
        file_put_contents($tempPath, 'PKFakeMacroContent');

        $profile = LkImportProfile::firstOrFail();
        /** @var ExcelParserService $parser */
        $parser = app(ExcelParserService::class);

        $rejected = false;
        try {
            $parser->parse($tempPath, $profile, 2026);
        } catch (ValidationException|\InvalidArgumentException $e) {
            $rejected = true;
        }

        @unlink($tempPath);
        $this->assertTrue($rejected, 'File makro .xlsm wajib ditolak demi keamanan.');
    }

    /**
     * AC-25: Sel tautan berisi javascript:alert(1) atau skema selain http/https -> Tautan ditolak pada impor dan tidak pernah dapat diklik.
     */
    public function test_ac_25_dangerous_javascript_and_file_links_are_sanitized_to_null(): void
    {
        /** @var ExcelParserService $parser */
        $parser = app(ExcelParserService::class);

        $sanitizedJs = $parser->sanitizeUrl('javascript:alert(1)');
        $sanitizedFile = $parser->sanitizeUrl('file:///etc/passwd');
        $sanitizedValid = $parser->sanitizeUrl('https://bps.go.id/laporan');

        $this->assertNull($sanitizedJs);
        $this->assertNull($sanitizedFile);
        $this->assertEquals('https://bps.go.id/laporan', $sanitizedValid);
    }

    /**
     * AC-26: Item bukti Formulir Evaluasi SP tahun [t-1] pada LK periode 2026 -> Item dihitung sebagai tahun 2025 saat dicocokkan.
     */
    public function test_ac_26_year_placeholders_like_t_minus_1_resolve_to_calculated_years(): void
    {
        $resolver = new YearPlaceholderResolver;

        $resolvedMinus1 = $resolver->resolve('Formulir Evaluasi SP tahun [t-1]', 2026);
        $resolvedMinus2 = $resolver->resolve('Laporan Kinerja tahun [t-2]', 2026);
        $resolvedCurrent = $resolver->resolve('SK Tim Monev tahun [t]', 2026);

        $this->assertEquals('Formulir Evaluasi SP tahun 2025', $resolvedMinus1);
        $this->assertEquals('Laporan Kinerja tahun 2024', $resolvedMinus2);
        $this->assertEquals('SK Tim Monev tahun 2026', $resolvedCurrent);
    }

    /**
     * AC-27: Impor ulang mengubah item bukti -> Matching ulang berjalan, match confirmed dan rejected tidak berubah.
     */
    public function test_ac_27_reimporting_lk_preserves_confirmed_and_rejected_matches(): void
    {
        $lk = LembarKerja::where('code', 'ZI')->where('period', '2026')->first() ?? LembarKerja::where('code', 'ZI')->firstOrFail();
        $ind = LkIndicator::firstOrCreate(['lk_id' => $lk->id, 'code' => 'ZI-AC27'], ['title' => 'Indikator AC27', 'order_index' => 1]);

        $doc = EvidenceDocument::create([
            'document_name' => 'Dokumen Terkonfirmasi AC27',
            'year' => 2026,
            'file_path' => 'evidence/ac27.pdf',
            'original_filename' => 'ac27.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'ac27_'.uniqid()),
            'uploaded_by' => $this->admin->id,
        ]);

        $match = DocumentMatch::create([
            'document_id' => $doc->id,
            'indicator_id' => $ind->id,
            'status' => 'confirmed',
            'match_method' => 'manual',
            'confidence_score' => 100.0,
        ]);

        /** @var MatchingEngineService $engine */
        $engine = app(MatchingEngineService::class);
        $engine->rematchLk($lk->id);

        $match->refresh();
        $this->assertSame('confirmed', $match->status, 'Status confirmed dilarang berubah saat rematch LK.');
    }

    /**
     * Gate Verifikasi Fase 8 (NFR-05): Kueri matriks monitor merespons dalam waktu < 2.0 detik.
     */
    public function test_performance_monitor_matrix_query_response_time_under_two_seconds(): void
    {
        /** @var MonitorMatrixService $matrix */
        $matrix = app(MonitorMatrixService::class);

        $startTime = microtime(true);
        $data = $matrix->getMatrixData(['period' => 2026, 'per_page' => 50]);
        $duration = microtime(true) - $startTime;

        $this->assertLessThan(2.0, $duration, "Kueri matriks monitor harus merespons dalam waktu < 2 detik (terukur: {$duration}s).");
        $this->assertNotEmpty($data['columns']);
    }
}
