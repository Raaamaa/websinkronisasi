<?php

declare(strict_types=1);

namespace App\Services\Matching\Rules;

use App\Models\EvidenceDocument;
use App\Models\LkIndicator;
use App\Models\SystemSetting;
use App\Services\Matching\DTOs\MatchCandidate;
use App\Services\Matching\Similarity\TrigramSimilarity;
use App\Services\Matching\TextPreprocessor;

class HeuristicMatchingRule
{
    public function __construct(
        protected TextPreprocessor $preprocessor,
        protected TrigramSimilarity $trigramSimilarity
    ) {}

    /**
     * Evaluate heuristic similarity rules (title and contextual content similarity).
     */
    public function evaluate(EvidenceDocument $document, LkIndicator $indicator): ?MatchCandidate
    {
        $threshold = (float) SystemSetting::get('matching.similarity_threshold', 75.0);
        $bestCandidate = null;
        $highestScore = 0.0;

        $items = $indicator->evidenceItems;
        $docNormalizedName = $this->preprocessor->removeStopwords($document->document_name);
        $docText = $document->extracted_text ? $this->preprocessor->normalize($document->extracted_text) : '';

        // Jika indikator tidak memiliki item bukti spesifik, buat item virtual dari judul indikator
        $targetItems = $items->isNotEmpty() ? $items : [null];

        foreach ($targetItems as $item) {
            $targetTitle = $item !== null
                ? $this->preprocessor->removeStopwords($item->resolved_name ?: $item->raw_template_name)
                : $this->preprocessor->removeStopwords($indicator->title);

            // 1. Komputasi Kemiripan Nama (Trigram & Levenshtein)
            $titleScore = $this->trigramSimilarity->compute($docNormalizedName, $targetTitle);

            // 2. Penalti Perbedaan Tahun (MATCH-03, MATCH-10)
            $targetYear = $indicator->lembarKerja?->period ?? $document->year;
            $hasYearPenalty = false;
            if ($document->year !== $targetYear) {
                $titleScore = max(0.0, $titleScore - 20.0);
                $hasYearPenalty = true;
            }

            // 3. Komputasi Kemiripan Isi Teks / Ekstraksi OCR
            $contentScore = 0.0;
            $matchedKeywords = [];
            $allKeywords = [];

            if ($docText !== '') {
                $rawTarget = ($item ? $item->resolved_name : '').' '.$indicator->title;
                $allKeywords = $this->preprocessor->extractKeywords($rawTarget);

                if (! empty($allKeywords)) {
                    foreach ($allKeywords as $kw) {
                        if (str_contains($docText, $kw)) {
                            $matchedKeywords[] = $kw;
                        }
                    }
                    $contentScore = (count($matchedKeywords) / count($allKeywords)) * 100.0;
                }
            }

            // 4. Pembobotan Skor Akhir (70% Judul + 30% Isi jika ada teks)
            if ($docText !== '' && ! empty($allKeywords)) {
                $finalScore = (0.70 * $titleScore) + (0.30 * $contentScore);
                $method = 'title_similarity';
            } else {
                $finalScore = $titleScore;
                $method = 'title_similarity';
            }

            $finalScore = round($finalScore, 2);

            if ($finalScore > $highestScore) {
                $highestScore = $finalScore;
                $bestCandidate = new MatchCandidate(
                    documentId: $document->id,
                    indicatorId: $indicator->id,
                    matchedItemId: $item?->id,
                    status: 'auto',
                    matchMethod: $method,
                    confidenceScore: $finalScore,
                    matchReasons: [
                        'score_components' => [
                            'title_score' => round($titleScore, 2),
                            'content_score' => round($contentScore, 2),
                            'year_penalty' => $hasYearPenalty ? -20.0 : 0.0,
                        ],
                        'matched_keywords' => $matchedKeywords,
                    ]
                );
            }
        }

        if ($highestScore >= $threshold && $bestCandidate !== null) {
            return $bestCandidate;
        }

        return null;
    }
}
