<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LembarKerja;
use Database\Seeders\InitialLkSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class IndicatorSeederIntervalTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seeded_lembar_kerja_maintain_proper_evaluation_intervals_and_indicators(): void
    {
        $this->seed(InitialLkSeeder::class);

        // 12 Bulan: ZI & SAKIP
        $zi = LembarKerja::where('code', 'ZI')->where('period', 2026)->first();
        $this->assertNotNull($zi);
        $this->assertSame('internal', $zi->category);
        $this->assertSame('12 Bulan', $zi->evaluation_interval);

        $sakip = LembarKerja::where('code', 'SAKIP')->where('period', 2026)->first();
        $this->assertNotNull($sakip);
        $this->assertSame('internal', $sakip->category);
        $this->assertSame('12 Bulan', $sakip->evaluation_interval);

        // 6 Bulan: PEKPPP
        $pekppp = LembarKerja::where('code', 'PEKPPP')->where('period', 2026)->first();
        $this->assertNotNull($pekppp);
        $this->assertSame('internal', $pekppp->category);
        $this->assertSame('6 Bulan', $pekppp->evaluation_interval);

        // 3 Bulan: KIP
        $kip = LembarKerja::where('code', 'KIP')->where('period', 2026)->first();
        $this->assertNotNull($kip);
        $this->assertSame('internal', $kip->category);
        $this->assertSame('3 Bulan', $kip->evaluation_interval);

        // 1 Bulan: DATA_SEKUNDER & DATA_SEKTORAL
        $dataSekunder = LembarKerja::where('code', 'DATA_SEKUNDER')->where('period', 2026)->first();
        $this->assertNotNull($dataSekunder);
        $this->assertSame('eksternal', $dataSekunder->category);
        $this->assertSame('1 Bulan', $dataSekunder->evaluation_interval);

        $dataSektoral = LembarKerja::where('code', 'DATA_SEKTORAL')->where('period', 2026)->first();
        $this->assertNotNull($dataSektoral);
        $this->assertSame('eksternal', $dataSektoral->category);
        $this->assertSame('1 Bulan', $dataSektoral->evaluation_interval);
    }
}
