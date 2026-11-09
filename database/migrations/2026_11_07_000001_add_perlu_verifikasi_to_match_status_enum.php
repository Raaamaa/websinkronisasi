<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * PostgreSQL ALTER TYPE cannot run inside a multi-statement transaction.
     */
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement("ALTER TYPE match_status ADD VALUE IF NOT EXISTS 'perlu_verifikasi';");

        // Daftarkan baseline konfigurasi matching.verification_threshold bila belum ada
        DB::statement("
            INSERT INTO system_settings (key, value, description)
            VALUES ('matching.verification_threshold', '60.0'::jsonb, 'Ambang batas skor minimal (0-100) untuk antrean perlu verifikasi')
            ON CONFLICT (key) DO NOTHING;
        ");
    }

    public function down(): void
    {
        // Nilai ENUM di PostgreSQL tidak mendukung DROP VALUE secara langsung
    }
};
