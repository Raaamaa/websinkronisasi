<?php

declare(strict_types=1);

namespace App\Services\Matching;

use App\Models\AuditLog;
use App\Models\DocumentMatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ManualMatchService
{
    /**
     * Manually link a document to an indicator as confirmed (MATCH-05, AC-16).
     * Protected against concurrency race conditions via database lock / transactions.
     */
    public function addMatch(string $documentId, string $indicatorId, User $actor, ?string $itemId = null): DocumentMatch
    {
        return DB::transaction(function () use ($documentId, $indicatorId, $itemId, $actor): DocumentMatch {
            /** @var DocumentMatch|null $match */
            $match = DocumentMatch::query()
                ->where('document_id', $documentId)
                ->where('indicator_id', $indicatorId)
                ->lockForUpdate()
                ->first();

            if ($match !== null) {
                $match->update([
                    'matched_item_id' => $itemId ?? $match->matched_item_id,
                    'status' => 'confirmed',
                    'match_method' => 'manual',
                    'confidence_score' => 100.0,
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]);
            } else {
                $match = DocumentMatch::create([
                    'document_id' => $documentId,
                    'indicator_id' => $indicatorId,
                    'matched_item_id' => $itemId,
                    'status' => 'confirmed',
                    'match_method' => 'manual',
                    'confidence_score' => 100.0,
                    'match_reasons' => ['source' => 'manual_admin'],
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]);
            }

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'match.manual_created',
                'entity_type' => 'DocumentMatch',
                'entity_id' => $match->id,
                'new_values' => [
                    'document_id' => $documentId,
                    'indicator_id' => $indicatorId,
                    'status' => 'confirmed',
                ],
            ]);

            return $match;
        });
    }

    /**
     * Manually remove a match (setting status to rejected per MATCH-05 & MATCH-06).
     */
    public function deleteMatch(string $documentId, string $indicatorId, User $actor): void
    {
        DB::transaction(function () use ($documentId, $indicatorId, $actor): void {
            $match = DocumentMatch::query()
                ->where('document_id', $documentId)
                ->where('indicator_id', $indicatorId)
                ->lockForUpdate()
                ->first();

            if ($match === null) {
                return;
            }

            $oldStatus = $match->status;

            // Invarian MATCH-06: Status diubah menjadi rejected agar tidak dibuat ulang oleh worker otomatis
            $match->update([
                'status' => 'rejected',
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'match.manual_deleted',
                'entity_type' => 'DocumentMatch',
                'entity_id' => $match->id,
                'old_values' => [
                    'status' => $oldStatus,
                ],
                'new_values' => [
                    'status' => 'rejected',
                    'verified_by' => $actor->id,
                ],
            ]);
        });
    }
}

