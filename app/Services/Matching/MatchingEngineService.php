<?php

declare(strict_types=1);

namespace App\Services\Matching;

use App\Models\DocumentMatch;
use App\Models\EvidenceDocument;
use App\Models\LkIndicator;
use App\Services\Matching\Rules\DeterministicMatchingRule;
use App\Services\Matching\Rules\HeuristicMatchingRule;
use Illuminate\Support\Collection;

class MatchingEngineService
{
    public function __construct(
        protected DeterministicMatchingRule $deterministicRule,
        protected HeuristicMatchingRule $heuristicRule,
        protected MatchingRepository $repository
    ) {}

    /**
     * Run matching pipeline for a given evidence document against active LK indicators.
     * Follows the tiered pipeline in MATCHING_ALGORITHM.md:
     * 1. Exact Code -> if any match, finish (confirmed)
     * 2. Exact Number -> if any match, finish (confirmed)
     * 3. Heuristic Similarity (Trigram + Content) -> auto if >= threshold
     *
     * @return Collection<int, DocumentMatch>
     */
    public function matchDocument(EvidenceDocument $document, ?string $targetLkId = null): Collection
    {
        $query = LkIndicator::query()
            ->with(['evidenceItems', 'lembarKerja'])
            ->whereHas('lembarKerja', function ($q): void {
                $q->where('is_active', true);
            });

        if ($targetLkId !== null) {
            $query->where('lk_id', $targetLkId);
        }

        $indicators = $query->get();

        // 1. Tahap 1: Deteksi Kode Indikator Eksplisit (exact_code)
        $exactCodeMatches = collect();
        foreach ($indicators as $indicator) {
            $candidate = $this->deterministicRule->evaluateExactCode($document, $indicator);
            if ($candidate !== null) {
                $saved = $this->repository->saveMatch($candidate);
                if ($saved !== null && $saved->status !== 'rejected') {
                    $exactCodeMatches->push($saved);
                }
            }
        }

        if ($exactCodeMatches->isNotEmpty()) {
            return $exactCodeMatches;
        }

        // 2. Tahap 2: Deteksi Nomor Dokumen Eksplisit (exact_number)
        $exactNumberMatches = collect();
        foreach ($indicators as $indicator) {
            $candidate = $this->deterministicRule->evaluateExactNumber($document, $indicator);
            if ($candidate !== null) {
                $saved = $this->repository->saveMatch($candidate);
                if ($saved !== null && $saved->status !== 'rejected') {
                    $exactNumberMatches->push($saved);
                }
            }
        }

        if ($exactNumberMatches->isNotEmpty()) {
            return $exactNumberMatches;
        }

        // 3. Tahap 3 & 4: Kemiripan Teks Heuristik (Trigram & Keyword Coverage)
        $heuristicMatches = collect();
        foreach ($indicators as $indicator) {
            $candidate = $this->heuristicRule->evaluate($document, $indicator);
            if ($candidate !== null) {
                $saved = $this->repository->saveMatch($candidate);
                if ($saved !== null && $saved->status !== 'rejected') {
                    $heuristicMatches->push($saved);
                }
            }
        }

        return $heuristicMatches;
    }

    /**
     * Run re-matching for all active evidence documents against a specified Lembar Kerja.
     */
    public function rematchLk(string $lkId): int
    {
        $totalMatches = 0;

        EvidenceDocument::query()
            ->whereNull('deleted_at')
            ->where('processing_status', 'selesai')
            ->chunkById(100, function ($documents) use ($lkId, &$totalMatches): void {
                foreach ($documents as $document) {
                    $matches = $this->matchDocument($document, $lkId);
                    $totalMatches += $matches->count();
                }
            });

        return $totalMatches;
    }
}
