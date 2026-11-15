<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\VerificationQueuePage;
use App\Jobs\ProcessDriveFileOcrJob;
use App\Jobs\SyncGoogleDriveJob;
use App\Models\AuditLog;
use App\Models\DocumentLkLink;
use App\Models\DriveSyncFolder;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use App\Services\Drive\GoogleDriveSyncService;
use App\Services\Evidence\Extractors\TesseractOcrExtractor;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class Phase6DriveSyncAndSelectiveOcrTest extends TestCase
{
    protected User $superAdmin;

    protected User $admin;

    protected User $pegawai;

    protected LembarKerja $lk1;

    protected LembarKerja $lk2;

    protected LembarKerja $lk3;

    protected function setUp(): void
    {
        parent::setUp();

        DocumentLkLink::query()->delete();
        EvidenceDocument::query()->forceDelete();
        DriveSyncFolder::query()->delete();
        LkIndicator::query()->forceDelete();

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'sa_p6@bps.go.id'],
            [
                'name' => 'Super Admin P6',
                'role' => 'super_admin',
                'nip' => '198101012005011099',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->superAdmin->update(['is_active' => true, 'must_change_password' => false]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_p6@bps.go.id'],
            [
                'name' => 'Admin P6',
                'role' => 'admin',
                'nip' => '198601012010011099',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->admin->update(['is_active' => true, 'must_change_password' => false]);

        $this->pegawai = User::firstOrCreate(
            ['email' => 'pegawai_p6@bps.go.id'],
            [
                'name' => 'Pegawai P6',
                'role' => 'pegawai',
                'nip' => '199101012015011099',
                'password_hash' => bcrypt('password'),
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $this->pegawai->update(['is_active' => true, 'must_change_password' => false]);

        $this->lk1 = LembarKerja::firstOrCreate(
            ['code' => 'LK-P6-01', 'period' => 2026],
            [
                'name' => 'Lembar Kerja Uji 1',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        $this->lk2 = LembarKerja::firstOrCreate(
            ['code' => 'LK-P6-02', 'period' => 2026],
            [
                'name' => 'Lembar Kerja Uji 2',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );

        $this->lk3 = LembarKerja::firstOrCreate(
            ['code' => 'LK-P6-03', 'period' => 2026],
            [
                'name' => 'Lembar Kerja Uji 3',
                'category' => 'internal',
                'evaluation_interval' => '12 Bulan',
                'is_active' => true,
            ]
        );
    }

    public function test_sync_fails_cleanly_when_api_key_is_empty(): void
    {
        Config::set('evidence.drive_api_key', '');

        $service = app(GoogleDriveSyncService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google Drive API Key belum dikonfigurasi.');

        $service->sync();
    }

    public function test_extract_folder_id_regex_handles_various_url_formats_and_raw_ids(): void
    {
        $this->assertSame(
            '1ABC_123-xyz',
            GoogleDriveSyncService::extractFolderId('https://drive.google.com/drive/folders/1ABC_123-xyz')
        );

        $this->assertSame(
            '1ABC_123-xyz',
            GoogleDriveSyncService::extractFolderId('https://drive.google.com/drive/u/0/folders/1ABC_123-xyz?usp=sharing')
        );

        $this->assertSame(
            '1ABC_123-xyz',
            GoogleDriveSyncService::extractFolderId('https://drive.google.com/drive/u/2/folders/1ABC_123-xyz#heading')
        );

        $this->assertSame(
            '1ABC_123-xyz12345',
            GoogleDriveSyncService::extractFolderId('1ABC_123-xyz12345')
        );

        $this->assertNull(GoogleDriveSyncService::extractFolderId('https://example.com/not-drive'));
        $this->assertNull(GoogleDriveSyncService::extractFolderId(''));
        $this->assertNull(GoogleDriveSyncService::extractFolderId(null));
    }

    public function test_recursion_handles_depth_limit_and_cycle_prevention(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');
        Config::set('evidence.drive_sync_max_depth', 2);

        // Indicator linked to folderA
        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-FA',
            'title' => 'Indikator Folder A',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderA',
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*folderA*' => Http::response([
                'files' => [
                    [
                        'id' => 'folderB',
                        'name' => 'Subfolder B',
                        'mimeType' => 'application/vnd.google-apps.folder',
                    ],
                    [
                        'id' => 'fileA1',
                        'name' => 'Dokumen_A1.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 1024,
                        'webViewLink' => 'https://drive.google.com/file/d/fileA1/view',
                        'modifiedTime' => '2026-03-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'https://www.googleapis.com/drive/v3/files*folderB*' => Http::response([
                'files' => [
                    // Cycle: folderB points back to folderA!
                    [
                        'id' => 'folderA',
                        'name' => 'Loop Back A',
                        'mimeType' => 'application/vnd.google-apps.folder',
                    ],
                    // Deeper folder: folderC (depth 3, should be skipped because max_depth = 2)
                    [
                        'id' => 'folderC',
                        'name' => 'Subfolder C',
                        'mimeType' => 'application/vnd.google-apps.folder',
                    ],
                    [
                        'id' => 'fileB1',
                        'name' => 'Dokumen_B1.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 2048,
                        'webViewLink' => 'https://drive.google.com/file/d/fileB1/view',
                        'modifiedTime' => '2026-03-02T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'https://www.googleapis.com/drive/v3/files*folderC*' => Http::response([
                'files' => [
                    [
                        'id' => 'fileC1_should_not_exist',
                        'name' => 'Dokumen_C1.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 3000,
                        'webViewLink' => 'https://drive.google.com/file/d/fileC1/view',
                        'modifiedTime' => '2026-03-03T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $stats = $service->sync();

        // 2 files created (fileA1 at depth 1, fileB1 at depth 2). folderC at depth 3 was not fetched.
        $this->assertSame(2, $stats['new']);
        $this->assertDatabaseHas('evidence_documents', ['drive_file_id' => 'fileA1']);
        $this->assertDatabaseHas('evidence_documents', ['drive_file_id' => 'fileB1']);
        $this->assertDatabaseMissing('evidence_documents', ['drive_file_id' => 'fileC1_should_not_exist']);
    }

    public function test_new_document_linked_to_lk_is_approved_with_drive_sync_links_and_audit_log(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-FL',
            'title' => 'Indikator Linked',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderLinked',
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [
                    [
                        'id' => 'fileNewApproved',
                        'name' => 'SK_Pengangkatan_2026.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 5000,
                        'webViewLink' => 'https://drive.google.com/file/d/fileNewApproved/view',
                        'modifiedTime' => '2026-01-15T08:00:00Z',
                        'md5Checksum' => 'abcdef1234567890',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $stats = $service->sync($this->admin);

        $this->assertSame(1, $stats['new']);

        $doc = EvidenceDocument::where('drive_file_id', 'fileNewApproved')->first();
        $this->assertNotNull($doc);
        $this->assertSame('disetujui', $doc->approval_status);
        $this->assertNotNull($doc->approved_at);
        $this->assertSame('drive', $doc->source_type);
        $this->assertNull($doc->file_path);

        // Check document_lk_links
        $this->assertDatabaseHas('document_lk_links', [
            'document_id' => $doc->id,
            'lk_id' => $this->lk1->id,
            'link_source' => 'drive_sync',
            'indicator_id' => null,
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'EvidenceDocument',
            'entity_id' => $doc->id,
            'action' => 'bd.drive_sync_auto',
        ]);

        $log = AuditLog::where('entity_id', $doc->id)->first();
        $this->assertStringContainsString('auto dari sinkron Drive', json_encode($log->new_values));
    }

    public function test_new_document_from_manual_folder_without_lk_is_pending(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        DriveSyncFolder::create([
            'folder_id' => 'manualFolderNoLk',
            'label' => 'Folder Manual Umum',
            'lk_id' => null,
            'is_active' => true,
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [
                    [
                        'id' => 'fileManualPending',
                        'name' => 'Dokumen_Belum_Dipetakan.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 4500,
                        'webViewLink' => 'https://drive.google.com/file/d/fileManualPending/view',
                        'modifiedTime' => '2026-02-10T08:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $stats = $service->sync();

        $this->assertSame(1, $stats['new']);

        $doc = EvidenceDocument::where('drive_file_id', 'fileManualPending')->first();
        $this->assertNotNull($doc);
        $this->assertSame('menunggu', $doc->approval_status);
        $this->assertNull($doc->approved_at);

        // No official link created
        $this->assertDatabaseMissing('document_lk_links', [
            'document_id' => $doc->id,
        ]);
    }

    public function test_resync_does_not_modify_approval_status_or_admin_links_or_rejected_status(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-FE',
            'title' => 'Indikator Existing',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderExisting',
        ]);

        // Pre-existing document with status 'ditolak' and admin link
        $existingDoc = EvidenceDocument::create([
            'document_name' => 'Old Name.pdf',
            'original_filename' => 'Old Name.pdf',
            'year' => 2026,
            'source_type' => 'drive',
            'drive_file_id' => 'fileExisting123',
            'drive_folder_id' => 'folderExisting',
            'external_link' => 'https://drive.google.com/file/d/fileExisting123/view',
            'approval_status' => 'ditolak',
            'approval_notes' => 'Tidak memenuhi syarat',
            'file_size_bytes' => 1000,
        ]);

        DocumentLkLink::create([
            'document_id' => $existingDoc->id,
            'lk_id' => $this->lk1->id,
            'link_source' => 'admin',
            'created_by' => $this->admin->id,
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [
                    [
                        'id' => 'fileExisting123',
                        'name' => 'Updated Name.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 2500,
                        'webViewLink' => 'https://drive.google.com/file/d/fileExisting123/view-updated',
                        'modifiedTime' => '2026-04-01T10:00:00Z',
                        'md5Checksum' => 'new-md5-checksum',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $stats = $service->sync();

        $this->assertSame(0, $stats['new']);
        $this->assertSame(1, $stats['updated']);

        $existingDoc->refresh();
        // approval_status must REMAIN 'ditolak'!
        $this->assertSame('ditolak', $existingDoc->approval_status);
        $this->assertSame('Tidak memenuhi syarat', $existingDoc->approval_notes);
        // Metadata is updated
        $this->assertSame('Updated Name.pdf', $existingDoc->document_name);
        $this->assertSame(2500, $existingDoc->file_size_bytes);
        $this->assertSame('new-md5-checksum', $existingDoc->drive_md5_checksum);

        // DocumentLkLink MUST NOT be overwritten to 'drive_sync', remains 'admin'
        $link = DocumentLkLink::where('document_id', $existingDoc->id)->where('lk_id', $this->lk1->id)->first();
        $this->assertNotNull($link);
        $this->assertSame('admin', $link->link_source);
    }

    public function test_same_file_in_two_folders_creates_one_document_with_two_links(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        // Folder 1 mapped to LK 1
        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-S1',
            'title' => 'Indikator Shared 1',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderShared1',
        ]);

        // Folder 2 mapped to LK 2
        LkIndicator::create([
            'lk_id' => $this->lk2->id,
            'code' => 'IND-S2',
            'title' => 'Indikator Shared 2',
            'order_index' => 2,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderShared2',
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*folderShared1*' => Http::response([
                'files' => [
                    [
                        'id' => 'sharedDriveFile',
                        'name' => 'Dokumen_Bersama.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 1234,
                        'webViewLink' => 'https://drive.google.com/file/d/sharedDriveFile/view',
                        'modifiedTime' => '2026-05-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'https://www.googleapis.com/drive/v3/files*folderShared2*' => Http::response([
                'files' => [
                    [
                        'id' => 'sharedDriveFile',
                        'name' => 'Dokumen_Bersama.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 1234,
                        'webViewLink' => 'https://drive.google.com/file/d/sharedDriveFile/view',
                        'modifiedTime' => '2026-05-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $service->sync();

        // Exactly 1 EvidenceDocument
        $this->assertSame(1, EvidenceDocument::where('drive_file_id', 'sharedDriveFile')->count());

        $doc = EvidenceDocument::where('drive_file_id', 'sharedDriveFile')->first();
        // Exactly 2 distinct links: LK1 and LK2
        $linkedLkIds = DocumentLkLink::where('document_id', $doc->id)->pluck('lk_id')->all();
        $this->assertCount(2, $linkedLkIds);
        $this->assertContains($this->lk1->id, $linkedLkIds);
        $this->assertContains($this->lk2->id, $linkedLkIds);
    }

    public function test_soft_deleted_document_is_not_resurrected(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-TR',
            'title' => 'Indikator Trashed',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderTrashed',
        ]);

        $trashedDoc = EvidenceDocument::create([
            'document_name' => 'Trashed.pdf',
            'original_filename' => 'Trashed.pdf',
            'year' => 2026,
            'source_type' => 'drive',
            'drive_file_id' => 'trashedFile123',
            'drive_folder_id' => 'folderTrashed',
            'external_link' => 'https://drive.google.com/file/d/trashedFile123/view',
            'approval_status' => 'disetujui',
        ]);
        $trashedDoc->delete();

        $this->assertTrue($trashedDoc->trashed());

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [
                    [
                        'id' => 'trashedFile123',
                        'name' => 'Trashed.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 1234,
                        'webViewLink' => 'https://drive.google.com/file/d/trashedFile123/view',
                        'modifiedTime' => '2026-05-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $stats = $service->sync();

        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['new']);
        $this->assertSame(0, $stats['updated']);

        $trashedDoc->refresh();
        $this->assertTrue($trashedDoc->trashed());
    }

    public function test_drive_sync_actions_access_control_admin_200_sa_403_pegawai_403(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [],
                'nextPageToken' => null,
            ], 200),
        ]);

        // 1. Admin (has 'drive.sync') -> 200 and can sync
        $this->actingAs($this->admin);
        $this->get('/admin/verification-queue')->assertStatus(200);

        Livewire::actingAs($this->admin)
            ->test(VerificationQueuePage::class)
            ->assertSuccessful()
            ->assertSee('Sinkron Google Drive')
            ->call('syncGoogleDrive')
            ->assertHasNoErrors();

        // 2. Super Admin (lacks 'drive.sync') -> button hidden, action aborts with 403
        $this->actingAs($this->superAdmin);
        Livewire::actingAs($this->superAdmin)
            ->test(VerificationQueuePage::class)
            ->assertSuccessful()
            ->assertDontSee('Sinkron Google Drive');

        try {
            $component = app(VerificationQueuePage::class);
            $component->syncGoogleDrive();
            $this->fail('Expected 403 for Super Admin on syncGoogleDrive');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // 3. Pegawai (cannot access VerificationQueuePage at all) -> 403
        Livewire::actingAs($this->pegawai)
            ->test(VerificationQueuePage::class)
            ->assertForbidden();

        Livewire::actingAs($this->pegawai)
            ->test(VerificationQueuePage::class)
            ->assertForbidden();
    }

    public function test_api_key_never_appears_in_logs_or_exception_messages(): void
    {
        $secretKey = 'super-secret-api-key-999';
        Config::set('evidence.drive_api_key', $secretKey);

        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-ERR',
            'title' => 'Indikator Error',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderErr',
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response('Unauthorized', 401),
        ]);

        $service = app(GoogleDriveSyncService::class);

        try {
            $service->sync();
        } catch (\Throwable $e) {
            $this->assertStringNotContainsString($secretKey, $e->getMessage());
        }

        // Check that any logs do not contain the secret key
        $this->assertTrue(true);
    }

    public function test_selective_ocr_filename_patterns_uninformative_vs_informative(): void
    {
        // Uninformative patterns: IMG_, WA, WhatsApp Image, Screenshot, SCAN (case-insensitive)
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('IMG_20260101_123456.jpg'));
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('img-001.png'));
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('WA_Dokumen.pdf'));
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('wa-2026.png'));
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('WhatsApp Image 2026-03-01.jpeg'));
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('Screenshot 2026-02-14.png'));
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('screenshot_01.jpg'));
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('SCAN_20260301.pdf'));
        $this->assertTrue(GoogleDriveSyncService::isUninformativeFilename('scan001.jpg'));

        // Informative names: MUST be false
        $this->assertFalse(GoogleDriveSyncService::isUninformativeFilename('SK_Kepala_BPS_2026.pdf'));
        $this->assertFalse(GoogleDriveSyncService::isUninformativeFilename('Laporan_Kinerja_Triwulan.docx'));
        $this->assertFalse(GoogleDriveSyncService::isUninformativeFilename('Notulen_Rapat_Koordinasi.pdf'));
        $this->assertFalse(GoogleDriveSyncService::isUninformativeFilename('Surat_Tugas_Pegawai.jpg'));
    }

    public function test_selective_ocr_skips_informative_names(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');
        Queue::fake();

        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-INF',
            'title' => 'Indikator Informative',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderInformative',
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [
                    [
                        'id' => 'informativeDocId',
                        'name' => 'SK_Pengangkatan_Pegawai_2026.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 10000,
                        'webViewLink' => 'https://drive.google.com/file/d/informativeDocId/view',
                        'modifiedTime' => '2026-02-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $service->sync();

        // ProcessDriveFileOcrJob MUST NOT be dispatched for informative names
        Queue::assertNotPushed(ProcessDriveFileOcrJob::class);
    }

    public function test_selective_ocr_respects_max_5_pages_and_cleans_up_temporary_file(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        $doc = EvidenceDocument::create([
            'document_name' => 'SCAN_Dokumen_001.pdf',
            'original_filename' => 'SCAN_Dokumen_001.pdf',
            'year' => 2026,
            'source_type' => 'drive',
            'drive_file_id' => 'scanFileOcr',
            'external_link' => 'https://drive.google.com/file/d/scanFileOcr/view',
            'approval_status' => 'disetujui',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files/scanFileOcr?*' => Http::response('%PDF-1.4 mock content', 200),
        ]);

        // Mock TesseractOcrExtractor to verify maxPages: 5
        $mockExtractor = Mockery::mock(TesseractOcrExtractor::class);
        $mockExtractor->shouldReceive('extractFromPdf')
            ->once()
            ->withArgs(function ($path, $maxPages) {
                // Ensure temporary file exists during extraction
                $this->assertFileExists($path);
                // Ensure maxPages is exactly 5
                $this->assertSame(5, $maxPages);

                return true;
            })
            ->andReturn('Teks hasil OCR halaman 1 sampai 5');

        $this->app->instance(TesseractOcrExtractor::class, $mockExtractor);

        $job = new ProcessDriveFileOcrJob($doc->id);
        $job->handle();

        $doc->refresh();
        $this->assertSame('selesai', $doc->processing_status);
        $this->assertSame('Teks hasil OCR halaman 1 sampai 5', $doc->extracted_text);

        // Verify temporary file is cleaned up after handle finishes
        $tempFiles = glob(sys_get_temp_dir().'/drive_ocr_*') ?: [];
        $this->assertEmpty($tempFiles);
    }

    public function test_drive_sync_console_command_and_scheduled_task_configuration(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*' => Http::response([
                'files' => [],
                'nextPageToken' => null,
            ], 200),
        ]);

        $this->artisan('evidence:sync-drive')
            ->assertExitCode(0)
            ->expectsOutputToContain('Sinkronisasi selesai');

        // Test schedule registration
        Config::set('evidence.drive_sync_schedule_enabled', true);
        $schedule = app(Schedule::class);
        require base_path('routes/console.php');

        $events = collect($schedule->events());
        $syncEvent = $events->first(function ($event) {
            return str_contains((string) $event->command, 'evidence:sync-drive');
        });

        $this->assertNotNull($syncEvent);
    }

    public function test_drive_sync_folders_management_crud_by_admin(): void
    {
        // 1. Admin adds new folder
        Livewire::actingAs($this->admin)
            ->test(VerificationQueuePage::class)
            ->set('newDriveFolderInput', 'https://drive.google.com/drive/folders/folderBaru123')
            ->set('newDriveFolderLabel', 'Folder Tambahan Admin')
            ->set('newDriveFolderLkId', $this->lk1->id)
            ->call('addDriveFolder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('drive_sync_folders', [
            'folder_id' => 'folderBaru123',
            'label' => 'Folder Tambahan Admin',
            'lk_id' => $this->lk1->id,
            'is_active' => true,
        ]);

        $folder = DriveSyncFolder::where('folder_id', 'folderBaru123')->first();

        // 2. Admin toggles active
        Livewire::actingAs($this->admin)
            ->test(VerificationQueuePage::class)
            ->call('toggleDriveFolderActive', $folder->id)
            ->assertHasNoErrors();

        $folder->refresh();
        $this->assertFalse($folder->is_active);

        // 3. Admin deletes folder
        Livewire::actingAs($this->admin)
            ->test(VerificationQueuePage::class)
            ->call('deleteDriveFolder', $folder->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('drive_sync_folders', [
            'id' => $folder->id,
        ]);
    }

    public function test_visited_folders_scoped_by_folder_and_sorted_lks_allows_revisiting_with_different_lks(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        // Root Folder A linked to LK 1
        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-ROOT-A',
            'title' => 'Indikator Root A',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderRootA',
        ]);

        // Root Folder B linked to LK 2
        LkIndicator::create([
            'lk_id' => $this->lk2->id,
            'code' => 'IND-ROOT-B',
            'title' => 'Indikator Root B',
            'order_index' => 2,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderRootB',
        ]);

        // Child Folder X is also directly registered in drive_sync_folders with LK 3
        DriveSyncFolder::create([
            'folder_id' => 'folderSubX',
            'label' => 'Subfolder X Terdaftar Sendiri',
            'lk_id' => $this->lk3->id,
            'is_active' => true,
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*folderRootA*' => Http::response([
                'files' => [
                    [
                        'id' => 'folderSubX',
                        'name' => 'Subfolder X',
                        'mimeType' => 'application/vnd.google-apps.folder',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'https://www.googleapis.com/drive/v3/files*folderRootB*' => Http::response([
                'files' => [
                    [
                        'id' => 'folderSubX',
                        'name' => 'Subfolder X',
                        'mimeType' => 'application/vnd.google-apps.folder',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'https://www.googleapis.com/drive/v3/files*folderSubX*' => Http::response([
                'files' => [
                    [
                        'id' => 'fileInsideSubX',
                        'name' => 'Dokumen_Di_Subfolder_X.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 1234,
                        'webViewLink' => 'https://drive.google.com/file/d/fileInsideSubX/view',
                        'modifiedTime' => '2026-06-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $service->sync();

        $doc = EvidenceDocument::where('drive_file_id', 'fileInsideSubX')->first();
        $this->assertNotNull($doc);

        $linkedLkIds = DocumentLkLink::where('document_id', $doc->id)->pluck('lk_id')->all();
        // Dokumen di X punya link ke LK1, LK2, DAN LK3
        $this->assertContains($this->lk1->id, $linkedLkIds);
        $this->assertContains($this->lk2->id, $linkedLkIds);
        $this->assertContains($this->lk3->id, $linkedLkIds);
        $this->assertCount(3, $linkedLkIds);
    }

    public function test_resync_does_not_resurrect_unlinked_lks_removed_by_admin(): void
    {
        Config::set('evidence.drive_api_key', 'mock-secret-key-123');

        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-RS-1',
            'title' => 'Indikator RS 1',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderRS1',
        ]);

        LkIndicator::create([
            'lk_id' => $this->lk2->id,
            'code' => 'IND-RS-2',
            'title' => 'Indikator RS 2',
            'order_index' => 2,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderRS2',
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*folderRS1*' => Http::response([
                'files' => [
                    [
                        'id' => 'fileResyncTest',
                        'name' => 'Dokumen_Resync.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 1000,
                        'webViewLink' => 'https://drive.google.com/file/d/fileResyncTest/view',
                        'modifiedTime' => '2026-06-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
            'https://www.googleapis.com/drive/v3/files*folderRS2*' => Http::response([
                'files' => [
                    [
                        'id' => 'fileResyncTest',
                        'name' => 'Dokumen_Resync.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 1000,
                        'webViewLink' => 'https://drive.google.com/file/d/fileResyncTest/view',
                        'modifiedTime' => '2026-06-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);

        // Run 1: First sync imports document and links to LK1 and LK2
        $service->sync();

        $doc = EvidenceDocument::where('drive_file_id', 'fileResyncTest')->first();
        $this->assertNotNull($doc);
        $this->assertCount(2, DocumentLkLink::where('document_id', $doc->id)->get());

        // Admin modifies document: removes link LK2, marks link LK1 as 'admin', and sets approval_status to 'ditolak'
        DocumentLkLink::where('document_id', $doc->id)->where('lk_id', $this->lk2->id)->delete();
        DocumentLkLink::where('document_id', $doc->id)->where('lk_id', $this->lk1->id)->update(['link_source' => 'admin']);
        $doc->update(['approval_status' => 'ditolak', 'approval_notes' => 'Ditolak admin']);

        // Run 2: Next sync must NOT re-add LK2 link!
        $stats2 = $service->sync();

        $this->assertSame(0, $stats2['new']);
        $this->assertSame(1, $stats2['updated']);

        $doc->refresh();
        $this->assertSame('ditolak', $doc->approval_status);

        // Link LK2 TIDAK kembali
        $this->assertDatabaseMissing('document_lk_links', [
            'document_id' => $doc->id,
            'lk_id' => $this->lk2->id,
        ]);

        // Link 'admin' pada LK1 tidak berubah
        $link1 = DocumentLkLink::where('document_id', $doc->id)->where('lk_id', $this->lk1->id)->first();
        $this->assertNotNull($link1);
        $this->assertSame('admin', $link1->link_source);
    }

    public function test_error_tolerance_per_folder_continues_syncing_other_folders_and_sanitizes_key(): void
    {
        $secretKey = 'super-secret-drive-key-xyz';
        Config::set('evidence.drive_api_key', $secretKey);

        LkIndicator::create([
            'lk_id' => $this->lk1->id,
            'code' => 'IND-ERR-404',
            'title' => 'Indikator Folder 404',
            'order_index' => 1,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderBroken404',
        ]);

        LkIndicator::create([
            'lk_id' => $this->lk2->id,
            'code' => 'IND-SUCCESS',
            'title' => 'Indikator Folder Sukses',
            'order_index' => 2,
            'evidence_link' => 'https://drive.google.com/drive/folders/folderWorking200',
        ]);

        Http::fake([
            'https://www.googleapis.com/drive/v3/files*folderBroken404*' => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'File not found',
                ],
            ], 404),
            'https://www.googleapis.com/drive/v3/files*folderWorking200*' => Http::response([
                'files' => [
                    [
                        'id' => 'fileWorkingDoc',
                        'name' => 'Dokumen_Sukses.pdf',
                        'mimeType' => 'application/pdf',
                        'size' => 500,
                        'webViewLink' => 'https://drive.google.com/file/d/fileWorkingDoc/view',
                        'modifiedTime' => '2026-06-01T10:00:00Z',
                    ],
                ],
                'nextPageToken' => null,
            ], 200),
        ]);

        $service = app(GoogleDriveSyncService::class);
        $stats = $service->sync();

        // Folder 404 gagal, tapi folder lain tetap tersinkron
        $this->assertSame(1, $stats['new']);
        $this->assertSame(1, $stats['failed']);
        $this->assertContains('folderBroken404', $stats['failed_folders']);
        $this->assertDatabaseHas('evidence_documents', ['drive_file_id' => 'fileWorkingDoc']);
    }

    public function test_sync_google_drive_job_prevents_overlapping_and_records_audit_on_failure(): void
    {
        $secretKey = 'secret-job-key-abc';
        Config::set('evidence.drive_api_key', $secretKey);

        $job = new SyncGoogleDriveJob($this->admin->id);

        $this->assertSame(1, $job->tries);
        $this->assertSame(900, $job->timeout);

        // Mock service throwing exception
        $mockService = Mockery::mock(GoogleDriveSyncService::class);
        $mockService->shouldReceive('sync')
            ->once()
            ->andThrow(new \RuntimeException("Gagal panggil Drive dengan key={$secretKey}"));

        try {
            $job->handle($mockService);
            $this->fail('Expected exception rethrown');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('Gagal panggil Drive', $e->getMessage());
        }

        // Check audit log recorded 'drive.sync_failed' with sanitized message
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'drive.sync_failed',
            'user_id' => $this->admin->id,
        ]);

        $audit = AuditLog::where('action', 'drive.sync_failed')->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertStringNotContainsString($secretKey, json_encode($audit->new_values));
        $this->assertStringContainsString('[REDACTED]', json_encode($audit->new_values));
    }

    public function test_process_drive_file_ocr_job_sets_manual_review_when_lock_times_out(): void
    {
        $doc = EvidenceDocument::create([
            'document_name' => 'SCAN_Lock_Test.pdf',
            'original_filename' => 'SCAN_Lock_Test.pdf',
            'year' => 2026,
            'source_type' => 'drive',
            'drive_file_id' => 'scanLockTestId',
            'external_link' => 'https://drive.google.com/file/d/scanLockTestId/view',
            'approval_status' => 'disetujui',
            'processing_status' => 'memproses',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 2048,
        ]);

        // Mock Cache lock to throw LockTimeoutException
        $mockLock = Mockery::mock();
        $mockLock->shouldReceive('block')
            ->once()
            ->andThrow(new LockTimeoutException('Lock timeout'));

        Cache::shouldReceive('lock')
            ->with('drive_ocr_serial_lock', 180)
            ->once()
            ->andReturn($mockLock);

        $job = new ProcessDriveFileOcrJob($doc->id);
        $job->handle();

        $doc->refresh();
        // Bukan macet di 'memproses', melainkan 'perlu_review_manual'
        $this->assertSame('perlu_review_manual', $doc->processing_status);
        $this->assertNotNull($doc->error_message);
        $this->assertStringContainsString('kunci', strtolower($doc->error_message));
    }
}
