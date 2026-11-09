<?php

declare(strict_types=1);

namespace App\Services\Matching\Rules;

use App\Models\EvidenceDocument;
use App\Models\LkIndicator;
use App\Services\Matching\DTOs\MatchCandidate;
use App\Services\Matching\TextPreprocessor;

class DeterministicMatchingRule
{
    // sementara, BELUM DIUKUR (K2)
    public const int MIN_CODE_LENGTH = 3;

    // sementara, BELUM DIUKUR (K2)
    public const int MIN_NUMBER_LENGTH = 4;

    public function __construct(
        protected TextPreprocessor $preprocessor
    ) {}

    /**
     * Evaluate deterministic rules (exact_code and exact_number).
     * Returns MatchCandidate (score 100.0, confirmed) if match found, or null otherwise.
     */
    public function evaluate(EvidenceDocument $document, LkIndicator $indicator): ?MatchCandidate
    {
        // 1. Tahap 1: Pencocokan Kode Indikator Eksplisit (exact_code)
        $codeMatch = $this->evaluateExactCode($document, $indicator);
        if ($codeMatch !== null) {
            return $codeMatch;
        }

        // 2. Tahap 2: Pencocokan Nomor Dokumen Eksplisit (exact_number)
        $numberMatch = $this->evaluateExactNumber($document, $indicator);
        if ($numberMatch !== null) {
            return $numberMatch;
        }

        return null;
    }

    /**
     * Check if indicator code appears in original_filename or document_name.
     */
    public function evaluateExactCode(EvidenceDocument $document, LkIndicator $indicator): ?MatchCandidate
    {
        $code = trim($indicator->code);
        if ($code === '' || mb_strlen($code) < self::MIN_CODE_LENGTH) {
            return null; // Kode terlalu pendek (misal: "1", "A") rawan bentrok angka biasa
        }

        $sources = array_filter([$document->original_filename, $document->document_name]);
        $escapedCode = preg_quote($code, '/');

        // Regex boundary: kode harus berdiri sendiri (dibatasi spasi, tanda kurung, underscore, atau tanda titik/dash di akhir)
        $pattern = '/(?:^|[\s_\[\(])'.$escapedCode.'(?:[\s_\]\)\.\-]|$)/i';

        foreach ($sources as $source) {
            if (preg_match($pattern, $source)) {
                $matchedItem = $indicator->evidenceItems->first();
                $titleMatched = $this->isTitleMatched($document, $indicator);

                return new MatchCandidate(
                    documentId: $document->id,
                    indicatorId: $indicator->id,
                    matchedItemId: $matchedItem?->id,
                    status: $titleMatched ? 'confirmed' : 'perlu_verifikasi',
                    matchMethod: 'exact_code',
                    confidenceScore: $titleMatched ? 100.0 : 70.0,
                    matchReasons: [
                        'rule' => 'exact_code',
                        'matched_code' => $code,
                        'matched_source' => $source,
                        'title_matched' => $titleMatched,
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Check if indicator title or evidence items match document name, filename, or extracted text.
     */
    public function isTitleMatched(EvidenceDocument $document, LkIndicator $indicator): bool
    {
        $rawTarget = $indicator->title;
        foreach ($indicator->evidenceItems as $item) {
            $rawTarget .= ' '.($item->resolved_name ?: $item->raw_template_name);
        }

        $targetKeywords = $this->preprocessor->extractKeywords($rawTarget);
        if (empty($targetKeywords)) {
            return false;
        }

        $docSources = array_filter([
            $document->document_name,
            $document->original_filename,
            $document->extracted_text,
        ]);
        $docCombined = implode(' ', $docSources);
        $docNormalized = $this->preprocessor->normalize($docCombined);

        foreach ($targetKeywords as $keyword) {
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/u', $docNormalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if document_number appears in indicator title or evidence items.
     */
    public function evaluateExactNumber(EvidenceDocument $document, LkIndicator $indicator): ?MatchCandidate
    {
        $docNumber = trim((string) $document->document_number);
        if ($docNumber === '' || mb_strlen($docNumber) < self::MIN_NUMBER_LENGTH) {
            return null;
        }

        $cleanDocNum = $this->preprocessor->normalize($docNumber);
        if ($cleanDocNum === '' || mb_strlen($cleanDocNum) < self::MIN_NUMBER_LENGTH) {
            return null;
        }

        $pattern = '/(?:^|[\s_\[\(\/\-])'.preg_quote($cleanDocNum, '/').'(?:[\s_\]\)\.\/\-]|$)/i';

        // Cek di judul indikator
        $cleanTitle = $this->preprocessor->normalize($indicator->title);
        if (preg_match($pattern, $cleanTitle)) {
            return new MatchCandidate(
                documentId: $document->id,
                indicatorId: $indicator->id,
                matchedItemId: $indicator->evidenceItems->first()?->id,
                status: 'confirmed',
                matchMethod: 'exact_number',
                confidenceScore: 100.0,
                matchReasons: [
                    'rule' => 'exact_number',
                    'matched_number' => $docNumber,
                    'matched_in' => 'indicator_title',
                ]
            );
        }

        // Cek di setiap item bukti
        foreach ($indicator->evidenceItems as $item) {
            $itemText = $this->preprocessor->normalize($item->resolved_name.' '.$item->raw_template_name);
            if (preg_match($pattern, $itemText)) {
                return new MatchCandidate(
                    documentId: $document->id,
                    indicatorId: $indicator->id,
                    matchedItemId: $item->id,
                    status: 'confirmed',
                    matchMethod: 'exact_number',
                    confidenceScore: 100.0,
                    matchReasons: [
                        'rule' => 'exact_number',
                        'matched_number' => $docNumber,
                        'matched_in' => 'evidence_item',
                    ]
                );
            }
        }

        return null;
    }
}
