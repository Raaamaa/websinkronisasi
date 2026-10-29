<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tipe ENUM
        DB::statement("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'document_processing_status') THEN
                CREATE TYPE document_processing_status AS ENUM (
                    'memproses',
                    'selesai',
                    'perlu_review_manual',
                    'gagal'
                );
            END IF;
        END $$;");

        // 2. Tabel evidence_documents (DATABASE.md Bagian 4.6)
        DB::statement("CREATE TABLE evidence_documents (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            document_name TEXT NOT NULL,
            document_number VARCHAR(128) NULL,
            year INTEGER NOT NULL,
            document_type VARCHAR(64) NULL,
            external_link TEXT NULL,
            file_path TEXT NOT NULL,
            original_filename TEXT NOT NULL,
            mime_type VARCHAR(128) NOT NULL,
            file_size_bytes BIGINT NOT NULL,
            file_hash VARCHAR(64) NOT NULL,
            extracted_text TEXT NULL,
            processing_status document_processing_status NOT NULL DEFAULT 'memproses',
            error_message TEXT NULL,
            uploaded_by UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMPTZ NULL,

            CONSTRAINT uq_documents_file_hash UNIQUE (file_hash),
            CONSTRAINT chk_document_file_size CHECK (file_size_bytes > 0 AND file_size_bytes <= 20971520),
            CONSTRAINT chk_document_external_link CHECK (
                external_link IS NULL OR 
                external_link ~* '^https?://.+'
            )
        );");

        DB::statement('CREATE INDEX idx_documents_year ON evidence_documents(year) WHERE deleted_at IS NULL;');
        DB::statement('CREATE INDEX idx_documents_type ON evidence_documents(document_type) WHERE deleted_at IS NULL;');
        DB::statement('CREATE INDEX idx_documents_status ON evidence_documents(processing_status) WHERE deleted_at IS NULL;');
        DB::statement('CREATE INDEX idx_documents_uploaded_by ON evidence_documents(uploaded_by);');

        DB::statement('CREATE INDEX idx_documents_trgm_name ON evidence_documents USING GIN (document_name gin_trgm_ops);');
        DB::statement('CREATE INDEX idx_documents_trgm_number ON evidence_documents USING GIN (document_number gin_trgm_ops) WHERE document_number IS NOT NULL;');

        DB::statement("CREATE INDEX idx_documents_fts ON evidence_documents USING GIN (
            to_tsvector('indonesian', 
                coalesce(document_name, '') || ' ' || 
                coalesce(document_number, '') || ' ' || 
                coalesce(extracted_text, '')
            )
        ) WHERE deleted_at IS NULL;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_documents');
        DB::statement('DROP TYPE IF EXISTS document_processing_status;');
    }
};
