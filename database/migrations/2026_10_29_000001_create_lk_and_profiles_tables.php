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
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'lk_category') THEN
                CREATE TYPE lk_category AS ENUM ('internal', 'eksternal');
            END IF;
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'import_parsing_strategy') THEN
                CREATE TYPE import_parsing_strategy AS ENUM (
                    'numbered_list_single_cell',
                    'checklist_sheet',
                    'single_item_per_row'
                );
            END IF;
        END $$;");

        // 2. Tabel lk_import_profiles (DATABASE.md Bagian 4.3)
        DB::statement("CREATE TABLE lk_import_profiles (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            name VARCHAR(100) NOT NULL,
            lk_code VARCHAR(32) NOT NULL,
            sheet_name VARCHAR(100) NOT NULL,
            header_row INTEGER NOT NULL DEFAULT 1,
            parsing_strategy import_parsing_strategy NOT NULL,
            column_mapping JSONB NOT NULL,
            target_unit_column VARCHAR(64) NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT chk_header_row_positive CHECK (header_row > 0)
        );");

        // 3. Tabel lembar_kerja (DATABASE.md Bagian 4.2)
        DB::statement("CREATE TABLE lembar_kerja (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            code VARCHAR(32) NOT NULL,
            name VARCHAR(255) NOT NULL,
            category lk_category NOT NULL,
            period INTEGER NOT NULL,
            description TEXT NULL,
            default_profile_id UUID NULL REFERENCES lk_import_profiles(id) ON DELETE SET NULL,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMPTZ NULL,

            CONSTRAINT uq_lk_code_period UNIQUE (code, period),
            CONSTRAINT chk_lk_period CHECK (period >= 2020 AND period <= 2099)
        );");

        DB::statement("CREATE INDEX idx_lk_category_period ON lembar_kerja(category, period) WHERE deleted_at IS NULL;");

        // 4. Tabel lk_indicators (DATABASE.md Bagian 4.4)
        DB::statement("CREATE TABLE lk_indicators (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            lk_id UUID NOT NULL REFERENCES lembar_kerja(id) ON DELETE CASCADE,
            parent_id UUID NULL REFERENCES lk_indicators(id) ON DELETE CASCADE,
            code VARCHAR(64) NOT NULL,
            title TEXT NOT NULL,
            order_index INTEGER NOT NULL DEFAULT 0,
            evidence_link TEXT NULL,
            pic VARCHAR(255) NULL,
            metadata JSONB NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMPTZ NULL,

            CONSTRAINT uq_indicator_lk_code UNIQUE (lk_id, code),
            CONSTRAINT chk_indicator_link_scheme CHECK (
                evidence_link IS NULL OR 
                evidence_link ~* '^https?://.+'
            )
        );");

        DB::statement("CREATE INDEX idx_indicators_lk_id ON lk_indicators(lk_id) WHERE deleted_at IS NULL;");
        DB::statement("CREATE INDEX idx_indicators_parent_id ON lk_indicators(parent_id);");
        DB::statement("CREATE INDEX idx_indicators_order ON lk_indicators(lk_id, order_index);");

        // 5. Tabel lk_evidence_items (DATABASE.md Bagian 4.5)
        DB::statement("CREATE TABLE lk_evidence_items (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            indicator_id UUID NOT NULL REFERENCES lk_indicators(id) ON DELETE CASCADE,
            raw_template_name TEXT NOT NULL,
            resolved_name TEXT NOT NULL,
            order_index INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,

            CONSTRAINT uq_evidence_item_indicator_resolved UNIQUE (indicator_id, resolved_name)
        );");

        DB::statement("CREATE INDEX idx_evidence_items_indicator_id ON lk_evidence_items(indicator_id);");
        DB::statement("CREATE INDEX idx_evidence_items_trgm_resolved ON lk_evidence_items USING GIN (resolved_name gin_trgm_ops);");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lk_evidence_items');
        Schema::dropIfExists('lk_indicators');
        Schema::dropIfExists('lembar_kerja');
        Schema::dropIfExists('lk_import_profiles');
        DB::statement('DROP TYPE IF EXISTS import_parsing_strategy;');
        DB::statement('DROP TYPE IF EXISTS lk_category;');
    }
};

