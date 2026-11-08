<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERINGATAN INTEGRITAS DATA:
 * Migrasi ini bersifat DATA-DESTRUKTIF SATU ARAH.
 * Kolom 'period' (varchar) dipisahkan menjadi 'period' (integer tahun) dan 'evaluation_interval' (varchar interval).
 * Baris duplikat LK dari periode lama dikonsolidasikan dan relasi child (lk_indicators, lk_import_logs)
 * dialihkan ke baris target sebelum baris duplikat dihapus.
 * Rollback (down) HANYA memulihkan struktur skema DDL (kolom varchar dan constraints),
 * TIDAK memulihkan baris LK yang telah dikonsolidasikan/dihapus.
 * Pemulihan data penuh hanya dapat dilakukan menggunakan salinan backup pg_dump sebelum migrasi.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom evaluation_interval jika belum ada
        if (! Schema::hasColumn('lembar_kerja', 'evaluation_interval')) {
            Schema::table('lembar_kerja', function (Blueprint $table): void {
                $table->string('evaluation_interval', 50)->default('12 Bulan')->after('period');
            });
        }

        // 2. Drop constraints lama
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS chk_lk_period;');
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS uq_lk_code_period;');

        // 3. Normalisasi data interval dan tahun menggunakan blok PL/pgSQL nowdoc
        DB::statement(<<<'SQL'
DO $$
DECLARE
    rec RECORD;
    target_id UUID;
BEGIN
    -- Isi evaluation_interval bagi baris yang periodnya adalah nama interval
    UPDATE lembar_kerja
    SET evaluation_interval = period
    WHERE period IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan');

    -- Untuk baris dengan interval default
    UPDATE lembar_kerja
    SET evaluation_interval = '12 Bulan'
    WHERE code = 'ZI' AND (evaluation_interval IS NULL OR evaluation_interval NOT IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan'));

    -- Konsolidasi baris duplikat (code, evaluation_interval):
    -- Alihkan relasi lk_import_logs dan lk_indicators dari baris duplikat ke baris primer
    FOR rec IN
        SELECT code, evaluation_interval, count(*) as cnt
        FROM lembar_kerja
        WHERE period IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan', '2026')
        GROUP BY code, evaluation_interval
        HAVING count(*) > 1
    LOOP
        -- Tentukan baris primer (prioritaskan yang period='2026')
        SELECT id INTO target_id
        FROM lembar_kerja
        WHERE code = rec.code AND evaluation_interval = rec.evaluation_interval
        ORDER BY (CASE WHEN period = '2026' THEN 0 ELSE 1 END), created_at ASC
        LIMIT 1;

        -- Alihkan child records lk_indicators
        UPDATE lk_indicators
        SET lk_id = target_id
        WHERE lk_id IN (
            SELECT id FROM lembar_kerja
            WHERE code = rec.code AND evaluation_interval = rec.evaluation_interval AND id != target_id
        );

        -- Alihkan child records lk_import_logs
        UPDATE lk_import_logs
        SET lk_id = target_id
        WHERE lk_id IN (
            SELECT id FROM lembar_kerja
            WHERE code = rec.code AND evaluation_interval = rec.evaluation_interval AND id != target_id
        );

        -- Hapus baris sekunder yang sudah dialihkan
        DELETE FROM lembar_kerja
        WHERE code = rec.code AND evaluation_interval = rec.evaluation_interval AND id != target_id;
    END LOOP;

    -- Update period semua baris yang tersisa menjadi integer tahun (2026 jika berupa string interval)
    UPDATE lembar_kerja
    SET period = '2026'
    WHERE period !~ '^[0-9]{4}$';
END $$;
SQL
        );

        // 4. Ubah tipe data kolom period menjadi integer
        DB::statement('ALTER TABLE lembar_kerja ALTER COLUMN period TYPE integer USING period::integer;');

        // 5. Pasang constraints baru
        DB::statement('ALTER TABLE lembar_kerja ADD CONSTRAINT chk_lk_period CHECK (period >= 2020 AND period <= 2099);');
        DB::statement("ALTER TABLE lembar_kerja ADD CONSTRAINT chk_lk_interval CHECK (evaluation_interval IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan'));");
        DB::statement('ALTER TABLE lembar_kerja ADD CONSTRAINT uq_lk_code_period_interval UNIQUE (code, period, evaluation_interval);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback skema murni (data-destruktif satu arah)
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS uq_lk_code_period_interval;');
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS chk_lk_interval;');
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS chk_lk_period;');

        DB::statement('ALTER TABLE lembar_kerja ALTER COLUMN period TYPE varchar(50) USING period::varchar;');
        DB::statement("ALTER TABLE lembar_kerja ADD CONSTRAINT chk_lk_period CHECK ((period::text = ANY (ARRAY['1 Bulan'::character varying::text, '3 Bulan'::character varying::text, '6 Bulan'::character varying::text, '12 Bulan'::character varying::text])) OR period::text ~ '^[0-9]{4}$'::text);");

        if (Schema::hasColumn('lembar_kerja', 'evaluation_interval')) {
            Schema::table('lembar_kerja', function (Blueprint $table): void {
                $table->dropColumn('evaluation_interval');
            });
        }
    }
};
