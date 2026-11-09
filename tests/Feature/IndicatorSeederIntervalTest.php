<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LembarKerja;
use Database\Seeders\DemoIndicatorAndSampleDocumentsSeeder;
use Database\Seeders\InitialLkSeeder;
use Tests\TestCase;

class IndicatorSeederIntervalTest extends TestCase
{
    public function test_seeded_lembar_kerja_maintain_proper_evaluation_intervals_and_indicators(): void
    {
        $this->seed(InitialLkSeeder::class);
        $this->seed(DemoIndicatorAndSampleDocumentsSeeder::class);

        // 12 Bulan: ZI & SAKIP
        $zi = LembarKerja::with(['indicators.evidenceItems'])->where('code', 'ZI')->where('period', 2026)->first();
        $this->assertNotNull($zi);
        $this->assertSame('12 Bulan', $zi->evaluation_interval);
        $this->assertGreaterThanOrEqual(1, $zi->indicators->count());
        $this->assertGreaterThanOrEqual(1, $zi->indicators->first()->evidenceItems->count());

        $sakip = LembarKerja::with(['indicators.evidenceItems'])->where('code', 'SAKIP')->where('period', 2026)->first();
        $this->assertNotNull($sakip);
        $this->assertSame('12 Bulan', $sakip->evaluation_interval);
        $this->assertGreaterThanOrEqual(1, $sakip->indicators->count());
        $this->assertGreaterThanOrEqual(1, $sakip->indicators->first()->evidenceItems->count());

        // 6 Bulan: PEKPPP
        $pekppp = LembarKerja::with(['indicators.evidenceItems'])->where('code', 'PEKPPP')->where('period', 2026)->first();
        $this->assertNotNull($pekppp);
        $this->assertSame('6 Bulan', $pekppp->evaluation_interval);
        $this->assertGreaterThanOrEqual(1, $pekppp->indicators->count());
        $this->assertGreaterThanOrEqual(1, $pekppp->indicators->first()->evidenceItems->count());

        // 3 Bulan: KIP
        $kip = LembarKerja::with(['indicators.evidenceItems'])->where('code', 'KIP')->where('period', 2026)->first();
        $this->assertNotNull($kip);
        $this->assertSame('3 Bulan', $kip->evaluation_interval);
        $this->assertGreaterThanOrEqual(1, $kip->indicators->count());
        $this->assertGreaterThanOrEqual(1, $kip->indicators->first()->evidenceItems->count());

        // 1 Bulan: DATA_SEKUNDER & DATA_SEKTORAL ada dari InitialLkSeeder
        $dataSekunder = LembarKerja::where('code', 'DATA_SEKUNDER')->where('period', 2026)->first();
        $this->assertNotNull($dataSekunder);
        $this->assertSame('1 Bulan', $dataSekunder->evaluation_interval);

        $dataSektoral = LembarKerja::where('code', 'DATA_SEKTORAL')->where('period', 2026)->first();
        $this->assertNotNull($dataSektoral);
        $this->assertSame('1 Bulan', $dataSektoral->evaluation_interval);
    }
}
