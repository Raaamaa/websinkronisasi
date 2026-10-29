<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tipe ENUM user_role
        DB::statement("DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role') THEN
                CREATE TYPE user_role AS ENUM ('super_admin', 'admin', 'pegawai');
            END IF;
        END $$;");

        // 2. Tabel users sesuai spesifikasi DATABASE.md Bagian 4.1
        DB::statement("CREATE TABLE users (
            id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
            email VARCHAR(255) NOT NULL,
            nip VARCHAR(64) NULL,
            name VARCHAR(255) NOT NULL,
            role user_role NOT NULL DEFAULT 'pegawai',
            password_hash VARCHAR(255) NULL,
            must_change_password BOOLEAN NOT NULL DEFAULT FALSE,
            is_active BOOLEAN NOT NULL DEFAULT TRUE,
            auth_provider VARCHAR(32) NOT NULL DEFAULT 'sso',
            auth_provider_id VARCHAR(255) NULL,
            remember_token VARCHAR(100) NULL,
            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMPTZ NULL,

            CONSTRAINT uq_users_email UNIQUE (email),
            CONSTRAINT uq_users_nip UNIQUE (nip),
            CONSTRAINT chk_users_auth CHECK (
                (auth_provider = 'local' AND password_hash IS NOT NULL) OR
                (auth_provider = 'sso')
            )
        );");

        DB::statement('CREATE INDEX idx_users_email_active ON users(email, is_active) WHERE deleted_at IS NULL;');
        DB::statement('CREATE INDEX idx_users_nip_active ON users(nip, is_active) WHERE deleted_at IS NULL AND nip IS NOT NULL;');
        DB::statement('CREATE INDEX idx_users_role ON users(role) WHERE deleted_at IS NULL;');

        // 3. Trigger proteksi Super Admin terakhir (AUTH-06, AC-04)
        DB::statement("CREATE OR REPLACE FUNCTION trg_prevent_last_super_admin_removal()
        RETURNS TRIGGER AS \$\$
        DECLARE
            active_super_admins_count INTEGER;
        BEGIN
            IF (TG_OP = 'DELETE' AND OLD.role = 'super_admin' AND OLD.is_active = TRUE AND OLD.deleted_at IS NULL) OR
               (TG_OP = 'UPDATE' AND OLD.role = 'super_admin' AND OLD.is_active = TRUE AND OLD.deleted_at IS NULL AND 
                (NEW.is_active = FALSE OR NEW.role != 'super_admin' OR NEW.deleted_at IS NOT NULL)) THEN
                
                SELECT COUNT(*) INTO active_super_admins_count
                FROM users
                WHERE role = 'super_admin'
                  AND is_active = TRUE
                  AND deleted_at IS NULL
                  AND id != OLD.id;

                IF active_super_admins_count < 1 THEN
                    RAISE EXCEPTION 'Aksi ditolak: Sistem harus memiliki setidaknya satu Super Admin aktif!' USING ERRCODE = 'P0001';
                END IF;
            END IF;

            IF TG_OP = 'DELETE' THEN
                RETURN OLD;
            ELSE
                RETURN NEW;
            END IF;
        END;
        \$\$ LANGUAGE plpgsql;");

        DB::statement('CREATE TRIGGER prevent_last_super_admin_removal
        BEFORE UPDATE OR DELETE ON users
        FOR EACH ROW
        EXECUTE FUNCTION trg_prevent_last_super_admin_removal();');

        // 4. Tabel password_reset_tokens
        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestampTz('created_at')->nullable();
        });

        // 5. Tabel sessions (kompatibel dengan UUID user_id)
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        DB::statement('DROP TRIGGER IF EXISTS prevent_last_super_admin_removal ON users;');
        DB::statement('DROP FUNCTION IF EXISTS trg_prevent_last_super_admin_removal();');
        Schema::dropIfExists('users');
        DB::statement('DROP TYPE IF EXISTS user_role;');
    }
};
