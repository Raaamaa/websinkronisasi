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
        // 1. Tabel audit_logs (DATABASE.md Bagian 4.9)
        DB::statement('CREATE TABLE audit_logs (
            id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            user_id UUID NULL REFERENCES users(id) ON DELETE SET NULL,
            action VARCHAR(64) NOT NULL,
            entity_type VARCHAR(64) NOT NULL,
            entity_id VARCHAR(64) NOT NULL,
            old_values JSONB NULL,
            new_values JSONB NULL,
            ip_address INET NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        );');

        DB::statement('CREATE INDEX idx_audit_logs_user ON audit_logs(user_id, created_at DESC);');
        DB::statement('CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);');
        DB::statement('CREATE INDEX idx_audit_logs_action ON audit_logs(action, created_at DESC);');
        DB::statement('CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at DESC);');

        // 2. Trigger anti-tampering (AUDIT-02)
        DB::statement("CREATE OR REPLACE FUNCTION trg_prevent_audit_tampering()
        RETURNS TRIGGER AS \$\$
        BEGIN
            RAISE EXCEPTION 'Operasi % pada tabel audit_logs dilarang demi integritas audit trail!', TG_OP USING ERRCODE = 'P0002';
        END;
        \$\$ LANGUAGE plpgsql;");

        DB::statement('CREATE TRIGGER prevent_audit_logs_mutation
        BEFORE UPDATE OR DELETE ON audit_logs
        FOR EACH ROW EXECUTE FUNCTION trg_prevent_audit_tampering();');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS prevent_audit_logs_mutation ON audit_logs;');
        DB::statement('DROP FUNCTION IF EXISTS trg_prevent_audit_tampering();');
        Schema::dropIfExists('audit_logs');
    }
};
