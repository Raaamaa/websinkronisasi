<?php

declare(strict_types=1);

namespace App\Services\Matching\Similarity;

class TrigramSimilarity
{
    /**
     * Compute string similarity (0.0 to 100.0) using Trigram and Token Levenshtein.
     */
    public function compute(string $s1, string $s2): float
    {
        $s1 = trim($s1);
        $s2 = trim($s2);

        if ($s1 === '' && $s2 === '') {
            return 100.0;
        }

        if ($s1 === '' || $s2 === '') {
            return 0.0;
        }

        if ($s1 === $s2) {
            return 100.0;
        }

        // 1. Trigram similarity
        $trigramSim = $this->trigramJaccard($s1, $s2);

        // 2. Token sort similarity
        $tokenSortSim = $this->tokenSortSimilarity($s1, $s2);

        // 3. Similar text direct percentage
        similar_text($s1, $s2, $percentDirect);
        $directSim = $percentDirect / 100.0;

        $maxSim = max($trigramSim, $tokenSortSim, $directSim);

        return round($maxSim * 100.0, 2);
    }

    /**
     * Calculate Trigram Jaccard Index (matching PostgreSQL pg_trgm behavior).
     */
    protected function trigramJaccard(string $s1, string $s2): float
    {
        $padded1 = '  '.mb_strtolower($s1, 'UTF-8').' ';
        $padded2 = '  '.mb_strtolower($s2, 'UTF-8').' ';

        $t1 = $this->extractTrigrams($padded1);
        $t2 = $this->extractTrigrams($padded2);

        if (empty($t1) || empty($t2)) {
            return 0.0;
        }

        // Pastikan multiset deduplikasi sebelum operasi himpunan (N-01)
        $u1 = array_unique($t1);
        $u2 = array_unique($t2);

        $intersection = count(array_intersect($u1, $u2));
        $union = count(array_unique(array_merge($u1, $u2)));

        return $union > 0 ? (float) ($intersection / $union) : 0.0;
    }

    /**
     * @return array<string>
     */
    protected function extractTrigrams(string $str): array
    {
        $len = mb_strlen($str, 'UTF-8');
        if ($len < 3) {
            return [];
        }

        $trigrams = [];
        for ($i = 0; $i <= $len - 3; $i++) {
            $trigrams[] = mb_substr($str, $i, 3, 'UTF-8');
        }

        return $trigrams;
    }

    /**
     * Calculate Token Sort Ratio similarity.
     */
    protected function tokenSortSimilarity(string $s1, string $s2): float
    {
        $words1 = preg_split('/\s+/u', mb_strtolower($s1, 'UTF-8')) ?: [];
        $words2 = preg_split('/\s+/u', mb_strtolower($s2, 'UTF-8')) ?: [];

        sort($words1);
        sort($words2);

        $sortedStr1 = implode(' ', $words1);
        $sortedStr2 = implode(' ', $words2);

        similar_text($sortedStr1, $sortedStr2, $percent);

        return $percent / 100.0;
    }
}
