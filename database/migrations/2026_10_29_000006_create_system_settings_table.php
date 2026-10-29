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
        // 1. Tabel system_settings (DATABASE.md Bagian 4.10)
        DB::statement('CREATE TABLE system_settings (
            key VARCHAR(64) PRIMARY KEY,
            value JSONB NOT NULL,
            description TEXT NULL,
            updated_by UUID NULL REFERENCES users(id) ON DELETE SET NULL,
            updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
        );');

        // 2. Data konfigurasi bawaan
        DB::statement("INSERT INTO system_settings (key, value, description) VALUES
            ('matching.similarity_threshold', '75.0'::jsonb, 'Ambang batas skor minimal (0-100) untuk pembuatan match auto'),
            ('matching.exact_code_bonus', '25.0'::jsonb, 'Bobot bonus skor kecocokan kode indikator pada nama file'),
            ('upload.max_file_size_mb', '20'::jsonb, 'Batas maksimal ukuran upload file bukti dukung dalam MB'),
            ('session.idle_timeout_minutes', '120'::jsonb, 'Batas waktu sesi tidak aktif sebelum logout otomatis (AUTH-09)');");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
