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
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'match_status') THEN
                CREATE TYPE match_status AS ENUM ('auto', 'confirmed', 'rejected');
            END IF;
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'match_method') THEN
                CREATE TYPE match_method AS ENUM (
                    'exact_number',
                    'exact_code',
                    'title_similarity',
                    'content_similarity',
                    'manual'
                );
            END IF;
        END $$;");

        // 2. Tabel document_matches (DATABASE.md Bagian 4.7)
        DB::statement("CREATE TABLE document_matches (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            document_id UUID NOT NULL REFERENCES evidence_documents(id) ON DELETE CASCADE,
            indicator_id UUID NOT NULL REFERENCES lk_indicators(id) ON DELETE CASCADE,
            matched_item_id UUID NULL REFERENCES lk_evidence_items(id) ON DELETE SET NULL,
            status match_status NOT NULL DEFAULT 'auto',
            match_method match_method NOT NULL,
            confidence_score NUMERIC(5,2) NOT NULL DEFAULT 0.00,
            match_reasons JSONB NOT NULL DEFAULT '{}'::jsonb,
            verified_by UUID NULL REFERENCES users(id) ON DELETE SET NULL,
            verified_at TIMESTAMPTZ NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT uq_matches_doc_indicator UNIQUE (document_id, indicator_id),
            CONSTRAINT chk_matches_score CHECK (confidence_score >= 0.00 AND confidence_score <= 100.00)
        );");

        // Indeks penting untuk matriks monitor (MON-02, MON-03, NFR-05)
        DB::statement("CREATE INDEX idx_matches_monitor_lookup ON document_matches(document_id, indicator_id) 
        WHERE status IN ('auto', 'confirmed');");

        // Indeks antrean verifikasi admin
        DB::statement("CREATE INDEX idx_matches_verification_queue ON document_matches(status, created_at) 
        WHERE status = 'auto';");

        DB::statement("CREATE INDEX idx_matches_indicator ON document_matches(indicator_id);");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_matches');
        DB::statement('DROP TYPE IF EXISTS match_method;');
        DB::statement('DROP TYPE IF EXISTS match_status;');
    }
};

