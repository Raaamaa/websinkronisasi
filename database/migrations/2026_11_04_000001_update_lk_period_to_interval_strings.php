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
        // 1. Drop check constraint lama
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS chk_lk_period;');

        // 2. Ubah tipe data period menjadi VARCHAR(50)
        DB::statement('ALTER TABLE lembar_kerja ALTER COLUMN period TYPE VARCHAR(50) USING period::varchar;');

        // 3. Normalisasi data lama menjadi interval '12 Bulan' jika sebelumnya berupa angka tahun
        DB::statement("UPDATE lembar_kerja SET period = '12 Bulan' WHERE period NOT IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan');");

        // 4. Pasang check constraint baru untuk interval evaluasi (mendukung 1 Bulan, 3 Bulan, 6 Bulan, 12 Bulan serta tahun 4 digit untuk kompatibilitas data/test)
        DB::statement("ALTER TABLE lembar_kerja ADD CONSTRAINT chk_lk_period CHECK (period IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan') OR period ~ '^[0-9]{4}$');");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS chk_lk_period;');
        DB::statement("DELETE FROM lembar_kerja WHERE period IN ('1 Bulan', '3 Bulan', '6 Bulan');");
        DB::statement("UPDATE lembar_kerja SET period = '2026' WHERE period = '12 Bulan';");
        DB::statement('ALTER TABLE lembar_kerja ALTER COLUMN period TYPE INTEGER USING period::integer;');
        DB::statement('ALTER TABLE lembar_kerja ADD CONSTRAINT chk_lk_period CHECK (period >= 2020 AND period <= 2099);');
    }
};
