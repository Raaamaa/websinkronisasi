<?php

declare(strict_types=1);

namespace App\Services\Matching;

class TextPreprocessor
{
    /**
     * Synonym dictionary for official government document terminology.
     *
     * @var array<string, string>
     */
    protected array $synonyms = [
        'sk' => 'surat keputusan',
        'sop' => 'standar operasional prosedur',
        'sp' => 'standar pelayanan',
        'lke' => 'lembar kerja evaluasi',
        'notulen' => 'notula',
        'monev' => 'monitoring evaluasi',
        'renja' => 'rencana kerja',
        'renstra' => 'rencana strategis',
    ];

    /**
     * Common Indonesian stopwords for heuristic matching.
     *
     * @var array<string>
     */
    protected array $stopwords = [
        'dan', 'atau', 'yang', 'untuk', 'pada', 'ke', 'di',
        'tentang', 'terkait', 'sebagai', 'oleh', 'dari', 'dalam',
        'dengan', 'adalah', 'yaitu', 'atas', 'serta', 'terhadap',
    ];

    /**
     * Normalize text: lowercase, sanitize punctuation (preserve ., -, /), expand synonyms.
     */
    public function normalize(string $text): string
    {
        // 1. Case folding
        $text = mb_strtolower(trim($text), 'UTF-8');

        // 2. Pembersihan simbol (pertahankan alfanumerik, spasi, ., -, /)
        $text = (string) preg_replace('/[^a-z0-9\s\.\-\/]/u', ' ', $text);

        // 3. Normalisasi spasi putih ganda
        $text = (string) preg_replace('/\s+/', ' ', $text);

        // 4. Ekspansi Kamus Sinonim Kata Baku (dengan word boundary)
        foreach ($this->synonyms as $abbr => $expansion) {
            $text = (string) preg_replace('/\b'.preg_quote($abbr, '/').'\b/u', $expansion, $text);
        }

        return trim($text);
    }

    /**
     * Remove Indonesian stopwords for heuristic similarity comparison.
     */
    public function removeStopwords(string $text): string
    {
        $normalized = $this->normalize($text);
        $words = preg_split('/\s+/', $normalized) ?: [];

        $filteredWords = array_filter($words, function (string $word): bool {
            return ! in_array($word, $this->stopwords, true) && mb_strlen($word) > 1;
        });

        return implode(' ', $filteredWords);
    }

    /**
     * Extract meaningful keywords (nouns/content words) as an array.
     *
     * @return array<string>
     */
    public function extractKeywords(string $text): array
    {
        $cleaned = $this->removeStopwords($text);
        $words = preg_split('/[\s\.\-\/]+/', $cleaned) ?: [];

        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) >= 3 && ! is_numeric($word)) {
                $keywords[$word] = true;
            }
        }

        return array_keys($keywords);
    }
}
