<?php

declare(strict_types=1);

namespace App\Services\Matching\DTOs;

class MatchCandidate
{
    /**
     * @param  array<string, mixed>  $matchReasons
     */
    public function __construct(
        public readonly string $documentId,
        public readonly string $indicatorId,
        public readonly ?string $matchedItemId,
        public readonly string $status,
        public readonly string $matchMethod,
        public readonly float $confidenceScore,
        public readonly array $matchReasons = []
    ) {}
}
