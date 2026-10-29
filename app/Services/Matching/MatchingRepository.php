<?php

declare(strict_types=1);

namespace App\Services\Matching;

use App\Models\DocumentMatch;
use App\Services\Matching\DTOs\MatchCandidate;
use Illuminate\Support\Facades\DB;

class MatchingRepository
{
    /**
     * Persist match candidate with strict anti-overwrite rejection protection (MATCH-06, AC-11).
     *
     * Invariants:
     * 1. Status 'rejected' MUST NEVER be overwritten by automatic matching.
     * 2. Status 'confirmed' MUST NEVER be demoted to 'auto'.
     */
    public function saveMatch(MatchCandidate $candidate): ?DocumentMatch
    {
        return DB::transaction(function () use ($candidate): ?DocumentMatch {
            /** @var DocumentMatch|null $existing */
            $existing = DocumentMatch::query()
                ->where('document_id', $candidate->documentId)
                ->where('indicator_id', $candidate->indicatorId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                // Invarian 1 (MATCH-06, AC-11): Pasangan yang pernah rejected dilarang keras dibuat ulang / ditimpa
                if ($existing->status === 'rejected') {
                    return $existing;
                }

                // Invarian 2: Status confirmed tidak boleh diturunkan derajatnya menjadi auto
                if ($existing->status === 'confirmed') {
                    if ($candidate->status === 'confirmed') {
                        $existing->update([
                            'confidence_score' => $candidate->confidenceScore,
                            'match_reasons' => $candidate->matchReasons,
                            'matched_item_id' => $candidate->matchedItemId ?? $existing->matched_item_id,
                        ]);
                    }

                    return $existing;
                }

                // Jika status saat ini masih 'auto', perbarui dengan kalkulasi terbaru
                $existing->update([
                    'matched_item_id' => $candidate->matchedItemId,
                    'status' => $candidate->status,
                    'match_method' => $candidate->matchMethod,
                    'confidence_score' => $candidate->confidenceScore,
                    'match_reasons' => $candidate->matchReasons,
                ]);

                return $existing;
            }

            // Buat record baru jika belum pernah ada
            return DocumentMatch::create([
                'document_id' => $candidate->documentId,
                'indicator_id' => $candidate->indicatorId,
                'matched_item_id' => $candidate->matchedItemId,
                'status' => $candidate->status,
                'match_method' => $candidate->matchMethod,
                'confidence_score' => $candidate->confidenceScore,
                'match_reasons' => $candidate->matchReasons,
            ]);
        });
    }
}
