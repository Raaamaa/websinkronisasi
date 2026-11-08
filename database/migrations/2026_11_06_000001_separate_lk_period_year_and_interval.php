<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PERINGATAN INTEGRITAS DATA:
 * Migrasi ini bersifat DATA-DESTRUKTIF SATU ARAH untuk duplikasi baris.
 * Kolom 'period' (varchar) dipisahkan menjadi 'period' (integer tahun) dan 'evaluation_interval' (varchar interval).
 * Baris duplikat LK dari periode lama dikonsolidasikan dan relasi child (lk_indicators, lk_import_logs)
 * dialihkan ke baris target sebelum baris duplikat dihapus.
 * Rollback (down) memulihkan struktur skema DDL (kolom varchar dan constraints) serta mengembalikan string interval ke kolom period.
 * Pemulihan baris yang telah terkonsolidasi penuh hanya dapat dilakukan menggunakan salinan backup pg_dump sebelum migrasi.
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
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS uq_lk_code_period_interval;');
        DB::statement('DROP INDEX IF EXISTS uq_lk_code_period_interval;');

        // 3. Normalisasi data interval, deteksi tahun, dan konsolidasi menggunakan blok PL/pgSQL nowdoc
        DB::statement(<<<'SQL'
DO $$
DECLARE
    rec RECORD;
    target_id UUID;
    detected_year TEXT;
BEGIN
    -- Isi evaluation_interval bagi baris yang periodnya adalah nama interval
    UPDATE lembar_kerja
    SET evaluation_interval = period
    WHERE period IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan');

    -- Untuk baris dengan interval default (misal baris yang periodnya adalah 4 digit tahun)
    UPDATE lembar_kerja
    SET evaluation_interval = '12 Bulan'
    WHERE evaluation_interval IS NULL OR evaluation_interval NOT IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan');

    -- Deteksi tahun bagi baris yang periodnya berupa nama interval
    FOR rec IN
        SELECT id, code, name, period FROM lembar_kerja WHERE period !~ '^[0-9]{4}$'
    LOOP
        detected_year := NULL;

        -- (1) Deteksi tahun dari baris sekutu dengan code sama yang memiliki 4 digit tahun
        SELECT period INTO detected_year
        FROM lembar_kerja
        WHERE code = rec.code AND period ~ '^[0-9]{4}$' AND id <> rec.id
        ORDER BY created_at ASC
        LIMIT 1;

        -- (2) Deteksi tahun dari regex 4 digit pada name LK
        IF detected_year IS NULL THEN
            SELECT substring(rec.name from '([0-9]{4})') INTO detected_year;
        END IF;

        -- (3) Deteksi tahun dari regex 4 digit pada file_name log impor
        IF detected_year IS NULL THEN
            SELECT substring(file_name from '([0-9]{4})') INTO detected_year
            FROM lk_import_logs
            WHERE lk_id = rec.id AND file_name ~ '[0-9]{4}'
            LIMIT 1;
        END IF;

        -- (4) Jika tetap tidak terdeteksi, gagalkan dengan pesan eksplisit
        IF detected_year IS NULL THEN
            RAISE EXCEPTION 'Gagal mendeteksi tahun untuk LK id=% code=% name=%', rec.id, rec.code, rec.name;
        END IF;

        UPDATE lembar_kerja
        SET period = detected_year
        WHERE id = rec.id;
    END LOOP;

    -- Konsolidasi baris duplikat (code, period, evaluation_interval)
    FOR rec IN
        SELECT code, period, evaluation_interval, count(*) as cnt
        FROM lembar_kerja
        GROUP BY code, period, evaluation_interval
        HAVING count(*) > 1
    LOOP
        -- Tentukan baris primer (prioritaskan yang memiliki log impor / indikator terbanyak)
        SELECT id INTO target_id
        FROM lembar_kerja
        WHERE code = rec.code AND period = rec.period AND evaluation_interval = rec.evaluation_interval
        ORDER BY (
            (SELECT count(*) FROM lk_indicators WHERE lk_id = lembar_kerja.id) +
            (SELECT count(*) FROM lk_import_logs WHERE lk_id = lembar_kerja.id)
        ) DESC, created_at ASC
        LIMIT 1;

        -- Alihkan child records lk_indicators
        UPDATE lk_indicators
        SET lk_id = target_id
        WHERE lk_id IN (
            SELECT id FROM lembar_kerja
            WHERE code = rec.code AND period = rec.period AND evaluation_interval = rec.evaluation_interval AND id != target_id
        );

        -- Alihkan child records lk_import_logs
        UPDATE lk_import_logs
        SET lk_id = target_id
        WHERE lk_id IN (
            SELECT id FROM lembar_kerja
            WHERE code = rec.code AND period = rec.period AND evaluation_interval = rec.evaluation_interval AND id != target_id
        );

        -- Hapus baris sekunder yang sudah dialihkan
        DELETE FROM lembar_kerja
        WHERE code = rec.code AND period = rec.period AND evaluation_interval = rec.evaluation_interval AND id != target_id;
    END LOOP;
END $$;
SQL
        );

        // 4. Ubah tipe data kolom period menjadi integer
        DB::statement('ALTER TABLE lembar_kerja ALTER COLUMN period TYPE integer USING period::integer;');

        // 5. Pasang constraints baru dan partial unique index
        DB::statement('ALTER TABLE lembar_kerja ADD CONSTRAINT chk_lk_period CHECK (period >= 2020 AND period <= 2099);');
        DB::statement("ALTER TABLE lembar_kerja ADD CONSTRAINT chk_lk_interval CHECK (evaluation_interval IN ('1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan'));");
        DB::statement('CREATE UNIQUE INDEX uq_lk_code_period_interval ON lembar_kerja (code, period, evaluation_interval) WHERE deleted_at IS NULL;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback skema murni (data-destruktif satu arah untuk duplikasi baris)
        DB::statement('DROP INDEX IF EXISTS uq_lk_code_period_interval;');
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS chk_lk_interval;');
        DB::statement('ALTER TABLE lembar_kerja DROP CONSTRAINT IF EXISTS chk_lk_period;');

        DB::statement('ALTER TABLE lembar_kerja ALTER COLUMN period TYPE varchar(50) USING period::varchar;');

        if (Schema::hasColumn('lembar_kerja', 'evaluation_interval')) {
            DB::statement("UPDATE lembar_kerja SET period = evaluation_interval WHERE evaluation_interval IN ('1 Bulan', '3 Bulan', '6 Bulan');");
            Schema::table('lembar_kerja', function (Blueprint $table): void {
                $table->dropColumn('evaluation_interval');
            });
        }

        DB::statement("ALTER TABLE lembar_kerja ADD CONSTRAINT chk_lk_period CHECK ((period::text = ANY (ARRAY['1 Bulan'::character varying::text, '3 Bulan'::character varying::text, '6 Bulan'::character varying::text, '12 Bulan'::character varying::text])) OR period::text ~ '^[0-9]{4}$'::text);");
    }
};
