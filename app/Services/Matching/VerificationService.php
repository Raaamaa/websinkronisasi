<?php

declare(strict_types=1);

namespace App\Services\Matching;

use App\Models\AuditLog;
use App\Models\DocumentMatch;
use App\Models\SystemSetting;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class VerificationService
{
    /**
     * Confirm an automatic match (MATCH-04, AC-10).
     */
    public function confirmMatch(string $matchId, User $actor): DocumentMatch
    {
        return DB::transaction(function () use ($matchId, $actor): DocumentMatch {
            /** @var DocumentMatch $match */
            $match = DocumentMatch::where('id', $matchId)->lockForUpdate()->firstOrFail();
            $oldStatus = $match->status;

            // Idempoten jika sudah dikonfirmasi
            if ($oldStatus === 'confirmed') {
                return $match;
            }

            // Invarian #2: Relasi berstatus rejected dilarang keras dikonfirmasi ulang
            if ($oldStatus === 'rejected') {
                throw new DomainException("Relasi berstatus 'rejected' tidak dapat dikonfirmasi ulang (Invarian #2).");
            }

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
        });
    }

    /**
     * Reject a match, removing it from monitor (MATCH-04, MATCH-06, AC-11).
     */
    public function rejectMatch(string $matchId, User $actor): DocumentMatch
    {
        return DB::transaction(function () use ($matchId, $actor): DocumentMatch {
            /** @var DocumentMatch $match */
            $match = DocumentMatch::where('id', $matchId)->lockForUpdate()->firstOrFail();
            $oldStatus = $match->status;

            if ($oldStatus === 'rejected') {
                return $match;
            }

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
        });
    }

    /**
     * Un-reject a rejected match (TASK-21b), restoring it to active verification status.
     */
    public function unrejectMatch(string $matchId, User $actor): DocumentMatch
    {
        return DB::transaction(function () use ($matchId, $actor): DocumentMatch {
            /** @var DocumentMatch $match */
            $match = DocumentMatch::where('id', $matchId)->lockForUpdate()->firstOrFail();
            $oldStatus = $match->status;

            if ($oldStatus !== 'rejected') {
                return $match;
            }

            // Pulihkan status: jika skor >= similarity_threshold -> auto, jika tidak -> perlu_verifikasi
            $threshold = (float) SystemSetting::get('matching.similarity_threshold', 75.0);
            $newStatus = $match->confidence_score >= $threshold ? 'auto' : 'perlu_verifikasi';

            $match->update([
                'status' => $newStatus,
                'verified_by' => null,
                'verified_at' => null,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'bd.match_unrejected',
                'entity_type' => 'DocumentMatch',
                'entity_id' => $match->id,
                'old_values' => [
                    'status' => 'rejected',
                ],
                'new_values' => [
                    'status' => $newStatus,
                    'unrejected_by' => $actor->id,
                ],
            ]);

            return $match;
        });
    }
}
