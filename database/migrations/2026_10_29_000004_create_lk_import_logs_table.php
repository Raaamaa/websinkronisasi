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
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'import_session_status') THEN
                CREATE TYPE import_session_status AS ENUM ('previewed', 'committed', 'failed');
            END IF;
        END $$;");

        // 2. Tabel lk_import_logs (DATABASE.md Bagian 4.8)
        DB::statement('CREATE TABLE lk_import_logs (
            id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            lk_id UUID NOT NULL REFERENCES lembar_kerja(id) ON DELETE CASCADE,
            user_id UUID NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
            profile_id UUID NOT NULL REFERENCES lk_import_profiles(id) ON DELETE RESTRICT,
            file_name VARCHAR(255) NOT NULL,
            file_hash VARCHAR(64) NOT NULL,
            status import_session_status NOT NULL,
            total_rows_read INTEGER NOT NULL DEFAULT 0,
            imported_indicators INTEGER NOT NULL DEFAULT 0,
            imported_items INTEGER NOT NULL DEFAULT 0,
            skipped_rows INTEGER NOT NULL DEFAULT 0,
            summary_details JSONB NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        );');

        DB::statement('CREATE INDEX idx_import_logs_lk ON lk_import_logs(lk_id, created_at DESC);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lk_import_logs');
        DB::statement('DROP TYPE IF EXISTS import_session_status;');
    }
};
