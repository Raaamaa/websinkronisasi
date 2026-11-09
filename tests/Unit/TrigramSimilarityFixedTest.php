<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Matching\Similarity\TrigramSimilarity;
use Tests\TestCase;

class TrigramSimilarityFixedTest extends TestCase
{
    protected TrigramSimilarity $similarity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->similarity = new TrigramSimilarity;
    }

    public function test_repeated_tokens_jaccard_does_not_exceed_one_hundred_percent(): void
    {
        // Bug N-01: 'ABC ABC ABC' vs 'ABC' menghasilkan trigram t1 berulang
        // Sebelum fix: intersection dihitung dari t1 non-unique sehingga intersection > union (> 100%)
        $score = $this->similarity->compute('ABC ABC ABC', 'ABC');

        $this->assertLessThanOrEqual(100.0, $score);
        $this->assertGreaterThanOrEqual(0.0, $score);
    }

    public function test_identical_strings_produce_exact_hundred_percent(): void
    {
        $score = $this->similarity->compute('Surat Keputusan Kepala BPS', 'Surat Keputusan Kepala BPS');

        $this->assertEqualsWithDelta(100.0, $score, 0.001);
    }

    public function test_disjoint_strings_produce_zero_similarity(): void
    {
        $score = $this->similarity->compute('xxxxxx', 'yyyyyy');

        $this->assertEqualsWithDelta(0.0, $score, 0.001);
    }
}
