<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LembarKerja;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase1PeriodMigrationTest extends TestCase
{
    public function test_lembar_kerja_period_is_integer_and_evaluation_interval_is_string(): void
    {
        $lk = LembarKerja::where('is_active', true)->first();
        $this->assertNotNull($lk);

        $this->assertIsInt($lk->period);
        $this->assertGreaterThanOrEqual(2020, $lk->period);
        $this->assertLessThanOrEqual(2099, $lk->period);

        if (array_key_exists('evaluation_interval', $lk->getAttributes())) {
            $this->assertIsString($lk->evaluation_interval);
            $this->assertContains($lk->evaluation_interval, ['1 Bulan', '3 Bulan', '6 Bulan', '12 Bulan']);
        }
    }

    public function test_lembar_kerja_can_be_queried_by_integer_period(): void
    {
        $lk = LembarKerja::where('period', 2026)->first();
        $this->assertNotNull($lk);
        $this->assertSame(2026, $lk->period);
    }

    public function test_migration_year_detection_from_ally_row(): void
    {
        // Jalur tahun dari sekutu: baris sekutu dengan code sama memiliki tahun 2025
        DB::statement('CREATE TEMP TABLE temp_migration_lk (id uuid, code text, name text, period text, evaluation_interval text, created_at timestamptz)');
        DB::statement('CREATE TEMP TABLE temp_migration_logs (id uuid, lk_id uuid, file_name text)');

        DB::statement("INSERT INTO temp_migration_lk (id, code, name, period, created_at) VALUES 
            ('11111111-1111-1111-1111-111111111111', 'TEST_SEKUTU', 'LK Sekutu 1', '6 Bulan', '2026-01-01'),
            ('22222222-2222-2222-2222-222222222222', 'TEST_SEKUTU', 'LK Sekutu 2', '2025', '2026-01-02')");

        DB::statement(<<<'SQL'
DO $$
DECLARE
    rec RECORD;
    detected_year TEXT;
BEGIN
    FOR rec IN
        SELECT id, code, name, period FROM temp_migration_lk WHERE period !~ '^[0-9]{4}$'
    LOOP
        detected_year := NULL;

        SELECT period INTO detected_year
        FROM temp_migration_lk
        WHERE code = rec.code AND period ~ '^[0-9]{4}$' AND id <> rec.id
        ORDER BY created_at ASC
        LIMIT 1;

        IF detected_year IS NULL THEN
            SELECT substring(rec.name from '([0-9]{4})') INTO detected_year;
        END IF;

        IF detected_year IS NULL THEN
            SELECT substring(file_name from '([0-9]{4})') INTO detected_year
            FROM temp_migration_logs
            WHERE lk_id = rec.id AND file_name ~ '[0-9]{4}'
            LIMIT 1;
        END IF;

        IF detected_year IS NULL THEN
            RAISE EXCEPTION 'Gagal mendeteksi tahun untuk LK id=% code=% name=%', rec.id, rec.code, rec.name;
        END IF;

        UPDATE temp_migration_lk
        SET period = detected_year
        WHERE id = rec.id;
    END LOOP;
END $$;
SQL
        );

        $period = DB::table('temp_migration_lk')->where('id', '11111111-1111-1111-1111-111111111111')->value('period');
        $this->assertSame('2025', $period);

        DB::statement('DROP TABLE temp_migration_lk');
        DB::statement('DROP TABLE temp_migration_logs');
    }

    public function test_migration_year_detection_raises_exception_when_undetected(): void
    {
        DB::statement('CREATE TEMP TABLE temp_migration_lk_err (id uuid, code text, name text, period text, evaluation_interval text, created_at timestamptz)');
        DB::statement('CREATE TEMP TABLE temp_migration_logs_err (id uuid, lk_id uuid, file_name text)');

        // Baris tanpa tahun di nama, tanpa sekutu bertahun, tanpa log impor
        DB::statement("INSERT INTO temp_migration_lk_err (id, code, name, period, created_at) VALUES 
            ('33333333-3333-3333-3333-333333333333', 'TEST_NO_YEAR', 'Instrumen Pengujian Tanpa Tahun', '6 Bulan', '2026-01-01')");

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Gagal mendeteksi tahun untuk LK');

        try {
            DB::statement(<<<'SQL'
DO $$
DECLARE
    rec RECORD;
    detected_year TEXT;
BEGIN
    FOR rec IN
        SELECT id, code, name, period FROM temp_migration_lk_err WHERE period !~ '^[0-9]{4}$'
    LOOP
        detected_year := NULL;

        SELECT period INTO detected_year
        FROM temp_migration_lk_err
        WHERE code = rec.code AND period ~ '^[0-9]{4}$' AND id <> rec.id
        ORDER BY created_at ASC
        LIMIT 1;

        IF detected_year IS NULL THEN
            SELECT substring(rec.name from '([0-9]{4})') INTO detected_year;
        END IF;

        IF detected_year IS NULL THEN
            SELECT substring(file_name from '([0-9]{4})') INTO detected_year
            FROM temp_migration_logs_err
            WHERE lk_id = rec.id AND file_name ~ '[0-9]{4}'
            LIMIT 1;
        END IF;

        IF detected_year IS NULL THEN
            RAISE EXCEPTION 'Gagal mendeteksi tahun untuk LK id=% code=% name=%', rec.id, rec.code, rec.name;
        END IF;

        UPDATE temp_migration_lk_err
        SET period = detected_year
        WHERE id = rec.id;
    END LOOP;
END $$;
SQL
            );
        } finally {
            DB::statement('DROP TABLE IF EXISTS temp_migration_lk_err');
            DB::statement('DROP TABLE IF EXISTS temp_migration_logs_err');
        }
    }

    public function test_lembar_kerja_partial_unique_index_allows_duplicate_code_period_if_soft_deleted(): void
    {
        $code = 'SOFT_UNIQ_'.strtoupper(Str::random(5));

        $lk1 = LembarKerja::create([
            'id' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Soft Unique Test 1',
            'category' => 'internal',
            'period' => 2026,
            'evaluation_interval' => '12 Bulan',
            'is_active' => true,
        ]);

        $lk1->delete(); // Soft delete

        // Membuat LK dengan code, period, interval yang sama diperbolehkan karena yang pertama sudah soft-deleted
        $lk2 = LembarKerja::create([
            'id' => (string) Str::uuid(),
            'code' => $code,
            'name' => 'Soft Unique Test 2',
            'category' => 'internal',
            'period' => 2026,
            'evaluation_interval' => '12 Bulan',
            'is_active' => true,
        ]);

        $this->assertNotNull($lk2);

        // Membersihkan
        $lk1->forceDelete();
        $lk2->forceDelete();
    }
}
