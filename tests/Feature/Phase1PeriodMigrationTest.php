<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LembarKerja;
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
}
