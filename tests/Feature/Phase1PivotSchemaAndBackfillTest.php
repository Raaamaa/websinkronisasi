<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DocumentLkLink;
use App\Models\DriveSyncFolder;
use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase1PivotSchemaAndBackfillTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pivot_columns_and_new_tables_exist_with_expected_foreign_keys(): void
    {
        $this->assertTrue(Schema::hasColumns('evidence_documents', [
            'approval_status',
            'approval_notes',
            'approved_by',
            'approved_at',
            'source_type',
            'drive_file_id',
            'drive_folder_id',
            'drive_md5_checksum',
            'drive_modified_at',
            'external_link',
        ]));

        $this->assertTrue(Schema::hasTable('document_lk_links'));
        $this->assertTrue(Schema::hasColumns('document_lk_links', [
            'id',
            'document_id',
            'lk_id',
            'indicator_id',
            'link_source',
            'created_by',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasTable('drive_sync_folders'));
        $this->assertTrue(Schema::hasColumns('drive_sync_folders', [
            'id',
            'folder_id',
            'label',
            'lk_id',
            'is_active',
            'last_synced_at',
            'created_at',
            'updated_at',
        ]));

        $fkTargets = DB::select(<<<'SQL'
            SELECT tc.table_name, kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_name IN ('document_lk_links', 'drive_sync_folders')
        SQL);

        $fkPairs = collect($fkTargets)
            ->mapWithKeys(fn (object $row): array => [
                "{$row->table_name}.{$row->column_name}" => "{$row->foreign_table_name}.{$row->foreign_column_name}",
            ])
            ->all();

        $this->assertSame('lembar_kerja.id', $fkPairs['document_lk_links.lk_id'] ?? null);
        $this->assertSame('evidence_documents.id', $fkPairs['document_lk_links.document_id'] ?? null);
        $this->assertSame('lk_indicators.id', $fkPairs['document_lk_links.indicator_id'] ?? null);
        $this->assertSame('lembar_kerja.id', $fkPairs['drive_sync_folders.lk_id'] ?? null);
    }

    public function test_file_size_constraint_allows_50mb_upload_and_rejects_over_50mb_upload(): void
    {
        $user = $this->createUser('pegawai');

        $validDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Batas 50 MB',
            'year' => 2026,
            'source_type' => 'upload',
            'file_path' => 'evidence/valid_50mb.pdf',
            'original_filename' => 'valid_50mb.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 52428800,
            'file_hash' => hash('sha256', 'valid_50mb_'.Str::uuid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);

        $this->assertSame(52428800, $validDoc->file_size_bytes);
        $this->assertSame('menunggu', $validDoc->approval_status);

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('chk_document_file_size');

        DB::transaction(function () use ($user): void {
            EvidenceDocument::create([
                'document_name' => 'Dokumen Melebihi 50 MB',
                'year' => 2026,
                'source_type' => 'upload',
                'file_path' => 'evidence/too_big.pdf',
                'original_filename' => 'too_big.pdf',
                'mime_type' => 'application/pdf',
                'file_size_bytes' => 52428801,
                'file_hash' => hash('sha256', 'too_big_'.Str::uuid()),
                'processing_status' => 'selesai',
                'uploaded_by' => $user->id,
            ]);
        });
    }

    public function test_drive_and_link_documents_insert_without_file_hash_file_path_or_uploaded_by(): void
    {
        $user = $this->createUser('pegawai');

        $driveWithoutSize = EvidenceDocument::create([
            'document_name' => 'Arsip SK Drive Lama.pdf',
            'year' => 2024,
            'source_type' => 'drive',
            'drive_file_id' => 'drv_'.Str::random(24),
            'drive_folder_id' => 'fld_'.Str::random(18),
            'external_link' => 'https://drive.google.com/file/d/abc123/view',
            'processing_status' => 'selesai',
        ]);

        $driveLargeMetadata = EvidenceDocument::create([
            'document_name' => 'Rekaman Sosialisasi 100MB.mp4',
            'year' => 2025,
            'source_type' => 'drive',
            'drive_file_id' => 'drv_'.Str::random(24),
            'drive_folder_id' => 'fld_'.Str::random(18),
            'file_size_bytes' => 104857600,
            'external_link' => 'https://drive.google.com/file/d/def456/view',
            'processing_status' => 'selesai',
        ]);

        $linkOnlyDoc = EvidenceDocument::create([
            'document_name' => 'Video Dokumentasi Kegiatan',
            'year' => 2026,
            'source_type' => 'link',
            'external_link' => 'https://drive.google.com/file/d/link789/view',
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);

        $this->assertNull($driveWithoutSize->file_hash);
        $this->assertNull($driveWithoutSize->file_path);
        $this->assertNull($driveWithoutSize->uploaded_by);
        $this->assertSame(104857600, $driveLargeMetadata->file_size_bytes);
        $this->assertNull($linkOnlyDoc->file_hash);
        $this->assertNull($linkOnlyDoc->file_path);
        $this->assertSame($user->id, $linkOnlyDoc->uploaded_by);
    }

    public function test_source_payload_constraint_rejects_incomplete_link_and_drive_rows(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('chk_document_source_payload');

        DB::transaction(function (): void {
            EvidenceDocument::create([
                'document_name' => 'Link Tanpa Uploader',
                'year' => 2026,
                'source_type' => 'link',
                'external_link' => 'https://example.com/dokumen',
                'processing_status' => 'selesai',
            ]);
        });
    }

    public function test_active_duplicate_sha256_is_rejected_while_soft_deleted_duplicate_and_null_hashes_are_allowed(): void
    {
        $user = $this->createUser('pegawai');
        $hash = hash('sha256', 'dedup_sample_'.Str::uuid());

        $first = EvidenceDocument::create([
            'document_name' => 'Bukti Aktif Pertama',
            'year' => 2026,
            'source_type' => 'upload',
            'file_path' => 'evidence/first.pdf',
            'original_filename' => 'first.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 2048,
            'file_hash' => $hash,
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);

        $duplicateRejected = false;
        try {
            DB::transaction(function () use ($user, $hash): void {
                EvidenceDocument::create([
                    'document_name' => 'Bukti Duplikat Aktif',
                    'year' => 2026,
                    'source_type' => 'upload',
                    'file_path' => 'evidence/second.pdf',
                    'original_filename' => 'second.pdf',
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => 2048,
                    'file_hash' => $hash,
                    'processing_status' => 'selesai',
                    'uploaded_by' => $user->id,
                ]);
            });
        } catch (QueryException $e) {
            $duplicateRejected = str_contains($e->getMessage(), 'uq_evidence_documents_hash_active');
        }

        $this->assertTrue($duplicateRejected, 'Duplikat SHA-256 aktif harus ditolak oleh uq_evidence_documents_hash_active');

        $first->delete();

        $reupload = EvidenceDocument::create([
            'document_name' => 'Bukti Unggah Ulang Setelah Soft Delete',
            'year' => 2026,
            'source_type' => 'upload',
            'file_path' => 'evidence/reupload.pdf',
            'original_filename' => 'reupload.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 2048,
            'file_hash' => $hash,
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);

        $this->assertSame($hash, $reupload->file_hash);

        $driveId = 'drv_dedup_'.Str::random(16);
        $drive1 = EvidenceDocument::create([
            'document_name' => 'Drive Null Hash 1',
            'year' => 2025,
            'source_type' => 'drive',
            'drive_file_id' => $driveId,
            'external_link' => 'https://drive.google.com/file/d/'.$driveId.'/view',
            'processing_status' => 'selesai',
        ]);
        $drive2 = EvidenceDocument::create([
            'document_name' => 'Drive Null Hash 2',
            'year' => 2025,
            'source_type' => 'drive',
            'drive_file_id' => 'drv_other_'.Str::random(16),
            'external_link' => 'https://drive.google.com/file/d/other/view',
            'processing_status' => 'selesai',
        ]);

        $this->assertNull($drive1->file_hash);
        $this->assertNull($drive2->file_hash);

        $driveDuplicateRejected = false;
        try {
            DB::transaction(function () use ($driveId): void {
                EvidenceDocument::create([
                    'document_name' => 'Drive Duplicate ID',
                    'year' => 2025,
                    'source_type' => 'drive',
                    'drive_file_id' => $driveId,
                    'external_link' => 'https://drive.google.com/file/d/'.$driveId.'/view',
                    'processing_status' => 'selesai',
                ]);
            });
        } catch (QueryException $e) {
            $driveDuplicateRejected = str_contains($e->getMessage(), 'uq_evidence_documents_drive_file_active');
        }

        $this->assertTrue($driveDuplicateRejected, 'Duplikat drive_file_id aktif harus ditolak oleh uq_evidence_documents_drive_file_active');
    }

    public function test_document_lk_links_and_drive_sync_folders_models_and_official_scope(): void
    {
        $admin = $this->createUser('admin');
        $lk = $this->createLembarKerja();
        $indicator = $this->createIndicator($lk);

        $doc = EvidenceDocument::create([
            'document_name' => 'SK Tim Reformasi Birokrasi',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'disetujui',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'file_path' => 'evidence/sk_rb.pdf',
            'original_filename' => 'sk_rb.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 4096,
            'file_hash' => hash('sha256', 'sk_rb_'.Str::uuid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $admin->id,
        ]);

        $proposedLink = DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'indicator_id' => null,
            'link_source' => 'proposed',
            'created_by' => $admin->id,
        ]);

        $adminLink = DocumentLkLink::create([
            'document_id' => $doc->id,
            'lk_id' => $lk->id,
            'indicator_id' => $indicator->id,
            'link_source' => 'admin',
            'created_by' => $admin->id,
        ]);

        $syncFolder = DriveSyncFolder::create([
            'folder_id' => '1AbCdEfGhIjKlMnOpQrStUvWxYz'.Str::random(4),
            'label' => 'Folder Bukti ZI 2026',
            'lk_id' => $lk->id,
            'is_active' => true,
        ]);

        $this->assertSame($admin->id, $doc->approvedBy?->id);
        $this->assertCount(2, $doc->lkLinks);
        $this->assertCount(1, $doc->officialLkLinks);
        $this->assertSame($adminLink->id, $doc->officialLkLinks->first()->id);
        $this->assertNotSame($proposedLink->id, $doc->officialLkLinks->first()->id);

        $this->assertSame($lk->id, $adminLink->lembarKerja?->id);
        $this->assertSame($indicator->id, $adminLink->indicator?->id);
        $this->assertSame($doc->id, $adminLink->document?->id);
        $this->assertSame($admin->id, $adminLink->creator?->id);

        $this->assertSame($lk->id, $syncFolder->lembarKerja?->id);
        $this->assertTrue($lk->documentLinks->contains('id', $adminLink->id));
        $this->assertTrue($lk->driveSyncFolders->contains('id', $syncFolder->id));
    }

    public function test_backfill_migration_maps_confirmed_matches_and_is_idempotent(): void
    {
        $user = $this->createUser('admin');
        $lk = $this->createLembarKerja();
        $ind1 = $this->createIndicator($lk, 'ZI-1.1');
        $ind2 = $this->createIndicator($lk, 'ZI-1.2');

        $confirmedDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Confirmed Lama',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'approved_at' => null,
            'file_path' => 'evidence/confirmed_old.pdf',
            'original_filename' => 'confirmed_old.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'confirmed_old_'.Str::uuid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);

        $autoOnlyDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Auto Lama',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'approved_at' => null,
            'file_path' => 'evidence/auto_old.pdf',
            'original_filename' => 'auto_old.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'auto_old_'.Str::uuid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);

        $unmatchedDoc = EvidenceDocument::create([
            'document_name' => 'Dokumen Tanpa Match',
            'year' => 2026,
            'source_type' => 'upload',
            'approval_status' => 'menunggu',
            'approved_at' => null,
            'file_path' => 'evidence/unmatched_old.pdf',
            'original_filename' => 'unmatched_old.pdf',
            'mime_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'file_hash' => hash('sha256', 'unmatched_old_'.Str::uuid()),
            'processing_status' => 'selesai',
            'uploaded_by' => $user->id,
        ]);

        DB::table('document_matches')->insert([
            [
                'id' => (string) Str::uuid(),
                'document_id' => $confirmedDoc->id,
                'indicator_id' => $ind1->id,
                'match_method' => 'manual',
                'confidence_score' => 1.00,
                'match_reasons' => json_encode(['source' => 'manual_legacy']),
                'status' => 'confirmed',
                'verified_by' => $user->id,
                'verified_at' => now()->subDay(),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'id' => (string) Str::uuid(),
                'document_id' => $confirmedDoc->id,
                'indicator_id' => $ind2->id,
                'match_method' => 'exact_code',
                'confidence_score' => 1.00,
                'match_reasons' => json_encode(['code' => $ind2->code]),
                'status' => 'confirmed',
                'verified_by' => $user->id,
                'verified_at' => now()->subHours(12),
                'created_at' => now()->subHours(12),
                'updated_at' => now()->subHours(12),
            ],
            [
                'id' => (string) Str::uuid(),
                'document_id' => $autoOnlyDoc->id,
                'indicator_id' => $ind1->id,
                'match_method' => 'title_similarity',
                'confidence_score' => 0.80,
                'match_reasons' => json_encode(['similarity' => 0.80]),
                'status' => 'auto',
                'verified_by' => null,
                'verified_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $backfillMigration = require base_path('database/migrations/2026_11_11_000002_backfill_pivot_approval_status_and_lk_links.php');

        // Jalankan pertama kali lalu ulangi kedua kali untuk memastikan idempoten.
        $backfillMigration->up();
        $backfillMigration->up();

        $confirmedDoc->refresh();
        $autoOnlyDoc->refresh();
        $unmatchedDoc->refresh();

        $this->assertSame('disetujui', $confirmedDoc->approval_status);
        $this->assertSame('upload', $confirmedDoc->source_type);
        $this->assertNotNull($confirmedDoc->approved_at);

        $links = DocumentLkLink::where('document_id', $confirmedDoc->id)->get();
        $this->assertCount(2, $links);
        $this->assertTrue($links->every(fn (DocumentLkLink $link): bool => $link->link_source === 'backfill' && $link->lk_id === $lk->id));

        $this->assertSame('menunggu', $autoOnlyDoc->approval_status);
        $this->assertCount(0, DocumentLkLink::where('document_id', $autoOnlyDoc->id)->get());

        $this->assertSame('menunggu', $unmatchedDoc->approval_status);
        $this->assertCount(0, DocumentLkLink::where('document_id', $unmatchedDoc->id)->get());
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function createLembarKerja(): LembarKerja
    {
        return LembarKerja::create([
            'code' => 'P1TST_'.strtoupper(Str::random(6)),
            'name' => 'Lembar Kerja Uji Phase 1',
            'category' => 'internal',
            'period' => 2026,
            'evaluation_interval' => '12 Bulan',
            'is_active' => true,
        ]);
    }

    private function createIndicator(LembarKerja $lk, string $code = 'IND-1'): LkIndicator
    {
        return LkIndicator::create([
            'lk_id' => $lk->id,
            'code' => $code.'_'.Str::random(4),
            'title' => 'Indikator '.$code,
            'order_index' => random_int(1, 500),
        ]);
    }
}
