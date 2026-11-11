<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah kolom status persetujuan & metadata Drive pada evidence_documents,
     * relaksasi kolom berkas agar mode drive/link valid, serta buat tabel
     * document_lk_links dan drive_sync_folders.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE evidence_documents
                ADD COLUMN approval_status VARCHAR(20) NOT NULL DEFAULT 'menunggu',
                ADD COLUMN approval_notes TEXT NULL,
                ADD COLUMN approved_by UUID NULL REFERENCES users(id) ON DELETE SET NULL,
                ADD COLUMN approved_at TIMESTAMPTZ NULL,
                ADD COLUMN source_type VARCHAR(16) NOT NULL DEFAULT 'upload',
                ADD COLUMN drive_file_id VARCHAR(128) NULL,
                ADD COLUMN drive_folder_id VARCHAR(128) NULL,
                ADD COLUMN drive_md5_checksum VARCHAR(64) NULL,
                ADD COLUMN drive_modified_at TIMESTAMPTZ NULL,
                ALTER COLUMN file_path DROP NOT NULL,
                ALTER COLUMN original_filename DROP NOT NULL,
                ALTER COLUMN mime_type DROP NOT NULL,
                ALTER COLUMN file_size_bytes DROP NOT NULL,
                ALTER COLUMN file_hash DROP NOT NULL,
                ALTER COLUMN uploaded_by DROP NOT NULL
        SQL);

        DB::statement('ALTER TABLE evidence_documents DROP CONSTRAINT IF EXISTS chk_document_file_size');

        DB::statement(<<<'SQL'
            ALTER TABLE evidence_documents
                ADD CONSTRAINT chk_document_approval_status
                    CHECK (approval_status IN ('menunggu', 'disetujui', 'revisi', 'ditolak')),
                ADD CONSTRAINT chk_document_source_type
                    CHECK (source_type IN ('upload', 'drive', 'link')),
                ADD CONSTRAINT chk_document_file_size
                    CHECK (
                        file_size_bytes IS NULL
                        OR (file_size_bytes > 0 AND (source_type = 'drive' OR file_size_bytes <= 52428800))
                    ),
                ADD CONSTRAINT chk_document_source_payload CHECK (
                    (
                        source_type = 'upload'
                        AND uploaded_by IS NOT NULL
                        AND file_path IS NOT NULL
                        AND original_filename IS NOT NULL
                        AND mime_type IS NOT NULL
                        AND file_size_bytes IS NOT NULL
                        AND file_hash IS NOT NULL
                    )
                    OR (source_type = 'drive' AND drive_file_id IS NOT NULL AND external_link IS NOT NULL)
                    OR (source_type = 'link' AND uploaded_by IS NOT NULL AND external_link IS NOT NULL)
                )
        SQL);

        DB::statement('DROP INDEX IF EXISTS uq_evidence_documents_hash_active');
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_evidence_documents_hash_active
                ON evidence_documents (file_hash)
                WHERE file_hash IS NOT NULL AND deleted_at IS NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_evidence_documents_drive_file_active
                ON evidence_documents (drive_file_id)
                WHERE drive_file_id IS NOT NULL AND deleted_at IS NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE INDEX idx_evidence_documents_approval_status
                ON evidence_documents (approval_status)
                WHERE deleted_at IS NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE document_lk_links (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                document_id UUID NOT NULL REFERENCES evidence_documents(id) ON DELETE CASCADE,
                lk_id UUID NOT NULL REFERENCES lembar_kerja(id) ON DELETE CASCADE,
                indicator_id UUID NULL REFERENCES lk_indicators(id) ON DELETE SET NULL,
                link_source VARCHAR(20) NOT NULL CONSTRAINT chk_doc_lk_link_source CHECK (link_source IN ('proposed', 'admin', 'drive_sync', 'backfill')),
                created_by UUID NULL REFERENCES users(id) ON DELETE SET NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_doc_lk_links_doc_lk_ind
                ON document_lk_links (
                    document_id,
                    lk_id,
                    COALESCE(indicator_id, '00000000-0000-0000-0000-000000000000'::uuid)
                )
        SQL);

        DB::statement('CREATE INDEX idx_doc_lk_links_lk_document ON document_lk_links (lk_id, document_id)');

        DB::statement(<<<'SQL'
            CREATE INDEX idx_doc_lk_links_official
                ON document_lk_links (document_id, lk_id)
                WHERE link_source IN ('admin', 'drive_sync', 'backfill')
        SQL);

        DB::statement(<<<'SQL'
            CREATE TABLE drive_sync_folders (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                folder_id VARCHAR(128) NOT NULL UNIQUE,
                label VARCHAR(255) NULL,
                lk_id UUID NULL REFERENCES lembar_kerja(id) ON DELETE SET NULL,
                is_active BOOLEAN NOT NULL DEFAULT true,
                last_synced_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS drive_sync_folders');
        DB::statement('DROP TABLE IF EXISTS document_lk_links');

        DB::statement('DROP INDEX IF EXISTS idx_evidence_documents_approval_status');
        DB::statement('DROP INDEX IF EXISTS uq_evidence_documents_drive_file_active');
        DB::statement('DROP INDEX IF EXISTS uq_evidence_documents_hash_active');

        DB::statement(<<<'SQL'
            ALTER TABLE evidence_documents
                DROP CONSTRAINT IF EXISTS chk_document_source_payload,
                DROP CONSTRAINT IF EXISTS chk_document_file_size,
                DROP CONSTRAINT IF EXISTS chk_document_source_type,
                DROP CONSTRAINT IF EXISTS chk_document_approval_status
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE evidence_documents
                DROP COLUMN IF EXISTS drive_modified_at,
                DROP COLUMN IF EXISTS drive_md5_checksum,
                DROP COLUMN IF EXISTS drive_folder_id,
                DROP COLUMN IF EXISTS drive_file_id,
                DROP COLUMN IF EXISTS source_type,
                DROP COLUMN IF EXISTS approved_at,
                DROP COLUMN IF EXISTS approved_by,
                DROP COLUMN IF EXISTS approval_notes,
                DROP COLUMN IF EXISTS approval_status
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE evidence_documents
                ALTER COLUMN file_path SET NOT NULL,
                ALTER COLUMN original_filename SET NOT NULL,
                ALTER COLUMN mime_type SET NOT NULL,
                ALTER COLUMN file_size_bytes SET NOT NULL,
                ALTER COLUMN file_hash SET NOT NULL,
                ALTER COLUMN uploaded_by SET NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE evidence_documents
                ADD CONSTRAINT chk_document_file_size
                    CHECK (file_size_bytes > 0 AND file_size_bytes <= 20971520)
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_evidence_documents_hash_active
                ON evidence_documents (file_hash)
                WHERE deleted_at IS NULL
        SQL);
    }
};

