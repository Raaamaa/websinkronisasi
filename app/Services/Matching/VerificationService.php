<?php

declare(strict_types=1);

namespace App\Services\Matching;

use App\Models\AuditLog;
use App\Models\DocumentMatch;
use App\Models\User;

class VerificationService
{
    /**
     * Confirm an automatic match (MATCH-04, AC-10).
     */
    public function confirmMatch(string $matchId, User $actor): DocumentMatch
    {
        $match = DocumentMatch::findOrFail($matchId);
        $oldStatus = $match->status;

        $match->update([
            'status' => 'confirmed',
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'match.verified',
            'entity_type' => 'DocumentMatch',
            'entity_id' => $match->id,
            'old_values' => [
                'status' => $oldStatus,
            ],
            'new_values' => [
                'status' => 'confirmed',
                'verified_by' => $actor->id,
            ],
        ]);

        return $match;
    }

    /**
     * Reject a match, removing it from monitor (MATCH-04, MATCH-06, AC-11).
     */
    public function rejectMatch(string $matchId, User $actor): DocumentMatch
    {
        $match = DocumentMatch::findOrFail($matchId);
        $oldStatus = $match->status;

        $match->update([
            'status' => 'rejected',
            'verified_by' => $actor->id,
            'verified_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $actor->id,
            'action' => 'match.rejected',
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

        return $match;
    }
}
