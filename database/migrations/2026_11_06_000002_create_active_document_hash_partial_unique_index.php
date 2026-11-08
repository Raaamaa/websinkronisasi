<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE evidence_documents DROP CONSTRAINT IF EXISTS uq_documents_file_hash;');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_evidence_documents_hash_active ON evidence_documents (file_hash) WHERE deleted_at IS NULL;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_evidence_documents_hash_active;');
        DB::statement('ALTER TABLE evidence_documents ADD CONSTRAINT uq_documents_file_hash UNIQUE (file_hash);');
    }
};
