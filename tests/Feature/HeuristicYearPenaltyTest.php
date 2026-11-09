<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\EvidenceDocument;
use App\Models\LembarKerja;
use App\Models\LkIndicator;
use App\Services\Matching\Rules\HeuristicMatchingRule;
use App\Services\Matching\Similarity\TrigramSimilarity;
use App\Services\Matching\TextPreprocessor;
use Illuminate\Support\Str;
use Tests\TestCase;

class HeuristicYearPenaltyTest extends TestCase
{
    protected HeuristicMatchingRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new HeuristicMatchingRule(
            new TextPreprocessor,
            new TrigramSimilarity
        );
    }

    public function test_document_with_discrepant_year_receives_twenty_point_penalty(): void
    {
        $lk = new LembarKerja([
            'code' => 'LK-YEAR-TEST',
            'name' => 'Lembar Kerja Uji Penalti Tahun',
            'period' => 2026,
            'evaluation_interval' => '12 Bulan',
            'is_active' => true,
        ]);
        $lk->id = (string) Str::uuid();

        $indicator = new LkIndicator([
            'code' => 'IND-YEAR-01',
            'title' => 'Laporan Kinerja Instansi Pemerintah BPS',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();
        $indicator->setRelation('lembarKerja', $lk);
        $indicator->setRelation('evidenceItems', collect());

        // Dokumen dengan tahun sama (2026)
        $docSameYear = new EvidenceDocument([
            'document_name' => 'Laporan Kinerja Instansi Pemerintah BPS',
            'year' => 2026,
        ]);
        $docSameYear->id = (string) Str::uuid();

        // Dokumen dengan tahun berbeda (2024)
        $docDiffYear = new EvidenceDocument([
            'document_name' => 'Laporan Kinerja Instansi Pemerintah BPS',
            'year' => 2024,
        ]);
        $docDiffYear->id = (string) Str::uuid();

        $candidateSame = $this->rule->evaluate($docSameYear, $indicator);
        $candidateDiff = $this->rule->evaluate($docDiffYear, $indicator);

        $this->assertNotNull($candidateSame);
        $this->assertNotNull($candidateDiff);

        $this->assertSame(0.0, $candidateSame->matchReasons['score_components']['year_penalty']);
        $this->assertSame(-20.0, $candidateDiff->matchReasons['score_components']['year_penalty']);

        $scoreDiff = $candidateSame->confidenceScore - $candidateDiff->confidenceScore;
        $this->assertEqualsWithDelta(20.0, $scoreDiff, 0.05);
    }

    public function test_document_year_as_string_also_triggers_year_penalty_accurately(): void
    {
        $lk = new LembarKerja([
            'code' => 'LK-STR-TEST',
            'name' => 'Lembar Kerja Uji String Tahun',
            'period' => 2026,
            'evaluation_interval' => '12 Bulan',
            'is_active' => true,
        ]);
        $lk->id = (string) Str::uuid();

        $indicator = new LkIndicator([
            'code' => 'IND-STR-01',
            'title' => 'Laporan Akuntabilitas Kinerja Instansi',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();
        $indicator->setRelation('lembarKerja', $lk);
        $indicator->setRelation('evidenceItems', collect());

        $docDiffStringYear = new EvidenceDocument([
            'document_name' => 'Laporan Akuntabilitas Kinerja Instansi',
            'year' => '2023',
        ]);
        $docDiffStringYear->id = (string) Str::uuid();

        $candidate = $this->rule->evaluate($docDiffStringYear, $indicator);

        $this->assertNotNull($candidate);
        $this->assertSame(-20.0, $candidate->matchReasons['score_components']['year_penalty']);
    }

    public function test_document_year_as_string_matching_lk_period_does_not_receive_false_penalty(): void
    {
        $lk = new LembarKerja([
            'code' => 'LK-MATCH-STR-TEST',
            'name' => 'Lembar Kerja Uji String Tahun Cocok',
            'period' => 2026,
            'evaluation_interval' => '12 Bulan',
            'is_active' => true,
        ]);
        $lk->id = (string) Str::uuid();

        $indicator = new LkIndicator([
            'code' => 'IND-MATCH-01',
            'title' => 'Laporan Akuntabilitas Kinerja Instansi Cocok',
            'order_index' => 1,
        ]);
        $indicator->id = (string) Str::uuid();
        $indicator->setRelation('lembarKerja', $lk);
        $indicator->setRelation('evidenceItems', collect());

        $docSameStringYear = new EvidenceDocument([
            'document_name' => 'Laporan Akuntabilitas Kinerja Instansi Cocok',
            'year' => '2026',
        ]);
        $docSameStringYear->id = (string) Str::uuid();

        $candidate = $this->rule->evaluate($docSameStringYear, $indicator);

        $this->assertNotNull($candidate);
        $this->assertSame(0.0, $candidate->matchReasons['score_components']['year_penalty']);
    }
}
