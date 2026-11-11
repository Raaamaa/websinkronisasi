<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Memetakan dokumen lama: yang punya match berstatus 'confirmed' diset
     * menjadi 'disetujui' dengan link_source = 'backfill' di document_lk_links;
     * sisanya dipertahankan sebagai 'menunggu'. Operasi bersifat idempoten.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::statement(<<<'SQL'
                UPDATE evidence_documents AS ed
                SET
                    approval_status = 'disetujui',
                    source_type = COALESCE(NULLIF(ed.source_type, ''), 'upload'),
                    approved_at = COALESCE(ed.approved_at, CURRENT_TIMESTAMP),
                    updated_at = CURRENT_TIMESTAMP
                WHERE EXISTS (
                    SELECT 1
                    FROM document_matches AS dm
                    WHERE dm.document_id = ed.id
                      AND dm.status = 'confirmed'
                )
            SQL);

            DB::statement(<<<'SQL'
                INSERT INTO document_lk_links (
                    id,
                    document_id,
                    lk_id,
                    indicator_id,
                    link_source,
                    created_by,
                    created_at,
                    updated_at
                )
                SELECT
                    gen_random_uuid(),
                    dm.document_id,
                    ind.lk_id,
                    dm.indicator_id,
                    'backfill',
                    dm.verified_by,
                    COALESCE(dm.verified_at, CURRENT_TIMESTAMP),
                    CURRENT_TIMESTAMP
                FROM document_matches AS dm
                INNER JOIN lk_indicators AS ind ON ind.id = dm.indicator_id
                WHERE dm.status = 'confirmed'
                ON CONFLICT DO NOTHING
            SQL);

            DB::statement(<<<'SQL'
                UPDATE evidence_documents AS ed
                SET
                    approval_status = 'menunggu',
                    source_type = COALESCE(NULLIF(ed.source_type, ''), 'upload')
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM document_matches AS dm
                    WHERE dm.document_id = ed.id
                      AND dm.status = 'confirmed'
                )
                  AND ed.approval_status = 'menunggu'
            SQL);
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::statement(<<<'SQL'
                DELETE FROM document_lk_links
                WHERE link_source = 'backfill'
            SQL);

            DB::statement(<<<'SQL'
                UPDATE evidence_documents
                SET
                    approval_status = 'menunggu',
                    approved_at = NULL
                WHERE approval_status = 'disetujui'
                  AND approved_by IS NULL
                  AND EXISTS (
                      SELECT 1
                      FROM document_matches AS dm
                      WHERE dm.document_id = evidence_documents.id
                        AND dm.status = 'confirmed'
                  )
            SQL);
        });
    }
};

